<?php
if (($_GET['pw'] ?? '') !== 'sota') {
    die('Access denied. Add ?pw=sota to the URL.');
}

require_once 'config.php';
$db = getDB();

echo "<pre style='font-family:monospace; font-size:14px; padding:20px;'>";
echo "KH1 Beta Feedback — DB Migration\n";
echo str_repeat("─", 40) . "\n\n";

try {
    $db->exec("CREATE TABLE IF NOT EXISTS kh1_beta_sessions (
        id           INT AUTO_INCREMENT PRIMARY KEY,
        callsign     VARCHAR(20) NOT NULL,
        email        VARCHAR(100) DEFAULT NULL,
        created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_callsign (callsign)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "✅ kh1_beta_sessions — OK\n";
} catch (PDOException $e) {
    echo "❌ kh1_beta_sessions — " . $e->getMessage() . "\n";
}

try {
    $db->exec("CREATE TABLE IF NOT EXISTS kh1_beta_responses (
        id               INT AUTO_INCREMENT PRIMARY KEY,
        callsign         VARCHAR(20) NOT NULL,
        step_key         VARCHAR(50) NOT NULL,
        rating           TINYINT DEFAULT NULL COMMENT '1=great 2=questions 3=trouble',
        feedback         TEXT DEFAULT NULL,
        packaging_intact TINYINT(1) DEFAULT NULL,
        tools_in_box     TINYINT(1) DEFAULT NULL,
        parts_undamaged  TINYINT(1) DEFAULT NULL,
        submitted_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_response (callsign, step_key)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "✅ kh1_beta_responses — OK\n";
} catch (PDOException $e) {
    echo "❌ kh1_beta_responses — " . $e->getMessage() . "\n";
}

echo "\n✅ Done. Delete this file after running.\n";
echo "</pre>";
?>
