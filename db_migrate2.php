<?php
/**
 * Migration: add business_expenses table (store-wide overhead expenses)
 * Run at: yoursite.com/projects/db_migrate2.php?password=sota
 * DELETE THIS FILE from the server after running!
 */

if (($_GET['password'] ?? '') !== 'sota') {
    die('Access denied. Add ?password=sota to the URL to run.');
}

require_once 'config.php';
$db = getDB();

echo "<h2>KI6CR Inventory — Business Expenses Migration</h2>\n";
echo "<div style='font-family: monospace; margin: 5px 0;'>";

try {
    $db->exec("CREATE TABLE IF NOT EXISTS business_expenses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        description VARCHAR(255) NOT NULL,
        cost DECIMAL(10, 2) NOT NULL,
        category VARCHAR(100) NOT NULL DEFAULT 'Other',
        expense_date DATE NOT NULL,
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_expense_date (expense_date)
    ) ENGINE=InnoDB");
    echo "<span style='color:green;font-weight:bold;'>✓</span> Created business_expenses table";
} catch (PDOException $e) {
    if (str_contains($e->getMessage(), 'already exists')) {
        echo "<span style='color:orange;font-weight:bold;'>SKIP</span> business_expenses table already exists";
    } else {
        echo "<span style='color:red;font-weight:bold;'>ERROR</span> " . $e->getMessage();
    }
}

echo "</div>\n<hr>\n";
echo "<p style='font-family:monospace;color:darkgreen;'><strong>Done!</strong> Delete this file: <code>ssh dreamhost-sota \"rm /home/chrisr069/ki6cr.com/projects/db_migrate2.php\"</code></p>\n";
