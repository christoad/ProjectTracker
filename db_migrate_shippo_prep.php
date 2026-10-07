<?php
/**
 * DB Migration — add columns to track Shippo shipment prep (rate-shopped
 * Shipment objects created ahead of time so Chris can bulk-buy/print labels
 * in Shippo's dashboard without fixing weight/carrier per order). See
 * CLAUDE.md "Shippo Shipment Prep".
 * Run once via browser with ?pw=sota, confirm all steps show green, then delete this file.
 */

if (($_GET['pw'] ?? '') !== 'sota') {
    http_response_code(403);
    die('Forbidden — pass ?pw=sota to run this migration.');
}

require_once 'config.php';
$db = getDB();

header('Content-Type: text/plain');
echo "Shippo Shipment Prep Migration\n===============================\n\n";

function add_column_if_missing($db, $table, $column, $definition) {
    $stmt = $db->prepare("
        SELECT COUNT(*) FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?
    ");
    $stmt->execute([$table, $column]);
    if ((int) $stmt->fetchColumn() === 0) {
        $db->exec("ALTER TABLE $table ADD COLUMN $column $definition");
        echo "✓ Added $table.$column.\n";
    } else {
        echo "- $table.$column already exists, skipping.\n";
    }
}

try {
    add_column_if_missing($db, 'orders', 'shippo_shipment_id', "VARCHAR(64) NULL DEFAULT NULL AFTER tracking_url");
    add_column_if_missing($db, 'orders', 'shippo_prep_started_at', "DATETIME NULL DEFAULT NULL AFTER shippo_shipment_id");
    add_column_if_missing($db, 'orders', 'shippo_shipment_error', "TEXT NULL DEFAULT NULL AFTER shippo_prep_started_at");
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

echo "\nDone. Delete this file from the server now.\n";
