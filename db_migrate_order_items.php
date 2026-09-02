<?php
/**
 * DB Migration — split `orders` into `orders` (one row per real order) +
 * `order_items` (one row per line item). See CLAUDE.md / project plan for why.
 *
 * Run once via browser with ?pw=sota, confirm every step shows green, then
 * delete this file from the server. Safe to re-run — every step is guarded.
 */

if (($_GET['pw'] ?? '') !== 'sota') {
    http_response_code(403);
    die('Forbidden — pass ?pw=sota to run this migration.');
}

require_once 'config.php';
$db = getDB();

header('Content-Type: text/plain');
echo "Order Items Migration\n======================\n\n";

// Note: SHOW COLUMNS/SHOW INDEX with a `?` placeholder throws under native
// (non-emulated) prepared statements — this DB connection uses
// PDO::ATTR_EMULATE_PREPARES => false. Query information_schema instead,
// which supports normal parameter binding.
function column_exists($db, $table, $column) {
    $stmt = $db->prepare("SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
    $stmt->execute([$table, $column]);
    return (bool) $stmt->fetch();
}

function index_exists($db, $table, $indexName) {
    $stmt = $db->prepare("SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?");
    $stmt->execute([$table, $indexName]);
    return (bool) $stmt->fetch();
}

// ── Step 1: backup ───────────────────────────────────────────────────────────
$backupFile = __DIR__ . '/orders_backup_' . date('Ymd_His') . '.json';
$allOrders = $db->query("SELECT * FROM orders")->fetchAll();
file_put_contents($backupFile, json_encode($allOrders, JSON_PRETTY_PRINT));
echo "✓ Backed up " . count($allOrders) . " orders rows to " . basename($backupFile) . "\n";
echo "  (download this file, then delete it from the server once you've verified everything)\n\n";

// ── Step 2: create order_items table ─────────────────────────────────────────
try {
    $db->exec("
        CREATE TABLE IF NOT EXISTS order_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            project_id INT NOT NULL,
            variation_combo_key VARCHAR(500) NULL,
            wc_line_item_id INT NULL,
            quantity INT NOT NULL DEFAULT 1,
            unit_price DECIMAL(10,2) NOT NULL DEFAULT 0,
            line_total DECIMAL(10,2) NOT NULL DEFAULT 0,
            inventory_deducted TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
            FOREIGN KEY (project_id) REFERENCES projects(id),
            UNIQUE KEY uniq_order_line (order_id, wc_line_item_id),
            INDEX idx_project_id (project_id)
        ) ENGINE=InnoDB
    ");
    echo "✓ Created (or already existed) order_items table.\n";
} catch (Exception $e) {
    die("✗ FATAL creating order_items: " . $e->getMessage() . "\n");
}

// ── Step 3: add new columns to orders ────────────────────────────────────────
foreach ([
    'wc_order_id' => "ALTER TABLE orders ADD COLUMN wc_order_id INT NULL UNIQUE AFTER order_number",
    'order_total' => "ALTER TABLE orders ADD COLUMN order_total DECIMAL(10,2) NULL",
    'tax_total'   => "ALTER TABLE orders ADD COLUMN tax_total DECIMAL(10,2) DEFAULT 0",
] as $col => $sql) {
    if (column_exists($db, 'orders', $col)) {
        echo "– Column orders.$col already exists, skipping.\n";
        continue;
    }
    try {
        $db->exec($sql);
        echo "✓ Added orders.$col\n";
    } catch (Exception $e) {
        die("✗ FATAL adding orders.$col: " . $e->getMessage() . "\n");
    }
}
echo "\n";

// ── Step 4: backfill order_items 1:1 from existing orders ───────────────────
// Guarded: only insert for orders that don't already have an order_items row.
// Requires the pre-migration source columns to still be present — if they're
// already dropped (a prior run got partway through), the data has already
// been backfilled, so skip straight through.
if (column_exists($db, 'orders', 'project_id') && column_exists($db, 'orders', 'quantity') && column_exists($db, 'orders', 'price_paid')) {
    $stmt = $db->query("
        SELECT o.id, o.project_id, o.variation_combo_key, o.quantity, o.price_paid, o.inventory_deducted, o.created_at
        FROM orders o
        WHERE o.project_id IS NOT NULL
          AND NOT EXISTS (SELECT 1 FROM order_items oi WHERE oi.order_id = o.id)
    ");
    $toMigrate = $stmt->fetchAll();

    $ins = $db->prepare("
        INSERT INTO order_items (order_id, project_id, variation_combo_key, wc_line_item_id, quantity, unit_price, line_total, inventory_deducted, created_at)
        VALUES (?, ?, ?, NULL, ?, ?, ?, ?, ?)
    ");
    $migrated = 0;
    foreach ($toMigrate as $o) {
        $qty       = max(1, (int) $o['quantity']);
        $unitPrice = round(((float) $o['price_paid']) / $qty, 2);
        $ins->execute([
            $o['id'], $o['project_id'], $o['variation_combo_key'] ?: null,
            $qty, $unitPrice, $o['price_paid'], $o['inventory_deducted'], $o['created_at'],
        ]);
        $migrated++;
    }
    echo "✓ Backfilled $migrated order_items row(s) from existing orders.\n\n";
} else {
    echo "– orders.project_id already dropped, skipping backfill (already migrated).\n\n";
}

// ── Step 5: backfill orders.wc_order_id from order_number ───────────────────
$updated = $db->exec("
    UPDATE orders
    SET wc_order_id = CAST(SUBSTRING(order_number, 4) AS UNSIGNED)
    WHERE source = 'woocommerce' AND order_number LIKE 'WC-%' AND wc_order_id IS NULL
");
echo "✓ Backfilled wc_order_id on $updated order(s).\n\n";

// ── Step 6: verify row counts before dropping anything ───────────────────────
$orderCount = (int) $db->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$itemCount  = (int) $db->query("SELECT COUNT(*) FROM order_items")->fetchColumn();
echo "orders: $orderCount rows | order_items: $itemCount rows\n";

if ($itemCount < $orderCount) {
    die("✗ ABORTING before dropping columns — order_items ($itemCount) has fewer rows than orders ($orderCount). Investigate before re-running.\n");
}
echo "✓ Row count check passed — safe to proceed.\n\n";

// ── Step 7: drop old unique key + moved columns from orders ─────────────────
if (index_exists($db, 'orders', 'unique_order_project')) {
    $db->exec("ALTER TABLE orders DROP INDEX unique_order_project");
    echo "✓ Dropped old unique_order_project key.\n";
} else {
    echo "– unique_order_project key already gone, skipping.\n";
}

// orders.project_id carries a legacy FK to projects(id) — must drop that
// constraint before the column itself will drop.
$stmt = $db->prepare("
    SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'project_id'
      AND REFERENCED_TABLE_NAME IS NOT NULL
");
$stmt->execute();
foreach ($stmt->fetchAll() as $fk) {
    try {
        $db->exec("ALTER TABLE orders DROP FOREIGN KEY `{$fk['CONSTRAINT_NAME']}`");
        echo "✓ Dropped FK orders.{$fk['CONSTRAINT_NAME']}\n";
    } catch (Exception $e) {
        echo "✗ Error dropping FK {$fk['CONSTRAINT_NAME']}: " . $e->getMessage() . "\n";
    }
}

foreach (['project_id', 'quantity', 'price_paid', 'variation_combo_key', 'inventory_deducted'] as $col) {
    if (!column_exists($db, 'orders', $col)) {
        echo "– orders.$col already dropped, skipping.\n";
        continue;
    }
    try {
        $db->exec("ALTER TABLE orders DROP COLUMN $col");
        echo "✓ Dropped orders.$col\n";
    } catch (Exception $e) {
        echo "✗ Error dropping orders.$col: " . $e->getMessage() . "\n";
    }
}

echo "\nDone. Verify the app works, download " . basename($backupFile) . ", then delete both it and this migration file from the server.\n";
