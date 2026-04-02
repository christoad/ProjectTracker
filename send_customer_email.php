<?php
/**
 * Send Customer Email - Sends order status update to customer
 */

require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$action = $_POST['action'] ?? '';

if ($action !== 'send_customer_email') {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid action']);
    exit;
}

try {
    $db = getDB();
    
    $order_id = $_POST['order_id'] ?? 0;
    
    // Get order details
    $stmt = $db->prepare("
        SELECT o.*, p.project_name 
        FROM orders o 
        LEFT JOIN projects p ON o.project_id = p.id 
        WHERE o.id = ?
    ");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();
    
    if (!$order) {
        http_response_code(404);
        echo json_encode(['error' => 'Order not found']);
        exit;
    }
    
    $customer_email = $order['customer_email'];
    $customer_name = $order['customer_name'];
    $order_number = $order['order_number'];
    $status = $order['status'];
    $tracking_number = $order['tracking_number'];
    $project_name = $order['project_name'];
    $quantity = $order['quantity'];
    
    // Build email based on status
    $subject = "Order Update: $order_number";
    $message = "Hi $customer_name,\n\n";
    
    switch ($status) {
        case 'paid':
            $subject = "Payment Received - $order_number";
            $message .= "Thank you for your payment! Your order is confirmed.\n\n";
            $message .= "Order Details:\n";
            $message .= "Kit: $project_name\n";
            $message .= "Quantity: $quantity\n";
            $message .= "Order Number: $order_number\n\n";
            $message .= "I'll be building your kit and will ship it out soon.\n\n";
            break;
            
        case 'shipped':
            $subject = "Your Order Has Shipped - $order_number";
            $message .= "Good news! Your order has been shipped.\n\n";
            $message .= "Order Details:\n";
            $message .= "Kit: $project_name\n";
            $message .= "Quantity: $quantity\n";
            $message .= "Order Number: $order_number\n";
            if ($tracking_number) {
                $message .= "Tracking Number: $tracking_number\n";
            }
            $message .= "\n";
            $message .= "You should receive your kit within 5-7 business days.\n\n";
            if ($tracking_number) {
                $message .= "You can track your package using the tracking number above.\n\n";
            }
            break;
            
        case 'completed':
            $subject = "Order Delivered - $order_number";
            $message .= "Your order has been marked as delivered!\n\n";
            $message .= "I hope you enjoy building your $project_name kit.\n\n";
            $message .= "If you have any questions or need help with assembly, just reply to this email.\n\n";
            $message .= "I'd love to see photos of your completed build if you'd like to share!\n\n";
            break;
            
        case 'cancelled':
            $subject = "Order Cancelled - $order_number";
            $message .= "Your order has been cancelled as requested.\n\n";
            $message .= "If this was a mistake or you have questions, please reply to this email.\n\n";
            break;
            
        default:
            $message .= "This is an update on your order.\n\n";
            $message .= "Order Number: $order_number\n";
            $message .= "Status: " . ucfirst($status) . "\n\n";
    }
    
    $message .= "If you have any questions, just reply to this email.\n\n";
    $message .= "73,\n";
    $message .= "Chris - KI6CR\n";
    
    $headers = "From: Chris Reddick - KI6CR <cr@christopherreddick.com>\r\n";
    $headers .= "Reply-To: cr@christopherreddick.com\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();
    
    $sent = mail($customer_email, $subject, $message, $headers);
    
    if ($sent) {
        echo json_encode(['success' => true, 'message' => "Email sent to $customer_email"]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to send email']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
