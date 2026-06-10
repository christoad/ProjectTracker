<?php
/**
 * WooCommerce ↔ Inventory Sync Endpoint
 *
 * POST  /woocommerce_webhook.php
 *   — receives WooCommerce order webhooks; deducts/restores BOM inventory
 *     and pushes recalculated stock back to WooCommerce
 *
 * GET   /woocommerce_webhook.php?action=sync&project_id=X
 *   — recalculate and push stock for one project
 *
 * GET   /woocommerce_webhook.php?action=sync_all
 *   — recalculate and push stock for every mapped project
 *
 * GET   /woocommerce_webhook.php?action=status
 *   — show all project↔product mappings with calculated available qty
 */

require_once 'config.php';
require_once 'woocommerce_sync.php';

header('Content-Type: application/json');

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// ── GET endpoints (manual triggers / status) ─────────────────────────────────

if ($method === 'GET') {
    if ($action === 'sync' && isset($_GET['project_id'])) {
        echo json_encode(wc_sync_project($db, (int) $_GET['project_id']));
        exit;
    }

    if ($action === 'sync_all') {
        $results = wc_sync_all_projects($db);
        echo json_encode(['synced' => count($results), 'results' => $results]);
        exit;
    }

    if ($action === 'status') {
        $stmt = $db->query("
            SELECT id, project_name, woocommerce_product_id, status
            FROM projects
            WHERE woocommerce_product_id IS NOT NULL
            ORDER BY project_name
        ");
        $out = [];
        foreach ($stmt->fetchAll() as $p) {
            $out[] = [
                'project_id'              => $p['id'],
                'project_name'            => $p['project_name'],
                'wc_product_id'           => $p['woocommerce_product_id'],
                'calculated_available_qty' => wc_calculate_available_qty($db, $p['id']),
                'project_status'          => $p['status'],
            ];
        }
        echo json_encode($out);
        exit;
    }

    http_response_code(400);
    echo json_encode(['error' => 'Unknown action. Use: sync, sync_all, status']);
    exit;
}

// ── POST: WooCommerce order webhook ─────────────────────────────────────────

if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$raw_body = file_get_contents('php://input');

// Verify HMAC signature sent by WooCommerce
$cfg = wc_get_config();
if ($cfg && $cfg['webhook_secret'] !== '') {
    $sig      = $_SERVER['HTTP_X_WC_WEBHOOK_SIGNATURE'] ?? '';
    $expected = base64_encode(hash_hmac('sha256', $raw_body, $cfg['webhook_secret'], true));
    if (!hash_equals($expected, $sig)) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid webhook signature']);
        exit;
    }
}

$order = json_decode($raw_body, true);
if (!$order || !isset($order['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid or empty order payload']);
    exit;
}

$wc_order_id = (int) $order['id'];
$wc_status   = $order['status'] ?? '';

// Which inventory action does this order status trigger?
$should_deduct  = in_array($wc_status, ['processing', 'on-hold']);
$should_restore = in_array($wc_status, ['cancelled', 'refunded']);

if (!$should_deduct && !$should_restore) {
    echo json_encode([
        'skipped'    => true,
        'wc_order_id' => $wc_order_id,
        'reason'     => "Status '$wc_status' requires no inventory action",
    ]);
    exit;
}

// Load any existing tracker record for this WC order
$stmt = $db->prepare("SELECT id, inventory_deducted, variation_combo_key FROM orders WHERE order_number = ?");
$stmt->execute(['WC-' . $wc_order_id]);
$existing = $stmt->fetch();

$affected_projects = [];
$item_log          = [];

foreach (($order['line_items'] ?? []) as $item) {
    $wc_product_id   = (int) ($item['product_id'] ?? 0);
    $wc_variation_id = (int) ($item['variation_id'] ?? 0);
    if (!$wc_product_id) continue;

    // Find the tracker project mapped to this WooCommerce product
    $stmt = $db->prepare("SELECT id, project_name FROM projects WHERE woocommerce_product_id = ?");
    $stmt->execute([$wc_product_id]);
    $project = $stmt->fetch();
    if (!$project) continue;

    // For variable products, look up which attribute combo this variation represents
    $combo     = null;
    $combo_key = '';
    if ($wc_variation_id) {
        $stmt = $db->prepare("SELECT combo_key FROM project_variation_mappings WHERE project_id = ? AND wc_variation_id = ?");
        $stmt->execute([$project['id'], $wc_variation_id]);
        $mapping = $stmt->fetch();
        if ($mapping) {
            $combo_key = $mapping['combo_key'];
            $combo     = wc_parse_combo_key($combo_key);
        }
    }

    $order_qty = max(1, (int) ($item['quantity'] ?? 1));

    if ($should_deduct) {
        if ($existing && $existing['inventory_deducted']) {
            $item_log[] = ['skipped' => true, 'reason' => 'Already deducted for this order'];
            continue;
        }

        $deductions = wc_deduct_bom_inventory($db, $project['id'], $order_qty, $combo);
        $item_log[] = ['project' => $project['project_name'], 'combo' => $combo_key ?: null, 'deductions' => $deductions];

        if (!$existing) {
            $customer = trim(($order['billing']['first_name'] ?? '') . ' ' . ($order['billing']['last_name'] ?? ''));
            $db->prepare("
                INSERT INTO orders
                    (order_number, project_id, customer_name, customer_email,
                     quantity, price_paid, order_date, status, notes, source, inventory_deducted, variation_combo_key)
                VALUES (?, ?, ?, ?, ?, ?, NOW(), 'paid', ?, 'woocommerce', 1, ?)
            ")->execute([
                'WC-' . $wc_order_id,
                $project['id'],
                $customer ?: 'WooCommerce Customer',
                $order['billing']['email'] ?? '',
                $order_qty,
                $item['total'] ?? '0',
                "WooCommerce order #{$wc_order_id}",
                $combo_key ?: null,
            ]);
        } else {
            $db->prepare("UPDATE orders SET inventory_deducted = 1 WHERE id = ?")
               ->execute([$existing['id']]);
        }

        $affected_projects[] = $project['id'];

    } elseif ($should_restore) {
        if (!$existing || !$existing['inventory_deducted']) {
            $item_log[] = ['skipped' => true, 'reason' => 'No prior deduction found for this order'];
            continue;
        }

        // Use the combo stored on the original order record for accurate restoration
        $restore_combo = null;
        if (!empty($existing['variation_combo_key'])) {
            $restore_combo = wc_parse_combo_key($existing['variation_combo_key']);
        }

        $restorations = wc_restore_bom_inventory($db, $project['id'], $order_qty, $restore_combo);
        $item_log[]   = ['project' => $project['project_name'], 'combo' => $existing['variation_combo_key'] ?? null, 'restorations' => $restorations];

        $db->prepare("UPDATE orders SET status = 'cancelled', inventory_deducted = 0 WHERE id = ?")
           ->execute([$existing['id']]);

        $affected_projects[] = $project['id'];
    }
}

// Push recalculated stock to WooCommerce for every project we touched
$sync_results = [];
foreach (array_unique($affected_projects) as $project_id) {
    $sync_results[] = wc_sync_project($db, $project_id);
}

echo json_encode([
    'success'      => true,
    'wc_order_id'  => $wc_order_id,
    'action'       => $should_deduct ? 'inventory_deducted' : 'inventory_restored',
    'item_log'     => $item_log,
    'sync_results' => $sync_results,
]);
