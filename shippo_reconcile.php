<?php
/**
 * Shippo → Tracker / WooCommerce Nightly Reconciliation
 *
 * CANONICAL SOURCE: ProjectTracker/shippo_reconcile.php
 * Deployed to:      ki6cr.com/projects/shippo_reconcile.php
 * Run by:           server crontab (CLI, not web-accessible logic-wise —
 *                    see CLAUDE.md for the schedule), also safe to run
 *                    manually via `php shippo_reconcile.php`.
 *
 * Why this exists: shippo_webhook.php normally does this in real time when
 * Shippo fires a transaction_created event. But a burst of many labels
 * bought at once (a Shippo "bulk buy labels" action) fires that many
 * webhook deliveries within the same second, and this DreamHost shared
 * hosting plan's PHP-FPM pool can't always keep up — some deliveries fail
 * and Shippo does not reliably retry them. Root cause + one-off manual
 * fix: see CLAUDE.md entry "Bulk Shippo Label Purchases Can Silently Fail
 * to Sync" (2026-09-21/22, order 4200058 and 14 others).
 *
 * This script re-derives the same "labels bought but never synced" state
 * by walking Shippo's own transaction history and cross-checking against
 * the tracker, then replays the same update shippo_webhook.php would have
 * done. It is idempotent — safe to run repeatedly, only touches orders
 * whose tracker row is missing the tracking number that Shippo has on file.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/woocommerce_sync.php';

// How far back to look each run. Generous on purpose — if a night's run
// ever fails to fire (server down, etc.) the next run still catches it.
const LOOKBACK_DAYS = 5;

$log_file = __DIR__ . '/shippo_reconcile.log';
function rlog(string $level, string $message, array $context = []): void {
    global $log_file;
    file_put_contents($log_file, json_encode([
        'time'    => date('Y-m-d H:i:s'),
        'level'   => $level,
        'message' => $message,
        'context' => $context,
    ]) . "\n", FILE_APPEND | LOCK_EX);
}

$env = parse_ini_file(__DIR__ . '/.env');
$shippo_token = $env['SHIPPO_API_TOKEN'] ?? '';
if (!$shippo_token) {
    rlog('error', 'SHIPPO_API_TOKEN not set in .env — aborting run');
    exit(1);
}

rlog('info', 'Reconcile run started', ['lookback_days' => LOOKBACK_DAYS]);

// ─── Pull recent SUCCESS transactions from Shippo, newest first ───────────────
$cutoff = time() - (LOOKBACK_DAYS * 86400);
$transactions = [];
$url = 'https://api.goshippo.com/transactions?results=100';
$pages = 0;

while ($url && $pages < 20) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Authorization: ShippoToken ' . $shippo_token],
        CURLOPT_TIMEOUT        => 20,
    ]);
    $resp = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code !== 200) {
        rlog('error', 'Shippo transactions fetch failed', ['http_code' => $http_code]);
        break;
    }

    $data = json_decode($resp, true);
    $stop = false;
    foreach (($data['results'] ?? []) as $t) {
        if (($t['status'] ?? '') !== 'SUCCESS') continue;
        $created_ts = strtotime($t['object_created'] ?? '');
        if ($created_ts && $created_ts < $cutoff) { $stop = true; continue; }
        if (empty($t['order']) || empty($t['tracking_number'])) continue;
        $transactions[] = $t;
    }
    $pages++;
    if ($stop) break;
    $url = $data['next'] ?? null;
}

rlog('info', 'Fetched Shippo transactions', ['count' => count($transactions), 'pages' => $pages]);

// ─── Cross-check each transaction's order against the tracker ─────────────────
$db = getDB();

$checked = 0;
$already_ok = 0;
$fixed = 0;
$tracker_missing = 0;
$errors = 0;

foreach ($transactions as $t) {
    $checked++;
    $tracking_number = trim($t['tracking_number']);
    $tracking_url    = $t['tracking_url_provider'] ?? '';
    $carrier         = strtoupper($t['carrier'] ?? '');
    $order_ref       = $t['order'];

    // Resolve WC display number (same logic as shippo_webhook.php)
    $wc_display_number = null;
    if (is_array($order_ref) && isset($order_ref['order_number'])) {
        $wc_display_number = (string) $order_ref['order_number'];
    } elseif (is_string($order_ref) && $order_ref !== '') {
        $ch = curl_init('https://api.goshippo.com/orders/' . urlencode($order_ref));
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['Authorization: ShippoToken ' . $shippo_token],
            CURLOPT_TIMEOUT        => 10,
        ]);
        $shippo_order = json_decode(curl_exec($ch), true);
        curl_close($ch);
        $wc_display_number = isset($shippo_order['order_number']) ? (string) $shippo_order['order_number'] : null;
    }

    if (!$wc_display_number) {
        $errors++;
        rlog('error', 'Could not resolve WC display number from Shippo order', ['order_ref' => $order_ref, 'tracking_number' => $tracking_number]);
        continue;
    }

    $wc_order_id = wc_resolve_order_id_from_display_number($wc_display_number);
    if (!$wc_order_id) {
        $errors++;
        rlog('error', 'Could not resolve internal WC order ID', ['wc_display_number' => $wc_display_number, 'tracking_number' => $tracking_number]);
        continue;
    }

    $stmt = $db->prepare("SELECT id, status, tracking_number FROM orders WHERE order_number = ?");
    $stmt->execute(['WC-' . $wc_order_id]);
    $order = $stmt->fetch();

    if (!$order) {
        $tracker_missing++;
        rlog('info', 'Skipped — order not found in tracker', ['wc_order_id' => $wc_order_id, 'wc_display_number' => $wc_display_number]);
        continue;
    }

    // Already synced — this transaction's tracking number is already on the tracker row.
    if ($order['tracking_number'] === $tracking_number) {
        $already_ok++;
        continue;
    }

    // ── Stuck order found — replay the same fix shippo_webhook.php would have done ──
    $db->prepare("
        UPDATE orders
        SET status = 'shipped', tracking_number = ?, tracking_url = ?, shipped_at = NOW()
        WHERE id = ?
    ")->execute([$tracking_number, $tracking_url, $order['id']]);

    $wc_status_result = wc_update_order_status($wc_order_id, 'completed');

    $note = "Shipped via {$carrier}. Tracking number: {$tracking_number}.";
    if ($tracking_url) $note .= " Track your package: {$tracking_url}";
    $wc_note_result = wc_add_order_note($wc_order_id, $note, true);

    $fixed++;
    rlog('info', 'Backfilled stuck order', [
        'wc_order_id'      => $wc_order_id,
        'wc_display_number' => $wc_display_number,
        'tracking_number'  => $tracking_number,
        'previous_status'  => $order['status'],
        'wc_status_result' => $wc_status_result,
        'wc_note_result'   => $wc_note_result,
    ]);
}

rlog('info', 'RECONCILE_SUMMARY', [
    'checked'         => $checked,
    'already_ok'      => $already_ok,
    'fixed'           => $fixed,
    'tracker_missing' => $tracker_missing,
    'errors'          => $errors,
]);

echo "RECONCILE_SUMMARY checked=$checked already_ok=$already_ok fixed=$fixed tracker_missing=$tracker_missing errors=$errors\n";
