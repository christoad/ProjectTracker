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

$wc_order_id = (int) $order['id'];
$wc_status   = $order['status'] ?? '';

// Deduct on: processing, on-hold (NOT completed — Shippo marks orders completed
// when a label is printed, which would trigger a second spurious deduction).
$should_deduct  = in_array($wc_status, ['processing', 'on-hold']);
$should_restore = in_array($wc_status, ['cancelled', 'refunded']);

if (!$should_deduct && !$should_restore) {
    echo json_encode([
        'skipped'     => true,
        'wc_order_id' => $wc_order_id,
        'reason'      => "Status '$wc_status' requires no inventory action",
    ]);
    exit;
}

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

    // Resolve which variation combo was ordered (null for simple products)
    $combo_key = null;
    if ($wc_variation_id) {
        $stmt = $db->prepare("
            SELECT combo_key FROM project_variation_mappings
            WHERE project_id = ? AND wc_variation_id = ?
        ");
        $stmt->execute([$project['id'], $wc_variation_id]);
        $mapping = $stmt->fetch();
        if ($mapping) {
            $combo_key = $mapping['combo_key'];
        }
    }

    $order_qty = max(1, (int) ($item['quantity'] ?? 1));

    // Transaction + FOR UPDATE: prevents double-deduction from near-simultaneous
    // order.created + order.updated webhooks (both arrive with status=processing).
    // The second request blocks on the lock, then sees inventory_deducted=1 and skips.
    $db->beginTransaction();
    try {
        $stmt = $db->prepare("
            SELECT id, inventory_deducted, variation_combo_key
            FROM orders
            WHERE order_number = ? AND project_id = ?
            FOR UPDATE
        ");
        $stmt->execute(['WC-' . $wc_order_id, $project['id']]);
        $existing = $stmt->fetch();

        if ($should_deduct) {
            if ($existing && $existing['inventory_deducted']) {
                $item_log[] = ['skipped' => true, 'reason' => 'Already deducted for this order'];
                $db->commit();
                continue;
            }

            // Pass combo_key as raw string — wc_deduct_bom_inventory parses it internally
            $deductions = wc_deduct_bom_inventory($db, $project['id'], $order_qty, $combo_key);
            $item_log[] = [
                'project'    => $project['project_name'],
                'combo_key'  => $combo_key,
                'deductions' => $deductions,
            ];

            $customer = trim(($order['billing']['first_name'] ?? '') . ' ' . ($order['billing']['last_name'] ?? ''));

            if (!$existing) {
                $db->prepare("
                    INSERT INTO orders
                        (order_number, project_id, customer_name, customer_email,
                         quantity, price_paid, order_date, status, notes, source,
                         inventory_deducted, variation_combo_key)
                    VALUES (?, ?, ?, ?, ?, ?, NOW(), 'paid', ?, 'woocommerce', 1, ?)
                ")->execute([
                    'WC-' . $wc_order_id,
                    $project['id'],
                    $customer ?: 'WooCommerce Customer',
                    $order['billing']['email'] ?? '',
                    $order_qty,
                    $item['total'] ?? '0',
                    "WooCommerce order #{$wc_order_id}",
                    $combo_key,
                ]);
            } else {
                $db->prepare("
                    UPDATE orders SET inventory_deducted = 1, variation_combo_key = ? WHERE id = ?
                ")->execute([$combo_key, $existing['id']]);
            }

            $affected_projects[] = $project['id'];

        } elseif ($should_restore) {
            if (!$existing || !$existing['inventory_deducted']) {
                $item_log[] = ['skipped' => true, 'reason' => 'No prior deduction found for this order'];
                $db->commit();
                continue;
            }

            // Use the combo_key stored at deduction time — ensures we restore the right parts
            $restore_combo_key = $existing['variation_combo_key'];

            // Pass combo_key as raw string — wc_restore_bom_inventory parses it internally
            $restorations = wc_restore_bom_inventory($db, $project['id'], $order_qty, $restore_combo_key);
            $item_log[] = [
                'project'      => $project['project_name'],
                'combo_key'    => $restore_combo_key,
                'restorations' => $restorations,
            ];

            $db->prepare("UPDATE orders SET status = 'cancelled', inventory_deducted = 0 WHERE id = ?")
               ->execute([$existing['id']]);

            $affected_projects[] = $project['id'];
        }

        $db->commit();

    } catch (Exception $e) {
        $db->rollBack();
        $item_log[] = [
            'error'   => $e->getMessage(),
            'project' => $project['project_name'],
        ];
    }
}

// Push recalculated stock to WooCommerce for every project touched
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
