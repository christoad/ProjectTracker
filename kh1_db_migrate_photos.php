<?php
if (($_GET['pw'] ?? '') !== 'sota') {
    die('Access denied. Add ?pw=sota to the URL.');
}

require_once 'config.php';
$db = getDB();

echo "<pre style='font-family:monospace; font-size:14px; padding:20px;'>";
echo "KH1 Beta Feedback — Photo Upload Migration\n";
echo str_repeat("─", 40) . "\n\n";

try {
    $db->exec("CREATE TABLE IF NOT EXISTS kh1_beta_photos (
        id         INT AUTO_INCREMENT PRIMARY KEY,
        callsign   VARCHAR(20) NOT NULL,
        step_key   VARCHAR(50) NOT NULL,
        filename   VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_callsign_step (callsign, step_key)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "✅ kh1_beta_photos — OK\n";
} catch (PDOException $e) {
    echo "❌ kh1_beta_photos — " . $e->getMessage() . "\n";
}

// Create upload directory
$upload_dir = __DIR__ . '/kh1_uploads/';
if (!is_dir($upload_dir)) {
    if (mkdir($upload_dir, 0755, true)) {
        echo "✅ kh1_uploads/ directory created\n";
    } else {
        echo "❌ Could not create kh1_uploads/ directory — create it manually and chmod 755\n";
    }
} else {
    echo "✅ kh1_uploads/ directory already exists\n";
}

echo "\n✅ Done. Delete this file after running.\n";
echo "</pre>";
?>
