<?php
/**
 * DB Migration — add orders.sticker_deducted to track the one-per-order
 * KI6CR Labs Sticker deduction (a non-BOM packaging item; exactly one is
 * included per order regardless of line-item count or quantity, so it can't
 * be modeled as a normal per-part-per-kit BOM row).
 * Run once via browser with ?pw=sota, confirm all steps show green, then delete this file.
 */

if (($_GET['pw'] ?? '') !== 'sota') {
    http_response_code(403);
    die('Forbidden — pass ?pw=sota to run this migration.');
}

require_once 'config.php';
$db = getDB();

header('Content-Type: text/plain');
echo "Sticker Deduction Migration\n============================\n\n";

try {
    $stmt = $db->prepare("
        SELECT COUNT(*) FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'sticker_deducted'
    ");
    $stmt->execute();
    if ((int)$stmt->fetchColumn() === 0) {
        $db->exec("ALTER TABLE orders ADD COLUMN sticker_deducted TINYINT(1) NOT NULL DEFAULT 0 AFTER status");
        echo "✓ Added orders.sticker_deducted column.\n";
    } else {
        echo "- orders.sticker_deducted already exists, skipping.\n";
    }
} catch (Exception $e) {
    echo "✗ Error adding sticker_deducted column: " . $e->getMessage() . "\n";
}

echo "\nDone. Delete this file from the server now.\n";
