<?php
require_once 'config.php';
header('Content-Type: application/json');

$ALLOWED_STEPS = [
    'packaging','step01','step02','step03','step04','step05','step06','step07',
    'step08','step09','step10','step11','step12','step13','step14','step15',
    'step16','step17','general'
];

$action   = $_POST['action'] ?? $_GET['action'] ?? '';
$db       = getDB();

function cleanCallsign(string $cs): string {
    return strtoupper(preg_replace('/[^A-Za-z0-9\/]/', '', $cs));
}

// ── save_session ─────────────────────────────────────────────────────────────
if ($action === 'save_session') {
    $callsign = cleanCallsign($_POST['callsign'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    if (strlen($callsign) < 3 || strlen($callsign) > 20) {
        jsonResponse(['error' => 'Invalid callsign'], 400);
    }
    $stmt = $db->prepare(
        "INSERT INTO kh1_beta_sessions (callsign, email)
         VALUES (?, ?)
         ON DUPLICATE KEY UPDATE email = IF(? != '', ?, email)"
    );
    $stmt->execute([$callsign, $email, $email, $email]);
    jsonResponse(['success' => true, 'callsign' => $callsign]);
}

// ── save_response ─────────────────────────────────────────────────────────────
if ($action === 'save_response') {
    global $ALLOWED_STEPS;
    $callsign = cleanCallsign($_POST['callsign'] ?? '');
    $step_key = $_POST['step_key'] ?? '';
    if (strlen($callsign) < 3 || !in_array($step_key, $ALLOWED_STEPS, true)) {
        jsonResponse(['error' => 'Invalid callsign or step'], 400);
    }

    $rating           = isset($_POST['rating']) && in_array((int)$_POST['rating'], [1,2,3])
                        ? (int)$_POST['rating'] : null;
    $feedback         = substr(trim($_POST['feedback'] ?? ''), 0, 2000);
    $pkg_intact       = isset($_POST['packaging_intact'])  ? (int)(bool)$_POST['packaging_intact']  : null;
    $tools_in_box     = isset($_POST['tools_in_box'])      ? (int)(bool)$_POST['tools_in_box']      : null;
    $parts_undamaged  = isset($_POST['parts_undamaged'])   ? (int)(bool)$_POST['parts_undamaged']   : null;

    $stmt = $db->prepare("
        INSERT INTO kh1_beta_responses
            (callsign, step_key, rating, feedback, packaging_intact, tools_in_box, parts_undamaged)
        VALUES (?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            rating           = VALUES(rating),
            feedback         = VALUES(feedback),
            packaging_intact = VALUES(packaging_intact),
            tools_in_box     = VALUES(tools_in_box),
            parts_undamaged  = VALUES(parts_undamaged),
            updated_at       = NOW()
    ");
    $stmt->execute([$callsign, $step_key, $rating, $feedback, $pkg_intact, $tools_in_box, $parts_undamaged]);
    jsonResponse(['success' => true]);
}

// ── get_responses ─────────────────────────────────────────────────────────────
if ($action === 'get_responses') {
    $callsign = cleanCallsign($_GET['callsign'] ?? '');
    if (strlen($callsign) < 3) {
        jsonResponse(['error' => 'Invalid callsign'], 400);
    }
    $stmt = $db->prepare("SELECT * FROM kh1_beta_responses WHERE callsign = ?");
    $stmt->execute([$callsign]);
    $rows = $stmt->fetchAll();
    $by_step = [];
    foreach ($rows as $r) {
        $by_step[$r['step_key']] = $r;
    }
    jsonResponse(['success' => true, 'responses' => $by_step]);
}

jsonResponse(['error' => 'Invalid action'], 400);
?>
