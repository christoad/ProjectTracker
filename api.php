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
        'low_stock_count' => $db->query("SELECT COUNT(*) FROM parts WHERE current_stock <= min_stock_level")->fetchColumn(),
        'pending_orders' => $db->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn(),
        'low_stock_parts' => $db->query("SELECT * FROM parts WHERE current_stock <= min_stock_level ORDER BY current_stock ASC LIMIT 10")->fetchAll(),
        'recent_orders' => $db->query("SELECT o.*, p.project_name FROM orders o JOIN projects p ON o.project_id = p.id ORDER BY o.created_at DESC LIMIT 5")->fetchAll(),
    ];
    jsonResponse($stats);
}

// Projects
if ($action === 'get_projects') {
    $projects = $db->query("
        SELECT p.*, COUNT(DISTINCT pp.part_id) as parts_count 
        FROM projects p
        LEFT JOIN project_parts pp ON p.id = pp.project_id
        GROUP BY p.id
        ORDER BY p.created_at DESC
    ")->fetchAll();
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
        ");
        $stmt->execute([$id]);
        $project['parts'] = $stmt->fetchAll();

        // Get research expenses for this project
        $stmt = $db->prepare("SELECT * FROM project_expenses WHERE project_id = ? ORDER BY expense_date DESC, created_at DESC");
        $stmt->execute([$id]);
        $project['expenses'] = $stmt->fetchAll();
        $project['total_expenses'] = array_sum(array_column($project['expenses'], 'cost'));

        // Calculate BOM costs and buildable quantity
        $total_bom_cost = 0;
        $min_buildable = PHP_INT_MAX;
        
        $actual_inventory_value = 0;  // Track actual value of inventory in stock
        
        foreach ($project['parts'] as &$part) {
            // Use weighted average cost from actual inventory first, fall back to supplier pricing
            $unit_cost = $part['weighted_avg_cost'] ?? $part['preferred_cost'] ?? $part['lowest_cost'] ?? 0;
            $part['unit_cost'] = $unit_cost;
            $part['line_total'] = $unit_cost * $part['quantity_required'];
            $total_bom_cost += $part['line_total'];
            
            // Calculate actual inventory value for this part (what's actually in stock)
            $actual_inventory_value += $part['current_stock'] * $unit_cost;
            
            // Calculate how many kits we can build based on this part
            if ($part['quantity_required'] > 0) {
                $buildable = floor($part['current_stock'] / $part['quantity_required']);
                $min_buildable = min($min_buildable, $buildable);
            }
        }
        
        if ($min_buildable === PHP_INT_MAX) {
            $min_buildable = 0;
        }
        
        $project['total_bom_cost'] = $total_bom_cost;
        $project['buildable_kits'] = $min_buildable;
        // FIXED: Show actual value of all inventory in stock, not just complete kits
        $project['total_inventory_value'] = $actual_inventory_value;
        // Cost of the kits we can actually build (for profit calculation)
        $buildable_kits_cost = $total_bom_cost * $min_buildable;
        
        // Calculate revenue and profit if retail price is set
        if ($project['retail_price'] > 0) {
            $project['projected_revenue'] = $project['retail_price'] * $min_buildable;
            // Projected profit is based on kits we can build, not total inventory
            $project['projected_profit'] = $project['projected_revenue'] - $buildable_kits_cost;
            $project['profit_margin_percent'] = $total_bom_cost > 0 ? (($project['retail_price'] - $total_bom_cost) / $project['retail_price'] * 100) : 0;
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
            $stmt = $db->prepare("UPDATE projects SET project_name = ?, description = ?, status = ?, retail_price = ?, ship_weight_oz = ?, pkg_length = ?, pkg_width = ?, pkg_height = ?, image_path = ? WHERE id = ?");
            $stmt->execute([$name, $description, $status, $retail_price, $ship_weight_oz, $pkg_length, $pkg_width, $pkg_height, $image_path, $id]);
        } else {
            $stmt = $db->prepare("UPDATE projects SET project_name = ?, description = ?, status = ?, retail_price = ?, ship_weight_oz = ?, pkg_length = ?, pkg_width = ?, pkg_height = ? WHERE id = ?");
            $stmt->execute([$name, $description, $status, $retail_price, $ship_weight_oz, $pkg_length, $pkg_width, $pkg_height, $id]);
        }
        jsonResponse(['success' => true, 'id' => $id]);
    } else {
        $stmt = $db->prepare("INSERT INTO projects (project_name, description, status, retail_price, ship_weight_oz, pkg_length, pkg_width, pkg_height, image_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $description, $status, $retail_price, $ship_weight_oz, $pkg_length, $pkg_width, $pkg_height, $image_path]);
        jsonResponse(['success' => true, 'id' => $db->lastInsertId()]);
    }
}

if ($action === 'delete_project') {
    $id = $_POST['id'] ?? 0;
    $stmt = $db->prepare("DELETE FROM projects WHERE id = ?");
    $stmt->execute([$id]);
    jsonResponse(['success' => true]);
}

// Parts
if ($action === 'get_parts') {
    $parts = $db->query("SELECT * FROM parts ORDER BY part_name ASC")->fetchAll();
    jsonResponse($parts);
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
        
        // Get recent check-ins
        $stmt = $db->prepare("SELECT * FROM inventory_checkins WHERE part_id = ? ORDER BY purchase_date DESC LIMIT 10");
        $stmt->execute([$id]);
        $part['checkins'] = $stmt->fetchAll();
        
        jsonResponse($part);
    } else {
        jsonResponse(['error' => 'Part not found'], 404);
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
    $project_id = $_POST['project_id'] ?? 0;
    $part_id = $_POST['part_id'] ?? 0;
    $quantity = $_POST['quantity_required'] ?? 1;
    $notes = $_POST['notes'] ?? '';
    
    $stmt = $db->prepare("INSERT INTO project_parts (project_id, part_id, quantity_required, notes) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE quantity_required = ?, notes = ?");
    $stmt->execute([$project_id, $part_id, $quantity, $notes, $quantity, $notes]);
    jsonResponse(['success' => true]);
}

if ($action === 'delete_project_part') {
    $id = $_POST['id'] ?? 0;
    $stmt = $db->prepare("DELETE FROM project_parts WHERE id = ?");
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

// Inventory Check-ins
if ($action === 'checkin_inventory') {
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
    
    // Begin transaction
    $db->beginTransaction();
    
    try {
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
        
        // Insert check-in record
        $stmt = $db->prepare("INSERT INTO inventory_checkins (part_id, quantity, unit_cost, total_cost, supplier_name, purchase_date, notes) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$part_id, $quantity, $unit_cost, $total_cost, $supplier_name, $purchase_date, $notes]);
        
        // Update part stock and weighted average cost
        $stmt = $db->prepare("UPDATE parts SET current_stock = current_stock + ?, weighted_avg_cost = ? WHERE id = ?");
        $stmt->execute([$quantity, $new_avg_cost, $part_id]);
        
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
        $stmt = $db->prepare("SELECT quantity, unit_cost FROM inventory_checkins WHERE id = ?");
        $stmt->execute([$id]);
        $checkin = $stmt->fetch();
        
        if (!$checkin) {
            jsonResponse(['error' => 'Check-in not found'], 404);
        }
        
        // Delete the check-in
        $stmt = $db->prepare("DELETE FROM inventory_checkins WHERE id = ?");
        $stmt->execute([$id]);
        
        // Reduce stock by the deleted quantity
        $stmt = $db->prepare("UPDATE parts SET current_stock = GREATEST(0, current_stock - ?) WHERE id = ?");
        $stmt->execute([$checkin['quantity'], $part_id]);
        
        // Recalculate weighted average cost from remaining check-ins
        $stmt = $db->prepare("
            SELECT SUM(quantity) as total_qty, SUM(quantity * unit_cost) as total_value 
            FROM inventory_checkins 
            WHERE part_id = ?
        ");
        $stmt->execute([$part_id]);
        $totals = $stmt->fetch();
        
        $new_avg_cost = $totals['total_qty'] > 0 ? $totals['total_value'] / $totals['total_qty'] : 0;
        
        $stmt = $db->prepare("UPDATE parts SET weighted_avg_cost = ? WHERE id = ?");
        $stmt->execute([$new_avg_cost, $part_id]);
        
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
        // Get old quantity
        $stmt = $db->prepare("SELECT quantity FROM inventory_checkins WHERE id = ?");
        $stmt->execute([$id]);
        $old_checkin = $stmt->fetch();
        
        if (!$old_checkin) {
            jsonResponse(['error' => 'Check-in not found'], 404);
        }
        
        $qty_diff = $quantity - $old_checkin['quantity'];
        
        // Update check-in
        $stmt = $db->prepare("UPDATE inventory_checkins SET quantity = ?, unit_cost = ?, total_cost = ?, supplier_name = ?, purchase_date = ?, notes = ? WHERE id = ?");
        $stmt->execute([$quantity, $unit_cost, $total_cost, $supplier_name, $purchase_date, $notes, $id]);
        
        // Adjust stock
        $stmt = $db->prepare("UPDATE parts SET current_stock = GREATEST(0, current_stock + ?) WHERE id = ?");
        $stmt->execute([$qty_diff, $part_id]);
        
        // Recalculate weighted average cost
        $stmt = $db->prepare("
            SELECT SUM(quantity) as total_qty, SUM(quantity * unit_cost) as total_value 
            FROM inventory_checkins 
            WHERE part_id = ?
        ");
        $stmt->execute([$part_id]);
        $totals = $stmt->fetch();
        
        $new_avg_cost = $totals['total_qty'] > 0 ? $totals['total_value'] / $totals['total_qty'] : 0;
        
        $stmt = $db->prepare("UPDATE parts SET weighted_avg_cost = ? WHERE id = ?");
        $stmt->execute([$new_avg_cost, $part_id]);
        
        $db->commit();
        jsonResponse(['success' => true]);
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
        SELECT o.*, p.project_name 
        FROM orders o 
        JOIN projects p ON o.project_id = p.id 
        ORDER BY o.order_date DESC
    ")->fetchAll();
    jsonResponse($orders);
}

if ($action === 'get_order') {
    $id = $_GET['id'] ?? 0;
    $stmt = $db->prepare("
        SELECT o.*, p.project_name, p.ship_weight_oz, p.pkg_length, p.pkg_width, p.pkg_height, p.retail_price
        FROM orders o
        JOIN projects p ON o.project_id = p.id
        WHERE o.id = ?
    ");
    $stmt->execute([$id]);
    jsonResponse($stmt->fetch() ?: ['error' => 'Order not found']);
}

if ($action === 'save_order') {
    $id = $_POST['id'] ?? null;
    $order_number = $_POST['order_number'] ?? 'ORD-' . date('Ymd') . '-' . rand(1000, 9999);
    $project_id = $_POST['project_id'] ?? 0;
    $customer_name = $_POST['customer_name'] ?? '';
    $customer_email = $_POST['customer_email'] ?? '';
    $customer_phone = $_POST['customer_phone'] ?? '';
    $customer_callsign = $_POST['customer_callsign'] ?? '';
    $quantity = $_POST['quantity'] ?? 1;
    $price_paid = $_POST['price_paid'] ?? 0;
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
    
    $db->beginTransaction();
    
    try {
        if ($id) {
            // Get old status and inventory_deducted status
            $stmt = $db->prepare("SELECT status, inventory_deducted, quantity, project_id FROM orders WHERE id = ?");
            $stmt->execute([$id]);
            $old_order = $stmt->fetch();
            $old_status = $old_order['status'];
            $already_deducted = $old_order['inventory_deducted'];
            
            // Update order
            $stmt = $db->prepare("UPDATE orders SET order_number = ?, project_id = ?, customer_name = ?, customer_email = ?, customer_phone = ?, customer_callsign = ?, quantity = ?, price_paid = ?, order_date = ?, status = ?, shipping_address = ?, notes = ?, source = ?, tracking_number = ?, shipping_charge = ?, ship_street = ?, ship_street2 = ?, ship_city = ?, ship_state = ?, ship_zip = ?, mail_service = ? WHERE id = ?");
            $stmt->execute([$order_number, $project_id, $customer_name, $customer_email, $customer_phone, $customer_callsign, $quantity, $price_paid, $order_date, $status, $shipping_address, $notes, $source, $tracking_number, $shipping_charge, $ship_street, $ship_street2, $ship_city, $ship_state, $ship_zip, $mail_service, $id]);
            
            // Handle inventory deduction
            $should_deduct = in_array($status, ['shipped', 'completed']);
            
            if ($should_deduct && !$already_deducted) {
                // Deduct inventory
                deductInventoryForOrder($db, $project_id, $quantity);
                $stmt = $db->prepare("UPDATE orders SET inventory_deducted = 1 WHERE id = ?");
                $stmt->execute([$id]);
            } elseif (!$should_deduct && $already_deducted) {
                // Restore inventory if order was cancelled or status changed back
                restoreInventoryForOrder($db, $old_order['project_id'], $old_order['quantity']);
                $stmt = $db->prepare("UPDATE orders SET inventory_deducted = 0 WHERE id = ?");
                $stmt->execute([$id]);
            }
            
            $db->commit();
            jsonResponse(['success' => true, 'id' => $id, 'status_changed' => $old_status !== $status, 'new_status' => $status]);
        } else {
            // Insert new order
            $inventory_deducted = in_array($status, ['shipped', 'completed']) ? 1 : 0;
            
            $stmt = $db->prepare("INSERT INTO orders (order_number, project_id, customer_name, customer_email, customer_phone, customer_callsign, quantity, price_paid, order_date, status, shipping_address, notes, source, inventory_deducted, tracking_number, shipping_charge, ship_street, ship_street2, ship_city, ship_state, ship_zip, mail_service) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$order_number, $project_id, $customer_name, $customer_email, $customer_phone, $customer_callsign, $quantity, $price_paid, $order_date, $status, $shipping_address, $notes, $source, $inventory_deducted, $tracking_number, $shipping_charge, $ship_street, $ship_street2, $ship_city, $ship_state, $ship_zip, $mail_service]);
            
            $new_id = $db->lastInsertId();
            
            // Deduct inventory if necessary
            if ($inventory_deducted) {
                deductInventoryForOrder($db, $project_id, $quantity);
            }
            
            $db->commit();
            jsonResponse(['success' => true, 'id' => $new_id, 'status_changed' => false, 'new_status' => $status]);
        }
    } catch (Exception $e) {
        $db->rollBack();
        jsonResponse(['error' => $e->getMessage()], 500);
    }
}

// Helper function to deduct inventory
function deductInventoryForOrder($db, $project_id, $order_quantity) {
    // Get BOM for this project
    $stmt = $db->prepare("SELECT part_id, quantity_required FROM project_parts WHERE project_id = ?");
    $stmt->execute([$project_id]);
    $bom_parts = $stmt->fetchAll();
    
    foreach ($bom_parts as $bom_part) {
        $deduct_qty = $bom_part['quantity_required'] * $order_quantity;
        $stmt = $db->prepare("UPDATE parts SET current_stock = GREATEST(0, current_stock - ?) WHERE id = ?");
        $stmt->execute([$deduct_qty, $bom_part['part_id']]);
    }
}

// Helper function to restore inventory
function restoreInventoryForOrder($db, $project_id, $order_quantity) {
    // Get BOM for this project
    $stmt = $db->prepare("SELECT part_id, quantity_required FROM project_parts WHERE project_id = ?");
    $stmt->execute([$project_id]);
    $bom_parts = $stmt->fetchAll();
    
    foreach ($bom_parts as $bom_part) {
        $restore_qty = $bom_part['quantity_required'] * $order_quantity;
        $stmt = $db->prepare("UPDATE parts SET current_stock = current_stock + ? WHERE id = ?");
        $stmt->execute([$restore_qty, $bom_part['part_id']]);
    }
}

if ($action === 'delete_order') {
    $id = $_POST['id'] ?? 0;
    $stmt = $db->prepare("DELETE FROM orders WHERE id = ?");
    $stmt->execute([$id]);
    jsonResponse(['success' => true]);
}

// Send invoice email
if ($action === 'send_invoice') {
    $order_id = (int)($_POST['order_id'] ?? 0);
    $stmt = $db->prepare("SELECT o.*, p.project_name, p.retail_price FROM orders o JOIN projects p ON o.project_id = p.id WHERE o.id = ?");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();
    if (!$order) jsonResponse(['error' => 'Order not found'], 404);
    if (!$order['customer_email']) jsonResponse(['error' => 'No email address on file for this customer.'], 400);

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

jsonResponse(['error' => 'Invalid action'], 400);
?>
