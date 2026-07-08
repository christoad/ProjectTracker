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
    $video_exts = ['mp4','mov','webm','avi','mkv','m4v'];
    $stmt = $db->prepare("SELECT id, step_key, filename FROM kh1_beta_photos WHERE callsign = ? ORDER BY created_at ASC");
    $stmt->execute([$callsign]);
    $photos_by_step = [];
    foreach ($stmt->fetchAll() as $p) {
        $ext  = strtolower(pathinfo($p['filename'], PATHINFO_EXTENSION));
        $photos_by_step[$p['step_key']][] = [
            'id'   => (int)$p['id'],
            'url'  => 'kh1_uploads/' . $p['filename'],
            'type' => in_array($ext, $video_exts) ? 'video' : 'image',
        ];
    }
    jsonResponse(['success' => true, 'responses' => $by_step, 'photos' => $photos_by_step]);
}

// ── upload_photo ──────────────────────────────────────────────────────────────
if ($action === 'upload_photo') {
    $callsign = cleanCallsign($_POST['callsign'] ?? '');
    $step_key = $_POST['step_key'] ?? '';
    if (strlen($callsign) < 3 || !in_array($step_key, $ALLOWED_STEPS, true)) {
        jsonResponse(['error' => 'Invalid callsign or step'], 400);
    }
    if (!isset($_FILES['photo']) || $_FILES['photo']['error'] === UPLOAD_ERR_INI_SIZE) {
        jsonResponse(['error' => 'File too large for the server. Try a shorter clip or smaller image.'], 400);
    }
    if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
        jsonResponse(['error' => 'No file uploaded'], 400);
    }
    $file = $_FILES['photo'];
    if ($file['size'] > 512 * 1024 * 1024) {
        jsonResponse(['error' => 'File too large (max 512MB)'], 400);
    }
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    $is_image = strpos($mime, 'image/') === 0;
    $is_video = strpos($mime, 'video/') === 0;
    if (!$is_image && !$is_video) {
        jsonResponse(['error' => 'Please upload an image or video file.'], 400);
    }
    $ext_map = [
        'image/jpeg'=>'jpg','image/png'=>'png','image/gif'=>'gif','image/webp'=>'webp',
        'image/heic'=>'heic','image/heif'=>'heif',
        'video/mp4'=>'mp4','video/quicktime'=>'mov','video/webm'=>'webm',
        'video/x-msvideo'=>'avi','video/x-matroska'=>'mkv','video/x-m4v'=>'m4v',
    ];
    $ext  = $ext_map[$mime] ?? ($is_video ? 'mp4' : 'jpg');
    $type = $is_video ? 'video' : 'image';
    $upload_dir = __DIR__ . '/kh1_uploads/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    $filename = uniqid($callsign . '_' . $step_key . '_', true) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $upload_dir . $filename)) {
        jsonResponse(['error' => 'Could not save file'], 500);
    }
    $stmt = $db->prepare("INSERT INTO kh1_beta_photos (callsign, step_key, filename) VALUES (?, ?, ?)");
    $stmt->execute([$callsign, $step_key, $filename]);
    jsonResponse(['success' => true, 'photo' => ['id' => (int)$db->lastInsertId(), 'url' => 'kh1_uploads/' . $filename, 'type' => $type]]);
}

// ── delete_photo ──────────────────────────────────────────────────────────────
if ($action === 'delete_photo') {
    $callsign = cleanCallsign($_POST['callsign'] ?? '');
    $photo_id = (int)($_POST['photo_id'] ?? 0);
    if (strlen($callsign) < 3 || $photo_id < 1) {
        jsonResponse(['error' => 'Invalid request'], 400);
    }
    $stmt = $db->prepare("SELECT filename FROM kh1_beta_photos WHERE id = ? AND callsign = ?");
    $stmt->execute([$photo_id, $callsign]);
    $row = $stmt->fetch();
    if (!$row) {
        jsonResponse(['error' => 'Photo not found'], 404);
    }
    $filepath = __DIR__ . '/kh1_uploads/' . $row['filename'];
    if (file_exists($filepath)) {
        unlink($filepath);
    }
    $stmt = $db->prepare("DELETE FROM kh1_beta_photos WHERE id = ? AND callsign = ?");
    $stmt->execute([$photo_id, $callsign]);
    jsonResponse(['success' => true]);
}

jsonResponse(['error' => 'Invalid action'], 400);
?>
