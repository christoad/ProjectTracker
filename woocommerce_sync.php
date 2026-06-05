<?php
/**
 * WooCommerce Stock Sync — shared helper functions
 * Included by woocommerce_webhook.php; not a standalone endpoint.
 */

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

/**
 * How many complete kits can be built right now?
 * Answer = MIN across all BOM parts of floor(stock / qty_required).
 */
function wc_calculate_available_qty($db, $project_id) {
    $stmt = $db->prepare("
        SELECT p.current_stock, pp.quantity_required
        FROM project_parts pp
        JOIN parts p ON p.id = pp.part_id
        WHERE pp.project_id = ?
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
 * Push a stock level to a WooCommerce product via REST API.
 */
function wc_push_stock($wc_product_id, $qty) {
    $cfg = wc_get_config();
    if (!$cfg) return ['error' => 'WooCommerce credentials not configured in .env'];

    $url     = rtrim($cfg['site_url'], '/') . '/wp-json/wc/v3/products/' . intval($wc_product_id);
    $payload = json_encode([
        'manage_stock'  => true,
        'stock_quantity' => max(0, (int) $qty),
        'stock_status'  => $qty > 0 ? 'instock' : 'outofstock',
    ]);

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
    curl_close($ch);

    $result = json_decode($response, true);

    if ($http_code >= 200 && $http_code < 300 && isset($result['id'])) {
        return ['success' => true, 'product_id' => $wc_product_id, 'new_stock' => $qty];
    }
    return ['error' => $result['message'] ?? "HTTP $http_code", 'raw' => $result];
}

/**
 * Calculate available qty for a project and push it to the mapped WC product.
 */
function wc_sync_project($db, $project_id) {
    $stmt = $db->prepare("SELECT woocommerce_product_id, project_name FROM projects WHERE id = ?");
    $stmt->execute([$project_id]);
    $project = $stmt->fetch();

    if (!$project || !$project['woocommerce_product_id']) {
        return ['skipped' => true, 'project_id' => $project_id, 'reason' => 'No WooCommerce product mapped'];
    }

    $qty    = wc_calculate_available_qty($db, $project_id);
    $result = wc_push_stock($project['woocommerce_product_id'], $qty);
    $result['project_name']     = $project['project_name'];
    $result['calculated_qty']   = $qty;
    return $result;
}

/**
 * Sync every project that has a WooCommerce product mapped.
 */
function wc_sync_all_projects($db) {
    $stmt = $db->query("
        SELECT id FROM projects
        WHERE woocommerce_product_id IS NOT NULL AND status != 'archived'
    ");
    $results = [];
    foreach ($stmt->fetchAll() as $row) {
        $results[] = wc_sync_project($db, $row['id']);
    }
    return $results;
}

/**
 * Deduct BOM components from parts inventory for one order.
 * Returns a log of every part touched.
 */
function wc_deduct_bom_inventory($db, $project_id, $order_qty) {
    $stmt = $db->prepare("
        SELECT pp.part_id, pp.quantity_required, p.part_name, p.current_stock
        FROM project_parts pp
        JOIN parts p ON p.id = pp.part_id
        WHERE pp.project_id = ?
    ");
    $stmt->execute([$project_id]);

    $log = [];
    foreach ($stmt->fetchAll() as $row) {
        $deduct    = $row['quantity_required'] * $order_qty;
        $new_stock = max(0, $row['current_stock'] - $deduct);
        $db->prepare("UPDATE parts SET current_stock = ? WHERE id = ?")
           ->execute([$new_stock, $row['part_id']]);
        $log[] = [
            'part_id'   => $row['part_id'],
            'part_name' => $row['part_name'],
            'deducted'  => $deduct,
            'old_stock' => $row['current_stock'],
            'new_stock' => $new_stock,
        ];
    }
    return $log;
}

/**
 * Restore BOM components to parts inventory (order cancelled/refunded).
 */
function wc_restore_bom_inventory($db, $project_id, $order_qty) {
    $stmt = $db->prepare("
        SELECT pp.part_id, pp.quantity_required, p.part_name, p.current_stock
        FROM project_parts pp
        JOIN parts p ON p.id = pp.part_id
        WHERE pp.project_id = ?
    ");
    $stmt->execute([$project_id]);

    $log = [];
    foreach ($stmt->fetchAll() as $row) {
        $restore   = $row['quantity_required'] * $order_qty;
        $new_stock = $row['current_stock'] + $restore;
        $db->prepare("UPDATE parts SET current_stock = ? WHERE id = ?")
           ->execute([$new_stock, $row['part_id']]);
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
