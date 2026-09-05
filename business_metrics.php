<?php
/**
 * Business Metrics API
 * Returns comprehensive P&L and business statistics
 */

require_once 'config.php';
require_once 'woocommerce_sync.php'; // for wc_parse_combo_key() — pure parsing helper, no side effects

header('Content-Type: application/json');
requireLogin();

$action = $_GET['action'] ?? '';
$year = $_GET['year'] ?? 'all';

if (!in_array($action, ['get_business_metrics', 'get_project_pl'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid action']);
    exit;
}

try {
    $db = getDB();
    
    // Build year filter (queries below alias the orders table as `o`)
    $yearFilter = '';
    $params = [];
    if ($year !== 'all' && $year !== 'trailing') {
        $yearFilter = "WHERE YEAR(o.order_date) = ?";
        $params[] = $year;
    } elseif ($year === 'trailing') {
        $yearFilter = "WHERE o.order_date >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)";
    }

    if ($action === 'get_project_pl') {
        $project_id = (int) ($_GET['project_id'] ?? 0);
        if ($project_id <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'project_id is required']);
            exit;
        }

        $stmt = $db->prepare("SELECT id, project_name, status, retail_price FROM projects WHERE id = ?");
        $stmt->execute([$project_id]);
        $project = $stmt->fetch();
        if (!$project) {
            http_response_code(404);
            echo json_encode(['error' => 'Project not found']);
            exit;
        }

        // Per-unit BOM cost for a given combo_key (fixed parts + that combo's variable
        // parts), cached per combo since the same combo repeats across many order items.
        $bomCostCache = [];
        $unitCostFor = function (?string $combo_key) use ($db, $project_id, &$bomCostCache) {
            $key = $combo_key ?? '';
            if (isset($bomCostCache[$key])) return $bomCostCache[$key];

            $stmt = $db->prepare("
                SELECT pp.quantity_required, p.weighted_avg_cost
                FROM project_parts pp
                INNER JOIN parts p ON pp.part_id = p.id
                WHERE pp.project_id = ? AND pp.variation_attribute = ''
            ");
            $stmt->execute([$project_id]);
            $unitCost = 0;
            foreach ($stmt->fetchAll() as $part) {
                $unitCost += $part['quantity_required'] * $part['weighted_avg_cost'];
            }

            if ($key !== '') {
                foreach (wc_parse_combo_key($key) as $attr => $val) {
                    $stmt3 = $db->prepare("
                        SELECT pp.quantity_required, p.weighted_avg_cost
                        FROM project_parts pp
                        INNER JOIN parts p ON pp.part_id = p.id
                        WHERE pp.project_id = ? AND pp.variation_attribute = ? AND pp.variation_value = ?
                    ");
                    $stmt3->execute([$project_id, $attr, $val]);
                    foreach ($stmt3->fetchAll() as $part) {
                        $unitCost += $part['quantity_required'] * $part['weighted_avg_cost'];
                    }
                }
            }

            return $bomCostCache[$key] = $unitCost;
        };

        // Revenue / COGS / units sold for this project, within the selected period
        $itemQuery = "
            SELECT oi.order_id, oi.variation_combo_key, oi.quantity, oi.line_total
            FROM order_items oi
            INNER JOIN orders o ON o.id = oi.order_id
            " . ($yearFilter !== '' ? $yearFilter . " AND" : "WHERE") . " oi.project_id = ?
        ";
        $stmt = $db->prepare($itemQuery);
        $stmt->execute(array_merge($params, [$project_id]));
        $items = $stmt->fetchAll();

        $revenue = 0; $cogs = 0; $unitsSold = 0; $orderIds = [];
        foreach ($items as $item) {
            $revenue   += (float) $item['line_total'];
            $unitsSold += (int) $item['quantity'];
            $cogs      += $unitCostFor($item['variation_combo_key']) * $item['quantity'];
            $orderIds[$item['order_id']] = true;
        }
        $orderCount = count($orderIds);

        $grossProfit = $revenue - $cogs;
        $margin = $revenue > 0 ? ($grossProfit / $revenue) * 100 : 0;

        // Research/dev spend — all-time, same convention as the business-wide dashboard
        // (research is a sunk cost, not something that resets per reporting period)
        $stmt = $db->prepare("SELECT COALESCE(SUM(cost),0) FROM project_expenses WHERE project_id = ?");
        $stmt->execute([$project_id]);
        $researchExpenses = (float) $stmt->fetchColumn();

        // Promo/giveaway inventory — real cost (BOM cost of parts given away free)
        // that never shows up as revenue, all-time same as research spend above
        $stmt = $db->prepare("SELECT variation_combo_key, SUM(quantity) as qty FROM promo_giveaways WHERE project_id = ? GROUP BY variation_combo_key");
        $stmt->execute([$project_id]);
        $giveawayUnits = 0; $giveawayCost = 0;
        foreach ($stmt->fetchAll() as $row) {
            $giveawayUnits += (int) $row['qty'];
            $giveawayCost  += $unitCostFor($row['variation_combo_key']) * $row['qty'];
        }

        $totalInvested = $researchExpenses + $giveawayCost;
        $netProfit = $grossProfit - $totalInvested;
        $brokeEven = $totalInvested <= 0 || $grossProfit >= $totalInvested;
        $breakEvenRemaining = $brokeEven ? 0 : round($totalInvested - $grossProfit, 2);

        echo json_encode([
            'project_id'          => (int) $project['id'],
            'project_name'        => $project['project_name'],
            'status'              => $project['status'],
            'retail_price'        => (float) $project['retail_price'],
            'period'              => $year,
            'units_sold'          => $unitsSold,
            'order_count'         => $orderCount,
            'revenue'             => round($revenue, 2),
            'cogs'                => round($cogs, 2),
            'gross_profit'        => round($grossProfit, 2),
            'margin'              => round($margin, 2),
            'research_expenses'   => round($researchExpenses, 2),
            'giveaway_units'      => $giveawayUnits,
            'giveaway_cost'       => round($giveawayCost, 2),
            'total_invested'      => round($totalInvested, 2),
            'net_profit'          => round($netProfit, 2),
            'broke_even'          => $brokeEven,
            'breakeven_remaining' => $breakEvenRemaining,
        ]);
        exit;
    }

    // 1. INVENTORY VALUE (Cost)
    $stmt = $db->query("
        SELECT SUM(current_stock * weighted_avg_cost) as total_inventory_cost
        FROM parts
    ");
    $inventoryCost = $stmt->fetch()['total_inventory_cost'] ?? 0;
    
    // 2. INVENTORY VALUE (Potential Revenue - unrealized)
    // Buildable kit count per active project (bottleneck across BOM parts, same
    // logic as wc_calculate_available_qty / wc_calculate_variation_qty) * retail
    // price. Summing each BOM part's stock independently instead massively
    // overstates this, since a kit with N parts would get counted ~N times over.
    $activeProjects = $db->query("SELECT id, retail_price FROM projects WHERE status = 'active'")->fetchAll();
    $unrealizedRevenue = 0;
    foreach ($activeProjects as $proj) {
        $stmt = $db->prepare("
            SELECT DISTINCT variation_attribute, variation_value
            FROM project_parts WHERE project_id = ? AND variation_attribute != ''
        ");
        $stmt->execute([$proj['id']]);
        $attributes = [];
        foreach ($stmt->fetchAll() as $row) {
            $attributes[$row['variation_attribute']][] = $row['variation_value'];
        }

        if (empty($attributes)) {
            $buildable = wc_calculate_available_qty($db, $proj['id']);
        } else {
            // Variable kit — sum buildable across every combination of variation
            // values, since each combo is an independently sellable variant.
            $combos = [[]];
            foreach ($attributes as $attr => $values) {
                $new_combos = [];
                foreach ($combos as $combo) {
                    foreach ($values as $val) {
                        $new_combos[] = $combo + [$attr => $val];
                    }
                }
                $combos = $new_combos;
            }
            $buildable = 0;
            foreach ($combos as $combo) {
                $buildable += wc_calculate_variation_qty($db, $proj['id'], wc_build_combo_key($combo));
            }
        }

        $unrealizedRevenue += $buildable * $proj['retail_price'];
    }
    
    // 3. ORDERS - REVENUE (sum of line items actually sold)
    $stmt = $db->prepare("
        SELECT SUM(oi.line_total) as total_revenue, COUNT(DISTINCT o.id) as order_count
        FROM orders o
        JOIN order_items oi ON oi.order_id = o.id
        $yearFilter
    ");
    $stmt->execute($params);
    $orderData = $stmt->fetch();
    $totalRevenue = $orderData['total_revenue'] ?? 0;
    $orderCount = $orderData['order_count'] ?? 0;

    // 4. ORDER ITEMS - COST (BOM cost of the specific variation actually sold —
    // fixed parts + only the variable parts matching that item's variation_combo_key)
    $stmt = $db->prepare("
        SELECT oi.project_id, oi.variation_combo_key, oi.quantity
        FROM order_items oi
        INNER JOIN orders o ON o.id = oi.order_id
        $yearFilter
    ");
    $stmt->execute($params);
    $soldItems = $stmt->fetchAll();

    $totalCOGS = 0; // Cost of Goods Sold
    $bomCostCache = []; // "project_id|combo_key" -> per-unit BOM cost

    foreach ($soldItems as $item) {
        $cacheKey = $item['project_id'] . '|' . ($item['variation_combo_key'] ?? '');
        if (!isset($bomCostCache[$cacheKey])) {
            $stmt2 = $db->prepare("
                SELECT pp.quantity_required, p.weighted_avg_cost
                FROM project_parts pp
                INNER JOIN parts p ON pp.part_id = p.id
                WHERE pp.project_id = ? AND pp.variation_attribute = ''
            ");
            $stmt2->execute([$item['project_id']]);
            $unitCost = 0;
            foreach ($stmt2->fetchAll() as $part) {
                $unitCost += $part['quantity_required'] * $part['weighted_avg_cost'];
            }

            if (!empty($item['variation_combo_key'])) {
                foreach (wc_parse_combo_key($item['variation_combo_key']) as $attr => $val) {
                    $stmt3 = $db->prepare("
                        SELECT pp.quantity_required, p.weighted_avg_cost
                        FROM project_parts pp
                        INNER JOIN parts p ON pp.part_id = p.id
                        WHERE pp.project_id = ? AND pp.variation_attribute = ? AND pp.variation_value = ?
                    ");
                    $stmt3->execute([$item['project_id'], $attr, $val]);
                    foreach ($stmt3->fetchAll() as $part) {
                        $unitCost += $part['quantity_required'] * $part['weighted_avg_cost'];
                    }
                }
            }
            $bomCostCache[$cacheKey] = $unitCost;
        }
        $totalCOGS += $bomCostCache[$cacheKey] * $item['quantity'];
    }

    // Shipping is an order-level cost — sum once per order, not per line item
    $stmt = $db->prepare("SELECT SUM(o.shipping_charge) as total_shipping FROM orders o $yearFilter");
    $stmt->execute($params);
    $totalShipping = (float) ($stmt->fetch()['total_shipping'] ?? 0);

    // 5. INVENTORY PURCHASES (money spent on inventory)
    $purchaseQuery = "
        SELECT SUM(quantity * unit_cost) as total_purchases
        FROM inventory_checkins
    ";
    
    if ($year !== 'all' && $year !== 'trailing') {
        $purchaseQuery .= " WHERE YEAR(purchase_date) = ?";
        $stmt = $db->prepare($purchaseQuery);
        $stmt->execute([$year]);
    } elseif ($year === 'trailing') {
        $purchaseQuery .= " WHERE purchase_date >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)";
        $stmt = $db->query($purchaseQuery);
    } else {
        $stmt = $db->query($purchaseQuery);
    }
    
    $totalPurchases = $stmt->fetch()['total_purchases'] ?? 0;
    
    // 6. RESEARCH EXPENSES (across all time — not filtered by period)
    $stmt = $db->query("SELECT SUM(cost) as total_research FROM project_expenses");
    $totalResearchExpenses = $stmt->fetch()['total_research'] ?? 0;

    // 7. OVERHEAD / BUSINESS EXPENSES (filtered by period)
    $overheadQuery = "SELECT SUM(cost) as total_overhead FROM business_expenses";
    if ($year !== 'all' && $year !== 'trailing') {
        $overheadQuery .= " WHERE YEAR(expense_date) = ?";
        $stmt = $db->prepare($overheadQuery);
        $stmt->execute([$year]);
    } elseif ($year === 'trailing') {
        $overheadQuery .= " WHERE expense_date >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)";
        $stmt = $db->query($overheadQuery);
    } else {
        $stmt = $db->query($overheadQuery);
    }
    $totalOverheadExpenses = $stmt->fetch()['total_overhead'] ?? 0;

    // 8. PROFIT CALCULATIONS
    $grossProfit = $totalRevenue - $totalCOGS;
    $netProfit = $grossProfit - $totalShipping - $totalResearchExpenses - $totalOverheadExpenses;
    $profitMargin = $totalRevenue > 0 ? ($grossProfit / $totalRevenue) * 100 : 0;
    
    // 7. ORDERS BY STATUS
    $statusQuery = "
        SELECT
            o.status as status,
            COUNT(DISTINCT o.id) as count,
            SUM(oi.line_total) as revenue
        FROM orders o
        INNER JOIN order_items oi ON oi.order_id = o.id
        $yearFilter
        GROUP BY o.status
    ";
    $stmt = $db->prepare($statusQuery);
    $stmt->execute($params);
    $ordersByStatus = $stmt->fetchAll();

    // 8. TOP SELLING PROJECTS
    $topProjectsQuery = "
        SELECT
            pr.id as project_id,
            pr.project_name,
            COUNT(DISTINCT o.id) as orders,
            SUM(oi.quantity) as units_sold,
            SUM(oi.line_total) as revenue
        FROM order_items oi
        INNER JOIN orders o ON o.id = oi.order_id
        INNER JOIN projects pr ON oi.project_id = pr.id
        $yearFilter
        GROUP BY oi.project_id
        ORDER BY revenue DESC
        LIMIT 5
    ";
    $stmt = $db->prepare($topProjectsQuery);
    $stmt->execute($params);
    $topProjects = $stmt->fetchAll();
    
    // Return comprehensive metrics
    $metrics = [
        'period' => $year,
        'inventory' => [
            'cost' => round($inventoryCost, 2),
            'unrealized_revenue' => round($unrealizedRevenue, 2)
        ],
        'orders' => [
            'count' => $orderCount,
            'revenue' => round($totalRevenue, 2),
            'cogs' => round($totalCOGS, 2),
            'shipping' => round($totalShipping, 2)
        ],
        'profit' => [
            'gross' => round($grossProfit, 2),
            'net' => round($netProfit, 2),
            'margin' => round($profitMargin, 2),
            'research_expenses' => round($totalResearchExpenses, 2),
            'overhead_expenses' => round($totalOverheadExpenses, 2)
        ],
        'purchases' => round($totalPurchases, 2),
        'orders_by_status' => $ordersByStatus,
        'top_projects' => $topProjects
    ];
    
    echo json_encode($metrics);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
