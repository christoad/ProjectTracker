<?php
if (($_GET['pw'] ?? '') !== 'sota') {
    die('Access denied. Add ?pw=sota to the URL.');
}

require_once 'config.php';
$db = getDB();

echo "<pre style='font-family:monospace; font-size:14px; padding:20px;'>";
echo "KH1 Beta Feedback — Build Time + Admin Reply Migration\n";
echo str_repeat("─", 40) . "\n\n";

function addColumnIfMissing(PDO $db, string $table, string $column, string $definition): void {
    $stmt = $db->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    if ($stmt->fetch()) {
        echo "⏭  $table.$column — already exists, skipped\n";
        return;
    }
    $db->exec("ALTER TABLE `$table` ADD COLUMN $column $definition");
    echo "✅ $table.$column — added\n";
}

try {
    addColumnIfMissing($db, 'kh1_beta_responses', 'build_time_estimate', "VARCHAR(200) DEFAULT NULL");
    addColumnIfMissing($db, 'kh1_beta_responses', 'admin_reply',         "TEXT DEFAULT NULL");
    addColumnIfMissing($db, 'kh1_beta_responses', 'admin_reply_at',      "TIMESTAMP NULL DEFAULT NULL");
} catch (PDOException $e) {
    echo "❌ " . $e->getMessage() . "\n";
}

echo "\n✅ Done. Delete this file after running.\n";
echo "</pre>";
?>
