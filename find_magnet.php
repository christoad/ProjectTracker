<?php
if (($_GET['pw'] ?? '') !== 'sota') { http_response_code(403); die('Forbidden'); }
require_once 'config.php';
$db = getDB();
$stmt = $db->query("SELECT id, part_number, part_name, current_stock FROM parts WHERE part_name LIKE '%magnet%' OR part_name LIKE '%Magnet%' ORDER BY part_number");
header('Content-Type: text/plain');
foreach ($stmt->fetchAll() as $r) {
    echo "ID {$r['id']} | {$r['part_number']} | {$r['part_name']} | stock: {$r['current_stock']}\n";
}
