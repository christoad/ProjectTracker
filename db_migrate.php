<?php
/**
 * One-time migration: add variable parts support
 * Run at: yoursite.com/projects/db_migrate.php?password=sota
 * DELETE THIS FILE from the server after running!
 */

if (($_GET['password'] ?? '') !== 'sota') {
    die('Access denied. Add ?password=sota to the URL to run.');
}

require_once 'config.php';
$db = getDB();

function run_step(string $label, callable $fn): void {
    echo "<div style='font-family: monospace; margin: 5px 0;'>";
    try {
        $fn();
        echo "<span style='color: green; font-weight: bold;'>✓</span> $label";
    } catch (PDOException $e) {
        $msg = $e->getMessage();
        $is_skip = (
            str_contains($msg, 'Duplicate column name') ||
            str_contains($msg, 'already exists') ||
            str_contains($msg, "Can't DROP") ||
            str_contains($msg, 'Duplicate key name')
        );
        if ($is_skip) {
            echo "<span style='color: orange; font-weight: bold;'>SKIP</span> $label (already applied)";
        } else {
            echo "<span style='color: red; font-weight: bold;'>ERROR</span> $label — $msg";
        }
    }
    echo "</div>\n";
}

echo "<h2>KI6CR Inventory — Variable Parts Migration</h2>\n";

run_step('Add variation_attribute column to project_parts', function() use ($db) {
    $db->exec("ALTER TABLE project_parts ADD COLUMN variation_attribute VARCHAR(100) NOT NULL DEFAULT ''");
});

run_step('Add variation_value column to project_parts', function() use ($db) {
    $db->exec("ALTER TABLE project_parts ADD COLUMN variation_value VARCHAR(255) NOT NULL DEFAULT ''");
});

run_step('Drop old unique index unique_project_part (project_id, part_id)', function() use ($db) {
    $db->exec("ALTER TABLE project_parts DROP INDEX unique_project_part");
});

run_step('Add new unique index (project_id, part_id, variation_attribute, variation_value)', function() use ($db) {
    $db->exec("ALTER TABLE project_parts ADD UNIQUE KEY unique_project_part (project_id, part_id, variation_attribute, variation_value)");
});

run_step('Create project_variation_mappings table', function() use ($db) {
    $db->exec("CREATE TABLE IF NOT EXISTS project_variation_mappings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        project_id INT NOT NULL,
        combo_key VARCHAR(500) NOT NULL,
        wc_variation_id INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
        UNIQUE KEY unique_project_combo (project_id, combo_key)
    ) ENGINE=InnoDB");
});

run_step('Add variation_combo_key column to orders', function() use ($db) {
    $db->exec("ALTER TABLE orders ADD COLUMN variation_combo_key VARCHAR(500) NULL");
});

echo "<hr>\n";
echo "<p style='font-family: monospace; color: darkgreen; font-size: 1.1em;'><strong>Done!</strong> All steps complete.</p>\n";
echo "<p style='font-family: monospace; color: red;'>Delete this file from the server: <code>rm /home/chrisr069/ki6cr.com/projects/db_migrate.php</code></p>\n";
