<?php
// db_migrate_bom_cost_override.php
// Adds a cost_override column to project_parts so a BOM line item's cost
// can be manually pinned instead of always deriving from parts.weighted_avg_cost.
// Requires ?pw=sota. Run once, then delete this file.

if (($_GET['pw'] ?? '') !== 'sota') {
    die('Wrong password.');
}

require_once 'config.php';
$db = getDB();

echo "<pre>\n";

try {
    $db->exec("ALTER TABLE project_parts ADD COLUMN cost_override DECIMAL(10,4) NULL DEFAULT NULL AFTER notes");
    echo "OK: Added 'cost_override' column to project_parts.\n";
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
        echo "SKIP: 'cost_override' column already exists.\n";
    } else {
        echo "ERROR: " . $e->getMessage() . "\n";
    }
}

echo "\nDone. Delete this file after verifying.\n</pre>";
