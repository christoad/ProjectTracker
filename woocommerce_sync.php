<?php
/**
 * WooCommerce Stock Sync — shared helper functions
 *
 * CANONICAL SOURCE: ProjectTracker/woocommerce_sync.php
 * Deployed to:      ki6cr.com/projects/woocommerce_sync.php
 *
 * ── Combo key convention ──────────────────────────────────────────────────────
 * All functions that accept variation data take a RAW COMBO KEY STRING,
 * e.g. "Color:Blue" or "Color:Blue|Size:M". Functions parse internally.
 * Callers NEVER pre-parse a combo_key into an array before passing it here.
 * Violation of this rule causes variable parts to be silently skipped.
 * ─────────────────────────────────────────────────────────────────────────────
 */

// ── Config ────────────────────────────────────────────────────────────────────

function wc_get_config() {
    static $cfg = null;
    if ($cfg !== null) return $cfg;

    $env = parse_ini_file(__DIR__ . '/.env');
    if (empty($env['WC_SITE_URL'])) return null;

    $cfg = [
        'site_url'       => $env['WC_SITE_URL'],
        'username'       => $env['WC_USERNAME'],
        'app_password'   => $env['WC_APP_PASSWORD'],
        'webhook_secret' => $env['WC_WEBHOOK_SECRET'] ?? '',
    ];
    return $cfg;
}

// ── Combo key helpers ─────────────────────────────────────────────────────────

/**
 * Build a stable, sorted pipe-delimited combo key string from an array.
 * e.g. ['Color' => 'Blue', 'Size' => 'M'] → "Color:Blue|Size:M"
 */
function wc_build_combo_key(array $combo): string {
    ksort($combo);
    $parts = [];
    foreach ($combo as $attr => $val) {
        $parts[] = $attr . ':' . $val;
    }
    return implode('|', $parts);
}

/**
 * Parse a combo key string into an associative array.
 * "Color:Blue|Size:M" → ['Color' => 'Blue', 'Size' => 'M']
 */
function wc_parse_combo_key(string $combo_key): array {
    $result = [];
    foreach (explode('|', $combo_key) as $pair) {
        if (strpos($pair, ':') !== false) {
            [$attr, $val] = explode(':', $pair, 2);
            $result[trim($attr)] = trim($val);
        }
    }
    return $result;
}

// ── Stock calculation ─────────────────────────────────────────────────────────

/**
 * How many complete kits can be built from a simple (non-variable) project?
 * Only fixed BOM parts (variation_attribute = '') constrain the count.
 */
function wc_calculate_available_qty($db, $project_id): int {
    $stmt = $db->prepare("
        SELECT p.current_stock, pp.quantity_required
        FROM project_parts pp
        JOIN parts p ON p.id = pp.part_id
        WHERE pp.project_id = ? AND pp.variation_attribute = ''
    ");
    $stmt->execute([$project_id]);
    $bom = $stmt->fetchAll();

    if (empty($bom)) return 0;

    $min = PHP_INT_MAX;
    foreach ($bom as $row) {
        if ($row['quantity_required'] <= 0) continue;
        $min = min($min, (int) floor($row['current_stock'] / $row['quantity_required']));
    }
    return $min === PHP_INT_MAX ? 0 : $min;
}

/**
 * How many complete kits of a specific variation can be built?
 * Constrains on: fixed parts (shared) + only the variable parts for this combo.
 *
 * @param string $combo_key  Raw combo key string, e.g. "Color:Blue"
 */
function wc_calculate_variation_qty($db, $project_id, string $combo_key): int {
    $min = PHP_INT_MAX;

    // Fixed parts (shared across all variations)
    $stmt = $db->prepare("
        SELECT p.current_stock, pp.quantity_required
        FROM project_parts pp
        JOIN parts p ON p.id = pp.part_id
        WHERE pp.project_id = ? AND pp.variation_attribute = ''
    ");
    $stmt->execute([$project_id]);
    foreach ($stmt->fetchAll() as $row) {
        if ($row['quantity_required'] <= 0) continue;
        $min = min($min, (int) floor($row['current_stock'] / $row['quantity_required']));
    }

    // Variable parts specific to this combo
    foreach (wc_parse_combo_key($combo_key) as $attr => $val) {
        $stmt = $db->prepare("
            SELECT p.current_stock, pp.quantity_required
            FROM project_parts pp
            JOIN parts p ON p.id = pp.part_id
            WHERE pp.project_id = ? AND pp.variation_attribute = ? AND pp.variation_value = ?
        ");
        $stmt->execute([$project_id, $attr, $val]);
        foreach ($stmt->fetchAll() as $row) {
            if ($row['quantity_required'] <= 0) continue;
            $min = min($min, (int) floor($row['current_stock'] / $row['quantity_required']));
        }
    }

    return $min === PHP_INT_MAX ? 0 : $min;
}

// ── WooCommerce fetch (live stock) ────────────────────────────────────────────

/** Fetch current stock_quantity for a simple WooCommerce product. */
function wc_fetch_product_stock(int $wc_product_id): ?int {
    $cfg = wc_get_config();
    if (!$cfg) return null;

    $url = rtrim($cfg['site_url'], '/') . '/wp-json/wc/v3/products/' . $wc_product_id;
    $ch  = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERPWD        => $cfg['username'] . ':' . $cfg['app_password'],
        CURLOPT_TIMEOUT        => 10,
    ]);
    $response  = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code !== 200) return null;
    $data = json_decode($response, true);
    return isset($data['stock_quantity']) ? (int) $data['stock_quantity'] : null;
}

/** Fetch current stock_quantity for a specific WooCommerce product variation. */
function wc_fetch_variation_stock_live(int $wc_product_id, int $wc_variation_id): ?int {
    $cfg = wc_get_config();
    if (!$cfg) return null;

    $url = rtrim($cfg['site_url'], '/') . '/wp-json/wc/v3/products/' . $wc_product_id . '/variations/' . $wc_variation_id;
    $ch  = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERPWD        => $cfg['username'] . ':' . $cfg['app_password'],
        CURLOPT_TIMEOUT        => 10,
    ]);
    $response  = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code !== 200) return null;
    $data = json_decode($response, true);
    return isset($data['stock_quantity']) ? (int) $data['stock_quantity'] : null;
}

/**
 * Fetch stock_quantity for many products/variations via curl_multi, in small
 * concurrent batches. Requests one-at-a-time to the WooCommerce REST API is what
 * made "Check WC Status" take 30-40+ seconds with a couple dozen variations mapped.
 * Firing ALL of them fully in parallel trips WooCommerce/host rate-limiting though
 * (observed requests silently failing above ~20 at once), so we cap concurrency
 * and go in small waves — still far faster than one-at-a-time, without the drops.
 *
 * $requests: [ key => ['product_id' => int, 'variation_id' => int|null], ... ]
 * Returns:   [ key => int|null ]
 */
function wc_fetch_stock_batch(array $requests): array {
    $results = array_fill_keys(array_keys($requests), null);
    if (empty($requests)) return $results;

    $cfg = wc_get_config();
    if (!$cfg) return $results;

    $CONCURRENCY = 5;
    foreach (array_chunk($requests, $CONCURRENCY, true) as $chunk) {
        $mh = curl_multi_init();
        $handles = [];
        foreach ($chunk as $key => $r) {
            $url = rtrim($cfg['site_url'], '/') . '/wp-json/wc/v3/products/' . (int) $r['product_id'];
            if (!empty($r['variation_id'])) {
                $url .= '/variations/' . (int) $r['variation_id'];
            }
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_USERPWD        => $cfg['username'] . ':' . $cfg['app_password'],
                CURLOPT_TIMEOUT        => 15,
            ]);
            curl_multi_add_handle($mh, $ch);
            $handles[$key] = $ch;
        }

        $running = null;
        do {
            $status = curl_multi_exec($mh, $running);
            if ($running) curl_multi_select($mh);
        } while ($running > 0 && $status === CURLM_OK);

        foreach ($handles as $key => $ch) {
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($http_code === 200) {
                $data = json_decode(curl_multi_getcontent($ch), true);
                $results[$key] = isset($data['stock_quantity']) ? (int) $data['stock_quantity'] : null;
            }
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
        }
        curl_multi_close($mh);
    }

    return $results;
}

// ── WooCommerce push ──────────────────────────────────────────────────────────

/** Push stock to a simple (non-variable) WooCommerce product. */
function wc_push_stock($wc_product_id, $qty): array {
    $cfg = wc_get_config();
    if (!$cfg) return ['error' => 'WooCommerce credentials not configured in .env'];

    $url     = rtrim($cfg['site_url'], '/') . '/wp-json/wc/v3/products/' . intval($wc_product_id);
    $payload = json_encode([
        'manage_stock'   => true,
        'stock_quantity' => max(0, (int) $qty),
        'stock_status'   => $qty > 0 ? 'instock' : 'outofstock',
    ]);
    return wc_do_put($url, $payload, $cfg);
}

/** Push stock to a specific WooCommerce product variation. */
function wc_push_variation_stock($wc_product_id, $wc_variation_id, $qty): array {
    $cfg = wc_get_config();
    if (!$cfg) return ['error' => 'WooCommerce credentials not configured in .env'];

    $url     = rtrim($cfg['site_url'], '/') . '/wp-json/wc/v3/products/' . intval($wc_product_id) . '/variations/' . intval($wc_variation_id);
    $payload = json_encode([
        'manage_stock'   => true,
        'stock_quantity' => max(0, (int) $qty),
        'stock_status'   => $qty > 0 ? 'instock' : 'outofstock',
    ]);
    $result = wc_do_put($url, $payload, $cfg);
    $result['variation_id'] = $wc_variation_id;
    return $result;
}

function wc_do_put(string $url, string $payload, array $cfg): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => 'PUT',
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_USERPWD        => $cfg['username'] . ':' . $cfg['app_password'],
        CURLOPT_TIMEOUT        => 15,
    ]);
    $response  = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_err  = curl_error($ch);
    curl_close($ch);

    $result = json_decode($response, true);
    if ($http_code >= 200 && $http_code < 300 && isset($result['id'])) {
        return ['success' => true, 'product_id' => $result['id'], 'new_stock' => $result['stock_quantity'] ?? null, 'stock_status' => $result['stock_status'] ?? null];
    }
    if ($http_code === 0) {
        return ['error' => 'No response from WooCommerce' . ($curl_err ? ": $curl_err" : ' (timed out)'), 'http_code' => 0];
    }
    return [
        'error'     => $result['message'] ?? "HTTP $http_code",
        'http_code' => $http_code,
        'wc_code'   => $result['code'] ?? null,
        'wc_data'   => $result['data'] ?? null,
        'raw_body'  => $response,
    ];
}

function wc_log(string $level, string $message, array $context = []): void {
    $entry = json_encode([
        'time'    => date('Y-m-d H:i:s'),
        'level'   => $level,
        'message' => $message,
        'context' => $context,
    ]) . "\n";
    file_put_contents(__DIR__ . '/wc_sync.log', $entry, FILE_APPEND | LOCK_EX);
}

// ── Sync ──────────────────────────────────────────────────────────────────────

/**
 * Recalculate available qty for a project and push to WooCommerce.
 * Variable products: pushes per-variation stock to each mapped WC variation.
 * Simple products:   pushes a single qty to the parent WC product.
 */
function wc_sync_project($db, int $project_id): array {
    $stmt = $db->prepare("SELECT woocommerce_product_id, project_name FROM projects WHERE id = ?");
    $stmt->execute([$project_id]);
    $project = $stmt->fetch();

    if (!$project || !$project['woocommerce_product_id']) {
        return ['skipped' => true, 'project_id' => $project_id, 'reason' => 'No WooCommerce product mapped'];
    }

    $wc_product_id = (int) $project['woocommerce_product_id'];

    // Check for variation mappings — if any exist, treat as variable product
    $stmt = $db->prepare("
        SELECT combo_key, wc_variation_id
        FROM project_variation_mappings
        WHERE project_id = ? AND wc_variation_id IS NOT NULL
    ");
    $stmt->execute([$project_id]);
    $mappings = $stmt->fetchAll();

    if (!empty($mappings)) {
        $cfg = wc_get_config();

        // Parent product must have manage_stock=false; otherwise WooCommerce parent-level
        // stock overrides all variation availability, making all variations show as out of stock.
        if ($cfg) {
            $parent_url = rtrim($cfg['site_url'], '/') . '/wp-json/wc/v3/products/' . $wc_product_id;
            wc_do_put($parent_url, json_encode(['manage_stock' => false]), $cfg);
        }

        $variation_results = [];
        foreach ($mappings as $m) {
            $qty    = wc_calculate_variation_qty($db, $project_id, $m['combo_key']);
            $result = wc_push_variation_stock($wc_product_id, (int) $m['wc_variation_id'], $qty);
            $result['combo_key']      = $m['combo_key'];
            $result['calculated_qty'] = $qty;
            $variation_results[]      = $result;
        }

        $any_error = array_filter($variation_results, fn($v) => !empty($v['error']));
        $log_level = $any_error ? 'error' : 'info';
        wc_log($log_level, $project['project_name'] . ' (variable) sync', [
            'project_id'    => $project_id,
            'wc_product_id' => $wc_product_id,
            'variations'    => array_map(fn($v) => [
                'combo'     => $v['combo_key'],
                'qty'       => $v['calculated_qty'],
                'success'   => $v['success'] ?? false,
                'error'     => $v['error'] ?? null,
                'http_code' => $v['http_code'] ?? null,
                'wc_code'   => $v['wc_code'] ?? null,
                'raw_body'  => $v['raw_body'] ?? null,
            ], $variation_results),
        ]);

        return [
            'project_name' => $project['project_name'],
            'variable'     => true,
            'type'         => 'variable',
            'variations'   => $variation_results,
        ];
    }

    // Simple product
    $qty    = wc_calculate_available_qty($db, $project_id);
    $result = wc_push_stock($wc_product_id, $qty);
    $result['project_name']   = $project['project_name'];
    $result['type']           = 'simple';
    $result['calculated_qty'] = $qty;

    wc_log(isset($result['error']) ? 'error' : 'info', $project['project_name'] . ' (simple) sync', [
        'project_id'    => $project_id,
        'wc_product_id' => $wc_product_id,
        'calculated_qty' => $qty,
        'success'       => $result['success'] ?? false,
        'error'         => $result['error'] ?? null,
        'http_code'     => $result['http_code'] ?? null,
        'wc_code'       => $result['wc_code'] ?? null,
        'raw_body'      => $result['raw_body'] ?? null,
    ]);

    return $result;
}

/** Sync every project that has a WooCommerce product mapped. */
function wc_sync_all_projects($db): array {
    $stmt = $db->query("
        SELECT id FROM projects
        WHERE woocommerce_product_id IS NOT NULL AND status NOT IN ('archived', 'trashed')
    ");
    $results = [];
    foreach ($stmt->fetchAll() as $row) {
        $results[] = wc_sync_project($db, (int) $row['id']);
    }
    return $results;
}

// ── Inventory deduction / restoration ────────────────────────────────────────

/**
 * Deduct BOM components from parts inventory for one order.
 *
 * Always deducts fixed parts (variation_attribute = '').
 * If $combo_key is provided, also deducts the variation-specific parts for
 * each attribute:value pair in the key.
 *
 * @param string|null $combo_key  Raw combo key string, e.g. "Color:Blue". NOT a pre-parsed array.
 */
function wc_deduct_bom_inventory($db, $project_id, $order_qty, ?string $combo_key = null): array {
    // Always deduct fixed (shared) parts
    $stmt = $db->prepare("
        SELECT pp.part_id, pp.quantity_required, p.part_name, p.current_stock
        FROM project_parts pp
        JOIN parts p ON p.id = pp.part_id
        WHERE pp.project_id = ? AND pp.variation_attribute = ''
    ");
    $stmt->execute([$project_id]);
    $parts = $stmt->fetchAll();

    // Deduct only the variable parts matching the specific variation purchased
    if ($combo_key !== null) {
        foreach (wc_parse_combo_key($combo_key) as $attr => $val) {
            $stmt = $db->prepare("
                SELECT pp.part_id, pp.quantity_required, p.part_name, p.current_stock
                FROM project_parts pp
                JOIN parts p ON p.id = pp.part_id
                WHERE pp.project_id = ? AND pp.variation_attribute = ? AND pp.variation_value = ?
            ");
            $stmt->execute([$project_id, $attr, $val]);
            foreach ($stmt->fetchAll() as $row) {
                $parts[] = $row;
            }
        }
    }

    $log = [];
    foreach ($parts as $row) {
        $deduct    = (int) $row['quantity_required'] * (int) $order_qty;
        $old_stock = (int) $row['current_stock'];
        $new_stock = max(0, $old_stock - $deduct);
        $upd = $db->prepare("UPDATE parts SET current_stock = ? WHERE id = ?");
        $upd->execute([$new_stock, $row['part_id']]);
        $log[] = [
            'part_id'       => $row['part_id'],
            'part_name'     => $row['part_name'],
            'deducted'      => $deduct,
            'old_stock'     => $old_stock,
            'new_stock'     => $new_stock,
            'rows_affected' => $upd->rowCount(),
        ];
    }
    return $log;
}

/**
 * Restore BOM components to parts inventory (order cancelled/refunded).
 * Mirror image of wc_deduct_bom_inventory — must pass the same combo_key
 * that was used at deduction time (stored on the order record).
 *
 * @param string|null $combo_key  Raw combo key string. NOT a pre-parsed array.
 */
function wc_restore_bom_inventory($db, $project_id, $order_qty, ?string $combo_key = null): array {
    $stmt = $db->prepare("
        SELECT pp.part_id, pp.quantity_required, p.part_name, p.current_stock
        FROM project_parts pp
        JOIN parts p ON p.id = pp.part_id
        WHERE pp.project_id = ? AND pp.variation_attribute = ''
    ");
    $stmt->execute([$project_id]);
    $parts = $stmt->fetchAll();

    if ($combo_key !== null) {
        foreach (wc_parse_combo_key($combo_key) as $attr => $val) {
            $stmt = $db->prepare("
                SELECT pp.part_id, pp.quantity_required, p.part_name, p.current_stock
                FROM project_parts pp
                JOIN parts p ON p.id = pp.part_id
                WHERE pp.project_id = ? AND pp.variation_attribute = ? AND pp.variation_value = ?
            ");
            $stmt->execute([$project_id, $attr, $val]);
            foreach ($stmt->fetchAll() as $row) {
                $parts[] = $row;
            }
        }
    }

    $log = [];
    foreach ($parts as $row) {
        $restore   = (int) $row['quantity_required'] * (int) $order_qty;
        $new_stock = (int) $row['current_stock'] + $restore;
        $db->prepare("UPDATE parts SET current_stock = ? WHERE id = ?")
           ->execute([$new_stock, $row['part_id']]);
        $log[] = [
            'part_id'   => $row['part_id'],
            'part_name' => $row['part_name'],
            'restored'  => $restore,
            'old_stock' => (int) $row['current_stock'],
            'new_stock' => $new_stock,
        ];
    }
    return $log;
}

// ── WooCommerce order management ──────────────────────────────────────────────

/** Set the status of a WooCommerce order via REST API. */
function wc_update_order_status($wc_order_id, string $status): array {
    $cfg = wc_get_config();
    if (!$cfg) return ['error' => 'WooCommerce credentials not configured in .env'];

    $url     = rtrim($cfg['site_url'], '/') . '/wp-json/wc/v3/orders/' . intval($wc_order_id);
    $payload = json_encode(['status' => $status]);
    $result  = wc_do_put($url, $payload, $cfg);
    if (isset($result['success'])) {
        $result['order_id'] = $wc_order_id;
        $result['status']   = $status;
    }
    return $result;
}

/**
 * Add a note to a WooCommerce order via REST API.
 * Set $customer_note = true to make it visible in the customer's My Account page.
 */
function wc_add_order_note($wc_order_id, string $note, bool $customer_note = false): array {
    $cfg = wc_get_config();
    if (!$cfg) return ['error' => 'WooCommerce credentials not configured in .env'];

    $url     = rtrim($cfg['site_url'], '/') . '/wp-json/wc/v3/orders/' . intval($wc_order_id) . '/notes';
    $payload = json_encode(['note' => $note, 'customer_note' => $customer_note]);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_USERPWD        => $cfg['username'] . ':' . $cfg['app_password'],
        CURLOPT_TIMEOUT        => 15,
    ]);
    $response  = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $result = json_decode($response, true);
    if ($http_code >= 200 && $http_code < 300 && isset($result['id'])) {
        return ['success' => true, 'note_id' => $result['id']];
    }
    return ['error' => $result['message'] ?? "HTTP $http_code"];
}
