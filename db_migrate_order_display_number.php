<?php
/**
 * DB Migration — add orders.wc_display_number to store WooCommerce's
 * customer-facing display order number (e.g. "4200058"), separate from
 * orders.order_number which is used internally as "WC-{internal_wc_id}"
 * and is never shown to the customer. Backfills existing WooCommerce
 * orders via the WC REST API (batched via ?include=, not one call per order).
 * Run once via browser with ?pw=sota, confirm all steps show green, then delete this file.
 */

if (($_GET['pw'] ?? '') !== 'sota') {
    http_response_code(403);
    die('Forbidden — pass ?pw=sota to run this migration.');
}

require_once 'config.php';
require_once 'woocommerce_sync.php';
$db = getDB();

header('Content-Type: text/plain');
echo "Order Display Number Migration\n===============================\n\n";

try {
    $stmt = $db->prepare("
        SELECT COUNT(*) FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'wc_display_number'
    ");
    $stmt->execute();
    if ((int)$stmt->fetchColumn() === 0) {
        $db->exec("ALTER TABLE orders ADD COLUMN wc_display_number VARCHAR(20) NULL AFTER order_number");
        echo "✓ Added orders.wc_display_number column.\n";
    } else {
        echo "- orders.wc_display_number already exists, skipping.\n";
    }
} catch (Exception $e) {
    echo "✗ Error adding wc_display_number column: " . $e->getMessage() . "\n";
    exit;
}

echo "\nBackfilling display numbers for existing WooCommerce orders...\n";

$cfg = wc_get_config();
if (!$cfg) {
    echo "✗ WooCommerce not configured (.env) — skipping backfill.\n";
    echo "\nDone. Delete this file from the server now.\n";
    exit;
}

$rows = $db->query("
    SELECT id, wc_order_id FROM orders
    WHERE source = 'woocommerce' AND wc_order_id IS NOT NULL AND wc_display_number IS NULL
")->fetchAll();
echo "Found " . count($rows) . " orders to backfill.\n\n";

$byWcId = [];
foreach ($rows as $row) {
    $byWcId[(int)$row['wc_order_id']] = (int)$row['id'];
}

$fixed  = 0;
$errors = 0;

foreach (array_chunk(array_keys($byWcId), 100) as $chunk) {
    $url = rtrim($cfg['site_url'], '/') . '/wp-json/wc/v3/orders?include=' . implode(',', $chunk) . '&per_page=100';
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_USERPWD        => $cfg['username'] . ':' . $cfg['app_password'],
        CURLOPT_TIMEOUT        => 30,
    ]);
    $response  = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code < 200 || $http_code >= 300) {
        echo "✗ Batch of " . count($chunk) . " orders: HTTP $http_code\n";
        $errors += count($chunk);
        continue;
    }

    $wcOrders = json_decode($response, true);
    if (!is_array($wcOrders)) {
        echo "✗ Batch of " . count($chunk) . " orders: unexpected response\n";
        $errors += count($chunk);
        continue;
    }

    $seen = [];
    foreach ($wcOrders as $wcOrder) {
        $wc_id  = (int) ($wcOrder['id'] ?? 0);
        $number = $wcOrder['number'] ?? null;
        if (!$wc_id || !$number || !isset($byWcId[$wc_id])) continue;

        $db->prepare("UPDATE orders SET wc_display_number = ? WHERE id = ?")
           ->execute([(string) $number, $byWcId[$wc_id]]);
        $fixed++;
        $seen[$wc_id] = true;
    }

    foreach ($chunk as $wc_id) {
        if (!isset($seen[$wc_id])) {
            echo "✗ wc_order_id=$wc_id not found in WooCommerce response (deleted order?)\n";
            $errors++;
        }
    }
}

echo "\n✓ Backfilled $fixed orders. $errors errors.\n";
echo "\nDone. Delete this file from the server now.\n";
