<?php
if (($_GET['pw'] ?? '') !== 'sota') { http_response_code(403); die('Forbidden'); }
require_once 'config.php';
$db = getDB();
header('Content-Type: text/plain');

echo "=== orders columns ===\n";
foreach ($db->query("SHOW COLUMNS FROM orders")->fetchAll() as $c) {
    echo str_pad($c['Field'], 24) . str_pad($c['Type'], 24) . ($c['Null']==='NO'?'NOT NULL ':'') . ($c['Default']!==null?"DEFAULT {$c['Default']} ":'') . "\n";
}

echo "\n=== orders row count ===\n";
echo $db->query("SELECT COUNT(*) FROM orders")->fetchColumn() . "\n";

echo "\n=== orders indexes ===\n";
foreach ($db->query("SHOW INDEX FROM orders")->fetchAll() as $i) {
    echo $i['Key_name'] . " -> " . $i['Column_name'] . ($i['Non_unique']=='0'?' (UNIQUE)':'') . "\n";
}

echo "\n=== duplicate order_number counts (same order_number, different project_id) ===\n";
foreach ($db->query("SELECT order_number, COUNT(*) c FROM orders GROUP BY order_number HAVING c > 1 ORDER BY c DESC LIMIT 20")->fetchAll() as $r) {
    echo $r['order_number'] . ": " . $r['c'] . " rows\n";
}

echo "\n=== source breakdown ===\n";
foreach ($db->query("SELECT source, COUNT(*) c FROM orders GROUP BY source")->fetchAll() as $r) {
    echo $r['source'] . ": " . $r['c'] . "\n";
}

echo "\n=== sample recent rows ===\n";
foreach ($db->query("SELECT id, order_number, project_id, quantity, price_paid, shipping_charge, status, source, variation_combo_key FROM orders ORDER BY id DESC LIMIT 10")->fetchAll() as $r) {
    echo json_encode($r) . "\n";
}

echo "\n=== projects.woocommerce_product_id set ===\n";
foreach ($db->query("SELECT id, project_name, woocommerce_product_id FROM projects WHERE woocommerce_product_id IS NOT NULL")->fetchAll() as $r) {
    echo json_encode($r) . "\n";
}
