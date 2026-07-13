<?php
// db_migrate_pending_orders.php
// Adds received/received_at columns to inventory_checkins.
// Marks all existing check-ins as received=1 (already in stock).
// Requires ?pw=sota. Run once, then delete this file.

if (($_GET['pw'] ?? '') !== 'sota') {
    die('Wrong password.');
}

require_once 'config.php';
$db = getDB();

echo "<pre>\n";

// Add 'received' column
try {
    $db->exec("ALTER TABLE inventory_checkins ADD COLUMN received TINYINT(1) NOT NULL DEFAULT 0");
    echo "OK: Added 'received' column.\n";
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
        echo "SKIP: 'received' column already exists.\n";
    } else {
        echo "ERROR: " . $e->getMessage() . "\n";
    }
}

// Add 'received_at' column
try {
    $db->exec("ALTER TABLE inventory_checkins ADD COLUMN received_at DATETIME NULL");
    echo "OK: Added 'received_at' column.\n";
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
        echo "SKIP: 'received_at' column already exists.\n";
    } else {
        echo "ERROR: " . $e->getMessage() . "\n";
    }
}

// Mark all existing check-ins as received (they already contributed to stock)
$affected = $db->exec("UPDATE inventory_checkins SET received = 1 WHERE received = 0");
echo "OK: Marked existing check-ins as received ($affected rows updated).\n";

echo "\nDone. Delete this file after verifying.\n</pre>";
