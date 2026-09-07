<?php
/**
 * DB Migration — add inventory_adjustments table (manual stock changes outside
 * normal purchase check-ins, e.g. raw parts consumed directly by a JLCPCB PCBA
 * run, damage/loss, or a manual count correction).
 * Run once via browser with ?pw=sota, confirm all steps show green, then delete this file.
 */

if (($_GET['pw'] ?? '') !== 'sota') {
    http_response_code(403);
    die('Forbidden — pass ?pw=sota to run this migration.');
}

require_once 'config.php';
$db = getDB();

header('Content-Type: text/plain');
echo "Inventory Adjustments Migration\n================================\n\n";

try {
    $db->exec("
        CREATE TABLE IF NOT EXISTS inventory_adjustments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            part_id INT NOT NULL,
            quantity_change INT NOT NULL,
            reason VARCHAR(100) NOT NULL,
            note VARCHAR(500) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (part_id) REFERENCES parts(id),
            INDEX idx_part_id (part_id)
        ) ENGINE=InnoDB
    ");
    echo "✓ Created (or already existed) inventory_adjustments table.\n";
} catch (Exception $e) {
    echo "✗ Error creating inventory_adjustments: " . $e->getMessage() . "\n";
}

echo "\nDone. Delete this file from the server now.\n";
