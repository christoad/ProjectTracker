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
    <link href="https://fonts.googleapis.com/css2?family=Figtree:wght@300;400;500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        :root {
          --bg-body:            #ede8df;
          --bg-card:            #f7f4ef;
          --bg-card-header:     #ede8df;
          --bg-dark:            #ede8df;
          --bg-medium:          #f7f4ef;
          --bg-light:           #c9b99a;
          --header-gradient:    linear-gradient(135deg, #3d5a2a 0%, #4f7a38 100%);
          --header-height:      56px;
          --nav-bg:             #251d12;
          --nav-border-bottom:  #4a7c38;
          --accent-primary:     #4a7c38;
          --accent-primary-dim: #3a6029;
          --border-card:        #c9b99a;
          --border-color:       #c9b99a;
          --text-primary:       #2c1f0e;
          --text-secondary:     #7a6a55;
          --text-dim:           #a89a85;
          --success:            #2d7a3a;
          --warning:            #c47d1a;
          --danger:             #b84444;
          --shadow-card:        0 2px 8px rgba(44,31,14,0.06);
          --shadow-header:      0 2px 16px rgba(44,31,14,0.22);
          --font-body:          'Figtree', sans-serif;
          --font-mono:          'IBM Plex Mono', monospace;
          --radius-sm:          3px;
          --radius-md:          4px;
          --radius-card:        6px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: var(--font-body);
            background: var(--bg-body);
            color: var(--text-primary);
            line-height: 1.6;
        }

        .app-header {
            background: var(--header-gradient);
            height: var(--header-height);
            padding: 0 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: var(--shadow-header);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .app-logo-block { display: flex; align-items: center; gap: 12px; }
        .app-logo-icon {
            width: 32px; height: 32px;
            border: 2px solid rgba(255,255,255,0.35);
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
        }
        .app-logo-diamond {
            width: 12px; height: 12px;
            background: #fff;
            transform: rotate(45deg);
            border-radius: 2px;
            opacity: 0.92;
        }
        .app-logo-callsign { font-family: var(--font-mono); font-size: 16px; font-weight: 700; color: #fff; letter-spacing: 2.5px; line-height: 1.1; }
        .app-logo-subtitle { font-size: 10px; color: rgba(255,255,255,0.58); letter-spacing: 0.8px; text-transform: uppercase; }

        .btn-ghost {
            padding: 5px 14px;
            background: rgba(255,255,255,0.10);
            border: 1px solid rgba(255,255,255,0.22);
            color: rgba(255,255,255,0.82);
            border-radius: var(--radius-md);
            font-size: 12px; cursor: pointer;
            font-family: var(--font-body); font-weight: 500;
            text-decoration: none; display: inline-block;
        }
        .btn-ghost:hover { background: rgba(255,255,255,0.18); }

        .page-body {
            max-width: 900px;
            margin: 28px auto;
            padding: 0 20px 40px;
        }

        .card {
            background: var(--bg-card);
            border: 1px solid var(--border-card);
            border-radius: var(--radius-card);
            padding: 0;
            margin-bottom: 1.5rem;
            box-shadow: var(--shadow-card);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 13px 20px;
            background: var(--bg-card-header);
            border-bottom: 1px solid var(--border-card);
            border-radius: var(--radius-card) var(--radius-card) 0 0;
        }

        .card-title {
            font-size: 11.5px;
            font-weight: 600;
            color: var(--text-primary);
            text-transform: uppercase;
            letter-spacing: 0.9px;
        }

        .card-body { padding: 20px 24px; }

        .btn {
            padding: 6px 15px;
            background: var(--accent-primary);
            border: 1px solid var(--accent-primary);
            color: #fff;
            border-radius: var(--radius-md);
            cursor: pointer;
            font-family: var(--font-body);
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
        }
        .btn:hover { background: var(--accent-primary-dim); border-color: var(--accent-primary-dim); }
        .btn-secondary {
            background: #ede8df;
            border-color: var(--border-card);
            color: var(--accent-primary);
        }
        .btn-secondary:hover { background: var(--bg-light); border-color: var(--accent-primary); color: var(--accent-primary); }

        .form-group { margin-bottom: 1rem; }
        .form-label {
            display: block;
            margin-bottom: 5px;
            font-size: 10.5px;
            font-weight: 600;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }
        .form-input, .form-textarea, .form-select {
            width: 100%;
            padding: 8px 12px;
            background: #fff;
            border: 1px solid var(--border-card);
            color: var(--text-primary);
            font-family: var(--font-mono);
            font-size: 13px;
            border-radius: var(--radius-md);
            transition: border-color 0.15s, box-shadow 0.15s;
        }
        .form-input:focus, .form-textarea:focus, .form-select:focus {
            outline: none;
            border-color: var(--accent-primary);
            box-shadow: 0 0 0 3px rgba(74,124,56,0.12);
        }
        .form-textarea { min-height: 200px; resize: vertical; }

        .instructions {
            background: rgba(74,124,56,0.06);
            border-left: 4px solid var(--accent-primary);
            padding: 16px 20px;
            margin-bottom: 20px;
            border-radius: 0 var(--radius-card) var(--radius-card) 0;
        }
        .instructions h3 { margin-top: 0; color: var(--accent-primary); font-size: 13px; margin-bottom: 8px; }

        .example-email {
            background: var(--bg-card-header);
            padding: 12px 16px;
            border-radius: var(--radius-md);
            font-family: var(--font-mono);
            font-size: 12px;
            margin: 12px 0;
            white-space: pre-wrap;
            border: 1px solid var(--border-card);
        }

        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }

        .preview-card {
            background: rgba(16,185,129,0.07);
            border: 1px solid rgba(16,185,129,0.35);
            padding: 14px 18px;
            border-radius: var(--radius-card);
            margin-bottom: 20px;
        }
        .preview-card h3 { margin-top: 0; color: var(--success); font-size: 13px; }

        .back-link { color: var(--accent-primary); text-decoration: none; font-size: 13px; }
        .back-link:hover { text-decoration: underline; }

        @media (max-width: 640px) {
            .grid-2 { grid-template-columns: 1fr; }
            .page-body { padding: 0 12px 24px; margin-top: 16px; }
            .app-header { padding: 0 16px; }
        }
    </style>
</head>
<body>
    <header class="app-header">
        <div class="app-logo-block">
            <div class="app-logo-icon"><div class="app-logo-diamond"></div></div>
            <div>
                <div class="app-logo-callsign">KI6CR</div>
                <div class="app-logo-subtitle">Inventory Manager</div>
            </div>
        </div>
        <a href="index.php" class="btn-ghost">← Main App</a>
    </header>
    <div class="page-body">
    <div class="card">
        <div class="card-header"><span class="card-title">Quick Order Entry</span></div>
        <div class="card-body">
        
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
            
            <button type="submit" class="btn">Parse Email</button>
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
                <a href="quick_order.php" class="btn btn-secondary">← Parse Another Email</a>
            </div>
        </form>

        <?php endif; ?>

        <p style="margin-top: 1.5rem;">
            <a href="index.php" class="back-link">← Back to Inventory Manager</a>
        </p>
        </div>
    </div>
    </div>
</body>
</html>
