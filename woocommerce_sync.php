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

/** Fetch current regular_price for a WooCommerce product (parent/simple product). */
function wc_fetch_product_price(int $wc_product_id): ?float {
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
    return isset($data['regular_price']) && $data['regular_price'] !== '' ? (float) $data['regular_price'] : null;
}

/**
 * Fetch current regular_price for a specific WooCommerce product variation.
 * Variable products leave the parent's own regular_price blank — price lives
 * on each variation — so this is what wc_pull_price uses for variable projects.
 */
function wc_fetch_variation_price(int $wc_product_id, int $wc_variation_id): ?float {
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
    return isset($data['regular_price']) && $data['regular_price'] !== '' ? (float) $data['regular_price'] : null;
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
 * Fetch stock_quantity and regular_price for many products/variations via
 * curl_multi, in small concurrent batches. Requests one-at-a-time to the
 * WooCommerce REST API is what made "Check WC Status" take 30-40+ seconds with
 * a couple dozen variations mapped. Firing ALL of them fully in parallel trips
 * WooCommerce/host rate-limiting though (observed requests silently failing
 * above ~20 at once), so we cap concurrency and go in small waves — still far
 * faster than one-at-a-time, without the drops.
 *
 * $requests: [ key => ['product_id' => int, 'variation_id' => int|null], ... ]
 * Returns:   [ key => ['stock_quantity' => int|null, 'price' => float|null] ]
 */
function wc_fetch_stock_batch(array $requests): array {
    $empty   = ['stock_quantity' => null, 'price' => null];
    $results = array_fill_keys(array_keys($requests), $empty);
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
                $results[$key] = [
                    'stock_quantity' => isset($data['stock_quantity']) ? (int) $data['stock_quantity'] : null,
                    'price'          => isset($data['regular_price']) && $data['regular_price'] !== ''
                        ? (float) $data['regular_price'] : null,
                ];
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

/**
 * Push stock for every variation of ONE product in a single WooCommerce REST API
 * call (POST .../variations/batch) instead of one PUT per variation.
 *
 * A large variable product pushed one-at-a-time — each with its own 15s timeout —
 * can take minutes and tie up a PHP process long enough to exhaust the host's
 * process pool, which is what made the whole app look like it "crashed" after
 * marking a pending order received for a part used in a large variable product
 * (2026-09-15). Concurrent curl_multi requests were tried first but didn't help —
 * this WooCommerce/DreamHost install appears to serialize writes to the same
 * product regardless of how many requests arrive at once (a 5-wide curl_multi
 * version of this still took ~3.5 minutes for 22 variations, about the same as
 * fully serial) — so the fix is fewer round trips, not more parallel ones.
 *
 * $updates: [ key => ['variation_id' => int, 'qty' => int], ... ]
 * Returns:  [ key => wc_do_put()-style result array ]
 */
function wc_push_variations_batch(int $wc_product_id, array $updates): array {
    $results = [];
    if (empty($updates)) return $results;

    $cfg = wc_get_config();
    if (!$cfg) {
        foreach ($updates as $key => $u) $results[$key] = ['error' => 'WooCommerce credentials not configured in .env'];
        return $results;
    }

    // WooCommerce caps batch endpoints at 100 items per request.
    foreach (array_chunk($updates, 100, true) as $chunk) {
        $keys_by_variation_id = [];
        $update_payload = [];
        foreach ($chunk as $key => $u) {
            $qty = (int) $u['qty'];
            $update_payload[] = [
                'id'             => (int) $u['variation_id'],
                'manage_stock'   => true,
                'stock_quantity' => max(0, $qty),
                'stock_status'   => $qty > 0 ? 'instock' : 'outofstock',
            ];
            $keys_by_variation_id[(int) $u['variation_id']] = $key;
        }

        $url = rtrim($cfg['site_url'], '/') . '/wp-json/wc/v3/products/' . $wc_product_id . '/variations/batch';
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_POSTFIELDS     => json_encode(['update' => $update_payload]),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_USERPWD        => $cfg['username'] . ':' . $cfg['app_password'],
            // One request now covers up to 100 variations processed server-side —
            // needs more headroom than a single-item push's 15s.
            CURLOPT_TIMEOUT        => 90,
        ]);
        $response  = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_err  = curl_error($ch);
        curl_close($ch);

        $data = json_decode($response, true);

        if ($http_code === 0) {
            $err = ['error' => 'No response from WooCommerce' . ($curl_err ? ": $curl_err" : ' (timed out)'), 'http_code' => 0];
            foreach ($chunk as $key => $u) $results[$key] = $err;
            continue;
        }

        if (!isset($data['update']) || !is_array($data['update'])) {
            $err = ['error' => $data['message'] ?? "HTTP $http_code", 'http_code' => $http_code, 'raw_body' => $response];
            foreach ($chunk as $key => $u) $results[$key] = $err;
            continue;
        }

        foreach ($data['update'] as $item) {
            $key = $keys_by_variation_id[$item['id'] ?? null] ?? null;
            if ($key === null) continue;
            $results[$key] = isset($item['error'])
                ? ['error' => $item['error']['message'] ?? 'Unknown error', 'wc_code' => $item['error']['code'] ?? null]
                : ['success' => true, 'product_id' => $item['id'], 'new_stock' => $item['stock_quantity'] ?? null, 'stock_status' => $item['stock_status'] ?? null];
        }
        foreach ($chunk as $key => $u) {
            if (!isset($results[$key])) $results[$key] = ['error' => 'No result for this variation in batch response'];
        }
    }

    return $results;
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

        // Compute quantities locally first (fast — all DB), then push all variations
        // to WooCommerce in one REST batch call via wc_push_variations_batch instead
        // of one PUT per variation — see that function's doc comment for why (a large
        // variable product pushed one-at-a-time can take minutes and stall the PHP
        // process, and concurrent requests didn't help because this WooCommerce
        // install serializes writes to the same product either way).
        $push_requests = [];
        foreach ($mappings as $m) {
            $push_requests[$m['combo_key']] = [
                'variation_id' => (int) $m['wc_variation_id'],
                'qty'          => wc_calculate_variation_qty($db, $project_id, $m['combo_key']),
            ];
        }
        $push_results = wc_push_variations_batch($wc_product_id, $push_requests);

        $variation_results = [];
        foreach ($mappings as $m) {
            $result = $push_results[$m['combo_key']] ?? ['error' => 'No result from batch push'];
            $result['combo_key']      = $m['combo_key'];
            $result['calculated_qty'] = $push_requests[$m['combo_key']]['qty'];
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

/**
 * Resolve a WooCommerce order's internal ID from its customer-facing display
 * order number (the "number" field from the REST API / order emails), which
 * differs from the internal ID because of this store's custom order
 * numbering offset. Used by the Shippo webhook, which only ever sees the
 * display number.
 */
function wc_resolve_order_id_from_display_number(string $display_number): ?int {
    $cfg = wc_get_config();
    if (!$cfg) return null;

    $url = rtrim($cfg['site_url'], '/') . '/wp-json/wc/v3/orders?search=' . urlencode($display_number);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_USERPWD        => $cfg['username'] . ':' . $cfg['app_password'],
        CURLOPT_TIMEOUT        => 15,
    ]);
    $response  = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code < 200 || $http_code >= 300) return null;

    $results = json_decode($response, true);
    if (!is_array($results)) return null;

    foreach ($results as $order) {
        if (isset($order['number']) && (string) $order['number'] === $display_number && isset($order['id'])) {
            return (int) $order['id'];
        }
    }

    return null;
}

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

// ── Order + line-item capture ────────────────────────────────────────────────

/** Fetch one page of WooCommerce orders via REST API. Empty array = no more pages. */
function wc_fetch_orders_page(int $page, int $perPage = 100): array {
    $cfg = wc_get_config();
    if (!$cfg) return [];

    $url = rtrim($cfg['site_url'], '/') . '/wp-json/wc/v3/orders?per_page=' . $perPage . '&page=' . $page . '&orderby=id&order=asc';
    $ch  = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERPWD        => $cfg['username'] . ':' . $cfg['app_password'],
        CURLOPT_TIMEOUT        => 30,
    ]);
    $response  = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code !== 200) return [];
    $data = json_decode($response, true);
    return is_array($data) ? $data : [];
}

/** Map a WooCommerce order status to the tracker's status enum. */
function wc_map_order_status(string $wcStatus): string {
    switch ($wcStatus) {
        case 'processing':
        case 'on-hold':
            return 'paid';
        case 'completed':
            return 'completed';
        case 'cancelled':
        case 'refunded':
        case 'failed':
        case 'trash':
            return 'cancelled';
        default:
            return 'pending';
    }
}

/**
 * Upsert one parent `orders` row + its `order_items` rows from a raw WooCommerce
 * order payload (webhook body or a single element of the REST API /orders list —
 * same shape either way).
 *
 * $deductInventory = true  -> live webhook path. Only acts when status is
 *                              processing/on-hold (deduct) or cancelled/refunded
 *                              (restore) — anything else is a no-op, matching the
 *                              old inline logic. Mutates parts.current_stock.
 * $deductInventory = false -> reconciliation/backfill path. Upserts order + item
 *                              data for ANY status so history is accurate, but
 *                              NEVER touches parts.current_stock. inventory_deducted
 *                              is set to reflect WC's own state (so a later live
 *                              webhook for the same order stays idempotent)
 *                              without actually deducting anything now.
 *
 * Combo keys are always raw strings — see file header note.
 */
function wc_upsert_order($db, array $wcOrder, bool $deductInventory): array {
    $wc_order_id = (int) ($wcOrder['id'] ?? 0);
    if (!$wc_order_id) return ['error' => 'Missing WooCommerce order id'];

    $wc_status      = $wcOrder['status'] ?? '';
    $should_deduct  = in_array($wc_status, ['processing', 'on-hold']);
    $should_restore = in_array($wc_status, ['cancelled', 'refunded']);

    $order_number   = 'WC-' . $wc_order_id;
    $tracker_status = wc_map_order_status($wc_status);
    $customer       = trim(($wcOrder['billing']['first_name'] ?? '') . ' ' . ($wcOrder['billing']['last_name'] ?? ''));
    $order_date     = str_replace('T', ' ', substr($wcOrder['date_created'] ?? date('c'), 0, 19));

    $db->beginTransaction();
    try {
        // Lock (or gap-lock, if it doesn't exist yet) the parent row for this WC order —
        // serializes near-simultaneous order.created + order.updated webhook deliveries.
        $stmt = $db->prepare("SELECT id, status FROM orders WHERE wc_order_id = ? FOR UPDATE");
        $stmt->execute([$wc_order_id]);
        $existingOrder = $stmt->fetch();

        // Nothing to do: order isn't tracked yet and this status requires no
        // inventory action (e.g. a still-pending or failed order) — skip
        // entirely rather than creating a placeholder row. An order that IS
        // already tracked always gets its status/fields updated below, even
        // for statuses like 'completed' that need no inventory action —
        // otherwise a shipped order stuck waiting on its next status change
        // (e.g. WC auto-completing it) would never be reflected here.
        if ($deductInventory && !$existingOrder && !$should_deduct && !$should_restore) {
            $db->rollBack();
            return ['skipped' => true, 'wc_order_id' => $wc_order_id, 'reason' => "Status '$wc_status' requires no inventory action"];
        }

        $orderFields = [
            'order_number'    => $order_number,
            'wc_order_id'     => $wc_order_id,
            'customer_name'   => $customer ?: 'WooCommerce Customer',
            'customer_email'  => $wcOrder['billing']['email'] ?? '',
            'customer_phone'  => $wcOrder['billing']['phone'] ?? '',
            'order_date'      => $order_date,
            'shipping_charge' => $wcOrder['shipping_total'] ?? 0,
            'tax_total'       => $wcOrder['total_tax'] ?? 0,
            'order_total'     => $wcOrder['total'] ?? null,
            'source'          => 'woocommerce',
        ];

        // Don't regress a status already advanced by Shippo (shipped) back down to
        // paid/pending — but do allow it to move forward to completed, or to
        // cancelled — reconciliation always reflects WC's own status otherwise.
        $set_status = true;
        if ($deductInventory && $existingOrder && $existingOrder['status'] === 'shipped') {
            $set_status = in_array($tracker_status, ['completed', 'cancelled']);
        }

        if (!$existingOrder) {
            $orderFields['notes']  = "WooCommerce order #{$wc_order_id}";
            $orderFields['status'] = $tracker_status;
            $cols = array_keys($orderFields);
            $db->prepare("INSERT INTO orders (" . implode(',', $cols) . ") VALUES (" . implode(',', array_fill(0, count($cols), '?')) . ")")
               ->execute(array_values($orderFields));
            $order_id = (int) $db->lastInsertId();
        } else {
            $order_id = (int) $existingOrder['id'];
            $setSql = [];
            $vals   = [];
            foreach ($orderFields as $col => $val) {
                $setSql[] = "$col = ?";
                $vals[]   = $val;
            }
            if ($set_status) { $setSql[] = 'status = ?'; $vals[] = $tracker_status; }
            $vals[] = $order_id;
            $db->prepare("UPDATE orders SET " . implode(', ', $setSql) . " WHERE id = ?")->execute($vals);
        }

        $item_log = [];
        $affected_projects = [];

        foreach (($wcOrder['line_items'] ?? []) as $item) {
            $wc_product_id   = (int) ($item['product_id'] ?? 0);
            $wc_variation_id = (int) ($item['variation_id'] ?? 0);
            $wc_line_item_id = (int) ($item['id'] ?? 0);
            if (!$wc_product_id || !$wc_line_item_id) continue;

            $stmt = $db->prepare("SELECT id, project_name FROM projects WHERE woocommerce_product_id = ?");
            $stmt->execute([$wc_product_id]);
            $project = $stmt->fetch();
            if (!$project) {
                $item_log[] = ['skipped' => true, 'reason' => "No project mapped to WC product $wc_product_id"];
                continue;
            }

            $combo_key = null;
            if ($wc_variation_id) {
                $stmt = $db->prepare("SELECT combo_key FROM project_variation_mappings WHERE project_id = ? AND wc_variation_id = ?");
                $stmt->execute([$project['id'], $wc_variation_id]);
                $mapping = $stmt->fetch();
                if ($mapping) $combo_key = $mapping['combo_key'];
            }

            $order_qty  = max(1, (int) ($item['quantity'] ?? 1));
            $line_total = (float) ($item['total'] ?? 0);
            $unit_price = $order_qty > 0 ? round($line_total / $order_qty, 2) : $line_total;

            $stmt = $db->prepare("SELECT id, inventory_deducted, variation_combo_key FROM order_items WHERE order_id = ? AND wc_line_item_id = ? FOR UPDATE");
            $stmt->execute([$order_id, $wc_line_item_id]);
            $existingItem = $stmt->fetch();
            $already_deducted = $existingItem && $existingItem['inventory_deducted'];

            if ($deductInventory && $should_deduct && !$already_deducted) {
                $deductions = wc_deduct_bom_inventory($db, $project['id'], $order_qty, $combo_key);
                $item_log[] = ['project' => $project['project_name'], 'combo_key' => $combo_key, 'deductions' => $deductions];
                $affected_projects[] = $project['id'];
                $inventory_deducted = 1;
            } elseif ($deductInventory && $should_restore && $already_deducted) {
                $restore_combo_key = $existingItem['variation_combo_key'];
                $restorations = wc_restore_bom_inventory($db, $project['id'], $order_qty, $restore_combo_key);
                $item_log[] = ['project' => $project['project_name'], 'combo_key' => $restore_combo_key, 'restorations' => $restorations];
                $affected_projects[] = $project['id'];
                $inventory_deducted = 0;
            } elseif (!$deductInventory) {
                // Reconciliation: reflect WC's own state without mutating stock.
                $inventory_deducted = in_array($wc_status, ['processing', 'on-hold', 'completed']) ? 1 : 0;
            } else {
                $item_log[] = ['skipped' => true, 'reason' => 'Already up to date for this order'];
                $inventory_deducted = $existingItem ? (int) $existingItem['inventory_deducted'] : 0;
            }

            if (!$existingItem) {
                $db->prepare("
                    INSERT INTO order_items (order_id, project_id, variation_combo_key, wc_line_item_id, quantity, unit_price, line_total, inventory_deducted)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ")->execute([$order_id, $project['id'], $combo_key, $wc_line_item_id, $order_qty, $unit_price, $line_total, $inventory_deducted]);
            } else {
                $db->prepare("
                    UPDATE order_items SET project_id = ?, variation_combo_key = ?, quantity = ?, unit_price = ?, line_total = ?, inventory_deducted = ?
                    WHERE id = ?
                ")->execute([$project['id'], $combo_key, $order_qty, $unit_price, $line_total, $inventory_deducted, $existingItem['id']]);
            }
        }

        $db->commit();

        return [
            'success'           => true,
            'wc_order_id'       => $wc_order_id,
            'order_id'          => $order_id,
            'item_log'          => $item_log,
            'affected_projects' => array_values(array_unique($affected_projects)),
        ];
    } catch (Exception $e) {
        $db->rollBack();
        return ['error' => $e->getMessage(), 'wc_order_id' => $wc_order_id];
    }
}
