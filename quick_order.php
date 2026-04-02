<?php
/**
 * Email Parser - Quick Order Entry
 * 
 * Copy/paste the notification email you get from WPForms
 * System automatically extracts: name, email, phone, callsign, etc.
 */

require_once 'config.php';
requireLogin();

$message = '';
$parsed_data = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['email_content'])) {
    $email = $_POST['email_content'];
    
    // Parse the email content
    $parsed_data = [];
    
    // Common patterns in WPForms notification emails
    // Name: John Smith
    if (preg_match('/Name[:\s]+(.+?)(?:\n|$)/i', $email, $matches)) {
        $parsed_data['name'] = trim($matches[1]);
    }
    
    // Email: john@example.com
    if (preg_match('/Email[:\s]+(.+?)(?:\n|$)/i', $email, $matches)) {
        $parsed_data['email'] = trim($matches[1]);
    }
    
    // Phone: 555-1234
    if (preg_match('/Phone[:\s]+(.+?)(?:\n|$)/i', $email, $matches)) {
        $parsed_data['phone'] = trim($matches[1]);
    }
    
    // Callsign: KI6XYZ
    if (preg_match('/Call\s*sign[:\s]+(.+?)(?:\n|$)/i', $email, $matches)) {
        $parsed_data['callsign'] = trim($matches[1]);
    }
    
    // Address (multi-line) - improved parsing
    if (preg_match('/(?:Shipping\s+)?Address[:\s]+(.*?)(?:\n(?:Single Line Text|City|State|Country|Special|Payment|Acknowledgement|\Z))/is', $email, $matches)) {
        $address_text = $matches[1];
        // Also capture "Single Line Text" field if present (usually suite/apt)
        if (preg_match('/Single Line Text[:\s]+(.+?)(?:\n|$)/i', $email, $suite_match)) {
            $address_text .= "\n" . trim($suite_match[1]);
        }
        // Also capture City if separate
        if (preg_match('/City[:\s]+(.+?)(?:\n|$)/i', $email, $city_match)) {
            $address_text .= "\n" . trim($city_match[1]);
        }
        // Also capture State if separate
        if (preg_match('/State[:\s\/]+(?:Province[:\s\/]+)?(?:Region[:\s]+)?(.+?)(?:\n|$)/i', $email, $state_match)) {
            $address_text .= ", " . trim($state_match[1]);
        }
        // Also capture Postal Code if separate
        if (preg_match('/Postal Code[:\s]+(.+?)(?:\n|$)/i', $email, $zip_match)) {
            $address_text .= " " . trim($zip_match[1]);
        }
        // Also capture Country if separate
        if (preg_match('/Country[:\s]+(.+?)(?:\n|$)/i', $email, $country_match)) {
            $address_text .= "\n" . trim($country_match[1]);
        }
        $parsed_data['address'] = trim($address_text);
    }
    
    // Project/Kit/Product
    if (preg_match('/(Project|Kit|Product)[:\s]+(.+?)(?:\n|$)/i', $email, $matches)) {
        $parsed_data['project'] = trim($matches[2]);
    }
    
    // Quantity
    if (preg_match('/Quantity[:\s]+(\d+)/i', $email, $matches)) {
        $parsed_data['quantity'] = intval($matches[1]);
    } else {
        $parsed_data['quantity'] = 1;
    }
    
    // Price/Amount/Total
    if (preg_match('/(Price|Amount|Total)[:\s]+\$?(\d+\.?\d*)/i', $email, $matches)) {
        $parsed_data['price'] = floatval($matches[2]);
    }
    
    // Notes/Comments/Message
    if (preg_match('/(Notes?|Comments?|Message)[:\s]+(.+?)(?:\n\n|\Z)/is', $email, $matches)) {
        $parsed_data['notes'] = trim($matches[2]);
    }
    
    // If we found at least a name, show the preview
    if (isset($parsed_data['name'])) {
        $message = "<div style='color: var(--success); padding: 1rem; background: #d1fae5; border-radius: 4px; margin-bottom: 1rem;'>
            ✓ Email parsed successfully! Review the data below and click 'Create Order'.
        </div>";
    } else {
        $message = "<div style='color: var(--warning); padding: 1rem; background: #fef3c7; border-radius: 4px; margin-bottom: 1rem;'>
            ⚠ Couldn't find customer name. Please check the email format or enter manually.
        </div>";
    }
}

// Create order if confirmed
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_order'])) {
    $db = getDB();
    
    $customer_name = $_POST['customer_name'] ?? '';
    $customer_email = $_POST['customer_email'] ?? '';
    $customer_phone = $_POST['customer_phone'] ?? '';
    $customer_callsign = $_POST['customer_callsign'] ?? '';
    $shipping_address = $_POST['shipping_address'] ?? '';
    $project_id = $_POST['project_id'] ?? 0;
    $quantity = $_POST['quantity'] ?? 1;
    $price_paid = $_POST['price_paid'] ?? 0;
    $notes = $_POST['notes'] ?? '';
    
    $order_number = 'EMAIL-' . date('Ymd') . '-' . rand(1000, 9999);
    
    $stmt = $db->prepare("
        INSERT INTO orders 
        (order_number, project_id, customer_name, customer_email, customer_phone, 
         customer_callsign, quantity, price_paid, order_date, status, shipping_address, 
         notes, source, inventory_deducted) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'pending', ?, ?, 'email_import', 0)
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
        $notes . "\n\n[Imported from email]"
    ]);
    
    header('Location: index.php?message=order_created');
    exit;
}

// Get projects for dropdown
$db = getDB();
$projects = $db->query("SELECT id, project_name, retail_price FROM projects WHERE status = 'active' ORDER BY project_name")->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quick Order Entry - KI6CR</title>
    <style>
        :root {
            --bg-dark: #f8f9fa;
            --bg-medium: #ffffff;
            --accent-primary: #2563eb;
            --text-primary: #1f2937;
            --text-secondary: #6b7280;
            --border-color: #e5e7eb;
            --success: #10b981;
            --warning: #f59e0b;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: var(--bg-dark);
            color: var(--text-primary);
            margin: 0;
            padding: 2rem;
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: var(--bg-medium);
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        h1 {
            color: var(--accent-primary);
            margin-top: 0;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            background: var(--accent-primary);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
        }
        
        .btn:hover {
            background: #1d4ed8;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .form-input,
        .form-textarea,
        .form-select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-family: inherit;
            font-size: 1rem;
        }
        
        .form-textarea {
            min-height: 200px;
            font-family: monospace;
        }
        
        .instructions {
            background: #eff6ff;
            border-left: 4px solid var(--accent-primary);
            padding: 1.5rem;
            margin-bottom: 2rem;
            border-radius: 4px;
        }
        
        .instructions h3 {
            margin-top: 0;
            color: var(--accent-primary);
        }
        
        .back-link {
            color: var(--accent-primary);
            text-decoration: none;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
        
        .example-email {
            background: #f9fafb;
            padding: 1rem;
            border-radius: 4px;
            font-family: monospace;
            font-size: 0.85rem;
            margin: 1rem 0;
            white-space: pre-wrap;
        }
        
        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        
        @media (max-width: 768px) {
            .grid-2 {
                grid-template-columns: 1fr;
            }
        }
        
        .preview-card {
            background: #f0fdf4;
            border: 2px solid var(--success);
            padding: 1.5rem;
            border-radius: 8px;
            margin: 2rem 0;
        }
        
        .preview-card h3 {
            margin-top: 0;
            color: var(--success);
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>⚡ Quick Order Entry</h1>
        
        <?php echo $message; ?>
        
        <?php if (!$parsed_data): ?>
        
        <div class="instructions">
            <h3>How to use:</h3>
            <ol style="margin: 0.5rem 0 0 1.5rem; padding: 0;">
                <li>Check your email for the WPForms notification</li>
                <li>Copy the entire email content</li>
                <li>Paste it in the box below</li>
                <li>Click "Parse Email"</li>
                <li>Review the extracted data</li>
                <li>Create order!</li>
            </ol>
            
            <p style="margin-top: 1rem;"><strong>Example email format:</strong></p>
            <div class="example-email">Name: John Smith
Email: john@example.com
Phone: 555-123-4567
Callsign: KI6XYZ
Project: 40m CW QRP Transceiver
Quantity: 1
Address: 123 Main St
         Burbank, CA 91501
Notes: Please ship ASAP</div>
        </div>
        
        <form method="POST">
            <div class="form-group">
                <label class="form-label">Paste Email Content Here:</label>
                <textarea name="email_content" class="form-textarea" placeholder="Paste your WPForms notification email here..." required></textarea>
            </div>
            
            <button type="submit" class="btn">📧 Parse Email</button>
        </form>
        
        <?php else: ?>
        
        <div class="preview-card">
            <h3>✓ Email Parsed Successfully!</h3>
            <p>Review the information below and adjust if needed:</p>
        </div>
        
        <form method="POST">
            <input type="hidden" name="create_order" value="1">
            
            <div class="grid-2">
                <div class="form-group">
                    <label class="form-label">Customer Name *</label>
                    <input type="text" name="customer_name" class="form-input" value="<?php echo htmlspecialchars($parsed_data['name'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="customer_email" class="form-input" value="<?php echo htmlspecialchars($parsed_data['email'] ?? ''); ?>">
                </div>
            </div>
            
            <div class="grid-2">
                <div class="form-group">
                    <label class="form-label">Phone</label>
                    <input type="text" name="customer_phone" class="form-input" value="<?php echo htmlspecialchars($parsed_data['phone'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Callsign</label>
                    <input type="text" name="customer_callsign" class="form-input" value="<?php echo htmlspecialchars($parsed_data['callsign'] ?? ''); ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Project/Kit *</label>
                <select name="project_id" class="form-select" required>
                    <option value="">Select project...</option>
                    <?php foreach ($projects as $p): ?>
                        <?php 
                        $selected = '';
                        if (isset($parsed_data['project']) && stripos($p['project_name'], $parsed_data['project']) !== false) {
                            $selected = 'selected';
                        }
                        ?>
                        <option value="<?php echo $p['id']; ?>" <?php echo $selected; ?>>
                            <?php echo htmlspecialchars($p['project_name']); ?> - $<?php echo number_format($p['retail_price'], 2); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="grid-2">
                <div class="form-group">
                    <label class="form-label">Quantity</label>
                    <input type="number" name="quantity" class="form-input" value="<?php echo $parsed_data['quantity'] ?? 1; ?>" min="1">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Price Paid ($)</label>
                    <input type="number" name="price_paid" class="form-input" step="0.01" value="<?php echo $parsed_data['price'] ?? ''; ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Shipping Address</label>
                <textarea name="shipping_address" class="form-input" style="min-height: 100px;"><?php echo htmlspecialchars($parsed_data['address'] ?? ''); ?></textarea>
            </div>
            
            <div class="form-group">
                <label class="form-label">Notes</label>
                <textarea name="notes" class="form-input" style="min-height: 80px;"><?php echo htmlspecialchars($parsed_data['notes'] ?? ''); ?></textarea>
            </div>
            
            <div style="display: flex; gap: 1rem;">
                <button type="submit" class="btn">✓ Create Order</button>
                <a href="quick_order.php" class="btn" style="background: #6b7280; text-decoration: none; display: inline-block;">← Parse Another Email</a>
            </div>
        </form>
        
        <?php endif; ?>
        
        <p style="margin-top: 2rem;">
            <a href="index.php" class="back-link">← Back to Inventory Manager</a>
        </p>
    </div>
</body>
</html>
