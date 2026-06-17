<?php
/**
 * WooCommerce Stock Sync — shared helper functions
 * Included by woocommerce_webhook.php; not a standalone endpoint.
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
 * Build a stable, sorted pipe-delimited key from an attribute→value map.
 * e.g. ['Color' => 'Red', 'Connector' => 'Male'] → "Color:Red|Connector:Male"
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
 * Parse a combo key back to an associative array.
 * "Color:Red|Connector:Male" → ['Color' => 'Red', 'Connector' => 'Male']
 */
function wc_parse_combo_key(string $combo_key): array {
    if ($combo_key === '') return [];
    $result = [];
    foreach (explode('|', $combo_key) as $pair) {
        [$attr, $val] = explode(':', $pair, 2);
        $result[$attr] = $val;
    }
    return $result;
}

// ── Variable project helpers ──────────────────────────────────────────────────

/** Returns true if the project has any variable BOM parts (with a non-empty variation_attribute). */
function wc_is_variable_project($db, int $project_id): bool {
    $stmt = $db->prepare("SELECT COUNT(*) FROM project_parts WHERE project_id = ? AND variation_attribute != ''");
    $stmt->execute([$project_id]);
    return (int) $stmt->fetchColumn() > 0;
}

/**
 * Return all distinct attributes and their option values for a project.
 * e.g. ['Connector' => ['Male', 'Female'], 'Color' => ['Black', 'Red']]
 */
function wc_get_project_attributes($db, int $project_id): array {
    $stmt = $db->prepare("
        SELECT DISTINCT variation_attribute, variation_value
        FROM project_parts
        WHERE project_id = ? AND variation_attribute != ''
        ORDER BY variation_attribute, variation_value
    ");
    $stmt->execute([$project_id]);
    $attributes = [];
    foreach ($stmt->fetchAll() as $row) {
        $attributes[$row['variation_attribute']][] = $row['variation_value'];
    }
    return $attributes;
}

/**
 * Generate all combinations (Cartesian product) of attribute values.
 * Input: ['Connector' => ['Male', 'Female'], 'Color' => ['Black', 'Red']]
 * Output: [['Connector'=>'Male','Color'=>'Black'], ['Connector'=>'Male','Color'=>'Red'], ...]
 */
function wc_generate_combos(array $attributes): array {
    if (empty($attributes)) return [];
    $combos = [[]];
    foreach ($attributes as $attr => $values) {
        $new_combos = [];
        foreach ($combos as $combo) {
            foreach ($values as $val) {
                $c = $combo;
                $c[$attr] = $val;
                $new_combos[] = $c;
            }
        }
        $combos = $new_combos;
    }
    foreach ($combos as &$combo) ksort($combo);
    return $combos;
}

// ── Stock calculation ─────────────────────────────────────────────────────────

/**
 * For non-variable projects: min(floor(stock/qty)) across ALL BOM parts.
 * (All parts have variation_attribute='' for non-variable projects, so this is unchanged.)
 */
function wc_calculate_available_qty($db, int $project_id): int {
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
 * For variable projects: min across fixed parts + the variable parts matching one specific combo.
 * $combo is an associative array e.g. ['Connector' => 'Male', 'Color' => 'Black'].
 */
function wc_calculate_variation_qty($db, int $project_id, array $combo): int {
    // Fixed parts (shared across all variations)
    $stmt = $db->prepare("
        SELECT p.current_stock, pp.quantity_required
        FROM project_parts pp JOIN parts p ON p.id = pp.part_id
        WHERE pp.project_id = ? AND pp.variation_attribute = ''
    ");
    $stmt->execute([$project_id]);

    $min = PHP_INT_MAX;
    foreach ($stmt->fetchAll() as $row) {
        if ($row['quantity_required'] <= 0) continue;
        $min = min($min, (int) floor($row['current_stock'] / $row['quantity_required']));
    }

    // Variable parts specific to this combo
    foreach ($combo as $attr => $val) {
        $stmt = $db->prepare("
            SELECT p.current_stock, pp.quantity_required
            FROM project_parts pp JOIN parts p ON p.id = pp.part_id
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

// ── WooCommerce push ──────────────────────────────────────────────────────────

/** Push stock to a simple (non-variable) WooCommerce product. */
function wc_push_stock(int $wc_product_id, int $qty): array {
    $cfg = wc_get_config();
    if (!$cfg) return ['error' => 'WooCommerce credentials not configured in .env'];

    $url     = rtrim($cfg['site_url'], '/') . '/wp-json/wc/v3/products/' . $wc_product_id;
    $payload = json_encode([
        'manage_stock'   => true,
        'stock_quantity' => max(0, $qty),
        'stock_status'   => $qty > 0 ? 'instock' : 'outofstock',
    ]);

    return wc_do_put($url, $payload, $cfg);
}

/** Push stock to a specific WooCommerce product variation. */
function wc_push_variation_stock(int $wc_product_id, int $wc_variation_id, int $qty): array {
    $cfg = wc_get_config();
    if (!$cfg) return ['error' => 'WooCommerce credentials not configured in .env'];

    $url     = rtrim($cfg['site_url'], '/') . '/wp-json/wc/v3/products/' . $wc_product_id . '/variations/' . $wc_variation_id;
    $payload = json_encode([
        'manage_stock'   => true,
        'stock_quantity' => max(0, $qty),
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
        return ['error' => 'No response from WooCommerce' . ($curl_err ? ": $curl_err" : ' (connection failed or timed out)')];
    }
    return ['error' => $result['message'] ?? "HTTP $http_code", 'raw' => $result];
}

// ── Sync ──────────────────────────────────────────────────────────────────────

/**
 * Calculate available qty for a project and push it to WooCommerce.
 * For variable projects, pushes each variation separately.
 */
function wc_sync_project($db, int $project_id): array {
    $stmt = $db->prepare("SELECT woocommerce_product_id, project_name FROM projects WHERE id = ?");
    $stmt->execute([$project_id]);
    $project = $stmt->fetch();

    if (!$project || !$project['woocommerce_product_id']) {
        return ['skipped' => true, 'project_id' => $project_id, 'reason' => 'No WooCommerce product mapped'];
    }

    $wc_product_id = (int) $project['woocommerce_product_id'];

    if (wc_is_variable_project($db, $project_id)) {
        $cfg = wc_get_config();

        // Disable parent-level stock management so WooCommerce uses per-variation stock.
        // If the parent manages stock at 0, it overrides all variation availability.
        if ($cfg) {
            $parent_url = rtrim($cfg['site_url'], '/') . '/wp-json/wc/v3/products/' . $wc_product_id;
            wc_do_put($parent_url, json_encode(['manage_stock' => false]), $cfg);
        }

        $attrs  = wc_get_project_attributes($db, $project_id);
        $combos = wc_generate_combos($attrs);

        $stmt = $db->prepare("SELECT combo_key, wc_variation_id FROM project_variation_mappings WHERE project_id = ?");
        $stmt->execute([$project_id]);
        $mappings = [];
        foreach ($stmt->fetchAll() as $m) {
            $mappings[$m['combo_key']] = (int) $m['wc_variation_id'];
        }

        $variation_results = [];
        foreach ($combos as $combo) {
            $combo_key = wc_build_combo_key($combo);
            if (empty($mappings[$combo_key])) {
                $variation_results[] = ['skipped' => true, 'combo' => $combo_key, 'reason' => 'No WC variation ID mapped'];
                continue;
            }
            $qty    = wc_calculate_variation_qty($db, $project_id, $combo);
            $result = wc_push_variation_stock($wc_product_id, $mappings[$combo_key], $qty);
            $result['combo']          = $combo_key;
            $result['calculated_qty'] = $qty;
            $variation_results[]      = $result;
        }

        return [
            'project_name' => $project['project_name'],
            'variable'     => true,
            'variations'   => $variation_results,
        ];
    }

    $qty    = wc_calculate_available_qty($db, $project_id);
    $result = wc_push_stock($wc_product_id, $qty);
    $result['project_name']   = $project['project_name'];
    $result['calculated_qty'] = $qty;
    return $result;
}

/** Sync every project that has a WooCommerce product mapped. */
function wc_sync_all_projects($db): array {
    $stmt = $db->query("
        SELECT id FROM projects
        WHERE woocommerce_product_id IS NOT NULL AND status != 'archived'
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
 * $combo: for variable projects, the associative array of chosen attribute values.
 *         null means deduct only fixed parts (or all parts if no variable parts exist).
 */
function wc_deduct_bom_inventory($db, int $project_id, int $order_qty, ?array $combo = null): array {
    // Always deduct fixed (shared) parts
    $stmt = $db->prepare("
        SELECT pp.part_id, pp.quantity_required, p.part_name, p.current_stock
        FROM project_parts pp JOIN parts p ON p.id = pp.part_id
        WHERE pp.project_id = ? AND pp.variation_attribute = ''
    ");
    $stmt->execute([$project_id]);
    $rows = $stmt->fetchAll();

    // Also deduct the variable parts matching this specific combo
    if ($combo) {
        foreach ($combo as $attr => $val) {
            $stmt = $db->prepare("
                SELECT pp.part_id, pp.quantity_required, p.part_name, p.current_stock
                FROM project_parts pp JOIN parts p ON p.id = pp.part_id
                WHERE pp.project_id = ? AND pp.variation_attribute = ? AND pp.variation_value = ?
            ");
            $stmt->execute([$project_id, $attr, $val]);
            foreach ($stmt->fetchAll() as $r) $rows[] = $r;
        }
    }

    $log = [];
    foreach ($rows as $row) {
        $part_id   = (int) $row['part_id'];
        $deduct    = (int) $row['quantity_required'] * $order_qty;
        $old_stock = (int) $row['current_stock'];
        $new_stock = max(0, $old_stock - $deduct);
        try {
            $upd = $db->prepare("UPDATE parts SET current_stock = ? WHERE id = ?");
            $upd->execute([$new_stock, $part_id]);
            $affected = $upd->rowCount();
            $log[] = [
                'part_id'   => $part_id,
                'part_name' => $row['part_name'],
                'deducted'  => $deduct,
                'old_stock' => $old_stock,
                'new_stock' => $new_stock,
                'rows_affected' => $affected,
            ];
        } catch (\Exception $e) {
            $log[] = [
                'part_id'   => $part_id,
                'part_name' => $row['part_name'],
                'error'     => $e->getMessage(),
            ];
        }
    }
    return $log;
}

/**
 * Restore BOM components to parts inventory (order cancelled/refunded).
 * $combo: same as wc_deduct_bom_inventory — pass the original variation combo.
 */
function wc_restore_bom_inventory($db, int $project_id, int $order_qty, ?array $combo = null): array {
    $stmt = $db->prepare("
        SELECT pp.part_id, pp.quantity_required, p.part_name, p.current_stock
        FROM project_parts pp JOIN parts p ON p.id = pp.part_id
        WHERE pp.project_id = ? AND pp.variation_attribute = ''
    ");
    $stmt->execute([$project_id]);
    $rows = $stmt->fetchAll();

    if ($combo) {
        foreach ($combo as $attr => $val) {
            $stmt = $db->prepare("
                SELECT pp.part_id, pp.quantity_required, p.part_name, p.current_stock
                FROM project_parts pp JOIN parts p ON p.id = pp.part_id
                WHERE pp.project_id = ? AND pp.variation_attribute = ? AND pp.variation_value = ?
            ");
            $stmt->execute([$project_id, $attr, $val]);
            foreach ($stmt->fetchAll() as $r) $rows[] = $r;
        }
    }

    $log = [];
    foreach ($rows as $row) {
        $restore   = $row['quantity_required'] * $order_qty;
        $new_stock = $row['current_stock'] + $restore;
        $db->prepare("UPDATE parts SET current_stock = ? WHERE id = ?")->execute([$new_stock, $row['part_id']]);
        $log[] = [
            'part_id'   => $row['part_id'],
            'part_name' => $row['part_name'],
            'restored'  => $restore,
            'old_stock' => $row['current_stock'],
            'new_stock' => $new_stock,
        ];
    }
    return $log;
}
