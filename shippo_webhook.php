<?php
/**
 * Shippo → Tracker / WooCommerce Status Sync
 *
 * CANONICAL SOURCE: ProjectTracker/shippo_webhook.php
 * Deployed to:      ki6cr.com/projects/shippo_webhook.php
 *
 * Receives transaction_created webhooks from Shippo (fired when a label is purchased).
 *
 * Flow:
 *   1. Resolves the WooCommerce order ID from the Shippo order (via API call if needed)
 *   2. Updates the tracker order: status → shipped, tracking number, tracking URL, shipped_at
 *   3. Marks the WooCommerce order as Completed (triggers WC customer completion email)
 *   4. Adds a customer-visible tracking note to the WooCommerce order
 *
 * Setup in Shippo:
 *   Settings → Webhooks → Add Webhook
 *   URL:   https://ki6cr.com/projects/shippo_webhook.php
 *   Event: transaction_created
 *   Mode:  Live
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

// Only handle successful label purchases
if ($payload['event'] !== 'transaction_created') {
    echo json_encode(['skipped' => true, 'reason' => 'Unhandled event: ' . $payload['event']]);
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
