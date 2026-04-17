<?php
/**
 * Order Webhook - Receives orders from standalone form
 * Sends email notification to cr@christopherreddick.com
 */

require_once 'config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    $db = getDB();
    
    // Extract all form fields
    $customer_name = $_POST['customer_name'] ?? '';
    $customer_email = $_POST['customer_email'] ?? '';
    $customer_phone = $_POST['customer_phone'] ?? '';
    $customer_callsign = $_POST['customer_callsign'] ?? '';
    
    // Shipping address fields
    $street_address = $_POST['street_address'] ?? '';
    $address_line_2 = $_POST['address_line_2'] ?? '';
    $city = $_POST['city'] ?? '';
    $state = $_POST['state'] ?? '';
    $postal_code = $_POST['postal_code'] ?? '';
    $country = $_POST['country'] ?? '';
    
    // Build complete address
    $shipping_address = $street_address;
    if (!empty($address_line_2)) {
        $shipping_address .= "\n" . $address_line_2;
    }
    $shipping_address .= "\n" . $city . ", " . $state . " " . $postal_code;
    $shipping_address .= "\n" . $country;
    
    $delivery_method = $_POST['delivery_method'] ?? 'Shipping';
    $delivery_instructions = $_POST['delivery_instructions'] ?? '';
    $payment_method = $_POST['payment_method'] ?? '';
    
    $project_id = $_POST['project_id'] ?? 0;
    $quantity = $_POST['quantity'] ?? 1;
    
    // Get project details for price
    $stmt = $db->prepare("SELECT project_name, retail_price FROM projects WHERE id = ?");
    $stmt->execute([$project_id]);
    $project = $stmt->fetch();
    
    $price_paid = $project ? $project['retail_price'] * $quantity : 0;
    $project_name = $project['project_name'] ?? 'Unknown Kit';
    
    // Validate required fields
    if (empty($customer_name) || empty($customer_email)) {
        http_response_code(400);
        echo json_encode(['error' => 'Name and email are required']);
        exit;
    }
    
    // Build notes
    $notes = "Delivery: $delivery_method\n";
    if (!empty($delivery_instructions)) {
        $notes .= "Instructions: $delivery_instructions\n";
    }
    $notes .= "Payment Method: $payment_method\n";
    $notes .= "\n[Order submitted via web form]";
    
    // Generate order number
    $order_number = 'WEB-' . date('Ymd') . '-' . rand(1000, 9999);
    
    // Insert order
    $stmt = $db->prepare("
        INSERT INTO orders
        (order_number, project_id, customer_name, customer_email, customer_phone,
         customer_callsign, quantity, price_paid, order_date, status, shipping_address,
         ship_street, ship_street2, ship_city, ship_state, ship_zip,
         notes, source, inventory_deducted)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'pending', ?, ?, ?, ?, ?, ?, ?, 'web_form', 0)
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
        $street_address,
        $address_line_2 ?: null,
        $city,
        $state,
        $postal_code,
        $notes
    ]);
    
    $order_id = $db->lastInsertId();
    
    // Send email notification to Chris
    $to = 'cr@christopherreddick.com';
    $subject = "New Kit Order: $order_number - $customer_name ($customer_callsign)";
    
    $email_body = "New kit order received via website!\n\n";
    $email_body .= "ORDER DETAILS\n";
    $email_body .= "=============\n";
    $email_body .= "Order Number: $order_number\n";
    $email_body .= "Date: " . date('Y-m-d H:i:s') . "\n\n";
    
    $email_body .= "CUSTOMER INFO\n";
    $email_body .= "=============\n";
    $email_body .= "Name: $customer_name\n";
    $email_body .= "Callsign: $customer_callsign\n";
    $email_body .= "Email: $customer_email\n";
    $email_body .= "Phone: $customer_phone\n\n";
    
    $email_body .= "KIT DETAILS\n";
    $email_body .= "===========\n";
    $email_body .= "Kit: $project_name\n";
    $email_body .= "Quantity: $quantity\n";
    $email_body .= "Price: $" . number_format($price_paid, 2) . " (plus shipping)\n\n";
    
    $email_body .= "SHIPPING\n";
    $email_body .= "========\n";
    $email_body .= "Method: $delivery_method\n";
    $email_body .= "Address:\n$shipping_address\n";
    if (!empty($delivery_instructions)) {
        $email_body .= "Instructions: $delivery_instructions\n";
    }
    $email_body .= "\n";
    
    $email_body .= "PAYMENT\n";
    $email_body .= "=======\n";
    $email_body .= "Method: $payment_method\n\n";
    
    $email_body .= "View in inventory system:\n";
    $email_body .= "https://ki6cr.com/projects/\n\n";
    
    $email_body .= "73!\n";
    
    $headers = "From: KI6CR Order System <noreply@ki6cr.com>\r\n";
    $headers .= "Reply-To: $customer_email\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();
    
    mail($to, $subject, $email_body, $headers);
    
    // Also send confirmation to customer
    $customer_subject = "Order Received - $order_number";
    $customer_body = "Hi $customer_name,\n\n";
    $customer_body .= "Thank you for your order! We've received your request for:\n\n";
    $customer_body .= "Kit: $project_name\n";
    $customer_body .= "Quantity: $quantity\n";
    $customer_body .= "Order Number: $order_number\n\n";
    $customer_body .= "I'll send you an invoice for payment via $payment_method shortly.\n\n";
    $customer_body .= "If you have any questions, just reply to this email.\n\n";
    $customer_body .= "73,\n";
    $customer_body .= "Chris - KI6CR\n";
    
    $customer_headers = "From: Chris Reddick - KI6CR <cr@christopherreddick.com>\r\n";
    $customer_headers .= "X-Mailer: PHP/" . phpversion();
    
    mail($customer_email, $customer_subject, $customer_body, $customer_headers);
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'order_id' => $order_id,
        'order_number' => $order_number,
        'message' => 'Order received successfully'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}
?>
