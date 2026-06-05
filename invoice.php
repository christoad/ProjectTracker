<?php
/**
 * Invoice — renders HTML invoice for an order.
 * Used two ways:
 *   GET  ?id=123          → preview in browser (direct navigation, requires login)
 *   include from api.php  → $order already set, outputs HTML for email
 */

if (!isset($order)) {
    // Direct browser preview
    require_once 'config.php';
    requireLogin();
    $order_id = (int)($_GET['id'] ?? 0);
    if (!$order_id) { header('Location: index.php'); exit; }
    $db = getDB();
    $stmt = $db->prepare("SELECT o.*, p.project_name, p.retail_price FROM orders o JOIN projects p ON o.project_id = p.id WHERE o.id = ?");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();
    if (!$order) { echo 'Order not found'; exit; }
}

$subtotal   = (float)$order['price_paid'];
$shipping   = (float)($order['shipping_charge'] ?? 0);
$total      = $subtotal + $shipping;
$ship_addr  = '';
if (!empty($order['ship_street'])) {
    $ship_addr = $order['ship_street'];
    if (!empty($order['ship_street2'])) $ship_addr .= ', ' . $order['ship_street2'];
    $ship_addr .= "\n" . $order['ship_city'] . ', ' . $order['ship_state'] . ' ' . $order['ship_zip'];
} elseif (!empty($order['shipping_address'])) {
    $ship_addr = $order['shipping_address'];
}
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Invoice <?= htmlspecialchars($order['order_number']) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Figtree:wght@300;400;500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
  body { font-family: 'Figtree', Arial, sans-serif; color: #2c1f0e; background: #ede8df; margin: 0; padding: 2rem; }
  .invoice-wrap { max-width: 680px; margin: 0 auto; background: white; border-radius: 8px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); overflow: hidden; }
  .inv-header { background: linear-gradient(135deg, #3d5a2a 0%, #4f7a38 100%); color: white; padding: 2rem 2.5rem; display: flex; justify-content: space-between; align-items: flex-start; }
  .inv-header h1 { margin: 0; font-size: 1.8rem; letter-spacing: 3px; font-family: 'IBM Plex Mono', monospace; }
  .inv-header .callsign { font-size: 0.85rem; opacity: 0.75; margin-top: 0.25rem; letter-spacing: 0.3px; }
  .inv-meta { text-align: right; font-size: 0.9rem; }
  .inv-meta .inv-num { font-size: 1.1rem; font-weight: 700; font-family: 'IBM Plex Mono', monospace; letter-spacing: 1px; }
  .inv-body { padding: 2rem 2.5rem; }
  .addr-row { display: flex; gap: 3rem; margin-bottom: 2rem; }
  .addr-block h3 { font-size: 0.7rem; text-transform: uppercase; letter-spacing: 1px; color: #7a6a55; margin: 0 0 0.4rem; font-weight: 600; }
  .addr-block p { margin: 0; line-height: 1.6; font-size: 0.9rem; white-space: pre-line; }
  table.items { width: 100%; border-collapse: collapse; margin-bottom: 1.5rem; }
  table.items th { background: #ede8df; padding: 0.6rem 1rem; text-align: left; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.8px; color: #7a6a55; font-weight: 600; border-bottom: 2px solid #4a7c38; }
  table.items td { padding: 0.75rem 1rem; border-bottom: 1px solid #ede8df; font-size: 0.9rem; }
  .totals { margin-left: auto; width: 260px; }
  .totals table { width: 100%; border-collapse: collapse; }
  .totals td { padding: 0.4rem 0.5rem; font-size: 0.9rem; }
  .totals td:last-child { text-align: right; font-weight: 600; font-family: 'IBM Plex Mono', monospace; }
  .totals .total-row td { font-size: 1rem; font-weight: 700; border-top: 2px solid #4a7c38; padding-top: 0.6rem; color: #4a7c38; font-family: 'IBM Plex Mono', monospace; }
  .payment-box { background: rgba(74,124,56,0.06); border-left: 4px solid #4a7c38; padding: 1rem 1.5rem; border-radius: 0 4px 4px 0; margin: 1.5rem 0; }
  .payment-box h3 { margin: 0 0 0.5rem; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; color: #3a6029; font-weight: 700; }
  .payment-box ul { margin: 0; padding-left: 1.25rem; font-size: 0.9rem; color: #4a7c38; line-height: 1.8; }
  .inv-footer { border-top: 1px solid #c9b99a; padding: 1.25rem 2.5rem; font-size: 0.8rem; color: #7a6a55; text-align: center; }
  @media print {
    body { background: white; padding: 0; }
    .invoice-wrap { box-shadow: none; }
    .no-print { display: none; }
  }
</style>
</head>
<body>

<div class="no-print" style="max-width:680px;margin:0 auto 1rem;display:flex;gap:0.75rem;">
  <button onclick="window.print()" style="padding:6px 16px;background:linear-gradient(135deg,#3d5a2a,#4f7a38);color:white;border:none;border-radius:4px;cursor:pointer;font-size:12px;font-family:'Figtree',sans-serif;font-weight:600;">Print / Save PDF</button>
  <a href="order_detail.php?id=<?= $order['id'] ?>" style="padding:6px 16px;background:#ede8df;color:#4a7c38;border:1px solid #c9b99a;border-radius:4px;text-decoration:none;font-size:12px;font-family:'Figtree',sans-serif;font-weight:500;">← Back to Order</a>
</div>

<div class="invoice-wrap">
  <div class="inv-header">
    <div>
      <h1>KI6CR</h1>
      <div class="callsign">Ham Radio Kits &nbsp;·&nbsp; cr@christopherreddick.com</div>
    </div>
    <div class="inv-meta">
      <div class="inv-num">INVOICE</div>
      <div><?= htmlspecialchars($order['order_number']) ?></div>
      <div style="margin-top:0.4rem;"><?= htmlspecialchars($order['order_date']) ?></div>
    </div>
  </div>

  <div class="inv-body">
    <div class="addr-row">
      <div class="addr-block">
        <h3>From</h3>
        <p>Chris Reddick
<?= htmlspecialchars(FROM_NAME ? FROM_NAME . "\n" : '') ?>KI6CR Ham Radio Kits
North Hollywood, CA 91601
cr@christopherreddick.com</p>
      </div>
      <div class="addr-block">
        <h3>Bill / Ship To</h3>
        <p><?= htmlspecialchars($order['customer_name']) ?>
<?php if ($order['customer_callsign']): ?><?= htmlspecialchars($order['customer_callsign']) . "\n" ?><?php endif; ?>
<?= htmlspecialchars($order['customer_email'] ?? '') ?>
<?= htmlspecialchars($ship_addr) ?></p>
      </div>
    </div>

    <table class="items">
      <thead>
        <tr>
          <th>Item</th>
          <th style="text-align:center;">Qty</th>
          <th style="text-align:right;">Unit Price</th>
          <th style="text-align:right;">Amount</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td><?= htmlspecialchars($order['project_name']) ?> — Kit</td>
          <td style="text-align:center;"><?= (int)$order['quantity'] ?></td>
          <td style="text-align:right;">$<?= number_format($order['quantity'] > 0 ? $subtotal / $order['quantity'] : $subtotal, 2) ?></td>
          <td style="text-align:right;">$<?= number_format($subtotal, 2) ?></td>
        </tr>
        <?php if ($shipping > 0): ?>
        <tr>
          <td>Shipping<?= $order['mail_service'] ? ' — ' . htmlspecialchars($order['mail_service']) : '' ?></td>
          <td></td>
          <td></td>
          <td style="text-align:right;">$<?= number_format($shipping, 2) ?></td>
        </tr>
        <?php endif; ?>
      </tbody>
    </table>

    <div class="totals">
      <table>
        <tr><td>Subtotal</td><td>$<?= number_format($subtotal, 2) ?></td></tr>
        <?php if ($shipping > 0): ?>
        <tr><td>Shipping</td><td>$<?= number_format($shipping, 2) ?></td></tr>
        <?php endif; ?>
        <tr class="total-row"><td>Total Due</td><td>$<?= number_format($total, 2) ?></td></tr>
      </table>
    </div>

    <div class="payment-box">
      <h3>Payment Instructions</h3>
      <ul>
        <li><strong>Venmo:</strong> @KI6CR</li>
        <li><strong>PayPal:</strong> cr@christopherreddick.com</li>
        <li><strong>Zelle:</strong> cr@christopherreddick.com</li>
        <li><strong>Check:</strong> Payable to Chris Reddick, mailed to North Hollywood CA 91601</li>
      </ul>
    </div>

    <?php if (!empty($order['notes'])): ?>
    <div style="margin-top:1rem; font-size:0.9rem; color:#6b7280;">
      <strong>Notes:</strong> <?= nl2br(htmlspecialchars($order['notes'])) ?>
    </div>
    <?php endif; ?>
  </div>

  <div class="inv-footer">
    Thank you for supporting ham radio! Questions? Reply to cr@christopherreddick.com &nbsp;·&nbsp; 73 de KI6CR
  </div>
</div>

</body>
</html>
