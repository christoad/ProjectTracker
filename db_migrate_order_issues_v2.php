<?php
/**
 * DB Migration: add order_issue_tracking.inventory_deducted (tracks which replacement
 * labels already deducted stock for a reshipment). Run once via browser with ?pw=sota,
 * confirm green, then delete this file.
 */

if (($_GET['pw'] ?? '') !== 'sota') {
    http_response_code(403);
    die('Forbidden: pass ?pw=sota to run this migration.');
}

require_once 'config.php';
$db = getDB();

header('Content-Type: text/plain');
echo "Order Issues Migration v2\n=========================\n\n";

try {
    $stmt = $db->prepare("
        SELECT COUNT(*) FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'order_issue_tracking' AND COLUMN_NAME = 'inventory_deducted'
    ");
    $stmt->execute();
    if ((int) $stmt->fetchColumn() === 0) {
        $db->exec("ALTER TABLE order_issue_tracking ADD COLUMN inventory_deducted TINYINT NOT NULL DEFAULT 0 AFTER note");
        echo "✓ Added order_issue_tracking.inventory_deducted.\n";
    } else {
        echo "- order_issue_tracking.inventory_deducted already exists, skipping.\n";
    }
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

echo "\nDone. Delete this file from the server now.\n";
