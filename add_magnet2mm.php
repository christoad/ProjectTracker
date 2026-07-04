<?php
if (($_GET['pw'] ?? '') !== 'sota') { http_response_code(403); die('Forbidden'); }
require_once 'config.php';
$db = getDB();
header('Content-Type: text/plain');

$project_id  = 8;
$part_id     = 54; // MECH-015 Neodymium Magnets 5mm x 2mm
$wc_var_id   = 179;
$option      = 'Neodymium Magnet 5mm x 2mm';
$combo_key   = "Part:$option";

// project_parts
$check = $db->prepare("SELECT id FROM project_parts WHERE project_id=? AND part_id=? AND variation_attribute='Part' AND variation_value=?");
$check->execute([$project_id, $part_id, $option]);
if ($check->fetch()) {
    echo "project_parts: already exists\n";
} else {
    $db->prepare("INSERT INTO project_parts (project_id, part_id, quantity_required, variation_attribute, variation_value) VALUES (?,?,1,'Part',?)")
       ->execute([$project_id, $part_id, $option]);
    echo "project_parts: inserted\n";
}

// variation_mapping
$check2 = $db->prepare("SELECT id FROM project_variation_mappings WHERE project_id=? AND combo_key=?");
$check2->execute([$project_id, $combo_key]);
if ($check2->fetch()) {
    echo "variation_mapping: already exists\n";
} else {
    $db->prepare("INSERT INTO project_variation_mappings (project_id, combo_key, wc_variation_id) VALUES (?,?,?)")
       ->execute([$project_id, $combo_key, $wc_var_id]);
    echo "variation_mapping: inserted — $combo_key → WC var $wc_var_id\n";
}

echo "Done.\n";
