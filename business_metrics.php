<?php
/**
 * Business Metrics API
 * Returns comprehensive P&L and business statistics
 */

require_once 'config.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
$year = $_GET['year'] ?? 'all';

if ($action !== 'get_business_metrics') {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid action']);
    exit;
}

try {
    $db = getDB();
    
    // Build year filter
    $yearFilter = '';
    $params = [];
    if ($year !== 'all' && $year !== 'trailing') {
        $yearFilter = "WHERE YEAR(order_date) = ?";
        $params[] = $year;
    } elseif ($year === 'trailing') {
        $yearFilter = "WHERE order_date >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)";
    }
    
    // 1. INVENTORY VALUE (Cost)
    $stmt = $db->query("
        SELECT SUM(current_stock * weighted_avg_cost) as total_inventory_cost
        FROM parts
    ");
    $inventoryCost = $stmt->fetch()['total_inventory_cost'] ?? 0;
    
    // 2. INVENTORY VALUE (Potential Revenue - unrealized)
    // Sum of all parts in all active projects * retail price
    $stmt = $db->query("
        SELECT 
            SUM(p.current_stock / NULLIF(pp.quantity_required, 0) * pr.retail_price) as potential_revenue
        FROM parts p
        INNER JOIN project_parts pp ON p.id = pp.part_id
        INNER JOIN projects pr ON pp.project_id = pr.id
        WHERE pr.status = 'active'
        GROUP BY p.id
    ");
    $unrealizedRevenue = 0;
    while ($row = $stmt->fetch()) {
        $unrealizedRevenue += $row['potential_revenue'] ?? 0;
    }
    
    // 3. ORDERS - REVENUE (price_paid)
    $stmt = $db->prepare("
        SELECT 
            SUM(price_paid) as total_revenue,
            COUNT(*) as order_count
        FROM orders
        $yearFilter
    ");
    $stmt->execute($params);
    $orderData = $stmt->fetch();
    $totalRevenue = $orderData['total_revenue'] ?? 0;
    $orderCount = $orderData['order_count'] ?? 0;
    
    // 4. ORDERS - COST (BOM cost of items sold)
    $costQuery = "
        SELECT 
            o.id,
            o.quantity,
            o.shipping_charge,
            o.project_id
        FROM orders o
        $yearFilter
    ";
    $stmt = $db->prepare($costQuery);
    $stmt->execute($params);
    $orders = $stmt->fetchAll();
    
    $totalCOGS = 0; // Cost of Goods Sold
    $totalShipping = 0;
    
    foreach ($orders as $order) {
        // Get BOM for this project
        $stmt = $db->prepare("
            SELECT pp.quantity_required, p.weighted_avg_cost
            FROM project_parts pp
            INNER JOIN parts p ON pp.part_id = p.id
            WHERE pp.project_id = ?
        ");
        $stmt->execute([$order['project_id']]);
        $bomParts = $stmt->fetchAll();
        
        $orderCost = 0;
        foreach ($bomParts as $part) {
            $orderCost += $part['quantity_required'] * $part['weighted_avg_cost'];
        }
        
        $totalCOGS += $orderCost * $order['quantity'];
        $totalShipping += $order['shipping_charge'];
    }
    
    // 5. INVENTORY PURCHASES (money spent on inventory)
    $purchaseQuery = "
        SELECT SUM(quantity * unit_cost) as total_purchases
        FROM inventory_checkins
    ";
    
    if ($year !== 'all' && $year !== 'trailing') {
        $purchaseQuery .= " WHERE YEAR(check_in_date) = ?";
        $stmt = $db->prepare($purchaseQuery);
        $stmt->execute([$year]);
    } elseif ($year === 'trailing') {
        $purchaseQuery .= " WHERE check_in_date >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)";
        $stmt = $db->query($purchaseQuery);
    } else {
        $stmt = $db->query($purchaseQuery);
    }
    
    $totalPurchases = $stmt->fetch()['total_purchases'] ?? 0;
    
    // 6. RESEARCH EXPENSES (across all time — not filtered by period)
    $stmt = $db->query("SELECT SUM(cost) as total_research FROM project_expenses");
    $totalResearchExpenses = $stmt->fetch()['total_research'] ?? 0;

    // 7. PROFIT CALCULATIONS
    $grossProfit = $totalRevenue - $totalCOGS;
    $netProfit = $grossProfit - $totalShipping - $totalResearchExpenses;
    $profitMargin = $totalRevenue > 0 ? ($grossProfit / $totalRevenue) * 100 : 0;
    
    // 7. ORDERS BY STATUS
    $statusQuery = "
        SELECT 
            status,
            COUNT(*) as count,
            SUM(price_paid) as revenue
        FROM orders
        $yearFilter
        GROUP BY status
    ";
    $stmt = $db->prepare($statusQuery);
    $stmt->execute($params);
    $ordersByStatus = $stmt->fetchAll();
    
    // 8. TOP SELLING PROJECTS
    $topProjectsQuery = "
        SELECT 
            pr.project_name,
            COUNT(o.id) as orders,
            SUM(o.quantity) as units_sold,
            SUM(o.price_paid) as revenue
        FROM orders o
        INNER JOIN projects pr ON o.project_id = pr.id
        $yearFilter
        GROUP BY o.project_id
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
            'research_expenses' => round($totalResearchExpenses, 2)
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
