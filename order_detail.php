<?php
require_once 'config.php';
requireLogin();

$order_id = (int)($_GET['id'] ?? 0);
if (!$order_id) { header('Location: index.php'); exit; }

$db = getDB();
$stmt = $db->prepare("
    SELECT o.*, p.project_name, p.ship_weight_oz, p.pkg_length, p.pkg_width, p.pkg_height, p.retail_price
    FROM orders o
    JOIN projects p ON o.project_id = p.id
    WHERE o.id = ?
");
$stmt->execute([$order_id]);
$order = $stmt->fetch();
if (!$order) { header('Location: index.php'); exit; }

$projects = $db->query("SELECT id, project_name FROM projects WHERE status IN ('active','planning') ORDER BY project_name")->fetchAll();

function statusBadge($s) {
    return ['pending'=>'warning','paid'=>'info','shipped'=>'info','completed'=>'success','cancelled'=>'danger'][$s] ?? 'info';
}

// Pre-fill ZIP from shipping_address blob if structured fields are empty
$ship_zip_fallback = $order['ship_zip'] ?? '';
if (!$ship_zip_fallback && !empty($order['shipping_address'])) {
    preg_match('/\b(\d{5})(-\d{4})?\b/', $order['shipping_address'], $m);
    $ship_zip_fallback = $m[1] ?? '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($order['order_number']) ?> — KI6CR Orders</title>
<style>
:root {
    --bg-dark: #f8f9fa; --bg-medium: #ffffff; --bg-light: #f1f3f5;
    --accent: #2563eb; --accent-dim: #1d4ed8;
    --text: #1f2937; --text-sec: #6b7280;
    --border: #e5e7eb;
    --success: #10b981; --warning: #f59e0b; --danger: #ef4444; --info: #3b82f6;
}
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: 'Courier New', Monaco, monospace; background: var(--bg-dark); color: var(--text); line-height: 1.6; }
.app-header { background: var(--bg-medium); border-bottom: 2px solid var(--accent); padding: 0.85rem 2rem; display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 10; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
.page-body { max-width: 920px; margin: 1.75rem auto; padding: 0 1.5rem 3rem; }
.card { background: var(--bg-medium); border: 1px solid var(--border); border-radius: 8px; margin-bottom: 1.5rem; }
.card-header { padding: 0.85rem 1.5rem; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; }
.card-title { font-size: 0.8rem; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; color: var(--text-sec); }
.card-body { padding: 1.5rem; }
.form-label { display: block; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-sec); margin-bottom: 0.2rem; }
.form-input, .form-select, .form-textarea { width: 100%; padding: 0.45rem 0.7rem; border: 1px solid var(--border); border-radius: 4px; font-family: inherit; font-size: 0.9rem; background: var(--bg-medium); color: var(--text); }
.form-input:focus, .form-select:focus, .form-textarea:focus { outline: none; border-color: var(--accent); box-shadow: 0 0 0 2px rgba(37,99,235,0.1); }
.form-textarea { min-height: 70px; resize: vertical; }
.form-group { margin-bottom: 1rem; }
.g2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
.g3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; }
.g4 { display: grid; grid-template-columns: 2fr 1fr 1fr 1fr; gap: 0.75rem; }
.flex { display: flex; gap: 0.6rem; align-items: center; flex-wrap: wrap; }
.btn { padding: 0.4rem 0.9rem; border: 1px solid var(--border); border-radius: 4px; background: var(--bg-light); color: var(--text); cursor: pointer; font-family: inherit; font-size: 0.85rem; text-decoration: none; display: inline-block; white-space: nowrap; }
.btn:hover { background: var(--border); }
.btn-primary { background: var(--accent); color: white; border-color: var(--accent); }
.btn-primary:hover { background: var(--accent-dim); }
.btn-success { background: var(--success); color: white; border-color: var(--success); }
.btn-danger { background: var(--danger); color: white; border-color: var(--danger); }
.badge { padding: 0.2rem 0.65rem; border-radius: 12px; font-size: 0.75rem; font-weight: bold; text-transform: uppercase; }
.badge-warning { background: #fef3c7; color: #92400e; }
.badge-info    { background: #dbeafe; color: #1e40af; }
.badge-success { background: #d1fae5; color: #065f46; }
.badge-danger  { background: #fee2e2; color: #991b1b; }
.divider { border: none; border-top: 1px solid var(--border); margin: 1.25rem 0; }
.notice { padding: 0.6rem 0.9rem; border-radius: 4px; font-size: 0.85rem; margin-top: 0.5rem; }
.n-info    { background: #dbeafe; color: #1e40af; }
.n-success { background: #d1fae5; color: #065f46; }
.n-warning { background: #fef3c7; color: #92400e; }
.n-danger  { background: #fee2e2; color: #991b1b; }
.tracking-result { margin-top: 0.6rem; padding: 0.7rem 0.9rem; background: var(--bg-light); border-radius: 4px; font-size: 0.85rem; border: 1px solid var(--border); }
@media (max-width: 640px) {
    .g2, .g3, .g4 { grid-template-columns: 1fr; }
    .page-body { padding: 0 1rem 2rem; }
    .app-header { padding: 0.75rem 1rem; }
}
</style>
</head>
<body>

<div class="app-header">
    <div class="flex">
        <a href="index.php" class="btn" style="font-size:0.8rem;">← Orders</a>
        <strong style="color:var(--accent);"><?= htmlspecialchars($order['order_number']) ?></strong>
        <span class="badge badge-<?= statusBadge($order['status']) ?>"><?= htmlspecialchars($order['status']) ?></span>
    </div>
    <span style="color:var(--text-sec); font-size:0.85rem;">KI6CR Inventory</span>
</div>

<div class="page-body">

<!-- ── Order Details ─────────────────────────────── -->
<div class="card">
    <div class="card-header"><span class="card-title">Order Details</span></div>
    <div class="card-body">
        <div class="g3">
            <div class="form-group">
                <label class="form-label">Order Number</label>
                <input type="text" id="orderNumber" class="form-input" value="<?= htmlspecialchars($order['order_number']) ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Order Date</label>
                <input type="date" id="orderDate" class="form-input" value="<?= htmlspecialchars($order['order_date']) ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Source</label>
                <input type="text" id="orderSource" class="form-input" value="<?= htmlspecialchars($order['source'] ?? 'manual') ?>">
            </div>
        </div>
        <div class="g3">
            <div class="form-group">
                <label class="form-label">Project / Kit</label>
                <select id="orderProject" class="form-select">
                    <?php foreach ($projects as $p): ?>
                    <option value="<?= $p['id'] ?>" <?= $p['id'] == $order['project_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($p['project_name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Quantity</label>
                <input type="number" id="orderQty" class="form-input" value="<?= (int)$order['quantity'] ?>" min="1">
            </div>
            <div class="form-group">
                <label class="form-label">Price Paid ($)</label>
                <input type="number" id="orderPrice" class="form-input" value="<?= htmlspecialchars($order['price_paid']) ?>" step="0.01" min="0">
            </div>
        </div>
        <div class="g2">
            <div class="form-group">
                <label class="form-label">Status</label>
                <select id="orderStatus" class="form-select">
                    <?php foreach (['pending','paid','shipped','completed','cancelled'] as $s): ?>
                    <option value="<?= $s ?>" <?= $order['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Notes</label>
                <textarea id="orderNotes" class="form-textarea"><?= htmlspecialchars($order['notes'] ?? '') ?></textarea>
            </div>
        </div>

        <hr class="divider">

        <div class="g3">
            <div class="form-group">
                <label class="form-label">Customer Name</label>
                <input type="text" id="orderCustomer" class="form-input" value="<?= htmlspecialchars($order['customer_name']) ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Callsign</label>
                <input type="text" id="orderCallsign" class="form-input" value="<?= htmlspecialchars($order['customer_callsign'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" id="orderEmail" class="form-input" value="<?= htmlspecialchars($order['customer_email'] ?? '') ?>">
            </div>
        </div>
        <div class="form-group" style="max-width:260px;">
            <label class="form-label">Phone</label>
            <input type="tel" id="orderPhone" class="form-input" value="<?= htmlspecialchars($order['customer_phone'] ?? '') ?>">
        </div>
    </div>
</div>

<!-- ── Shipping Address ───────────────────────────── -->
<div class="card">
    <div class="card-header">
        <span class="card-title">Shipping Address</span>
    </div>
    <div class="card-body">
        <div class="form-group">
            <label class="form-label">Street</label>
            <input type="text" id="shipStreet" class="form-input" value="<?= htmlspecialchars($order['ship_street'] ?? '') ?>" placeholder="123 Main St">
        </div>
        <div class="form-group">
            <input type="text" id="shipStreet2" class="form-input" value="<?= htmlspecialchars($order['ship_street2'] ?? '') ?>" placeholder="Apt, Suite, Unit (optional)">
        </div>
        <div class="g4">
            <div class="form-group">
                <label class="form-label">City</label>
                <input type="text" id="shipCity" class="form-input" value="<?= htmlspecialchars($order['ship_city'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">State</label>
                <input type="text" id="shipState" class="form-input" value="<?= htmlspecialchars($order['ship_state'] ?? '') ?>" placeholder="CA" maxlength="2" style="text-transform:uppercase;">
            </div>
            <div class="form-group">
                <label class="form-label">ZIP</label>
                <input type="text" id="shipZip" class="form-input" value="<?= htmlspecialchars($ship_zip_fallback) ?>" placeholder="91601" maxlength="10">
            </div>
            <div class="form-group">
                <label class="form-label">Country</label>
                <input type="text" id="shipCountry" class="form-input" value="<?= htmlspecialchars($order['ship_country'] ?? 'USA') ?>">
            </div>
        </div>
        <?php if (!empty($order['shipping_address']) && empty($order['ship_street'])): ?>
        <div class="notice n-warning" style="margin-top:0.75rem;">
            Legacy address on file — fill in the fields above.<br>
            <small style="opacity:0.8;"><?= nl2br(htmlspecialchars($order['shipping_address'])) ?></small>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- ── Shipping ──────────────────────────────────── -->
<div class="card">
    <div class="card-header"><span class="card-title">Shipping</span></div>
    <div class="card-body">

        <div class="g3">
            <div class="form-group">
                <label class="form-label">Shipping Charge ($)</label>
                <input type="number" id="orderShippingCharge" class="form-input" value="<?= htmlspecialchars($order['shipping_charge'] ?? 0) ?>" step="0.01" min="0">
            </div>
            <div class="form-group" style="grid-column: span 2;">
                <label class="form-label">Mail Service Used</label>
                <input type="text" id="orderMailService" class="form-input" value="<?= htmlspecialchars($order['mail_service'] ?? '') ?>" placeholder="e.g. Ground Advantage, Priority Mail">
            </div>
        </div>

        <hr class="divider">

        <div class="form-group">
            <label class="form-label">Tracking Number</label>
            <input type="text" id="orderTracking" class="form-input" value="<?= htmlspecialchars($order['tracking_number'] ?? '') ?>" placeholder="94001118992235..." style="font-size:0.85rem;">
        </div>

    </div>
</div>

<!-- ── Invoice & Email ───────────────────────────── -->
<div class="card">
    <div class="card-header"><span class="card-title">Invoice & Email</span></div>
    <div class="card-body">
        <div class="flex">
            <a href="invoice.php?id=<?= $order_id ?>" target="_blank" class="btn">Preview Invoice</a>
            <button class="btn btn-success" onclick="sendInvoice()">Email Invoice to Customer</button>
            <button class="btn" onclick="sendStatusEmail()">Send Status Email</button>
        </div>
        <div id="emailResult"></div>
    </div>
</div>

<!-- ── Save / Delete ─────────────────────────────── -->
<div class="card">
    <div class="card-body">
        <div class="flex">
            <button class="btn btn-primary" onclick="saveOrder()">Save Changes</button>
            <button class="btn btn-danger" onclick="deleteOrder()">Delete Order</button>
        </div>
        <div id="saveResult"></div>
    </div>
</div>

</div><!-- /page-body -->

<script>
const ORDER_ID = <?= $order_id ?>;
const PROJECT_ID = <?= (int)$order['project_id'] ?>;

// ── Save ─────────────────────────────────────────────────
async function saveOrder() {
    const r = document.getElementById('saveResult');
    r.innerHTML = '<span style="color:var(--text-sec)">Saving…</span>';

    const addr = buildAddress();
    const fd = new FormData();
    fd.append('action', 'save_order');
    fd.append('id', ORDER_ID);
    fd.append('order_number',       document.getElementById('orderNumber').value);
    fd.append('project_id',         document.getElementById('orderProject').value);
    fd.append('customer_name',      document.getElementById('orderCustomer').value);
    fd.append('customer_email',     document.getElementById('orderEmail').value);
    fd.append('customer_phone',     document.getElementById('orderPhone').value);
    fd.append('customer_callsign',  document.getElementById('orderCallsign').value);
    fd.append('quantity',           document.getElementById('orderQty').value);
    fd.append('price_paid',         document.getElementById('orderPrice').value);
    fd.append('order_date',         document.getElementById('orderDate').value);
    fd.append('status',             document.getElementById('orderStatus').value);
    fd.append('tracking_number',    document.getElementById('orderTracking').value);
    fd.append('shipping_charge',    document.getElementById('orderShippingCharge').value);
    fd.append('mail_service',       document.getElementById('orderMailService').value);
    fd.append('notes',              document.getElementById('orderNotes').value);
    fd.append('source',             document.getElementById('orderSource').value);
    fd.append('ship_street',        document.getElementById('shipStreet').value);
    fd.append('ship_street2',       document.getElementById('shipStreet2').value);
    fd.append('ship_city',          document.getElementById('shipCity').value);
    fd.append('ship_state',         document.getElementById('shipState').value);
    fd.append('ship_zip',           document.getElementById('shipZip').value);
    fd.append('ship_country',       document.getElementById('shipCountry').value);
    fd.append('shipping_address',   addr);

    try {
        const resp = await fetch('api.php', { method: 'POST', body: fd });
        const d = await resp.json();
        if (d.success) {
            r.innerHTML = '<span class="notice n-success" style="display:inline-block;">Saved.</span>';
            setTimeout(() => r.innerHTML = '', 3000);
        } else {
            r.innerHTML = `<div class="notice n-danger">${d.error || 'Save failed.'}</div>`;
        }
    } catch(e) {
        r.innerHTML = '<div class="notice n-danger">Request failed.</div>';
    }
}

function buildAddress() {
    return [
        document.getElementById('shipStreet').value,
        document.getElementById('shipStreet2').value,
        document.getElementById('shipCity').value + ', ' + document.getElementById('shipState').value + ' ' + document.getElementById('shipZip').value,
    ].filter(p => p.trim()).join('\n');
}

async function deleteOrder() {
    if (!confirm('Delete this order? Cannot be undone.')) return;
    const fd = new FormData();
    fd.append('action', 'delete_order');
    fd.append('id', ORDER_ID);
    await fetch('api.php', { method: 'POST', body: fd });
    window.location.href = 'index.php';
}

// ── Invoice / Email ──────────────────────────────────────
async function sendInvoice() {
    const r = document.getElementById('emailResult');
    r.innerHTML = '<span style="color:var(--text-sec);">Sending invoice…</span>';
    const fd = new FormData();
    fd.append('action', 'send_invoice');
    fd.append('order_id', ORDER_ID);
    try {
        const resp = await fetch('api.php', { method:'POST', body:fd });
        const d = await resp.json();
        r.innerHTML = d.success
            ? `<div class="notice n-success">${d.message}</div>`
            : `<div class="notice n-danger">${d.error}</div>`;
    } catch(e) { r.innerHTML = '<div class="notice n-danger">Request failed.</div>'; }
}

async function sendStatusEmail() {
    const r = document.getElementById('emailResult');
    r.innerHTML = '<span style="color:var(--text-sec);">Sending…</span>';
    const fd = new FormData();
    fd.append('action', 'send_customer_email');
    fd.append('order_id', ORDER_ID);
    try {
        const resp = await fetch('send_customer_email.php', { method:'POST', body:fd });
        const d = await resp.json();
        r.innerHTML = d.success
            ? `<div class="notice n-success">${d.message}</div>`
            : `<div class="notice n-danger">${d.error}</div>`;
    } catch(e) { r.innerHTML = '<div class="notice n-danger">Request failed.</div>'; }
}
</script>
</body>
</html>
