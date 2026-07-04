<?php
/**
 * KH1 CW Key Replacement Parts — Tracker Project Setup
 *
 * Creates a new tracker project for spare parts and wires up WooCommerce
 * variation mappings. Safe to run multiple times (idempotent).
 *
 * Run once in browser: https://ki6cr.com/projects/spare_parts_setup.php?pw=sota
 * Delete the file after confirming all steps show success.
 */

if (($_GET['pw'] ?? '') !== 'sota') {
    http_response_code(403);
    die('Forbidden — pass ?pw=sota');
}

require_once 'config.php';
$db = getDB();

echo "<pre>\n";

// ── 1. Look up tracker part IDs ──────────────────────────────────────────────

$part_numbers = [
    '42-001', '42-002', '42-003', '42-004', '42-005', '42-006',
    '42-007', '42-008',
    'CONN-001', 'HW-008', 'HW-007', 'HW-010', 'HW-006', 'HW-011',
    'HW-004', 'HW-013', 'MECH-002', 'MECH-003', 'PCB-001',
];

$placeholders = implode(',', array_fill(0, count($part_numbers), '?'));
$stmt = $db->prepare("SELECT id, part_number, part_name FROM parts WHERE part_number IN ($placeholders)");
$stmt->execute($part_numbers);
$parts_by_number = [];
foreach ($stmt->fetchAll() as $row) {
    $parts_by_number[$row['part_number']] = $row;
}

echo "Found " . count($parts_by_number) . " of " . count($part_numbers) . " parts in tracker:\n";
foreach ($part_numbers as $pn) {
    if (isset($parts_by_number[$pn])) {
        echo "  ✓ $pn → ID {$parts_by_number[$pn]['id']} ({$parts_by_number[$pn]['part_name']})\n";
    } else {
        echo "  ✗ MISSING: $pn\n";
    }
}

// WooCommerce product ID and variation IDs (from creation step)
$wc_product_id = 159;

// Maps part_number → [wc_variation_id, wc_option_name, qty_required_for_replacement]
// qty_required_for_replacement = 1 for all (customers buy single replacement pieces)
$variation_map = [
    '42-001'   => [160, 'KH1 Key Body',                          1],
    '42-002'   => [161, 'Paddle - Left',                          1],
    '42-003'   => [162, 'Paddle - Right',                         1],
    '42-004'   => [163, 'Key Cap',                                1],
    '42-005'   => [164, 'Travel Case & Jig',                      1],
    '42-006'   => [165, 'Travel Case Lid',                        1],
    '42-007'   => [166, 'M3 Standoff Cap (3D Printed)',           1],
    '42-008'   => [167, 'M3 x 2mm Spacer (3D Printed)',          1],
    'CONN-001' => [168, '3.5mm TRS Plug',                        1],
    'HW-008'   => [169, 'M2x3mm Set Screw - Brass',              1],
    'HW-007'   => [170, 'M2x2mm Set Screw - Stainless',          1],
    'HW-010'   => [171, 'M3 Shoulder Bolt (11mm)',               1],
    'HW-006'   => [172, 'M3 x 8mm Round Standoff',              1],
    'HW-011'   => [173, 'M2x3mm Set Screw - Stainless Slotted', 1],
    'HW-004'   => [174, 'M3x10mm Socket Head Screw - Black Oxide', 1],
    'HW-013'   => [175, 'Stainless Spacer 3mm x 5mm',           1],
    'MECH-002' => [176, 'Neodymium Magnet 5mm x 1mm',           1],
    'MECH-003' => [177, 'F693ZZ Flanged Bearing',               1],
    'PCB-001'  => [178, 'KH1 Breakout Board',                   1],
];

// ── 2. Create or find the spare parts project ────────────────────────────────

$existing = $db->prepare("SELECT id FROM projects WHERE project_name = 'KH1 CW Key Replacement Parts'");
$existing->execute();
$project_row = $existing->fetch();

if ($project_row) {
    $project_id = (int) $project_row['id'];
    echo "\nProject already exists (ID $project_id) — skipping insert.\n";
} else {
    $db->prepare("
        INSERT INTO projects (project_name, description, status, retail_price, woocommerce_product_id)
        VALUES (?, ?, 'active', 0, ?)
    ")->execute([
        'KH1 CW Key Replacement Parts',
        'Individual replacement parts for the KH1 CW Key. Each variation maps to one specific part.',
        $wc_product_id,
    ]);
    $project_id = (int) $db->lastInsertId();
    echo "\nCreated project ID $project_id — KH1 CW Key Replacement Parts\n";
}

// ── 3. Insert project_parts (one per part, all as variable BOM entries) ──────

echo "\nInserting project_parts:\n";
foreach ($variation_map as $part_number => [$wc_var_id, $wc_option, $qty]) {
    if (!isset($parts_by_number[$part_number])) {
        echo "  ✗ SKIP (part not in tracker): $part_number\n";
        continue;
    }
    $part_id = (int) $parts_by_number[$part_number]['id'];

    // Check for existing entry
    $check = $db->prepare("
        SELECT id FROM project_parts
        WHERE project_id = ? AND part_id = ? AND variation_attribute = 'Part' AND variation_value = ?
    ");
    $check->execute([$project_id, $part_id, $wc_option]);
    if ($check->fetch()) {
        echo "  ~ already exists: $part_number → Part:$wc_option\n";
        continue;
    }

    $db->prepare("
        INSERT INTO project_parts (project_id, part_id, quantity_required, variation_attribute, variation_value)
        VALUES (?, ?, ?, 'Part', ?)
    ")->execute([$project_id, $part_id, $qty, $wc_option]);
    echo "  ✓ $part_number → Part:$wc_option (qty $qty)\n";
}

// ── 4. Insert variation mappings ─────────────────────────────────────────────

echo "\nInserting project_variation_mappings:\n";
foreach ($variation_map as $part_number => [$wc_var_id, $wc_option, $qty]) {
    $combo_key = "Part:$wc_option";

    $check = $db->prepare("
        SELECT id FROM project_variation_mappings
        WHERE project_id = ? AND combo_key = ?
    ");
    $check->execute([$project_id, $combo_key]);
    if ($check->fetch()) {
        echo "  ~ already exists: $combo_key → WC var $wc_var_id\n";
        continue;
    }

    $db->prepare("
        INSERT INTO project_variation_mappings (project_id, combo_key, wc_variation_id)
        VALUES (?, ?, ?)
    ")->execute([$project_id, $combo_key, $wc_var_id]);
    echo "  ✓ $combo_key → WC variation $wc_var_id\n";
}

// ── 5. Verify ────────────────────────────────────────────────────────────────

$pp_count = $db->prepare("SELECT COUNT(*) FROM project_parts WHERE project_id = ?");
$pp_count->execute([$project_id]);
$vm_count = $db->prepare("SELECT COUNT(*) FROM project_variation_mappings WHERE project_id = ?");
$vm_count->execute([$project_id]);

echo "\nVerification:\n";
echo "  project_parts rows: " . $pp_count->fetchColumn() . "\n";
echo "  variation_mapping rows: " . $vm_count->fetchColumn() . "\n";
echo "\nDone. Delete this file after confirming success:\n";
echo "  ssh dreamhost-sota \"rm /home/chrisr069/ki6cr.com/projects/spare_parts_setup.php\"\n";
echo "</pre>\n";
