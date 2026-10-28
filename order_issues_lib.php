<?php
/**
 * Helpers for the Order Issues tab: find a shipped order's tracking numbers in
 * Shippo, pull live carrier tracking status, and build carrier tracking links.
 * Read-only against Shippo (never buys or voids labels).
 */

function oi_shippo_get(string $url_or_path): array {
    static $token = null;
    if ($token === null) {
        $env = parse_ini_file(__DIR__ . '/.env');
        $token = $env['SHIPPO_API_TOKEN'] ?? '';
    }
    if ($token === '') return [0, null];

    $url = str_starts_with($url_or_path, 'http') ? $url_or_path : 'https://api.goshippo.com' . $url_or_path;
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Authorization: ShippoToken ' . $token],
        CURLOPT_TIMEOUT        => 15,
    ]);
    $resp = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$code, $resp ? json_decode($resp, true) : null];
}

/** Normalize a carrier name ("USPS", "DHL Express", "FedEx") to Shippo's /tracks/ carrier token. */
function oi_carrier_token(string $carrier): string {
    $c = strtolower(trim($carrier));
    if (str_contains($c, 'usps')) return 'usps';
    if (str_contains($c, 'ups'))  return 'ups';
    if (str_contains($c, 'dhl'))  return 'dhl_express';
    if (str_contains($c, 'fedex')) return 'fedex';
    return $c !== '' ? preg_replace('/[^a-z0-9_]/', '_', $c) : 'usps';
}

function oi_guess_carrier(string $tn): string {
    $tn = strtoupper(preg_replace('/\s+/', '', $tn));
    if (str_starts_with($tn, '1Z')) return 'ups';
    if (preg_match('/^\d{20,22}$/', $tn) || preg_match('/^[A-Z]{2}\d{9}US$/', $tn)) return 'usps';
    if (preg_match('/^\d{12}$|^\d{15}$/', $tn)) return 'fedex';
    if (preg_match('/^\d{10}$/', $tn)) return 'dhl_express';
    return 'usps';
}

function oi_track_url(string $carrier, string $tn): string {
    $e = urlencode($tn);
    switch (oi_carrier_token($carrier)) {
        case 'ups':         return "https://www.ups.com/track?tracknum=$e";
        case 'fedex':       return "https://www.fedex.com/fedextrack/?trknbr=$e";
        case 'dhl_express': return "https://www.dhl.com/us-en/home/tracking/tracking-express.html?submit=1&tracking-id=$e";
        default:            return "https://tools.usps.com/go/TrackConfirmAction_input?origTrackNum=$e";
    }
}

/**
 * Walk Shippo's order list (newest first) for a WooCommerce display number and
 * return each successful label on it: [{tracking_number, carrier, tracking_url}].
 * Stops paging once past the order's date, so recent orders are fast.
 */
function oi_find_shippo_tracking(string $display_number, ?string $order_date = null): array {
    $stop_before = $order_date ? strtotime($order_date) - 3 * 86400 : 0;
    $url = '/orders?results=100';
    $found = null;

    for ($page = 0; $url && $page < 12; $page++) {
        [$code, $data] = oi_shippo_get($url);
        if ($code !== 200 || !is_array($data)) return [];
        $oldest = PHP_INT_MAX;
        foreach (($data['results'] ?? []) as $o) {
            $placed = strtotime($o['placed_at'] ?? '') ?: PHP_INT_MAX;
            $oldest = min($oldest, $placed);
            if ((string) ($o['order_number'] ?? '') === $display_number) { $found = $o; break 2; }
        }
        if ($stop_before && $oldest < $stop_before) break;
        $url = $data['next'] ?? null;
    }
    if (!$found) return [];

    $out = [];
    foreach (($found['transactions'] ?? []) as $t) {
        if (empty($t['object_id'])) continue;
        [$code, $tx] = oi_shippo_get('/transactions/' . $t['object_id']);
        if ($code !== 200 || ($tx['status'] ?? '') !== 'SUCCESS' || empty($tx['tracking_number'])) continue;

        $carrier = '';
        if (!empty($tx['rate']) && is_string($tx['rate'])) {
            [$rc, $rate] = oi_shippo_get('/rates/' . $tx['rate']);
            if ($rc === 200) $carrier = oi_carrier_token($rate['provider'] ?? '');
        }
        if ($carrier === '') $carrier = oi_guess_carrier($tx['tracking_number']);

        $out[] = [
            'tracking_number' => $tx['tracking_number'],
            'carrier'         => $carrier,
            'tracking_url'    => $tx['tracking_url_provider'] ?: oi_track_url($carrier, $tx['tracking_number']),
        ];
    }
    return $out;
}

function oi_shippo_date(?string $iso): ?string {
    if (!$iso) return null;
    $ts = strtotime($iso);
    return $ts ? date('Y-m-d H:i:s', $ts) : null;
}

/** Pull live status for one order_issue_tracking row and save it. Returns the updated row. */
function oi_refresh_tracking_row(PDO $db, int $tracking_id): ?array {
    $stmt = $db->prepare("SELECT * FROM order_issue_tracking WHERE id = ?");
    $stmt->execute([$tracking_id]);
    $row = $stmt->fetch();
    if (!$row) return null;

    [$code, $data] = oi_shippo_get('/tracks/' . rawurlencode(oi_carrier_token($row['carrier'])) . '/' . rawurlencode($row['tracking_number']));
    $now = date('Y-m-d H:i:s');

    if ($code !== 200 || !is_array($data) || empty($data['tracking_status'])) {
        $err = $code === 0 ? 'Shippo token not set' : 'Shippo returned no tracking info (HTTP ' . $code . ')';
        $db->prepare("UPDATE order_issue_tracking SET last_checked_at = ?, last_error = ? WHERE id = ?")
           ->execute([$now, $err, $tracking_id]);
    } else {
        $ts = $data['tracking_status'];
        $status = strtoupper($ts['status'] ?? 'UNKNOWN');
        $status_date = oi_shippo_date($ts['status_date'] ?? null);
        $history = [];
        foreach (($data['tracking_history'] ?? []) as $h) {
            $loc = $h['location'] ?? [];
            $history[] = [
                'date'    => oi_shippo_date($h['status_date'] ?? null),
                'status'  => $h['status'] ?? '',
                'details' => $h['status_details'] ?? '',
                'place'   => trim(($loc['city'] ?? '') . ', ' . ($loc['state'] ?? ''), ' ,'),
            ];
        }
        $db->prepare("
            UPDATE order_issue_tracking
            SET tracking_status = ?, status_detail = ?, status_date = ?, eta = ?,
                delivered_at = ?, history = ?, last_checked_at = ?, last_error = NULL
            WHERE id = ?
        ")->execute([
            $status,
            mb_substr($ts['status_details'] ?? '', 0, 255),
            $status_date,
            oi_shippo_date($data['eta'] ?? null),
            $status === 'DELIVERED' ? $status_date : null,
            json_encode($history),
            $now,
            $tracking_id,
        ]);
    }

    $stmt->execute([$tracking_id]);
    return $stmt->fetch();
}

/**
 * A replacement label means we boxed up and shipped the whole order again, so every
 * line item's BOM parts plus the one-per-order sticker come out of stock a second time.
 * Claimed atomically via order_issue_tracking.inventory_deducted so it can only ever
 * happen once per label. Pushes the new stock counts to the store afterward.
 * Returns a list of "Kit xN" strings that were deducted (empty if already done).
 */
function oi_deduct_replacement(PDO $db, int $tracking_id): array {
    $stmt = $db->prepare("
        SELECT i.order_id FROM order_issue_tracking t JOIN order_issues i ON i.id = t.issue_id WHERE t.id = ?
    ");
    $stmt->execute([$tracking_id]);
    $order_id = (int) $stmt->fetchColumn();
    if (!$order_id) return [];

    $summary = []; $projects = [];
    $db->beginTransaction();
    try {
        $claim = $db->prepare("UPDATE order_issue_tracking SET inventory_deducted = 1 WHERE id = ? AND inventory_deducted = 0");
        $claim->execute([$tracking_id]);
        if ($claim->rowCount() === 0) { $db->rollBack(); return []; }

        $items = $db->prepare("
            SELECT oi.project_id, oi.quantity, oi.variation_combo_key, p.project_name
            FROM order_items oi JOIN projects p ON p.id = oi.project_id WHERE oi.order_id = ?
        ");
        $items->execute([$order_id]);
        foreach ($items->fetchAll() as $it) {
            $combo = ($it['variation_combo_key'] ?? '') !== '' ? $it['variation_combo_key'] : null;
            wc_deduct_bom_inventory($db, (int) $it['project_id'], (int) $it['quantity'], $combo);
            $projects[(int) $it['project_id']] = true;
            $summary[] = $it['project_name'] . ' x' . $it['quantity'];
        }
        if (wc_deduct_order_sticker($db)) $summary[] = 'sticker';
        $db->commit();
    } catch (Throwable $e) {
        if ($db->inTransaction()) $db->rollBack();
        throw $e;
    }

    foreach (array_keys($projects) as $pid) {
        try { wc_sync_project($db, $pid); } catch (Throwable $e) { /* store push is best effort */ }
    }
    return $summary;
}

/** Undo oi_deduct_replacement (a replacement label that was removed or added by mistake). */
function oi_restore_replacement(PDO $db, int $tracking_id): void {
    $stmt = $db->prepare("
        SELECT i.order_id, t.inventory_deducted FROM order_issue_tracking t JOIN order_issues i ON i.id = t.issue_id WHERE t.id = ?
    ");
    $stmt->execute([$tracking_id]);
    $row = $stmt->fetch();
    if (!$row || !$row['inventory_deducted']) return;

    $projects = [];
    $db->beginTransaction();
    try {
        $claim = $db->prepare("UPDATE order_issue_tracking SET inventory_deducted = 0 WHERE id = ? AND inventory_deducted = 1");
        $claim->execute([$tracking_id]);
        if ($claim->rowCount() === 0) { $db->rollBack(); return; }
        $items = $db->prepare("SELECT project_id, quantity, variation_combo_key FROM order_items WHERE order_id = ? AND project_id IS NOT NULL");
        $items->execute([$row['order_id']]);
        foreach ($items->fetchAll() as $it) {
            $combo = ($it['variation_combo_key'] ?? '') !== '' ? $it['variation_combo_key'] : null;
            wc_restore_bom_inventory($db, (int) $it['project_id'], (int) $it['quantity'], $combo);
            $projects[(int) $it['project_id']] = true;
        }
        wc_restore_order_sticker($db);
        $db->commit();
    } catch (Throwable $e) {
        if ($db->inTransaction()) $db->rollBack();
        throw $e;
    }
    foreach (array_keys($projects) as $pid) {
        try { wc_sync_project($db, $pid); } catch (Throwable $e) { }
    }
}
