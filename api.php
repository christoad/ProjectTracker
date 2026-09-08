<?php
/**
 * KI6CR Inventory Management System - API Handler
 */

require_once 'config.php';

header('Content-Type: application/json');

// Get request data
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$db = getDB();

// Public actions (no auth required)
if ($action === 'login') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $stmt = $db->prepare("SELECT id, username, password_hash FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        
        $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);
        
        jsonResponse(['success' => true, 'username' => $user['username']]);
    } else {
        jsonResponse(['error' => 'Invalid credentials'], 401);
    }
    exit;
}

if ($action === 'logout') {
    session_destroy();
    jsonResponse(['success' => true]);
}

if ($action === 'check_auth') {
    jsonResponse(['authenticated' => isLoggedIn()]);
}

// All other actions require authentication
requireLogin();

// Dashboard
if ($action === 'get_dashboard') {
    $stats = [
        'total_projects' => $db->query("SELECT COUNT(*) FROM projects WHERE status = 'active'")->fetchColumn(),
        'total_parts' => $db->query("SELECT COUNT(*) FROM parts")->fetchColumn(),
        'low_stock_count' => $db->query("
            SELECT COUNT(*) FROM parts p
            WHERE p.current_stock <= p.min_stock_level
              AND EXISTS (
                  SELECT 1 FROM project_parts pp
                  JOIN projects proj ON proj.id = pp.project_id
                  WHERE pp.part_id = p.id AND proj.status = 'active'
              )
        ")->fetchColumn(),
        'pending_orders' => $db->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn(),
        'low_stock_parts' => $db->query("
            SELECT * FROM parts p
            WHERE p.current_stock <= p.min_stock_level
              AND EXISTS (
                  SELECT 1 FROM project_parts pp
                  JOIN projects proj ON proj.id = pp.project_id
                  WHERE pp.part_id = p.id AND proj.status = 'active'
              )
            ORDER BY p.current_stock ASC LIMIT 10
        ")->fetchAll(),
        'recent_orders' => $db->query("
            SELECT o.*,
                   GROUP_CONCAT(CONCAT(p.project_name, ' ×', oi.quantity) SEPARATOR ', ') AS items_summary,
                   SUM(oi.line_total) AS total_price
            FROM orders o
            LEFT JOIN order_items oi ON oi.order_id = o.id
            LEFT JOIN projects p ON p.id = oi.project_id
            GROUP BY o.id
            ORDER BY o.created_at DESC LIMIT 5
        ")->fetchAll(),
    ];

    // ── Bottleneck insights ───────────────────────────────────────────────────
    // One query fetches all BOM rows for all active projects at once.
    $bom_rows = $db->query("
        SELECT
            proj.id          AS project_id,
            proj.project_name,
            proj.retail_price,
            pp.quantity_required,
            pp.variation_attribute,
            pp.variation_value,
            p.id             AS part_id,
            p.part_name,
            p.part_number,
            p.current_stock,
            p.weighted_avg_cost,
            (SELECT cost  FROM part_sources ps WHERE ps.part_id = p.id AND ps.is_preferred = 1 LIMIT 1) AS preferred_cost,
            (SELECT MIN(cost) FROM part_sources ps WHERE ps.part_id = p.id) AS lowest_cost
        FROM projects proj
        JOIN project_parts pp ON proj.id = pp.project_id
        JOIN parts p          ON pp.part_id = p.id
        WHERE proj.status = 'active'
        ORDER BY proj.project_name, p.part_name
    ")->fetchAll();

    // Group rows by project
    $by_project = [];
    foreach ($bom_rows as $row) {
        $pid = $row['project_id'];
        if (!isset($by_project[$pid])) {
            $by_project[$pid] = [
                'project_name' => $row['project_name'],
                'retail_price' => (float) $row['retail_price'],
                'parts'        => [],
            ];
        }
        $by_project[$pid]['parts'][] = $row;
    }

    $bottleneck_insights = [];
    foreach ($by_project as $project_id => $project) {
        $rows = array_values(array_filter($project['parts'], fn($p) => $p['quantity_required'] > 0));
        if (empty($rows)) continue;

        // Annotate every BOM row (fixed AND variable) with its own buildable count and unit cost
        foreach ($rows as &$r) {
            $r['buildable']  = (int) floor($r['current_stock'] / $r['quantity_required']);
            $r['unit_cost']  = $r['weighted_avg_cost'] > 0
                ? (float) $r['weighted_avg_cost']
                : (float) ($r['preferred_cost'] ?? $r['lowest_cost'] ?? 0);
        }
        unset($r);

        $fixed    = array_values(array_filter($rows, fn($r) => empty($r['variation_attribute'])));
        $variable = array_values(array_filter($rows, fn($r) => !empty($r['variation_attribute'])));

        $fixed_min = PHP_INT_MAX;
        foreach ($fixed as $fp) $fixed_min = min($fixed_min, $fp['buildable']);

        // Group variable rows into their specific variation combos (e.g. "Color: Blue") —
        // a combo's buildable count is constrained by shared fixed parts PLUS that combo's
        // own parts only. Pooling across different combo values (as the old code did) is
        // meaningless since a customer picks exactly one value, not all of them.
        $combos = [];
        foreach ($variable as $vr) {
            $combos[$vr['variation_attribute'] . '::' . $vr['variation_value']][] = $vr;
        }
        $combo_mins = [];
        foreach ($combos as $comboRows) {
            $m = $fixed_min;
            foreach ($comboRows as $r) $m = min($m, $r['buildable']);
            $combo_mins[] = $m;
        }

        // Current bottleneck = the worst-off SKU (or the shared fixed parts if no variations)
        $current_buildable = empty($combo_mins) ? $fixed_min : min($combo_mins);
        if ($current_buildable === PHP_INT_MAX) continue;

        $all_parts = array_map(fn($r) => [
            'part_id'           => (int) $r['part_id'],
            'part_name'         => $r['part_name'],
            'part_number'       => $r['part_number'],
            'current_stock'     => (int) $r['current_stock'],
            'quantity_required' => (int) $r['quantity_required'],
            'buildable'         => (int) $r['buildable'],
            'unit_cost'         => (float) $r['unit_cost'],
            'variation'         => empty($r['variation_attribute']) ? null : ($r['variation_attribute'] . ': ' . $r['variation_value']),
        ], $rows);

        // Sort ascending so the true bottleneck part(s) surface first
        usort($all_parts, fn($a, $b) => $a['buildable'] - $b['buildable']);

        $max_buildable = 0;
        foreach ($all_parts as $p) $max_buildable = max($max_buildable, $p['buildable']);

        $bottleneck_insights[] = [
            'project_id'        => $project_id,
            'project_name'      => $project['project_name'],
            'retail_price'      => $project['retail_price'],
            'current_buildable' => $current_buildable,
            'max_buildable'     => $max_buildable,
            'all_fixed_parts'   => $all_parts,
        ];
    }

    // Most urgent first: 0-buildable projects up top, then fewest kits
    usort($bottleneck_insights, fn($a, $b) => $a['current_buildable'] - $b['current_buildable']);

    $stats['bottleneck_insights'] = $bottleneck_insights;
    jsonResponse($stats);
}

// Projects
if ($action === 'get_projects') {
    $projects = $db->query("
        SELECT p.*, COUNT(DISTINCT pp.part_id) as parts_count
        FROM projects p
        LEFT JOIN project_parts pp ON p.id = pp.project_id
        WHERE p.status IN ('active','archived','planning')
        GROUP BY p.id
        ORDER BY p.created_at DESC
    ")->fetchAll();

    // Compute buildable_kits for every project in two queries total
    $allParts = $db->query("
        SELECT pp.project_id, pp.quantity_required, pp.variation_attribute, p.current_stock
        FROM project_parts pp
        JOIN parts p ON pp.part_id = p.id
    ")->fetchAll();

    $partsByProject = [];
    foreach ($allParts as $part) {
        $partsByProject[$part['project_id']][] = $part;
    }

    foreach ($projects as &$project) {
        $parts = $partsByProject[$project['id']] ?? [];
        $minBuildable = PHP_INT_MAX;
        $variationGroups = [];
        foreach ($parts as $part) {
            if (empty($part['variation_attribute'])) {
                if ($part['quantity_required'] > 0) {
                    $minBuildable = min($minBuildable, floor($part['current_stock'] / $part['quantity_required']));
                }
            } else {
                $attr = $part['variation_attribute'];
                if ($part['quantity_required'] > 0) {
                    $variationGroups[$attr] = ($variationGroups[$attr] ?? 0) + floor($part['current_stock'] / $part['quantity_required']);
                }
            }
        }
        foreach ($variationGroups as $groupBuildable) {
            $minBuildable = min($minBuildable, $groupBuildable);
        }
        $project['buildable_kits'] = ($minBuildable === PHP_INT_MAX) ? 0 : $minBuildable;
    }
    unset($project);

    jsonResponse($projects);
}

if ($action === 'get_project') {
    $id = $_GET['id'] ?? 0;
    $stmt = $db->prepare("SELECT * FROM projects WHERE id = ?");
    $stmt->execute([$id]);
    $project = $stmt->fetch();
    
    if ($project) {
        // Get parts for this project with detailed info including preferred supplier
        $stmt = $db->prepare("
            SELECT pp.*, p.part_number, p.part_name, p.current_stock, p.description, p.weighted_avg_cost, p.category,
                   (SELECT MIN(ps.cost) FROM part_sources ps WHERE ps.part_id = p.id) as lowest_cost,
                   (SELECT cost FROM part_sources ps WHERE ps.part_id = p.id AND ps.is_preferred = 1 LIMIT 1) as preferred_cost,
                   (SELECT supplier_name FROM part_sources ps WHERE ps.part_id = p.id AND ps.is_preferred = 1 LIMIT 1) as preferred_supplier,
                   (SELECT supplier_part_number FROM part_sources ps WHERE ps.part_id = p.id AND ps.is_preferred = 1 LIMIT 1) as preferred_supplier_pn,
                   (SELECT manufacturer_part_number FROM part_sources ps WHERE ps.part_id = p.id AND ps.is_preferred = 1 LIMIT 1) as preferred_mfr_pn,
                   (SELECT url FROM part_sources ps WHERE ps.part_id = p.id AND ps.is_preferred = 1 LIMIT 1) as preferred_url
            FROM project_parts pp
            JOIN parts p ON pp.part_id = p.id
            WHERE pp.project_id = ?
            ORDER BY pp.sort_order ASC, pp.id ASC
        ");
        $stmt->execute([$id]);
        $project['parts'] = $stmt->fetchAll();

        // Get research expenses for this project
        $stmt = $db->prepare("SELECT * FROM project_expenses WHERE project_id = ? ORDER BY expense_date DESC, created_at DESC");
        $stmt->execute([$id]);
        $project['expenses'] = $stmt->fetchAll();
        $project['total_expenses'] = array_sum(array_column($project['expenses'], 'cost'));

        // Calculate BOM costs and buildable quantity
        $fixed_bom_cost = 0;
        $min_buildable = PHP_INT_MAX;
        $variation_group_buildable = []; // keyed by variation_attribute
        $variation_line_cost = [];       // [attr][value] => summed line_total for that option

        foreach ($project['parts'] as &$part) {
            // Use weighted average cost from actual inventory first, fall back to supplier pricing
            $unit_cost = $part['weighted_avg_cost'] > 0
                ? (float) $part['weighted_avg_cost']
                : (float) ($part['preferred_cost'] ?? $part['lowest_cost'] ?? 0);
            $part['unit_cost'] = $unit_cost;
            $part['line_total'] = $unit_cost * $part['quantity_required'];

            if (empty($part['variation_attribute'])) {
                // Fixed part — counts toward shared BOM cost and constrains buildable count
                $fixed_bom_cost += $part['line_total'];
                if ($part['quantity_required'] > 0) {
                    $buildable = floor($part['current_stock'] / $part['quantity_required']);
                    $min_buildable = min($min_buildable, $buildable);
                }
            } else {
                // Variable part — pool stock across all options of the same attribute.
                // Any option satisfies the requirement, so sum buildable counts across all values.
                $attr = $part['variation_attribute'];
                $val  = $part['variation_value'];
                $variation_line_cost[$attr][$val] = ($variation_line_cost[$attr][$val] ?? 0) + $part['line_total'];
                if ($part['quantity_required'] > 0) {
                    $buildable = floor($part['current_stock'] / $part['quantity_required']);
                    $variation_group_buildable[$attr] = ($variation_group_buildable[$attr] ?? 0) + $buildable;
                }
            }
        }
        unset($part);

        // Each variation attribute group must be satisfiable — apply its pooled buildable as a constraint
        foreach ($variation_group_buildable as $attr => $group_buildable) {
            $min_buildable = min($min_buildable, $group_buildable);
        }

        if ($min_buildable === PHP_INT_MAX) {
            $min_buildable = 0;
        }

        // Realistic per-kit cost for every variation combination (Cartesian product across
        // attributes) = fixed parts cost + that combo's specific option costs. Fixed-only BOM
        // cost isn't realistic once a project has variations — every kit needs a variation part.
        $combos = [[]];
        foreach ($variation_line_cost as $attr => $values) {
            $new_combos = [];
            foreach ($combos as $combo) {
                foreach (array_keys($values) as $val) {
                    $c = $combo;
                    $c[$attr] = $val;
                    $new_combos[] = $c;
                }
            }
            $combos = $new_combos;
        }

        $variation_costs = [];
        foreach ($combos as $combo) {
            if (empty($combo)) continue; // project has no variation parts
            $cost  = $fixed_bom_cost;
            $label = [];
            foreach ($combo as $attr => $val) {
                $cost += $variation_line_cost[$attr][$val] ?? 0;
                $label[] = "$attr: $val";
            }
            $variation_costs[] = ['label' => implode(', ', $label), 'cost' => $cost];
        }

        // Average all-in cost per kit across every variation combo — used for BOM cost / profit /
        // inventory value, since we don't know in advance which variation the next order needs.
        $avg_kit_cost = !empty($variation_costs)
            ? array_sum(array_column($variation_costs, 'cost')) / count($variation_costs)
            : $fixed_bom_cost;

        $project['fixed_bom_cost']  = $fixed_bom_cost;
        $project['variation_costs'] = $variation_costs;
        $project['total_bom_cost']  = $avg_kit_cost; // realistic all-in cost per kit
        $project['buildable_kits']  = $min_buildable;
        // Value of materials tied up in the kits we can actually build right now
        $project['total_inventory_value'] = $avg_kit_cost * $min_buildable;

        // Calculate revenue and profit if retail price is set
        if ($project['retail_price'] > 0) {
            $project['projected_revenue'] = $project['retail_price'] * $min_buildable;
            $project['projected_profit'] = $project['projected_revenue'] - $project['total_inventory_value'];
            $project['profit_margin_percent'] = $avg_kit_cost > 0 ? (($project['retail_price'] - $avg_kit_cost) / $project['retail_price'] * 100) : 0;
        } else {
            $project['projected_revenue'] = 0;
            $project['projected_profit'] = 0;
            $project['profit_margin_percent'] = 0;
        }

        jsonResponse($project);
    } else {
        jsonResponse(['error' => 'Project not found'], 404);
    }
}

if ($action === 'save_project') {
    $id = $_POST['id'] ?? null;
    $name = $_POST['project_name'] ?? '';
    $description = $_POST['description'] ?? '';
    $status = $_POST['status'] ?? 'active';
    $retail_price = $_POST['retail_price'] ?? 0;
    $ship_weight_oz = isset($_POST['ship_weight_oz']) && $_POST['ship_weight_oz'] !== '' ? (float)$_POST['ship_weight_oz'] : null;
    $pkg_length     = isset($_POST['pkg_length'])     && $_POST['pkg_length']     !== '' ? (float)$_POST['pkg_length']     : null;
    $pkg_width      = isset($_POST['pkg_width'])      && $_POST['pkg_width']      !== '' ? (float)$_POST['pkg_width']      : null;
    $pkg_height     = isset($_POST['pkg_height'])     && $_POST['pkg_height']     !== '' ? (float)$_POST['pkg_height']     : null;
    $woocommerce_product_id = isset($_POST['woocommerce_product_id']) && $_POST['woocommerce_product_id'] !== '' ? (int)$_POST['woocommerce_product_id'] : null;
    $image_path = $_POST['image_path'] ?? null;
    
    // Handle image upload if present
    if (isset($_FILES['project_image']) && $_FILES['project_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/projects/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES['project_image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (in_array($file_extension, $allowed)) {
            $filename = 'project_' . ($id ?? time()) . '_' . uniqid() . '.' . $file_extension;
            $filepath = $upload_dir . $filename;
            
            if (move_uploaded_file($_FILES['project_image']['tmp_name'], $filepath)) {
                $image_path = $filepath;
            }
        }
    }
    
    if ($id) {
        if ($image_path) {
            $stmt = $db->prepare("UPDATE projects SET project_name = ?, description = ?, status = ?, retail_price = ?, ship_weight_oz = ?, pkg_length = ?, pkg_width = ?, pkg_height = ?, image_path = ?, woocommerce_product_id = ? WHERE id = ?");
            $stmt->execute([$name, $description, $status, $retail_price, $ship_weight_oz, $pkg_length, $pkg_width, $pkg_height, $image_path, $woocommerce_product_id, $id]);
        } else {
            $stmt = $db->prepare("UPDATE projects SET project_name = ?, description = ?, status = ?, retail_price = ?, ship_weight_oz = ?, pkg_length = ?, pkg_width = ?, pkg_height = ?, woocommerce_product_id = ? WHERE id = ?");
            $stmt->execute([$name, $description, $status, $retail_price, $ship_weight_oz, $pkg_length, $pkg_width, $pkg_height, $woocommerce_product_id, $id]);
        }
        jsonResponse(['success' => true, 'id' => $id]);
    } else {
        $stmt = $db->prepare("INSERT INTO projects (project_name, description, status, retail_price, ship_weight_oz, pkg_length, pkg_width, pkg_height, image_path, woocommerce_product_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $description, $status, $retail_price, $ship_weight_oz, $pkg_length, $pkg_width, $pkg_height, $image_path, $woocommerce_product_id]);
        jsonResponse(['success' => true, 'id' => $db->lastInsertId()]);
    }
}

if ($action === 'delete_project') {
    $id = $_POST['id'] ?? 0;
    $stmt = $db->prepare("UPDATE projects SET status = 'trashed' WHERE id = ?");
    $stmt->execute([$id]);
    jsonResponse(['success' => true]);
}

if ($action === 'restore_project') {
    $id = $_POST['id'] ?? 0;
    $stmt = $db->prepare("UPDATE projects SET status = 'active' WHERE id = ?");
    $stmt->execute([$id]);
    jsonResponse(['success' => true]);
}

if ($action === 'copy_project') {
    $id = (int)($_POST['id'] ?? 0);
    $stmt = $db->prepare("SELECT * FROM projects WHERE id = ?");
    $stmt->execute([$id]);
    $src = $stmt->fetch();
    if (!$src) { jsonResponse(['success' => false, 'error' => 'Project not found']); }

    $copy = $db->prepare("INSERT INTO projects (project_name, description, status, retail_price, ship_weight_oz, pkg_length, pkg_width, pkg_height, image_path) VALUES (?, ?, 'planning', ?, ?, ?, ?, ?, ?)");
    $copy->execute([
        $src['project_name'] . ' (Copy)',
        $src['description'],
        $src['retail_price'],
        $src['ship_weight_oz'],
        $src['pkg_length'],
        $src['pkg_width'],
        $src['pkg_height'],
        $src['image_path'],
    ]);
    $newId = $db->lastInsertId();

    $parts = $db->prepare("SELECT part_id, quantity_required, variation_attribute, variation_value FROM project_parts WHERE project_id = ?");
    $parts->execute([$id]);
    $ins = $db->prepare("INSERT INTO project_parts (project_id, part_id, quantity_required, variation_attribute, variation_value) VALUES (?, ?, ?, ?, ?)");
    foreach ($parts->fetchAll() as $row) {
        $ins->execute([$newId, $row['part_id'], $row['quantity_required'], $row['variation_attribute'], $row['variation_value']]);
    }

    jsonResponse(['success' => true, 'id' => $newId]);
}

if ($action === 'get_trashed_projects') {
    $projects = $db->query("SELECT id, project_name, description, created_at FROM projects WHERE status NOT IN ('active','archived','planning') ORDER BY project_name")->fetchAll();
    jsonResponse($projects);
}

// WooCommerce sync — proxied through api.php so browser content blockers
// don't flag the woocommerce_webhook.php URL pattern.
if (in_array($action, ['wc_status', 'wc_sync', 'wc_sync_all', 'wc_push_manual_stock', 'wc_pull_price'])) {
    require_once 'woocommerce_sync.php';

    if ($action === 'wc_status') {
        $stmt = $db->query("
            SELECT id, project_name, woocommerce_product_id, status, retail_price
            FROM projects
            WHERE woocommerce_product_id IS NOT NULL AND status NOT IN ('archived', 'trashed')
            ORDER BY project_name
        ");
        $projects = $stmt->fetchAll();

        // Pass 1: gather tracker-side data and every live WC lookup we'll need,
        // but don't hit the network yet — fetch them all in one parallel batch below.
        $prepped  = [];
        $requests = [];
        foreach ($projects as $p) {
            $wc_product_id = (int) $p['woocommerce_product_id'];
            $tracker_price = (float) $p['retail_price'];

            $vstmt = $db->prepare("
                SELECT combo_key, wc_variation_id
                FROM project_variation_mappings
                WHERE project_id = ? AND wc_variation_id IS NOT NULL
            ");
            $vstmt->execute([$p['id']]);
            $mappings = $vstmt->fetchAll();

            if (!empty($mappings)) {
                // Price lives on the project as a single value. WooCommerce leaves a
                // variable product's own regular_price blank (price is set per-variation),
                // so use the first variation's price as the project's effective price —
                // it's already being fetched for stock, so this adds no extra request.
                $priceReqKey = 'v' . $p['id'] . '_' . $mappings[0]['wc_variation_id'];

                $variations = [];
                foreach ($mappings as $m) {
                    // Pass combo_key as raw string — wc_calculate_variation_qty parses internally
                    $tracker_qty = wc_calculate_variation_qty($db, $p['id'], $m['combo_key']);
                    $reqKey = 'v' . $p['id'] . '_' . $m['wc_variation_id'];
                    $requests[$reqKey] = ['product_id' => $wc_product_id, 'variation_id' => (int) $m['wc_variation_id']];
                    $variations[] = [
                        'combo'        => $m['combo_key'],
                        'variation_id' => (int) $m['wc_variation_id'],
                        'tracker_qty'  => $tracker_qty,
                        'req_key'      => $reqKey,
                    ];
                }
                $prepped[] = [
                    'project_id'     => $p['id'],
                    'project_name'   => $p['project_name'],
                    'wc_product_id'  => $wc_product_id,
                    'variable'       => true,
                    'variations'     => $variations,
                    'project_status' => $p['status'],
                    'tracker_price'  => $tracker_price,
                    'price_req_key'  => $priceReqKey,
                ];
            } else {
                // Simple product: the stock request below already hits the product
                // endpoint, which also carries price — reuse it instead of a second call.
                $tracker_qty = wc_calculate_available_qty($db, $p['id']);
                $reqKey = 'p' . $p['id'];
                $requests[$reqKey] = ['product_id' => $wc_product_id, 'variation_id' => null];
                $prepped[] = [
                    'project_id'               => $p['id'],
                    'project_name'             => $p['project_name'],
                    'wc_product_id'            => $wc_product_id,
                    'calculated_available_qty' => $tracker_qty,
                    'req_key'                  => $reqKey,
                    'project_status'           => $p['status'],
                    'tracker_price'            => $tracker_price,
                    'price_req_key'            => $reqKey,
                ];
            }
        }

        $wcData = wc_fetch_stock_batch($requests);

        // Pass 2: stitch the live results back onto the prepped rows.
        $out = [];
        foreach ($prepped as $row) {
            $wc_price = $wcData[$row['price_req_key']]['price'] ?? null;
            $row['wc_price']    = $wc_price;
            $row['price_match'] = $wc_price !== null && round($wc_price, 2) === round($row['tracker_price'], 2);
            unset($row['price_req_key']);

            if (!empty($row['variable'])) {
                foreach ($row['variations'] as &$v) {
                    $wc_qty = $wcData[$v['req_key']]['stock_quantity'] ?? null;
                    $v['wc_qty'] = $wc_qty;
                    $v['match']  = $wc_qty !== null && $wc_qty === $v['tracker_qty'];
                    unset($v['req_key']);
                }
                unset($v);
            } else {
                $wc_qty = $wcData[$row['req_key']]['stock_quantity'] ?? null;
                $row['wc_stock_qty'] = $wc_qty;
                $row['match']        = $wc_qty !== null && $wc_qty === $row['calculated_available_qty'];
            }
            unset($row['req_key']);
            $out[] = $row;
        }
        jsonResponse($out);
    }

    if ($action === 'wc_sync') {
        $project_id = (int)($_GET['project_id'] ?? $_POST['project_id'] ?? 0);
        jsonResponse(wc_sync_project($db, $project_id));
    }

    if ($action === 'wc_sync_all') {
        $results = wc_sync_all_projects($db);
        jsonResponse(['synced' => count($results), 'results' => $results]);
    }

    if ($action === 'wc_push_manual_stock') {
        $project_id   = (int)($_POST['project_id'] ?? 0);
        $qty          = (int)($_POST['qty'] ?? -1);
        $variation_id = isset($_POST['variation_id']) && $_POST['variation_id'] !== '' ? (int) $_POST['variation_id'] : null;

        if ($project_id <= 0 || $qty < 0) {
            jsonResponse(['error' => 'Invalid project or quantity'], 400);
        }

        $stmt = $db->prepare("SELECT woocommerce_product_id FROM projects WHERE id = ?");
        $stmt->execute([$project_id]);
        $wc_product_id = $stmt->fetchColumn();
        if (!$wc_product_id) {
            jsonResponse(['error' => 'Project is not mapped to a WooCommerce product'], 400);
        }

        $result = $variation_id
            ? wc_push_variation_stock($wc_product_id, $variation_id, $qty)
            : wc_push_stock($wc_product_id, $qty);

        wc_log(isset($result['success']) ? 'info' : 'error', 'Manual stock override', [
            'project_id' => $project_id, 'variation_id' => $variation_id, 'qty' => $qty, 'result' => $result,
        ]);

        jsonResponse($result);
    }

    if ($action === 'wc_pull_price') {
        $project_id = (int)($_POST['project_id'] ?? 0);
        if ($project_id <= 0) {
            jsonResponse(['error' => 'Invalid project'], 400);
        }

        $stmt = $db->prepare("SELECT woocommerce_product_id FROM projects WHERE id = ?");
        $stmt->execute([$project_id]);
        $wc_product_id = $stmt->fetchColumn();
        if (!$wc_product_id) {
            jsonResponse(['error' => 'Project is not mapped to a WooCommerce product'], 400);
        }

        // Variable products leave the parent's own price blank — pull from the
        // first mapped variation instead, same as the wc_status comparison does.
        $vstmt = $db->prepare("SELECT wc_variation_id FROM project_variation_mappings WHERE project_id = ? AND wc_variation_id IS NOT NULL LIMIT 1");
        $vstmt->execute([$project_id]);
        $wc_variation_id = $vstmt->fetchColumn();

        $price = $wc_variation_id
            ? wc_fetch_variation_price($wc_product_id, $wc_variation_id)
            : wc_fetch_product_price($wc_product_id);
        if ($price === null) {
            jsonResponse(['error' => 'Could not fetch a live price from WooCommerce']);
        }

        $upd = $db->prepare("UPDATE projects SET retail_price = ? WHERE id = ?");
        $upd->execute([$price, $project_id]);

        wc_log('info', 'Pulled retail price from WooCommerce', [
            'project_id' => $project_id, 'new_price' => $price,
        ]);

        jsonResponse(['success' => true, 'new_price' => $price]);
    }

    if ($action === 'wc_sync_log') {
        $log_file = __DIR__ . '/wc_sync.log';
        if (!file_exists($log_file)) { jsonResponse(['entries' => []]); }
        $lines   = array_filter(explode("\n", file_get_contents($log_file)));
        $entries = array_map(fn($l) => json_decode($l, true), array_slice(array_values($lines), -100));
        jsonResponse(['entries' => array_reverse($entries)]);
    }

    if ($action === 'wc_sync_log_clear') {
        file_put_contents(__DIR__ . '/wc_sync.log', '');
        jsonResponse(['success' => true]);
    }
}

// Parts
if ($action === 'get_parts') {
    $parts = $db->query("
        SELECT p.*,
            (SELECT COALESCE(SUM(ic.quantity), 0) FROM inventory_checkins ic WHERE ic.part_id = p.id AND ic.received = 0) as pending_order_qty,
            (SELECT COUNT(*) FROM inventory_checkins ic WHERE ic.part_id = p.id AND ic.received = 0) as pending_order_count
        FROM parts p
        ORDER BY p.part_name ASC
    ")->fetchAll();
    jsonResponse($parts);
}

if ($action === 'get_unassigned_part_ids') {
    $ids = $db->query("SELECT id FROM parts WHERE id NOT IN (SELECT DISTINCT part_id FROM project_parts)")->fetchAll(PDO::FETCH_COLUMN);
    jsonResponse(array_map('intval', $ids));
}

if ($action === 'get_part') {
    $id = $_GET['id'] ?? 0;
    $stmt = $db->prepare("SELECT * FROM parts WHERE id = ?");
    $stmt->execute([$id]);
    $part = $stmt->fetch();
    
    if ($part) {
        // Get sources for this part
        $stmt = $db->prepare("SELECT * FROM part_sources WHERE part_id = ? ORDER BY is_preferred DESC, cost ASC");
        $stmt->execute([$id]);
        $part['sources'] = $stmt->fetchAll();
        
        // Get check-ins: pending first, then received newest-first
        $stmt = $db->prepare("SELECT * FROM inventory_checkins WHERE part_id = ? ORDER BY received ASC, purchase_date DESC LIMIT 50");
        $stmt->execute([$id]);
        $part['checkins'] = $stmt->fetchAll();

        // Get manual stock adjustments (e.g. JLCPCB PCBA consumption, damage, corrections)
        $stmt = $db->prepare("SELECT * FROM inventory_adjustments WHERE part_id = ? ORDER BY created_at DESC LIMIT 50");
        $stmt->execute([$id]);
        $part['adjustments'] = $stmt->fetchAll();

        jsonResponse($part);
    } else {
        jsonResponse(['error' => 'Part not found'], 404);
    }
}

// Manual stock adjustments — deduct (or add back) raw part quantity outside the normal
// purchase check-in / project BOM flow. Used e.g. when raw parts are consumed directly
// by a JLCPCB PCBA run and never return as usable stock for other projects.
if ($action === 'save_inventory_adjustment') {
    $part_id  = (int)($_POST['part_id'] ?? 0);
    $quantity = (int)($_POST['quantity'] ?? 0);
    $direction = ($_POST['direction'] ?? 'remove') === 'add' ? 'add' : 'remove';
    $reason   = trim($_POST['reason'] ?? '');
    $note     = trim($_POST['note'] ?? '');

    if (!$part_id || $quantity <= 0 || $reason === '') {
        jsonResponse(['error' => 'part_id, a positive quantity, and a reason are required'], 400);
        exit;
    }

    $quantity_change = $direction === 'add' ? $quantity : -$quantity;

    $db->beginTransaction();
    try {
        $stmt = $db->prepare("SELECT current_stock FROM parts WHERE id = ? FOR UPDATE");
        $stmt->execute([$part_id]);
        $part = $stmt->fetch();
        if (!$part) {
            $db->rollBack();
            jsonResponse(['error' => 'Part not found'], 404);
            exit;
        }

        $new_stock = (int)$part['current_stock'] + $quantity_change;
        if ($new_stock < 0) {
            $db->rollBack();
            jsonResponse(['error' => "Cannot remove {$quantity} units — only {$part['current_stock']} in stock"], 400);
            exit;
        }

        $stmt = $db->prepare("UPDATE parts SET current_stock = ? WHERE id = ?");
        $stmt->execute([$new_stock, $part_id]);

        $stmt = $db->prepare("INSERT INTO inventory_adjustments (part_id, quantity_change, reason, note) VALUES (?, ?, ?, ?)");
        $stmt->execute([$part_id, $quantity_change, $reason, $note !== '' ? $note : null]);

        $db->commit();
        jsonResponse(['success' => true, 'id' => $db->lastInsertId(), 'new_stock' => $new_stock]);
    } catch (Exception $e) {
        $db->rollBack();
        jsonResponse(['error' => 'Failed to save adjustment: ' . $e->getMessage()], 500);
    }
}

if ($action === 'delete_inventory_adjustment') {
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) {
        jsonResponse(['error' => 'id is required'], 400);
        exit;
    }

    $db->beginTransaction();
    try {
        $stmt = $db->prepare("SELECT part_id, quantity_change FROM inventory_adjustments WHERE id = ?");
        $stmt->execute([$id]);
        $adj = $stmt->fetch();
        if (!$adj) {
            $db->rollBack();
            jsonResponse(['error' => 'Adjustment not found'], 404);
            exit;
        }

        $stmt = $db->prepare("SELECT current_stock FROM parts WHERE id = ? FOR UPDATE");
        $stmt->execute([$adj['part_id']]);
        $part = $stmt->fetch();

        $reverted_stock = (int)$part['current_stock'] - (int)$adj['quantity_change'];
        if ($reverted_stock < 0) {
            $db->rollBack();
            jsonResponse(['error' => 'Cannot delete — reverting would make stock negative'], 400);
            exit;
        }

        $stmt = $db->prepare("UPDATE parts SET current_stock = ? WHERE id = ?");
        $stmt->execute([$reverted_stock, $adj['part_id']]);

        $stmt = $db->prepare("DELETE FROM inventory_adjustments WHERE id = ?");
        $stmt->execute([$id]);

        $db->commit();
        jsonResponse(['success' => true]);
    } catch (Exception $e) {
        $db->rollBack();
        jsonResponse(['error' => 'Failed to delete adjustment: ' . $e->getMessage()], 500);
    }
}

if ($action === 'get_next_part_number') {
    $prefix = $_GET['prefix'] ?? '';
    if (!$prefix) {
        jsonResponse(['error' => 'No prefix provided'], 400);
    }
    $stmt = $db->prepare("SELECT part_number FROM parts WHERE part_number LIKE ?");
    $stmt->execute([$prefix . '-%']);
    $rows = $stmt->fetchAll();

    $maxNum = 0;
    foreach ($rows as $row) {
        if (preg_match('/^' . preg_quote($prefix, '/') . '-(\d+)/i', $row['part_number'], $matches)) {
            $num = intval($matches[1]);
            if ($num > $maxNum) $maxNum = $num;
        }
    }

    $next = str_pad($maxNum + 1, 3, '0', STR_PAD_LEFT);
    jsonResponse(['next_part_number' => $prefix . '-' . $next]);
}

if ($action === 'save_part') {
    $id = $_POST['id'] ?? null;
    $part_number = $_POST['part_number'] ?? '';
    $part_name = $_POST['part_name'] ?? '';
    $description = $_POST['description'] ?? '';
    $category = $_POST['category'] ?? '';
    $current_stock = $_POST['current_stock'] ?? 0;
    $min_stock_level = $_POST['min_stock_level'] ?? 0;
    
    if ($id) {
        $stmt = $db->prepare("UPDATE parts SET part_number = ?, part_name = ?, description = ?, category = ?, current_stock = ?, min_stock_level = ? WHERE id = ?");
        $stmt->execute([$part_number, $part_name, $description, $category, $current_stock, $min_stock_level, $id]);
        jsonResponse(['success' => true, 'id' => $id]);
    } else {
        $stmt = $db->prepare("INSERT INTO parts (part_number, part_name, description, category, current_stock, min_stock_level) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$part_number, $part_name, $description, $category, $current_stock, $min_stock_level]);
        jsonResponse(['success' => true, 'id' => $db->lastInsertId()]);
    }
}

if ($action === 'delete_part') {
    $id = $_POST['id'] ?? 0;
    $stmt = $db->prepare("DELETE FROM parts WHERE id = ?");
    $stmt->execute([$id]);
    jsonResponse(['success' => true]);
}

// Copy Part
if ($action === 'copy_part') {
    $id = $_POST['id'] ?? 0;
    
    // Get original part
    $stmt = $db->prepare("SELECT * FROM parts WHERE id = ?");
    $stmt->execute([$id]);
    $part = $stmt->fetch();
    
    if (!$part) {
        jsonResponse(['error' => 'Part not found'], 404);
        exit;
    }
    
    try {
        // Create copy with modified part number
        $new_part_number = $part['part_number'] . '-COPY';
        $stmt = $db->prepare("INSERT INTO parts (part_number, part_name, description, category, current_stock, min_stock_level, weighted_avg_cost) VALUES (?, ?, ?, ?, 0, ?, 0)");
        $stmt->execute([$new_part_number, $part['part_name'] . ' (Copy)', $part['description'], $part['category'], $part['min_stock_level']]);
        
        $new_id = $db->lastInsertId();
        
        // Copy sources too
        $stmt = $db->prepare("SELECT * FROM part_sources WHERE part_id = ?");
        $stmt->execute([$id]);
        $sources = $stmt->fetchAll();
        
        foreach ($sources as $source) {
            $stmt = $db->prepare("INSERT INTO part_sources (part_id, supplier_name, supplier_part_number, manufacturer_part_number, cost, url, is_preferred, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $new_id,
                $source['supplier_name'],
                $source['supplier_part_number'],
                $source['manufacturer_part_number'],
                $source['cost'],
                $source['url'],
                0, // Don't copy preferred status
                $source['notes']
            ]);
        }
        
        jsonResponse(['success' => true, 'id' => $new_id]);
        exit;
    } catch (Exception $e) {
        jsonResponse(['error' => 'Failed to copy part: ' . $e->getMessage()], 500);
        exit;
    }
}

// Part Sources
if ($action === 'save_source') {
    $id = $_POST['id'] ?? null;
    $part_id = $_POST['part_id'] ?? 0;
    $supplier_name = $_POST['supplier_name'] ?? '';
    $supplier_part_number = $_POST['supplier_part_number'] ?? '';
    $manufacturer_part_number = $_POST['manufacturer_part_number'] ?? '';
    $cost = $_POST['cost'] ?? 0;
    $url = $_POST['url'] ?? '';
    $is_preferred = isset($_POST['is_preferred']) ? 1 : 0;
    $notes = $_POST['notes'] ?? '';
    
    if ($id) {
        $stmt = $db->prepare("UPDATE part_sources SET part_id = ?, supplier_name = ?, supplier_part_number = ?, manufacturer_part_number = ?, cost = ?, url = ?, is_preferred = ?, notes = ? WHERE id = ?");
        $stmt->execute([$part_id, $supplier_name, $supplier_part_number, $manufacturer_part_number, $cost, $url, $is_preferred, $notes, $id]);
        jsonResponse(['success' => true, 'id' => $id]);
    } else {
        $stmt = $db->prepare("INSERT INTO part_sources (part_id, supplier_name, supplier_part_number, manufacturer_part_number, cost, url, is_preferred, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$part_id, $supplier_name, $supplier_part_number, $manufacturer_part_number, $cost, $url, $is_preferred, $notes]);
        jsonResponse(['success' => true, 'id' => $db->lastInsertId()]);
    }
}

if ($action === 'delete_source') {
    $id = $_POST['id'] ?? 0;
    $stmt = $db->prepare("DELETE FROM part_sources WHERE id = ?");
    $stmt->execute([$id]);
    jsonResponse(['success' => true]);
}

// Project Parts (BOM)
if ($action === 'add_project_part') {
    $project_id          = $_POST['project_id'] ?? 0;
    $part_id             = $_POST['part_id'] ?? 0;
    $quantity            = $_POST['quantity_required'] ?? 1;
    $notes               = $_POST['notes'] ?? '';
    $variation_attribute = $_POST['variation_attribute'] ?? '';
    $variation_value     = $_POST['variation_value'] ?? '';

    $sortStmt = $db->prepare("SELECT COALESCE(MAX(sort_order), 0) + 1 FROM project_parts WHERE project_id = ?");
    $sortStmt->execute([$project_id]);
    $sort_order = (int)$sortStmt->fetchColumn();

    $stmt = $db->prepare("
        INSERT INTO project_parts (project_id, part_id, quantity_required, notes, variation_attribute, variation_value, sort_order)
        VALUES (?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE quantity_required = ?, notes = ?
    ");
    $stmt->execute([$project_id, $part_id, $quantity, $notes, $variation_attribute, $variation_value, $sort_order, $quantity, $notes]);
    jsonResponse(['success' => true]);
}

if ($action === 'reorder_bom') {
    $items = json_decode($_POST['items'] ?? '[]', true);
    if (!is_array($items)) { jsonResponse(['success' => false, 'error' => 'Invalid data']); }
    $stmt = $db->prepare("UPDATE project_parts SET sort_order = ? WHERE id = ?");
    foreach ($items as $item) {
        $stmt->execute([(int)$item['sort_order'], (int)$item['id']]);
    }
    jsonResponse(['success' => true]);
}

// Update an existing BOM row's quantity by its primary key (works for both fixed and variable parts)
if ($action === 'update_project_part') {
    $id       = $_POST['id'] ?? 0;
    $quantity = (int)($_POST['quantity_required'] ?? 1);
    $stmt = $db->prepare("UPDATE project_parts SET quantity_required = ? WHERE id = ?");
    $stmt->execute([$quantity, $id]);

    // Also update variation fields if provided
    if (isset($_POST['variation_attribute'])) {
        $attr = trim($_POST['variation_attribute']);
        $val  = trim($_POST['variation_value'] ?? '');
        $stmt = $db->prepare("UPDATE project_parts SET variation_attribute = ?, variation_value = ? WHERE id = ?");
        $stmt->execute([$attr ?: null, $val ?: null, $id]);
    }

    jsonResponse(['success' => true]);
}

if ($action === 'delete_project_part') {
    $id = $_POST['id'] ?? 0;
    $stmt = $db->prepare("DELETE FROM project_parts WHERE id = ?");
    $stmt->execute([$id]);
    jsonResponse(['success' => true]);
}

// Distinct variation attribute names + their values used anywhere across all projects,
// so the "Add Variable Part" form can suggest existing ones instead of retyping.
if ($action === 'get_variation_options') {
    $rows = $db->query("
        SELECT DISTINCT variation_attribute, variation_value
        FROM project_parts
        WHERE variation_attribute != ''
        ORDER BY variation_attribute, variation_value
    ")->fetchAll();

    $attributes = [];
    foreach ($rows as $row) {
        $attributes[$row['variation_attribute']][] = $row['variation_value'];
    }
    jsonResponse(['attributes' => $attributes]);
}

// Return all variation combinations for a project with buildable qty and WC variation ID mappings
if ($action === 'get_project_variations') {
    $project_id = (int)($_GET['project_id'] ?? 0);

    $stmt = $db->prepare("
        SELECT DISTINCT variation_attribute, variation_value
        FROM project_parts
        WHERE project_id = ? AND variation_attribute != ''
        ORDER BY variation_attribute, variation_value
    ");
    $stmt->execute([$project_id]);
    $rows = $stmt->fetchAll();

    $attributes = [];
    foreach ($rows as $row) {
        $attributes[$row['variation_attribute']][] = $row['variation_value'];
    }

    if (empty($attributes)) {
        jsonResponse(['has_variations' => false, 'attributes' => [], 'combos' => []]);
        exit;
    }

    // Generate Cartesian product of all attribute values
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

    $build_combo_key = function(array $combo): string {
        ksort($combo);
        $parts = [];
        foreach ($combo as $a => $v) $parts[] = $a . ':' . $v;
        return implode('|', $parts);
    };

    // Load existing WC variation ID mappings
    $stmt = $db->prepare("SELECT combo_key, wc_variation_id FROM project_variation_mappings WHERE project_id = ?");
    $stmt->execute([$project_id]);
    $mappings = [];
    foreach ($stmt->fetchAll() as $m) {
        $mappings[$m['combo_key']] = $m['wc_variation_id'];
    }

    // Pre-load fixed parts once
    $stmt = $db->prepare("
        SELECT p.current_stock, pp.quantity_required
        FROM project_parts pp JOIN parts p ON p.id = pp.part_id
        WHERE pp.project_id = ? AND pp.variation_attribute = ''
    ");
    $stmt->execute([$project_id]);
    $fixed_parts = $stmt->fetchAll();

    $combo_results = [];
    foreach ($combos as $combo) {
        ksort($combo);
        $combo_key = $build_combo_key($combo);

        $min = PHP_INT_MAX;
        foreach ($fixed_parts as $f) {
            if ($f['quantity_required'] <= 0) continue;
            $min = min($min, (int)floor($f['current_stock'] / $f['quantity_required']));
        }

        foreach ($combo as $attr => $val) {
            $stmt = $db->prepare("
                SELECT p.current_stock, pp.quantity_required
                FROM project_parts pp JOIN parts p ON p.id = pp.part_id
                WHERE pp.project_id = ? AND pp.variation_attribute = ? AND pp.variation_value = ?
            ");
            $stmt->execute([$project_id, $attr, $val]);
            foreach ($stmt->fetchAll() as $v) {
                if ($v['quantity_required'] <= 0) continue;
                $min = min($min, (int)floor($v['current_stock'] / $v['quantity_required']));
            }
        }

        $combo_results[] = [
            'combo'           => $combo,
            'combo_key'       => $combo_key,
            'buildable'       => $min === PHP_INT_MAX ? 0 : $min,
            'wc_variation_id' => $mappings[$combo_key] ?? null,
        ];
    }

    jsonResponse(['has_variations' => true, 'attributes' => $attributes, 'combos' => $combo_results]);
}

// Save or update a WooCommerce variation ID for a specific attribute combination
if ($action === 'save_variation_mapping') {
    $project_id      = (int)($_POST['project_id'] ?? 0);
    $combo_key       = trim($_POST['combo_key'] ?? '');
    $wc_variation_id = ($_POST['wc_variation_id'] ?? '') !== '' ? (int)$_POST['wc_variation_id'] : null;

    if (!$project_id || $combo_key === '') {
        jsonResponse(['error' => 'project_id and combo_key are required'], 400);
        exit;
    }

    $stmt = $db->prepare("
        INSERT INTO project_variation_mappings (project_id, combo_key, wc_variation_id)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE wc_variation_id = ?, updated_at = NOW()
    ");
    $stmt->execute([$project_id, $combo_key, $wc_variation_id, $wc_variation_id]);
    jsonResponse(['success' => true]);
}

// Promo / Freebie Giveaways — deduct BOM inventory for kits given away, not sold
if ($action === 'save_promo') {
    require_once 'woocommerce_sync.php';

    $project_id = (int)($_POST['project_id'] ?? 0);
    $combo_key  = trim($_POST['combo_key'] ?? '');
    $quantity   = (int)($_POST['quantity'] ?? 0);
    $note       = trim($_POST['note'] ?? '');

    if (!$project_id || $quantity <= 0) {
        jsonResponse(['error' => 'project_id and a positive quantity are required'], 400);
        exit;
    }

    $log = wc_deduct_bom_inventory($db, $project_id, $quantity, $combo_key !== '' ? $combo_key : null);

    $stmt = $db->prepare("
        INSERT INTO promo_giveaways (project_id, variation_combo_key, quantity, note)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$project_id, $combo_key !== '' ? $combo_key : null, $quantity, $note !== '' ? $note : null]);

    jsonResponse(['success' => true, 'id' => $db->lastInsertId(), 'log' => $log]);
}

if ($action === 'get_promo_history') {
    $project_id = (int)($_GET['project_id'] ?? 0);
    if (!$project_id) {
        jsonResponse(['error' => 'project_id is required'], 400);
        exit;
    }
    $stmt = $db->prepare("
        SELECT id, variation_combo_key, quantity, note, created_at
        FROM promo_giveaways
        WHERE project_id = ?
        ORDER BY created_at DESC
        LIMIT 50
    ");
    $stmt->execute([$project_id]);
    jsonResponse($stmt->fetchAll());
}

if ($action === 'delete_promo') {
    require_once 'woocommerce_sync.php';

    $id = (int)($_POST['id'] ?? 0);
    if (!$id) {
        jsonResponse(['error' => 'id is required'], 400);
        exit;
    }

    $stmt = $db->prepare("SELECT project_id, variation_combo_key, quantity FROM promo_giveaways WHERE id = ?");
    $stmt->execute([$id]);
    $promo = $stmt->fetch();
    if (!$promo) {
        jsonResponse(['error' => 'Promo record not found'], 404);
        exit;
    }

    wc_restore_bom_inventory($db, $promo['project_id'], $promo['quantity'], $promo['variation_combo_key'] ?: null);

    $stmt = $db->prepare("DELETE FROM promo_giveaways WHERE id = ?");
    $stmt->execute([$id]);

    jsonResponse(['success' => true]);
}

// Project Expenses
if ($action === 'save_project_expense') {
    $id = $_POST['id'] ?? null;
    $project_id = $_POST['project_id'] ?? 0;
    $description = $_POST['description'] ?? '';
    $cost = $_POST['cost'] ?? 0;
    $expense_date = $_POST['expense_date'] ?? null;

    if ($id) {
        $stmt = $db->prepare("UPDATE project_expenses SET description = ?, cost = ?, expense_date = ? WHERE id = ?");
        $stmt->execute([$description, $cost, $expense_date ?: null, $id]);
        jsonResponse(['success' => true, 'id' => $id]);
    } else {
        $stmt = $db->prepare("INSERT INTO project_expenses (project_id, description, cost, expense_date) VALUES (?, ?, ?, ?)");
        $stmt->execute([$project_id, $description, $cost, $expense_date ?: null]);
        jsonResponse(['success' => true, 'id' => $db->lastInsertId()]);
    }
}

if ($action === 'delete_project_expense') {
    $id = $_POST['id'] ?? 0;
    $stmt = $db->prepare("DELETE FROM project_expenses WHERE id = ?");
    $stmt->execute([$id]);
    jsonResponse(['success' => true]);
}

// Business Expenses (store-wide overhead — not tied to a specific project)
if ($action === 'get_business_expenses') {
    $stmt = $db->query("SELECT * FROM business_expenses ORDER BY expense_date DESC, created_at DESC");
    jsonResponse($stmt->fetchAll());
}

if ($action === 'save_business_expense') {
    $id           = $_POST['id'] ?? null;
    $description  = trim($_POST['description'] ?? '');
    $cost         = $_POST['cost'] ?? 0;
    $category     = trim($_POST['category'] ?? 'Other');
    $expense_date = $_POST['expense_date'] ?? date('Y-m-d');
    $notes        = trim($_POST['notes'] ?? '');

    if ($id) {
        $stmt = $db->prepare("UPDATE business_expenses SET description=?, cost=?, category=?, expense_date=?, notes=? WHERE id=?");
        $stmt->execute([$description, $cost, $category, $expense_date, $notes, $id]);
        jsonResponse(['success' => true, 'id' => $id]);
    } else {
        $stmt = $db->prepare("INSERT INTO business_expenses (description, cost, category, expense_date, notes) VALUES (?,?,?,?,?)");
        $stmt->execute([$description, $cost, $category, $expense_date, $notes]);
        jsonResponse(['success' => true, 'id' => $db->lastInsertId()]);
    }
}

if ($action === 'delete_business_expense') {
    $id = $_POST['id'] ?? 0;
    $stmt = $db->prepare("DELETE FROM business_expenses WHERE id = ?");
    $stmt->execute([$id]);
    jsonResponse(['success' => true]);
}

// Inventory Check-ins
if ($action === 'checkin_inventory') {
    $part_id = $_POST['part_id'] ?? 0;
    $quantity = $_POST['quantity'] ?? 0;
    $gross_total = $_POST['gross_total'] ?? null;
    $unit_cost = $_POST['unit_cost'] ?? null;
    $received = intval($_POST['received'] ?? 0);

    // Calculate the other value if only one is provided
    if ($gross_total !== null && $unit_cost === null) {
        $gross_total = floatval($gross_total);
        $unit_cost = $quantity > 0 ? $gross_total / $quantity : 0;
    } elseif ($unit_cost !== null && $gross_total === null) {
        $unit_cost = floatval($unit_cost);
        $gross_total = $unit_cost * $quantity;
    } elseif ($gross_total !== null && $unit_cost !== null) {
        // Both provided, use gross_total to recalculate unit_cost
        $gross_total = floatval($gross_total);
        $unit_cost = $quantity > 0 ? $gross_total / $quantity : 0;
    } else {
        jsonResponse(['error' => 'Must provide either unit_cost or gross_total'], 400);
    }

    $total_cost = $gross_total;
    $supplier_name = $_POST['supplier_name'] ?? '';
    $purchase_date = $_POST['purchase_date'] ?? date('Y-m-d');
    $notes = $_POST['notes'] ?? '';
    $received_at = $received ? date('Y-m-d H:i:s') : null;

    $db->beginTransaction();

    try {
        // Insert order record (pending or already-received)
        $stmt = $db->prepare("INSERT INTO inventory_checkins (part_id, quantity, unit_cost, total_cost, supplier_name, purchase_date, notes, received, received_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$part_id, $quantity, $unit_cost, $total_cost, $supplier_name, $purchase_date, $notes, $received, $received_at]);

        $new_avg_cost = null;

        if ($received) {
            // Get current stock and weighted average cost
            $stmt = $db->prepare("SELECT current_stock, weighted_avg_cost FROM parts WHERE id = ?");
            $stmt->execute([$part_id]);
            $part = $stmt->fetch();

            $current_stock = $part['current_stock'];
            $current_avg_cost = $part['weighted_avg_cost'] ?? 0;

            // Calculate new weighted average cost
            $old_value = $current_stock * $current_avg_cost;
            $new_value = $quantity * $unit_cost;
            $total_quantity = $current_stock + $quantity;
            $new_avg_cost = $total_quantity > 0 ? ($old_value + $new_value) / $total_quantity : 0;

            // Update part stock and weighted average cost
            $stmt = $db->prepare("UPDATE parts SET current_stock = current_stock + ?, weighted_avg_cost = ? WHERE id = ?");
            $stmt->execute([$quantity, $new_avg_cost, $part_id]);
        }

        $db->commit();
        jsonResponse(['success' => true, 'new_avg_cost' => $new_avg_cost]);
    } catch (Exception $e) {
        $db->rollBack();
        jsonResponse(['error' => $e->getMessage()], 500);
    }
}

if ($action === 'delete_checkin') {
    $id = $_POST['id'] ?? 0;
    $part_id = $_POST['part_id'] ?? 0;

    $db->beginTransaction();

    try {
        // Get the check-in details before deleting
        $stmt = $db->prepare("SELECT quantity, unit_cost, received FROM inventory_checkins WHERE id = ?");
        $stmt->execute([$id]);
        $checkin = $stmt->fetch();

        if (!$checkin) {
            jsonResponse(['error' => 'Check-in not found'], 404);
        }

        // Delete the record
        $stmt = $db->prepare("DELETE FROM inventory_checkins WHERE id = ?");
        $stmt->execute([$id]);

        if ($checkin['received']) {
            // Only reverse stock for received orders
            $stmt = $db->prepare("UPDATE parts SET current_stock = GREATEST(0, current_stock - ?) WHERE id = ?");
            $stmt->execute([$checkin['quantity'], $part_id]);

            // Recalculate weighted average cost from remaining received check-ins
            $stmt = $db->prepare("
                SELECT SUM(quantity) as total_qty, SUM(quantity * unit_cost) as total_value
                FROM inventory_checkins
                WHERE part_id = ? AND received = 1
            ");
            $stmt->execute([$part_id]);
            $totals = $stmt->fetch();

            $new_avg_cost = $totals['total_qty'] > 0 ? $totals['total_value'] / $totals['total_qty'] : 0;

            $stmt = $db->prepare("UPDATE parts SET weighted_avg_cost = ? WHERE id = ?");
            $stmt->execute([$new_avg_cost, $part_id]);
        }

        $db->commit();
        jsonResponse(['success' => true]);
    } catch (Exception $e) {
        $db->rollBack();
        jsonResponse(['error' => $e->getMessage()], 500);
    }
}

if ($action === 'edit_checkin') {
    $id = $_POST['id'] ?? 0;
    $part_id = $_POST['part_id'] ?? 0;
    $quantity = $_POST['quantity'] ?? 0;
    $gross_total = $_POST['gross_total'] ?? null;
    $unit_cost = $_POST['unit_cost'] ?? null;
    
    // Calculate the other value if only one is provided
    if ($gross_total !== null && $unit_cost === null) {
        $gross_total = floatval($gross_total);
        $unit_cost = $quantity > 0 ? $gross_total / $quantity : 0;
    } elseif ($unit_cost !== null && $gross_total === null) {
        $unit_cost = floatval($unit_cost);
        $gross_total = $unit_cost * $quantity;
    } elseif ($gross_total !== null && $unit_cost !== null) {
        $gross_total = floatval($gross_total);
        $unit_cost = $quantity > 0 ? $gross_total / $quantity : 0;
    }
    
    $total_cost = $gross_total;
    $supplier_name = $_POST['supplier_name'] ?? '';
    $purchase_date = $_POST['purchase_date'] ?? date('Y-m-d');
    $notes = $_POST['notes'] ?? '';
    
    $db->beginTransaction();
    
    try {
        // Get old quantity and received status
        $stmt = $db->prepare("SELECT quantity, received FROM inventory_checkins WHERE id = ?");
        $stmt->execute([$id]);
        $old_checkin = $stmt->fetch();

        if (!$old_checkin) {
            jsonResponse(['error' => 'Check-in not found'], 404);
        }

        // Update the record fields
        $stmt = $db->prepare("UPDATE inventory_checkins SET quantity = ?, unit_cost = ?, total_cost = ?, supplier_name = ?, purchase_date = ?, notes = ? WHERE id = ?");
        $stmt->execute([$quantity, $unit_cost, $total_cost, $supplier_name, $purchase_date, $notes, $id]);

        if ($old_checkin['received']) {
            $qty_diff = $quantity - $old_checkin['quantity'];

            // Adjust stock
            $stmt = $db->prepare("UPDATE parts SET current_stock = GREATEST(0, current_stock + ?) WHERE id = ?");
            $stmt->execute([$qty_diff, $part_id]);

            // Recalculate weighted average cost from received check-ins only
            $stmt = $db->prepare("
                SELECT SUM(quantity) as total_qty, SUM(quantity * unit_cost) as total_value
                FROM inventory_checkins
                WHERE part_id = ? AND received = 1
            ");
            $stmt->execute([$part_id]);
            $totals = $stmt->fetch();

            $new_avg_cost = $totals['total_qty'] > 0 ? $totals['total_value'] / $totals['total_qty'] : 0;

            $stmt = $db->prepare("UPDATE parts SET weighted_avg_cost = ? WHERE id = ?");
            $stmt->execute([$new_avg_cost, $part_id]);
        }

        $db->commit();
        jsonResponse(['success' => true]);
    } catch (Exception $e) {
        $db->rollBack();
        jsonResponse(['error' => $e->getMessage()], 500);
    }
}

if ($action === 'mark_received') {
    $id = $_POST['id'] ?? 0;
    $part_id = $_POST['part_id'] ?? 0;

    $db->beginTransaction();

    try {
        $stmt = $db->prepare("SELECT * FROM inventory_checkins WHERE id = ? AND received = 0");
        $stmt->execute([$id]);
        $checkin = $stmt->fetch();

        if (!$checkin) {
            $db->rollBack();
            jsonResponse(['error' => 'Pending order not found or already received'], 404);
        }

        $quantity = (float)$checkin['quantity'];
        $unit_cost = (float)$checkin['unit_cost'];

        // Mark as received
        $stmt = $db->prepare("UPDATE inventory_checkins SET received = 1, received_at = NOW() WHERE id = ?");
        $stmt->execute([$id]);

        // Get current stock and WAC
        $stmt = $db->prepare("SELECT current_stock, weighted_avg_cost FROM parts WHERE id = ?");
        $stmt->execute([$part_id]);
        $part = $stmt->fetch();

        $current_stock = (float)$part['current_stock'];
        $current_avg_cost = (float)($part['weighted_avg_cost'] ?? 0);

        // Calculate new WAC
        $old_value = $current_stock * $current_avg_cost;
        $new_value = $quantity * $unit_cost;
        $total_quantity = $current_stock + $quantity;
        $new_avg_cost = $total_quantity > 0 ? ($old_value + $new_value) / $total_quantity : 0;

        // Update stock and WAC
        $stmt = $db->prepare("UPDATE parts SET current_stock = current_stock + ?, weighted_avg_cost = ? WHERE id = ?");
        $stmt->execute([$quantity, $new_avg_cost, $part_id]);

        $db->commit();

        // Collect project IDs that need WC sync
        $stmt = $db->prepare("SELECT DISTINCT project_id FROM project_parts WHERE part_id = ?");
        $stmt->execute([$part_id]);
        $project_ids = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));

        // skip_wc=1: return immediately so the UI can update; caller fires WC sync in the background
        if (!empty($_POST['skip_wc'])) {
            jsonResponse(['success' => true, 'new_avg_cost' => $new_avg_cost, 'project_ids' => $project_ids]);
        }

        $sync_results = [];
        if (!empty($project_ids)) {
            require_once 'woocommerce_sync.php';
            foreach ($project_ids as $pid) {
                $sync_results[] = wc_sync_project($db, $pid);
            }
        }

        jsonResponse(['success' => true, 'new_avg_cost' => $new_avg_cost, 'sync_results' => $sync_results]);
    } catch (Exception $e) {
        $db->rollBack();
        jsonResponse(['error' => $e->getMessage()], 500);
    }
}

if ($action === 'get_checkins') {
    $part_id = $_GET['part_id'] ?? null;
    
    if ($part_id) {
        $stmt = $db->prepare("SELECT * FROM inventory_checkins WHERE part_id = ? ORDER BY purchase_date DESC");
        $stmt->execute([$part_id]);
    } else {
        $stmt = $db->query("
            SELECT ic.*, p.part_name, p.part_number 
            FROM inventory_checkins ic 
            JOIN parts p ON ic.part_id = p.id 
            ORDER BY ic.purchase_date DESC 
            LIMIT 50
        ");
    }
    
    jsonResponse($stmt->fetchAll());
}

// Orders
if ($action === 'get_orders') {
    $orders = $db->query("
        SELECT o.*,
               GROUP_CONCAT(CONCAT(p.project_name,
                   IF(oi.variation_combo_key IS NOT NULL AND oi.variation_combo_key != '',
                      CONCAT(' (', REPLACE(REPLACE(oi.variation_combo_key, ':', ': '), '|', ', '), ')'), ''),
                   ' ×', oi.quantity) SEPARATOR ', ') AS items_summary,
               SUM(oi.quantity)   AS total_quantity,
               SUM(oi.line_total) AS total_price
        FROM orders o
        LEFT JOIN order_items oi ON oi.order_id = o.id
        LEFT JOIN projects p ON p.id = oi.project_id
        GROUP BY o.id
        ORDER BY o.order_date DESC
    ")->fetchAll();
    jsonResponse($orders);
}

if ($action === 'get_order') {
    $id = (int)($_GET['id'] ?? 0);
    $stmt = $db->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$id]);
    $order = $stmt->fetch();
    if (!$order) jsonResponse(['error' => 'Order not found'], 404);

    $stmt = $db->prepare("
        SELECT oi.*, p.project_name, p.ship_weight_oz, p.pkg_length, p.pkg_width, p.pkg_height, p.retail_price
        FROM order_items oi
        JOIN projects p ON p.id = oi.project_id
        WHERE oi.order_id = ?
        ORDER BY oi.id
    ");
    $stmt->execute([$id]);
    $order['items'] = $stmt->fetchAll();

    jsonResponse($order);
}

// Manual order entry (order_detail.php Save Changes, index.php New Order modal,
// quick_order.php) — always a single line item; WooCommerce orders with
// multiple line items are captured by wc_upsert_order() in woocommerce_sync.php.
// Manual/editable order entry — index.php "New Order" modal and order_detail.php
// "Save Changes" both post here. `items` is a JSON array of
// {id?, project_id, combo_key?, quantity, price} (price = line total, not unit
// price). Items missing from the array on an update are removed (restoring any
// deducted inventory first); new ones (no id) are inserted.
if ($action === 'save_order') {
    require_once 'woocommerce_sync.php';

    $id = $_POST['id'] ?? null;
    $order_number = $_POST['order_number'] ?? 'ORD-' . date('Ymd') . '-' . rand(1000, 9999);
    $customer_name = $_POST['customer_name'] ?? '';
    $customer_email = $_POST['customer_email'] ?? '';
    $customer_phone = $_POST['customer_phone'] ?? '';
    $customer_callsign = $_POST['customer_callsign'] ?? '';
    $order_date = $_POST['order_date'] ?? date('Y-m-d');
    $status = $_POST['status'] ?? 'pending';
    $shipping_address = $_POST['shipping_address'] ?? '';
    $notes = $_POST['notes'] ?? '';
    $source = $_POST['source'] ?? 'manual';
    $tracking_number = $_POST['tracking_number'] ?? '';
    $shipping_charge = $_POST['shipping_charge'] ?? 0;
    $ship_street  = $_POST['ship_street']  ?? null;
    $ship_street2 = $_POST['ship_street2'] ?? null;
    $ship_city    = $_POST['ship_city']    ?? null;
    $ship_state   = $_POST['ship_state']   ?? null;
    $ship_zip     = $_POST['ship_zip']     ?? null;
    $mail_service = $_POST['mail_service'] ?? null;

    $items = json_decode($_POST['items'] ?? '[]', true);
    if (!is_array($items) || empty($items)) {
        jsonResponse(['error' => 'At least one order item is required'], 400);
        exit;
    }

    $should_deduct = in_array($status, ['shipped', 'completed']);

    $db->beginTransaction();
    try {
        if ($id) {
            $stmt = $db->prepare("SELECT status FROM orders WHERE id = ?");
            $stmt->execute([$id]);
            $old_order = $stmt->fetch();
            if (!$old_order) throw new Exception('Order not found');
            $old_status = $old_order['status'];

            $db->prepare("
                UPDATE orders SET order_number = ?, customer_name = ?, customer_email = ?, customer_phone = ?, customer_callsign = ?,
                    order_date = ?, status = ?, shipping_address = ?, notes = ?, source = ?, tracking_number = ?, shipping_charge = ?,
                    ship_street = ?, ship_street2 = ?, ship_city = ?, ship_state = ?, ship_zip = ?, mail_service = ?
                WHERE id = ?
            ")->execute([$order_number, $customer_name, $customer_email, $customer_phone, $customer_callsign,
                $order_date, $status, $shipping_address, $notes, $source, $tracking_number, $shipping_charge,
                $ship_street, $ship_street2, $ship_city, $ship_state, $ship_zip, $mail_service, $id]);
            $order_id = (int) $id;
        } else {
            $db->prepare("
                INSERT INTO orders (order_number, customer_name, customer_email, customer_phone, customer_callsign,
                    order_date, status, shipping_address, notes, source, tracking_number, shipping_charge,
                    ship_street, ship_street2, ship_city, ship_state, ship_zip, mail_service)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ")->execute([$order_number, $customer_name, $customer_email, $customer_phone, $customer_callsign,
                $order_date, $status, $shipping_address, $notes, $source, $tracking_number, $shipping_charge,
                $ship_street, $ship_street2, $ship_city, $ship_state, $ship_zip, $mail_service]);
            $order_id   = (int) $db->lastInsertId();
            $old_status = null;
        }

        $stmt = $db->prepare("SELECT id, project_id, quantity, variation_combo_key, inventory_deducted FROM order_items WHERE order_id = ?");
        $stmt->execute([$order_id]);
        $existingItems = [];
        foreach ($stmt->fetchAll() as $row) $existingItems[(int) $row['id']] = $row;

        $keptIds = [];
        foreach ($items as $item) {
            $item_project_id  = (int) ($item['project_id'] ?? 0);
            $item_combo_key   = trim($item['combo_key'] ?? '');
            $item_qty         = max(1, (int) ($item['quantity'] ?? 1));
            $item_price       = (float) ($item['price'] ?? 0);
            $item_unit        = $item_qty > 0 ? round($item_price / $item_qty, 2) : $item_price;
            $existing_item_id = (int) ($item['id'] ?? 0);

            if ($existing_item_id && isset($existingItems[$existing_item_id])) {
                $old = $existingItems[$existing_item_id];
                $already_deducted = (bool) $old['inventory_deducted'];

                $db->prepare("UPDATE order_items SET project_id = ?, variation_combo_key = ?, quantity = ?, unit_price = ?, line_total = ? WHERE id = ?")
                   ->execute([$item_project_id, $item_combo_key ?: null, $item_qty, $item_unit, $item_price, $existing_item_id]);

                if ($should_deduct && !$already_deducted) {
                    wc_deduct_bom_inventory($db, $item_project_id, $item_qty, $item_combo_key ?: null);
                    $db->prepare("UPDATE order_items SET inventory_deducted = 1 WHERE id = ?")->execute([$existing_item_id]);
                } elseif (!$should_deduct && $already_deducted) {
                    wc_restore_bom_inventory($db, $old['project_id'], $old['quantity'], $old['variation_combo_key'] ?: null);
                    $db->prepare("UPDATE order_items SET inventory_deducted = 0 WHERE id = ?")->execute([$existing_item_id]);
                }
                $keptIds[] = $existing_item_id;
            } else {
                $inventory_deducted = $should_deduct ? 1 : 0;
                $db->prepare("INSERT INTO order_items (order_id, project_id, variation_combo_key, quantity, unit_price, line_total, inventory_deducted) VALUES (?, ?, ?, ?, ?, ?, ?)")
                   ->execute([$order_id, $item_project_id, $item_combo_key ?: null, $item_qty, $item_unit, $item_price, $inventory_deducted]);
                if ($inventory_deducted) {
                    wc_deduct_bom_inventory($db, $item_project_id, $item_qty, $item_combo_key ?: null);
                }
                $keptIds[] = (int) $db->lastInsertId();
            }
        }

        // Anything present before but missing from this submit was removed in the UI
        foreach ($existingItems as $eid => $old) {
            if (in_array($eid, $keptIds)) continue;
            if ($old['inventory_deducted']) {
                wc_restore_bom_inventory($db, $old['project_id'], $old['quantity'], $old['variation_combo_key'] ?: null);
            }
            $db->prepare("DELETE FROM order_items WHERE id = ?")->execute([$eid]);
        }

        $db->commit();
        jsonResponse([
            'success'        => true,
            'id'             => $order_id,
            'status_changed' => $old_status !== null && $old_status !== $status,
            'new_status'     => $status,
        ]);
    } catch (Exception $e) {
        $db->rollBack();
        jsonResponse(['error' => $e->getMessage()], 500);
    }
}

if ($action === 'delete_order') {
    require_once 'woocommerce_sync.php';
    $id = (int)($_POST['id'] ?? 0);

    // Restore any deducted inventory before the order (and its items, via FK cascade) is removed.
    $stmt = $db->prepare("SELECT project_id, quantity, variation_combo_key FROM order_items WHERE order_id = ? AND inventory_deducted = 1");
    $stmt->execute([$id]);
    foreach ($stmt->fetchAll() as $item) {
        wc_restore_bom_inventory($db, $item['project_id'], $item['quantity'], $item['variation_combo_key'] ?: null);
    }

    $db->prepare("DELETE FROM orders WHERE id = ?")->execute([$id]);
    jsonResponse(['success' => true]);
}

// Refresh all order + line-item data from WooCommerce's own order history.
// Data only — never touches parts.current_stock (inventory has moved on since
// these orders were placed; this is for accurate reporting, not re-deduction).
if ($action === 'wc_reconcile_orders') {
    require_once 'woocommerce_sync.php';
    set_time_limit(240);

    $page = 1;
    $ordersProcessed = 0;
    $errors = [];
    while (true) {
        $wcOrders = wc_fetch_orders_page($page, 100);
        if (empty($wcOrders)) break;

        foreach ($wcOrders as $wcOrder) {
            $result = wc_upsert_order($db, $wcOrder, false);
            if (isset($result['error'])) {
                $errors[] = $result;
            } else {
                $ordersProcessed++;
            }
        }

        if (count($wcOrders) < 100 || $page > 50) break;
        $page++;
    }

    jsonResponse(['success' => true, 'orders_processed' => $ordersProcessed, 'errors' => $errors]);
}

// Send invoice email
if ($action === 'send_invoice') {
    $order_id = (int)($_POST['order_id'] ?? 0);
    $stmt = $db->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();
    if (!$order) jsonResponse(['error' => 'Order not found'], 404);
    if (!$order['customer_email']) jsonResponse(['error' => 'No email address on file for this customer.'], 400);

    $stmt = $db->prepare("SELECT oi.*, p.project_name FROM order_items oi JOIN projects p ON p.id = oi.project_id WHERE oi.order_id = ? ORDER BY oi.id");
    $stmt->execute([$order_id]);
    $order['items'] = $stmt->fetchAll();

    $subject = "Invoice: " . $order['order_number'] . " — KI6CR Ham Radio Kits";

    // Build HTML invoice for email
    ob_start();
    include __DIR__ . '/invoice.php';
    $html = ob_get_clean();

    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: Chris Reddick - KI6CR <cr@christopherreddick.com>\r\n";
    $headers .= "Reply-To: cr@christopherreddick.com\r\n";

    $sent = mail($order['customer_email'], $subject, $html, $headers);
    jsonResponse($sent
        ? ['success' => true, 'message' => "Invoice sent to " . $order['customer_email']]
        : ['error' => 'mail() failed — check server mail config.']
    );
}

// Tasks
if ($action === 'get_tasks') {
    $project_id = (int)($_GET['project_id'] ?? 0);
    if (!$project_id) jsonResponse(['error' => 'project_id required'], 400);
    $stmt = $db->prepare("SELECT * FROM project_tasks WHERE project_id = ? ORDER BY COALESCE(parent_id, 0), sort_order, id");
    $stmt->execute([$project_id]);
    jsonResponse($stmt->fetchAll());
}

if ($action === 'save_task') {
    $id         = (int)($_POST['id'] ?? 0);
    $project_id = (int)($_POST['project_id'] ?? 0);
    $parent_id  = $_POST['parent_id'] !== '' ? (int)$_POST['parent_id'] : null;
    $title      = trim($_POST['title'] ?? '');
    $notes      = trim($_POST['notes'] ?? '');

    if (!$project_id || $title === '') jsonResponse(['error' => 'project_id and title required'], 400);

    if ($id) {
        $stmt = $db->prepare("UPDATE project_tasks SET title=?, notes=?, updated_at=NOW() WHERE id=? AND project_id=?");
        $stmt->execute([$title, $notes, $id, $project_id]);
        jsonResponse(['success' => true, 'id' => $id]);
    } else {
        // Place at end of sibling group
        $stmt = $db->prepare("SELECT COALESCE(MAX(sort_order),0)+1 FROM project_tasks WHERE project_id=? AND " . ($parent_id === null ? "parent_id IS NULL" : "parent_id=?"));
        if ($parent_id === null) {
            $stmt->execute([$project_id]);
        } else {
            $stmt->execute([$project_id, $parent_id]);
        }
        $sort = (int)$stmt->fetchColumn();
        $stmt = $db->prepare("INSERT INTO project_tasks (project_id, parent_id, title, notes, sort_order) VALUES (?,?,?,?,?)");
        $stmt->execute([$project_id, $parent_id, $title, $notes, $sort]);
        jsonResponse(['success' => true, 'id' => $db->lastInsertId()]);
    }
}

if ($action === 'toggle_task') {
    $id      = (int)($_POST['id'] ?? 0);
    $is_done = (int)($_POST['is_done'] ?? 0);
    $stmt = $db->prepare("UPDATE project_tasks SET is_done=?, updated_at=NOW() WHERE id=?");
    $stmt->execute([$is_done, $id]);
    jsonResponse(['success' => true]);
}

if ($action === 'delete_task') {
    $id = (int)($_POST['id'] ?? 0);
    // Children cascade via FK; delete parent first to let FK do the work
    $stmt = $db->prepare("DELETE FROM project_tasks WHERE id=?");
    $stmt->execute([$id]);
    jsonResponse(['success' => true]);
}

if ($action === 'reorder_tasks') {
    $items = json_decode($_POST['items'] ?? '[]', true);
    if (!is_array($items)) jsonResponse(['error' => 'invalid items'], 400);
    $stmt = $db->prepare("UPDATE project_tasks SET sort_order=? WHERE id=?");
    foreach ($items as $item) {
        $stmt->execute([(int)$item['sort_order'], (int)$item['id']]);
    }
    jsonResponse(['success' => true]);
}

// Change password
if ($action === 'change_password') {
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    
    $stmt = $db->prepare("SELECT password_hash FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($current, $user['password_hash'])) {
        $newHash = password_hash($new, PASSWORD_DEFAULT);
        $stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        $stmt->execute([$newHash, $_SESSION['user_id']]);
        jsonResponse(['success' => true]);
    } else {
        jsonResponse(['error' => 'Current password is incorrect'], 401);
    }
}

// ── KH1 Beta Feedback Admin ───────────────────────────────────────────────────

if ($action === 'kh1_beta_list') {
    $builders = $db->query("
        SELECT
            s.callsign,
            s.email,
            s.created_at,
            COUNT(r.id)                                          AS steps_saved,
            SUM(r.rating = 3)                                    AS trouble_count,
            SUM(r.reviewed = 0)                                  AS unreviewed_count,
            MAX(r.updated_at)                                    AS last_active
        FROM kh1_beta_sessions s
        LEFT JOIN kh1_beta_responses r ON r.callsign = s.callsign
        GROUP BY s.callsign, s.email, s.created_at
        ORDER BY last_active DESC
    ")->fetchAll();

    $total_unreviewed = (int) $db->query("SELECT COUNT(*) FROM kh1_beta_responses WHERE reviewed = 0")->fetchColumn();

    // Step-level issue summary (count of 'had trouble' per step across all builders)
    $step_issues = $db->query("
        SELECT step_key, COUNT(*) AS trouble_builders
        FROM kh1_beta_responses
        WHERE rating = 3
        GROUP BY step_key
        ORDER BY trouble_builders DESC
    ")->fetchAll();

    // Packaging summary
    $pkg = $db->query("
        SELECT
            SUM(packaging_intact = 0) AS damaged_pkg,
            SUM(tools_in_box = 0)     AS missing_tools,
            SUM(parts_undamaged = 0)  AS damaged_parts,
            COUNT(*)                  AS total_pkg_responses
        FROM kh1_beta_responses
        WHERE step_key = 'packaging'
    ")->fetch();

    jsonResponse(['builders' => $builders, 'step_issues' => $step_issues, 'packaging' => $pkg, 'total_unreviewed' => $total_unreviewed]);
}

if ($action === 'kh1_beta_detail') {
    $callsign = strtoupper(preg_replace('/[^A-Za-z0-9\/]/', '', $_GET['callsign'] ?? ''));
    if (strlen($callsign) < 3) jsonResponse(['error' => 'Invalid callsign'], 400);
    $stmt = $db->prepare("SELECT * FROM kh1_beta_responses WHERE callsign = ? ORDER BY step_key");
    $stmt->execute([$callsign]);
    $responses = $stmt->fetchAll();
    $video_exts = ['mp4','mov','webm','avi','mkv','m4v'];
    $stmt = $db->prepare("SELECT id, step_key, filename FROM kh1_beta_photos WHERE callsign = ? ORDER BY created_at ASC");
    $stmt->execute([$callsign]);
    $photos_by_step = [];
    foreach ($stmt->fetchAll() as $p) {
        $ext = strtolower(pathinfo($p['filename'], PATHINFO_EXTENSION));
        $photos_by_step[$p['step_key']][] = [
            'id'   => (int)$p['id'],
            'url'  => 'kh1_uploads/' . $p['filename'],
            'type' => in_array($ext, $video_exts) ? 'video' : 'image',
        ];
    }
    jsonResponse(['responses' => $responses, 'photos' => $photos_by_step]);
}

if ($action === 'kh1_beta_save_reply') {
    $KH1_STEPS = [
        'packaging','step01','step02','step03','step04','step05','step06','step07',
        'step08','step09','step10','step11','step12','step13','step14','step15',
        'step16','step17','general'
    ];
    $callsign = strtoupper(preg_replace('/[^A-Za-z0-9\/]/', '', $_POST['callsign'] ?? ''));
    $step_key = $_POST['step_key'] ?? '';
    $reply    = substr(trim($_POST['reply'] ?? ''), 0, 2000);
    if (strlen($callsign) < 3 || !in_array($step_key, $KH1_STEPS, true)) {
        jsonResponse(['error' => 'Invalid callsign or step'], 400);
    }
    $stmt = $db->prepare("
        INSERT INTO kh1_beta_responses (callsign, step_key, admin_reply, admin_reply_at)
        VALUES (?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE
            admin_reply    = VALUES(admin_reply),
            admin_reply_at = NOW()
    ");
    $stmt->execute([$callsign, $step_key, $reply]);
    jsonResponse(['success' => true]);
}

if ($action === 'kh1_beta_mark_reviewed') {
    $KH1_STEPS = [
        'packaging','step01','step02','step03','step04','step05','step06','step07',
        'step08','step09','step10','step11','step12','step13','step14','step15',
        'step16','step17','general'
    ];
    $callsign = strtoupper(preg_replace('/[^A-Za-z0-9\/]/', '', $_POST['callsign'] ?? ''));
    $step_key = $_POST['step_key'] ?? '';
    $reviewed = (int)(bool)($_POST['reviewed'] ?? 0);
    if (strlen($callsign) < 3 || !in_array($step_key, $KH1_STEPS, true)) {
        jsonResponse(['error' => 'Invalid callsign or step'], 400);
    }
    $stmt = $db->prepare("
        UPDATE kh1_beta_responses
        SET reviewed = ?, reviewed_at = ?
        WHERE callsign = ? AND step_key = ?
    ");
    $stmt->execute([$reviewed, $reviewed ? date('Y-m-d H:i:s') : null, $callsign, $step_key]);
    jsonResponse(['success' => true]);
}

if ($action === 'kh1_beta_mark_all_reviewed') {
    $callsign = strtoupper(preg_replace('/[^A-Za-z0-9\/]/', '', $_POST['callsign'] ?? ''));
    if (strlen($callsign) < 3) {
        jsonResponse(['error' => 'Invalid callsign'], 400);
    }
    $stmt = $db->prepare("
        UPDATE kh1_beta_responses
        SET reviewed = 1, reviewed_at = NOW()
        WHERE callsign = ? AND reviewed = 0
    ");
    $stmt->execute([$callsign]);
    jsonResponse(['success' => true, 'marked' => $stmt->rowCount()]);
}

jsonResponse(['error' => 'Invalid action'], 400);
?>
