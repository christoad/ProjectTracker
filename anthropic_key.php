<?php
/**
 * Claude API key setup for the Parts tab order import.
 *
 * The key is typed here and written straight into the server .env, so it never
 * passes through chat, git, or client-side code. The page never displays the
 * key back, only its last 4 characters. Login required.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/vendor/autoload.php';

use Anthropic\Client;
use Anthropic\Core\Exceptions\APIConnectionException;
use Anthropic\Core\Exceptions\APIStatusException;
use Anthropic\Core\Exceptions\AuthenticationException;
use Anthropic\Core\Exceptions\PermissionDeniedException;

if (!isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$envFile = __DIR__ . '/.env';

if (empty($_SESSION['key_form_token'])) {
    $_SESSION['key_form_token'] = bin2hex(random_bytes(16));
}

function current_key_hint($envFile) {
    $env = parse_ini_file($envFile);
    $k = $env['ANTHROPIC_API_KEY'] ?? '';
    return $k === '' ? null : substr($k, -4);
}

$message = null;
$isError = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $key = trim($_POST['api_key'] ?? '');

    if (!hash_equals($_SESSION['key_form_token'], $_POST['token'] ?? '')) {
        $message = 'This form expired. Reload the page and try again.';
        $isError = true;
    } elseif (!preg_match('/^sk-ant-[A-Za-z0-9_\-]{20,}$/', $key)) {
        // Strict pattern also guarantees nothing but key characters reaches .env
        $message = "That doesn't look like an Anthropic API key. It should start with sk-ant- and have no spaces.";
        $isError = true;
    } else {
        // Check the key works before saving it. Looking up a model is free.
        try {
            (new Client(apiKey: $key))->models->retrieve('claude-opus-5');
            $ok = true;
        } catch (AuthenticationException $e) {
            $ok = false;
            $message = 'Anthropic rejected that key. Check that you copied the whole thing, or create a new one.';
        } catch (PermissionDeniedException $e) {
            $ok = false;
            $message = "That key doesn't have permission to use the API. Check its workspace in the Anthropic Console.";
        } catch (APIStatusException | APIConnectionException $e) {
            $ok = false;
            $message = 'Could not check the key with Anthropic right now. Try again in a minute.';
        }

        if (!$ok) {
            $isError = true;
        } else {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES);
            $lines = array_values(array_filter($lines, fn($l) => !preg_match('/^\s*ANTHROPIC_API_KEY\s*=/', $l)));
            $lines[] = 'ANTHROPIC_API_KEY="' . $key . '"';
            $tmp = $envFile . '.tmp';
            if (file_put_contents($tmp, implode("\n", $lines) . "\n", LOCK_EX) === false || !rename($tmp, $envFile)) {
                @unlink($tmp);
                $message = "The key works, but the server couldn't save it to .env.";
                $isError = true;
            } else {
                @chmod($envFile, 0600);
                $message = 'Key saved and working. You can close this page and use Import a Parts Order on the Parts tab.';
            }
        }
    }
    $_SESSION['key_form_token'] = bin2hex(random_bytes(16));
}

$hint = current_key_hint($envFile);
$h = fn($s) => htmlspecialchars($s, ENT_QUOTES);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Claude API Key</title>
<link href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
    :root {
        --bg-body: #e8f0fe; --bg-card: #f4f8ff; --border-card: #c7d9fb;
        --accent-primary: #1a56db; --text-primary: #0f1c3f; --text-secondary: #6b7280;
        --success: #10b981; --danger: #ef4444;
    }
    body { margin: 0; background: var(--bg-body); color: var(--text-primary); font-family: 'Figtree', sans-serif; }
    .wrap { max-width: 560px; margin: 48px auto; padding: 0 16px; }
    .card { background: var(--bg-card); border: 1px solid var(--border-card); border-radius: 6px; padding: 24px; box-shadow: 0 2px 8px rgba(10,30,100,0.06); }
    h1 { font-size: 1.3rem; margin: 0 0 12px; }
    p { color: var(--text-secondary); line-height: 1.5; font-size: 0.95rem; }
    input[type=password] { width: 100%; box-sizing: border-box; padding: 10px; font-family: 'IBM Plex Mono', monospace; font-size: 0.9rem; border: 1px solid var(--border-card); border-radius: 4px; }
    button { margin-top: 12px; background: var(--accent-primary); color: #fff; border: 0; border-radius: 4px; padding: 10px 18px; font: 600 0.95rem 'Figtree', sans-serif; cursor: pointer; }
    .msg { padding: 10px 12px; border-radius: 4px; margin-bottom: 16px; font-size: 0.95rem; }
    .ok { background: #d1fae5; color: #065f46; } .err { background: #fee2e2; color: #991b1b; }
    .status { font-size: 0.9rem; margin-bottom: 16px; }
    code { font-family: 'IBM Plex Mono', monospace; }
    a { color: var(--accent-primary); }
</style>
</head>
<body>
<div class="wrap">
    <div class="card">
        <h1>Claude API Key</h1>
        <?php if ($message): ?>
            <div class="msg <?= $isError ? 'err' : 'ok' ?>"><?= $h($message) ?></div>
        <?php endif; ?>
        <div class="status">
            <?= $hint
                ? 'A key is set (ends in <code>' . $h($hint) . '</code>). Paste a new one below to replace it.'
                : 'No key is set yet.' ?>
        </div>
        <p>This key lets the Parts tab's Import a Parts Order box ask Claude to read pasted invoices. It's saved only in the server's private settings file, and this page never shows it again.</p>
        <form method="post" autocomplete="off">
            <input type="hidden" name="token" value="<?= $h($_SESSION['key_form_token']) ?>">
            <input type="password" name="api_key" placeholder="sk-ant-..." required>
            <button type="submit">Check and Save Key</button>
        </form>
        <p style="margin-top: 20px;"><a href="index.php">Back to the tracker</a></p>
    </div>
</div>
</body>
</html>
