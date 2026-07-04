<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>KH1 Beta Feedback — QR Code</title>
<link href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body {
  font-family: 'Figtree', sans-serif;
  background: #f1f5f9;
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 40px 20px;
  gap: 24px;
}
.card {
  background: #fff;
  border-radius: 16px;
  padding: 36px 40px;
  max-width: 460px;
  width: 100%;
  text-align: center;
  box-shadow: 0 4px 24px rgba(0,0,0,0.10);
}
.eyebrow {
  font-family: 'IBM Plex Mono', monospace;
  font-size: 0.68rem;
  letter-spacing: 0.2em;
  text-transform: uppercase;
  color: #1a56db;
  margin-bottom: 6px;
}
h1 { font-size: 1.5rem; font-weight: 700; color: #0f1c3f; letter-spacing: -0.02em; margin-bottom: 4px; }
.sub { font-size: 0.9rem; color: #6b7280; margin-bottom: 28px; }
.qr-wrap {
  display: inline-block;
  padding: 16px;
  background: #fff;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  margin-bottom: 20px;
}
.qr-wrap img { display: block; width: 240px; height: 240px; }
.url-box {
  background: #f8faff;
  border: 1px solid #c7d9fb;
  border-radius: 8px;
  padding: 12px 16px;
  font-family: 'IBM Plex Mono', monospace;
  font-size: 0.82rem;
  color: #1a56db;
  word-break: break-all;
  margin-bottom: 20px;
}
.note {
  font-size: 0.82rem;
  color: #9ca3af;
  line-height: 1.5;
}
.print-btn {
  margin-top: 24px;
  display: inline-block;
  background: #1a56db;
  color: #fff;
  border: none;
  border-radius: 8px;
  padding: 12px 28px;
  font-family: 'Figtree', sans-serif;
  font-size: 0.92rem;
  font-weight: 600;
  cursor: pointer;
  text-decoration: none;
}
.print-btn:hover { background: #1240a8; }

@media print {
  body { background: #fff; padding: 0; }
  .print-btn { display: none; }
  .card { box-shadow: none; border: 1px solid #e2e8f0; }
}
</style>
</head>
<body>

<?php
$FEEDBACK_URL = 'https://ki6cr.com/projects/kh1_feedback.php';
$qr_url = 'https://api.qrserver.com/v1/create-qr-code/?size=480x480&qzone=2&format=png&data=' . urlencode($FEEDBACK_URL);
?>

<div class="card">
  <div class="eyebrow">KI6CR Labs · Beta Program</div>
  <h1>KH1 CW Key</h1>
  <p class="sub">Beta Builder Feedback — scan to submit yours</p>
  <div class="qr-wrap">
    <img src="<?= htmlspecialchars($qr_url) ?>" alt="QR Code for KH1 beta feedback form" width="240" height="240">
  </div>
  <div class="url-box"><?= htmlspecialchars($FEEDBACK_URL) ?></div>
  <p class="note">Point your phone camera at the QR code, or type the URL into any browser. Your responses save automatically as you go.</p>
  <br>
  <button class="print-btn" onclick="window.print()">🖨 Print This Page</button>
</div>

</body>
</html>
