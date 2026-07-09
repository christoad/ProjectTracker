<?php
/**
 * Shippo → Tracker / WooCommerce Status Sync + Delivery Email
 *
 * CANONICAL SOURCE: ProjectTracker/shippo_webhook.php
 * Deployed to:      ki6cr.com/projects/shippo_webhook.php
 *
 * Handles two Shippo webhook events:
 *
 * 1. transaction_created — fired when a label is purchased
 *    Flow:
 *      a. Resolves the WooCommerce order ID from the Shippo order (via API call if needed)
 *      b. Updates the tracker order: status → shipped, tracking number, tracking URL, shipped_at
 *      c. Marks the WooCommerce order as Completed (triggers WC customer completion email)
 *      d. Adds a customer-visible tracking note to the WooCommerce order
 *
 * 2. track_updated — fired when tracking status changes
 *    When status = DELIVERED: looks up the tracker order by tracking number and sends
 *    a personalized delivery email from orders@ki6cr-labs.com with Reply-To set to Chris.
 *
 * Setup in Shippo:
 *   Settings → Webhooks → Add Webhook (repeat for each event)
 *   URL:    https://ki6cr.com/projects/shippo_webhook.php
 *   Events: transaction_created AND track_updated
 *   Mode:   Live
 *
 * No webhook signing secret is required (Shippo's UI doesn't provide one
 * for this webhook type — just a mode toggle).
 */

require_once 'config.php';
require_once 'woocommerce_sync.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$raw_body = file_get_contents('php://input');
$env      = parse_ini_file(__DIR__ . '/.env');

$payload = json_decode($raw_body, true);
if (!$payload || !isset($payload['event'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid or empty payload']);
    exit;
}

$event = $payload['event'] ?? '';

// ─── track_updated: send delivery email when package is delivered ─────────────
if ($event === 'track_updated') {
    $data            = $payload['data'] ?? [];
    $tracking_status = $data['tracking_status']['status'] ?? '';
    $tracking_number = trim($data['tracking_number'] ?? '');

    if ($tracking_status !== 'DELIVERED') {
        echo json_encode(['skipped' => true, 'reason' => 'Tracking status not DELIVERED: ' . $tracking_status]);
        exit;
    }

    if (!$tracking_number) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing tracking_number in track_updated payload']);
        exit;
    }

    $db   = getDB();
    $stmt = $db->prepare("
        SELECT o.order_number, o.customer_name, o.customer_email, p.project_name
        FROM orders o
        LEFT JOIN projects p ON o.project_id = p.id
        WHERE o.tracking_number = ?
        LIMIT 1
    ");
    $stmt->execute([$tracking_number]);
    $order = $stmt->fetch();

    if (!$order) {
        echo json_encode(['skipped' => true, 'reason' => 'No tracker order found for tracking number: ' . $tracking_number]);
        exit;
    }

    $email_sent = send_delivery_email($order);

    echo json_encode([
        'success'        => true,
        'event'          => 'track_updated',
        'status'         => 'DELIVERED',
        'order_number'   => $order['order_number'],
        'customer_email' => $order['customer_email'],
        'email_sent'     => $email_sent,
    ]);
    exit;
}

// ─── transaction_created: label purchased → update tracker + WooCommerce ──────
if ($event !== 'transaction_created') {
    echo json_encode(['skipped' => true, 'reason' => 'Unhandled event: ' . $event]);
    exit;
}

$data   = $payload['data'] ?? [];
$status = $data['status'] ?? '';

if ($status !== 'SUCCESS') {
    echo json_encode(['skipped' => true, 'reason' => 'Transaction status not SUCCESS: ' . $status]);
    exit;
}

$tracking_number = trim($data['tracking_number'] ?? '');
$tracking_url    = $data['tracking_url_provider'] ?? '';
$carrier         = strtoupper($data['carrier'] ?? '');
$order_ref       = $data['order'] ?? null;

if (!$tracking_number || !$order_ref) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing tracking_number or order reference in payload']);
    exit;
}

// Resolve WooCommerce order ID from the Shippo order reference.
// Shippo's WooCommerce integration stores the WC order ID as shop_order_id.
// The order field may be an expanded object or a plain string ID.
$wc_order_id = null;

if (is_array($order_ref) && isset($order_ref['shop_order_id'])) {
    $wc_order_id = (int) $order_ref['shop_order_id'];
} elseif (is_string($order_ref) && $order_ref !== '') {
    $shippo_token = $env['SHIPPO_API_TOKEN'] ?? '';
    if (!$shippo_token) {
        http_response_code(500);
        echo json_encode(['error' => 'SHIPPO_API_TOKEN not set in .env — cannot resolve order']);
        exit;
    }

    $ch = curl_init('https://api.goshippo.com/orders/' . urlencode($order_ref));
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Authorization: ShippoToken ' . $shippo_token],
        CURLOPT_TIMEOUT        => 10,
    ]);
    $shippo_order = json_decode(curl_exec($ch), true);
    curl_close($ch);

    $wc_order_id = isset($shippo_order['shop_order_id'])
        ? (int) $shippo_order['shop_order_id']
        : null;
}

if (!$wc_order_id) {
    http_response_code(422);
    echo json_encode([
        'error'     => 'Could not resolve WooCommerce order ID from Shippo order',
        'order_ref' => $order_ref,
    ]);
    exit;
}

// Update the tracker order
$db   = getDB();
$stmt = $db->prepare("SELECT id, status FROM orders WHERE order_number = ?");
$stmt->execute(['WC-' . $wc_order_id]);
$order = $stmt->fetch();

if (!$order) {
    // Not fatal — may be an order placed before the tracker integration existed
    echo json_encode(['skipped' => true, 'reason' => 'Order WC-' . $wc_order_id . ' not found in tracker']);
    exit;
}

$db->prepare("
    UPDATE orders
    SET status          = 'shipped',
        tracking_number = ?,
        tracking_url    = ?,
        shipped_at      = NOW()
    WHERE id = ?
")->execute([$tracking_number, $tracking_url, $order['id']]);

// Mark WooCommerce order as Completed (triggers customer completion email)
$wc_status_result = wc_update_order_status($wc_order_id, 'completed');

// Add customer-visible tracking note to the WooCommerce order
$note = "Shipped via {$carrier}. Tracking number: {$tracking_number}.";
if ($tracking_url) {
    $note .= " Track your package: {$tracking_url}";
}
$wc_note_result = wc_add_order_note($wc_order_id, $note, true);

echo json_encode([
    'success'          => true,
    'wc_order_id'      => $wc_order_id,
    'tracking_number'  => $tracking_number,
    'carrier'          => $carrier,
    'tracker_updated'  => true,
    'wc_status_result' => $wc_status_result,
    'wc_note_result'   => $wc_note_result,
]);

// ─── Delivery email helper ────────────────────────────────────────────────────

function send_delivery_email(array $order): bool {
    $first_name  = explode(' ', trim($order['customer_name']))[0];
    $email       = $order['customer_email'];
    $kit_name    = $order['project_name'] ?? 'your kit';
    $order_number = $order['order_number'];

    $subject = "Your KI6CR Labs order has arrived!";

    $message  = "Hi {$first_name},\n\n";
    $message .= "Your {$kit_name} just arrived! I hope you enjoy the build.\n\n";
    $message .= "If you run into any questions during assembly, just reply to this email — I read every one personally and I'm happy to help.\n\n";
    $message .= "I'd love to hear how it goes, and if you get on the air with it, feel free to send me a note.\n\n";
    $message .= "73 de KI6CR,\n";
    $message .= "Chris\n";
    $message .= "ki6cr-labs.com\n";

    $headers  = "From: KI6CR Labs <orders@ki6cr-labs.com>\r\n";
    $headers .= "Reply-To: cr@christopherreddick.com\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();

    return mail($email, $subject, $message, $headers);
}
