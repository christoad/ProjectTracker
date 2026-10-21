<?php
/**
 * DB Migration: add demand_events table (planned order spikes for the Reorder Forecast,
 * e.g. a podcast or video featuring a kit). Seeds the two October 2026 podcast airings.
 * Run once via browser with ?pw=sota, confirm all steps show green, then delete this file.
 */

if (($_GET['pw'] ?? '') !== 'sota') {
    http_response_code(403);
    die('Forbidden: pass ?pw=sota to run this migration.');
}

require_once 'config.php';
$db = getDB();

header('Content-Type: text/plain');
echo "Demand Events Migration\n=======================\n\n";

try {
    $db->exec("
        CREATE TABLE IF NOT EXISTS demand_events (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(150) NOT NULL,
            event_date DATE NOT NULL,
            extra_orders INT NOT NULL,
            spread_days INT NOT NULL DEFAULT 14,
            note VARCHAR(500) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_event_date (event_date)
        ) ENGINE=InnoDB
    ");
    echo "✓ Created (or already existed) demand_events table.\n";
} catch (Exception $e) {
    echo "✗ Error creating demand_events: " . $e->getMessage() . "\n";
}

$seeds = [
    ['Sofas on the Air podcast', '2026-10-13', 30, 14, 'Estimate. SOTA-focused audience, roughly 600-900 YouTube views per episode plus podcast listeners.'],
    ['Ham Radio Workbench podcast', '2026-10-20', 25, 21, 'Estimate. ~3K YouTube subscribers, ~800-1,000 views per episode, plus a larger audio podcast audience.'],
];
foreach ($seeds as $s) {
    try {
        $chk = $db->prepare("SELECT COUNT(*) FROM demand_events WHERE name = ? AND event_date = ?");
        $chk->execute([$s[0], $s[1]]);
        if ($chk->fetchColumn() > 0) {
            echo "• Skipped (already exists): {$s[0]} on {$s[1]}\n";
            continue;
        }
        $db->prepare("INSERT INTO demand_events (name, event_date, extra_orders, spread_days, note) VALUES (?, ?, ?, ?, ?)")
           ->execute($s);
        echo "✓ Added: {$s[0]} on {$s[1]} (+{$s[2]} orders)\n";
    } catch (Exception $e) {
        echo "✗ Error adding {$s[0]}: " . $e->getMessage() . "\n";
    }
}

echo "\nDone. Delete this file from the server now.\n";
