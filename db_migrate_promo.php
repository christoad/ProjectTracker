<?php
/**
 * DB Migration — add promo_giveaways table (tracks promo/freebie inventory deductions)
 * Run once via browser with ?pw=sota, confirm all steps show green, then delete this file.
 */

if (($_GET['pw'] ?? '') !== 'sota') {
    http_response_code(403);
    die('Forbidden — pass ?pw=sota to run this migration.');
}

require_once 'config.php';
$db = getDB();

header('Content-Type: text/plain');
echo "Promo Giveaways Migration\n==========================\n\n";

try {
    $db->exec("
        CREATE TABLE IF NOT EXISTS promo_giveaways (
            id INT AUTO_INCREMENT PRIMARY KEY,
            project_id INT NOT NULL,
            variation_combo_key VARCHAR(500) NULL,
            quantity INT NOT NULL,
            note VARCHAR(500) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (project_id) REFERENCES projects(id),
            INDEX idx_project_id (project_id)
        ) ENGINE=InnoDB
    ");
    echo "✓ Created (or already existed) promo_giveaways table.\n";
} catch (Exception $e) {
    echo "✗ Error creating promo_giveaways: " . $e->getMessage() . "\n";
}

echo "\nDone. Delete this file from the server now.\n";
