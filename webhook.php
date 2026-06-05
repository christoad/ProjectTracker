<?php
/**
 * WPForms Webhook Endpoint
 * 
 * URL: https://ki6cr.com/projects/webhook.php
 * 
 * Configure in WPForms:
 * 1. Go to WPForms → Settings → Integrations → Webhooks
 * 2. Add this URL as the webhook endpoint
 * 3. Select your order form
 * 4. Map the fields as shown below
 */

require_once 'config.php';

// Log webhook requests for debugging
$log_file = 'webhook_log.txt';
file_put_contents($log_file, date('Y-m-d H:i:s') . " - Webhook received\n", FILE_APPEND);

// Get POST data from WPForms
$input = file_get_contents('php://input');
file_put_contents($log_file, "Raw input: " . $input . "\n", FILE_APPEND);

// WPForms sends data as JSON
$data = json_decode($input, true);

// If not JSON, try form data
if (!$data) {
    $data = $_POST;
}

file_put_contents($log_file, "Parsed data: " . print_r($data, true) . "\n", FILE_APPEND);

if (empty($data)) {
    http_response_code(400);
    echo json_encode(['error' => 'No data received']);
    exit;
}

try {
    $db = getDB();
    
    // Map WPForms fields to our database
    // ADJUST THESE FIELD NAMES to match your WPForms field names/IDs
    
    // WPForms sends fields as either field_id or field_name
    // Check both patterns
    
    $customer_name = $data['name'] ?? $data['customer_name'] ?? $data['field_1'] ?? '';
    $customer_email = $data['email'] ?? $data['customer_email'] ?? $data['field_2'] ?? '';
    $customer_phone = $data['phone'] ?? $data['customer_phone'] ?? $data['field_3'] ?? '';
    $customer_callsign = $data['callsign'] ?? $data['customer_callsign'] ?? $data['field_4'] ?? '';
    $shipping_address = $data['address'] ?? $data['shipping_address'] ?? $data['field_5'] ?? '';
    
    // Project/Kit selection - this should be a dropdown in your form
    $project_name = $data['project'] ?? $data['kit'] ?? $data['field_6'] ?? '';
    
    // Try to find project by name
    $stmt = $db->prepare("SELECT id, retail_price FROM projects WHERE project_name LIKE ? LIMIT 1");
    $stmt->execute(['%' . $project_name . '%']);
    $project = $stmt->fetch();
    
    if (!$project) {
        // Default to first active project if not found
        $stmt = $db->query("SELECT id, retail_price FROM projects WHERE status = 'active' LIMIT 1");
        $project = $stmt->fetch();
    }
    
    $project_id = $project['id'] ?? 0;
    $price_paid = $data['price'] ?? $data['amount'] ?? $project['retail_price'] ?? 0;
    
    $quantity = $data['quantity'] ?? $data['qty'] ?? 1;
    $notes = $data['notes'] ?? $data['comments'] ?? '';
    
    // Add any payment info from WPForms payment addons
    if (isset($data['payment_total'])) {
        $price_paid = $data['payment_total'];
    }
    
    // Generate order number
    $order_number = 'WEB-' . date('Ymd') . '-' . rand(1000, 9999);
    
    // Insert order with 'pending' status and source 'webhook'
    $stmt = $db->prepare("
        INSERT INTO orders 
        (order_number, project_id, customer_name, customer_email, customer_phone, 
         customer_callsign, quantity, price_paid, order_date, status, shipping_address, 
         notes, source, inventory_deducted) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'pending', ?, ?, 'webhook', 0)
    ");
    
    $stmt->execute([
        $order_number,
        $project_id,
        $customer_name,
        $customer_email,
        $customer_phone,
        $customer_callsign,
        $quantity,
        $price_paid,
        $shipping_address,
        $notes . "\n\n[Auto-imported from web form]"
    ]);
    
    $order_id = $db->lastInsertId();
    
    file_put_contents($log_file, "Order created: #$order_id\n\n", FILE_APPEND);
    
    // Return success
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'order_id' => $order_id,
        'order_number' => $order_number,
        'message' => 'Order imported successfully'
    ]);
    
} catch (Exception $e) {
    file_put_contents($log_file, "Error: " . $e->getMessage() . "\n\n", FILE_APPEND);
    
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}
?>
