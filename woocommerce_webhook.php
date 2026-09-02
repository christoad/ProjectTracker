<?php
/**
 * WooCommerce ↔ Inventory Sync Endpoint
 *
 * CANONICAL SOURCE: ProjectTracker/woocommerce_webhook.php
 * Deployed to:      ki6cr.com/projects/woocommerce_webhook.php
 *
 * POST  /woocommerce_webhook.php
 *   — Receives WooCommerce order webhooks (order.created, order.updated).
 *     Deducts or restores BOM inventory and pushes new stock to WooCommerce.
 *
 * GET   /woocommerce_webhook.php?action=sync&project_id=X
 *   — Recalculate and push stock for one project.
 *
 * GET   /woocommerce_webhook.php?action=sync_all
 *   — Recalculate and push stock for every mapped project.
 *
 * GET   /woocommerce_webhook.php?action=status
 *   — Show all project↔product mappings with calculated vs live WC stock.
 *
 * ── Inventory deduction logic ─────────────────────────────────────────────────
 * Statuses that trigger deduction:  processing, on-hold
 * Statuses that trigger restoration: cancelled, refunded
 * All others (completed, shipped, etc.) are skipped — inventory was already
 * deducted when the order moved to processing/on-hold.
 *
 * ── Race condition protection ─────────────────────────────────────────────────
 * WooCommerce fires order.created AND order.updated nearly simultaneously
 * when a new order comes in, both with status=processing. Without protection,
 * both webhooks pass the "already deducted?" check before either writes the
 * result, causing double-deduction.
 *
 * Fix: each order item is processed inside a transaction with a FOR UPDATE
 * lock on the orders row. The second webhook blocks until the first commits,
 * then sees inventory_deducted=1 and skips.
 *
 * ── Combo key convention ──────────────────────────────────────────────────────
 * combo_key values are always passed as RAW STRINGS (e.g. "Color:Blue").
 * wc_deduct_bom_inventory and wc_restore_bom_inventory parse them internally.
 * Never pre-parse a combo_key into an array before calling those functions.
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
              AND status = 'active'
            ORDER BY project_name
        ");
        $out = [];
        foreach ($stmt->fetchAll() as $p) {
            $wc_product_id = (int) $p['woocommerce_product_id'];

            $vstmt = $db->prepare("
                SELECT combo_key, wc_variation_id
                FROM project_variation_mappings
                WHERE project_id = ? AND wc_variation_id IS NOT NULL
            ");
            $vstmt->execute([$p['id']]);
            $mappings = $vstmt->fetchAll();

            if (!empty($mappings)) {
                $variations = [];
                foreach ($mappings as $m) {
                    // Pass combo_key as raw string — wc_calculate_variation_qty parses internally
                    $tracker_qty = wc_calculate_variation_qty($db, $p['id'], $m['combo_key']);
                    $wc_qty      = wc_fetch_variation_stock_live($wc_product_id, (int) $m['wc_variation_id']);
                    $variations[] = [
                        'combo_key'    => $m['combo_key'],
                        'variation_id' => (int) $m['wc_variation_id'],
                        'buildable'    => $tracker_qty,
                        'wc_stock'     => $wc_qty,
                        'in_sync'      => $wc_qty !== null && $wc_qty === $tracker_qty,
                    ];
                }
                $out[] = [
                    'project_id'     => $p['id'],
                    'project_name'   => $p['project_name'],
                    'wc_product_id'  => $wc_product_id,
                    'type'           => 'variable',
                    'variations'     => $variations,
                    'project_status' => $p['status'],
                ];
            } else {
                $tracker_qty = wc_calculate_available_qty($db, $p['id']);
                $wc_qty      = wc_fetch_product_stock($wc_product_id);
                $out[] = [
                    'project_id'     => $p['id'],
                    'project_name'   => $p['project_name'],
                    'wc_product_id'  => $wc_product_id,
                    'type'           => 'simple',
                    'buildable'      => $tracker_qty,
                    'wc_stock'       => $wc_qty,
                    'in_sync'        => $wc_qty !== null && $wc_qty === $tracker_qty,
                    'project_status' => $p['status'],
                ];
            }
        }
        echo json_encode($out);
        exit;
    }

    http_response_code(400);
    echo json_encode(['error' => 'Unknown action. Use: sync, sync_all, status']);
    exit;
}

// ── POST: WooCommerce order webhook ──────────────────────────────────────────

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

// wc_upsert_order() upserts the parent order row + one order_items row per line
// item (keyed by wc_line_item_id, so two line items for the same project — e.g.
// two different color variants bought together — both get captured and both
// deduct, instead of the second one being silently skipped).
$result = wc_upsert_order($db, $order, true);

if (isset($result['skipped']) || isset($result['error'])) {
    echo json_encode($result);
    exit;
}

// Push recalculated stock to WooCommerce for every project touched
$sync_results = [];
foreach ($result['affected_projects'] as $project_id) {
    $sync_results[] = wc_sync_project($db, $project_id);
}

echo json_encode([
    'success'      => true,
    'wc_order_id'  => $result['wc_order_id'],
    'item_log'     => $result['item_log'],
    'sync_results' => $sync_results,
]);
