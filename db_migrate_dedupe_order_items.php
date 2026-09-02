<?php
/**
 * Cleanup — the 2026-09-01 orders/order_items migration backfilled one
 * order_items row per pre-migration order with wc_line_item_id = NULL. The
 * WooCommerce reconciliation pass (api.php?action=wc_reconcile_orders) then
 * inserted the real per-line-item rows keyed on wc_line_item_id, but never
 * matched or removed the legacy NULL rows — so every reconciled WC order now
 * double-counts its first line item in business_metrics.php and any other
 * SUM(oi.line_total) query.
 *
 * This deletes the legacy (wc_line_item_id IS NULL) rows for any order that
 * already has a reconciled (wc_line_item_id IS NOT NULL) sibling row — those
 * legacy rows are fully superseded, and inventory deduction already ignores
 * them (wc_upsert_order's SELECT ... FOR UPDATE matches on wc_line_item_id,
 * so a NULL row is never touched), so deleting them cannot double-restore or
 * double-deduct stock.
 *
 * Run once via browser with ?pw=sota (dry run — lists what would be deleted).
 * Re-run with &pw=sota&confirm=yes to actually delete. Delete this file after.
 */

if (($_GET['pw'] ?? '') !== 'sota') {
    http_response_code(403);
    die('Forbidden — pass ?pw=sota to run this migration.');
}

require_once 'config.php';
$db = getDB();

header('Content-Type: text/plain');
echo "Order Items De-duplication\n===========================\n\n";

$stmt = $db->query("
    SELECT oi.*, o.order_number, pr.project_name
    FROM order_items oi
    JOIN orders o ON o.id = oi.order_id
    JOIN projects pr ON pr.id = oi.project_id
    WHERE oi.wc_line_item_id IS NULL
      AND EXISTS (
          SELECT 1 FROM order_items oi2
          WHERE oi2.order_id = oi.order_id AND oi2.wc_line_item_id IS NOT NULL
      )
    ORDER BY oi.order_id
");
$dupes = $stmt->fetchAll();

echo "Found " . count($dupes) . " legacy row(s) shadowed by a reconciled row:\n\n";
foreach ($dupes as $d) {
    echo "  order_items.id={$d['id']}  order {$d['order_number']}  {$d['project_name']}  qty={$d['quantity']}  \${$d['line_total']}\n";
}

if (count($dupes) === 0) {
    echo "\nNothing to delete. Safe to delete this file.\n";
    exit;
}

$backupFile = __DIR__ . '/order_items_dupe_backup_' . date('Ymd_His') . '.json';
file_put_contents($backupFile, json_encode($dupes, JSON_PRETTY_PRINT));
echo "\n✓ Backed up " . count($dupes) . " row(s) to " . basename($backupFile) . " before deleting.\n";

if (($_GET['confirm'] ?? '') !== 'yes') {
    echo "\nDry run only — nothing deleted. Re-run with &confirm=yes to delete these " . count($dupes) . " row(s).\n";
    exit;
}

$ids = array_column($dupes, 'id');
$placeholders = implode(',', array_fill(0, count($ids), '?'));
$del = $db->prepare("DELETE FROM order_items WHERE id IN ($placeholders)");
$del->execute($ids);

echo "\n✓ Deleted " . $del->rowCount() . " legacy duplicate row(s).\n";
echo "Verify the Business tab totals look right, then delete this file and " . basename($backupFile) . " from the server.\n";
