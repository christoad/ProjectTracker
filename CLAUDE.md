# CLAUDE.md — KI6CR Inventory Manager (ProjectTracker)

This file provides guidance to Claude Code when working in this repository.

---

## What This Is

KI6CR Inventory Manager — a single-operator ham radio kit business tool for tracking parts inventory, kit projects (BOMs), customer orders, and business P&L. Built in PHP/MySQL, deployed to a shared DreamHost server.

---

## Two-Project Architecture — READ THIS FIRST

This project (`ProjectTracker`) and the WooCommerce webstore (`KI6CR-LABS-Webstore`) are tightly coupled but live in separate folders. Understanding the division of responsibility is critical to avoid editing the wrong files.

### ProjectTracker — source of truth for everything backend
- **All inventory logic, webhook handlers, and API files live here.**
- Deploys to: `ki6cr.com/projects/` (the inventory management app)
- Git repo: this folder is the git repository

### KI6CR-LABS-Webstore — WooCommerce store customizations only
- Contains only WordPress/WooCommerce theme files and plugins:
  `ki6cr-custom.php`, `ki6cr-shipping-banner.php`, `index.php`, etc.
- Deploys to: `ki6cr-labs.com/` (the public-facing WooCommerce store)
- Does NOT contain webhook handlers, sync logic, or api.php

### Files that exist ONLY in ProjectTracker (never in KI6CR-LABS-Webstore)
- `api.php` — tracker AJAX backend
- `woocommerce_webhook.php` — WC order webhook handler + sync trigger endpoint
- `woocommerce_sync.php` — shared helper functions for WC integration
- `shippo_webhook.php` — Shippo label webhook handler

If you ever find copies of these files in `KI6CR-LABS-Webstore/`, they are stale duplicates — delete them from there and work only from `ProjectTracker/`.

---

## Deployment

Deploy via rsync using the `dreamhost-sota` SSH alias. Remote path: `/home/chrisr069/ki6cr.com/projects/`

```bash
rsync -avz "file.php" dreamhost-sota:/home/chrisr069/ki6cr.com/projects/
```

To deploy all PHP/JS files at once:
```bash
rsync -avz --include="*.php" --include="*.js" --exclude="*" . dreamhost-sota:/home/chrisr069/ki6cr.com/projects/
```

`.env` is never committed and must be created manually on the server from `.env.example`.

---

## File Reference

| File | Role |
|------|------|
| `index.php` | Single-page app: all HTML, CSS (~800 lines), and JS (~1700 lines). No external JS frameworks. |
| `api.php` | Central AJAX backend. All `action=` requests dispatched via `if ($action === '...')` chains. |
| `config.php` | DB connection (`getDB()`), session bootstrap, auth helpers. |
| `woocommerce_webhook.php` | Receives WooCommerce order webhooks (POST). Also serves as manual sync/status trigger (GET). |
| `woocommerce_sync.php` | All WC integration helpers: stock calc, push, deduct, restore, order management. |
| `shippo_webhook.php` | Receives Shippo `transaction_created` webhooks; updates tracker + WC order status. |
| `business_metrics.php` | Separate P&L/metrics endpoint queried by `index.php` for the Business tab. |
| `order_detail.php` | Standalone order detail page. |
| `quick_order.php` | Auth-required UI for manually entering orders. |
| `invoice.php` | Print-focused invoice page. |
| `send_customer_email.php` | Sends order confirmation emails. |

---

## Data Model (Key Tables)

- `projects` — kit products with `retail_price`, `status`, `woocommerce_product_id`
- `parts` — shared inventory with `current_stock`, `weighted_avg_cost`, `min_stock_level`
- `project_parts` — BOM: which parts (and `quantity_required`) belong to each project. Has `variation_attribute` and `variation_value` columns for variable kits.
- `project_variation_mappings` — maps each BOM combo_key to a WooCommerce variation ID
- `part_sources` — supplier options per part; one marked `is_preferred`
- `orders` — customer orders linked to a project; `order_number` uses "WC-{id}" format for WooCommerce orders
- `inventory_checkins` — stock receipt log; drives `weighted_avg_cost` via weighted average
- `project_expenses` — research/dev costs per project

---

## BOM Type System — Fixed vs Variable Parts

Every `project_parts` row is either **fixed** or **variable**:

| Type | `variation_attribute` | `variation_value` | Meaning |
|------|-----------------------|-------------------|---------|
| Fixed | `''` (empty string) | `''` | Shared across all variations; always deducted |
| Variable | e.g. `'Color'` | e.g. `'Blue'` | Only deducted when this specific value is ordered |

**Example — CEC CW Cable Adaptor:**
- CONN-003 (fixed) → deducted for every order regardless of color
- 42-010 Blue cable (variable, Color=Blue) → deducted only for Blue orders
- 42-009 Grey cable (variable, Color=Grey) → deducted only for Grey orders

The BOM UI in `index.php` lets you assign variation_attribute/variation_value when adding a part to a project.

---

## Combo Key Convention — CRITICAL

A **combo_key** is a pipe-delimited string representing a specific variation combination, e.g. `"Color:Blue"` or `"Color:Blue|Size:M"`.

**Rule: combo_key values are always passed as raw strings. Functions in `woocommerce_sync.php` parse them internally.**

```php
// CORRECT
wc_deduct_bom_inventory($db, $project_id, $qty, 'Color:Blue');
wc_calculate_variation_qty($db, $project_id, 'Color:Blue');

// WRONG — causes variable parts to be silently skipped
$parsed = wc_parse_combo_key('Color:Blue');  // → ['Color' => 'Blue']
wc_deduct_bom_inventory($db, $project_id, $qty, $parsed);  // BUG
```

This rule exists because PHP will silently coerce an array to the string "Array" in many contexts, causing `wc_parse_combo_key` to receive "Array" and return an empty result — meaning variable parts are never found and never deducted.

---

## WooCommerce Webhook Flow

### Order comes in (new purchase)

1. Customer buys on ki6cr-labs.com
2. WooCommerce fires **two** webhooks nearly simultaneously:
   - `order.created` (status = processing)
   - `order.updated` (status = processing)
3. Both hit `woocommerce_webhook.php` (POST)
4. Each webhook does `SELECT ... FOR UPDATE` inside a transaction — the second request blocks until the first commits
5. First request: deducts BOM inventory, inserts order row with `inventory_deducted=1`
6. Second request: sees `inventory_deducted=1`, skips (idempotent)
7. Pushes recalculated stock counts to WooCommerce via REST API

### Order statuses and their inventory effect

| WC Status | Action |
|-----------|--------|
| `processing` | Deduct inventory |
| `on-hold` | Deduct inventory |
| `completed` | **No action** (already deducted at processing) |
| `cancelled` | Restore inventory |
| `refunded` | Restore inventory |
| All others | Skipped |

**`completed` must NOT trigger deduction.** Shippo marks WooCommerce orders as `completed` when a label is printed, which fires another `order.updated` webhook. If `completed` were in the deduct list, it would double-deduct every time you ship.

### Shippo label printed

1. You print a label in Shippo
2. Shippo fires `transaction_created` to `shippo_webhook.php`
3. Webhook resolves the WC order ID via Shippo's REST API (`shop_order_id` field)
4. Updates tracker: `status = shipped`, `tracking_number`, `tracking_url`, `shipped_at = NOW()`
5. Calls WooCommerce REST API to set order status → `completed`
6. Adds customer-visible tracking note to WC order

### Order cancelled

1. WooCommerce fires `order.updated` (status = cancelled or refunded)
2. `woocommerce_webhook.php` looks up the original `variation_combo_key` stored on the order
3. Restores exactly the parts that were deducted (fixed + the original variation parts)
4. Sets `inventory_deducted = 0` on the order row

---

## Race Condition Protection

WooCommerce consistently delivers duplicate webhooks for new orders. Protection is applied at two levels:

1. **Application level:** The deduction block uses `SELECT ... FOR UPDATE` inside a PDO transaction. The second concurrent request blocks on the lock, then sees `inventory_deducted=1` and exits without touching inventory.

2. **Database level:** A UNIQUE constraint on `orders(order_number, project_id)` prevents duplicate rows from being inserted even if the application logic somehow fails. Run `db_migrate.php` to install this constraint.

---

## Variation Mapping Maintenance

The `project_variation_mappings` table stores `combo_key` values that must **exactly match** the `variation_attribute` + `variation_value` pairs in `project_parts`. If you restructure a WooCommerce product's variation attributes (rename, add, remove options), the mappings go stale.

**Symptoms of stale mappings:**
- Fixed parts (like CONN-003) deduct correctly
- Variable parts (specific color cables) do NOT deduct
- WooCommerce stock numbers diverge from tracker

**Fix:** Delete the stale rows from `project_variation_mappings` and re-enter correct mappings via the project's Variation Mappings UI in the tracker.

---

## Cost / Profit Logic

`weighted_avg_cost` on `parts` is recalculated on each `checkin_inventory` action. BOM cost uses weighted avg cost first, falling back to preferred supplier price, then lowest supplier price. Buildable kit count is `floor(min(current_stock / quantity_required))` across all fixed BOM parts, then constrained by each variation group.

---

## Auth

Session-based. `requireLogin()` returns 401 JSON if not authenticated — the frontend JS (`checkAuth()`) redirects to the login overlay on 401.

---

## UI Design System

### Fonts (Google Fonts)
```html
<link href="https://fonts.googleapis.com/css2?family=Figtree:wght@300;400;500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
```
- **UI text / labels / buttons:** `Figtree, sans-serif`
- **Data / numbers / order IDs / part numbers:** `IBM Plex Mono, monospace`

### Color Tokens
```css
:root {
  --bg-body: #e8f0fe;
  --bg-card: #f4f8ff;
  --bg-card-header: #eef3fd;
  --bg-card-alt-row: #f8fafe;
  --bg-light: #c7d9fb;
  --header-gradient: linear-gradient(135deg, #1a56db 0%, #0680c6 100%);
  --header-height: 56px;
  --nav-bg: #162038;
  --nav-border-bottom: #1a56db;
  --nav-tab-active-bg: #1a56db;
  --nav-tab-color: #5d729e;
  --accent-primary: #1a56db;
  --accent-primary-dim: #1240a8;
  --accent-secondary: #0680c6;
  --border-card: #c7d9fb;
  --border-table-head: #1a56db;
  --text-primary: #0f1c3f;
  --text-secondary: #6b7280;
  --text-dim: #9ca3af;
  --success: #10b981;
  --warning: #f59e0b;
  --danger: #ef4444;
  --info: #3b82f6;
  --shadow-card: 0 2px 8px rgba(10,30,100,0.06);
  --shadow-header: 0 2px 16px rgba(15,28,63,0.22);
  --shadow-modal: 0 20px 60px rgba(0,0,0,0.30);
  --font-body: 'Figtree', sans-serif;
  --font-mono: 'IBM Plex Mono', monospace;
  --radius-sm: 3px;
  --radius-md: 4px;
  --radius-card: 6px;
  --radius-modal: 8px;
  --z-header: 100;
  --z-modal: 1000;
}
```

---

## WooCommerce Integration — Operational Notes

### Variation mappings must stay in sync with the BOM
The `project_variation_mappings` table stores `combo_key` values like `Color:Grey` that must **exactly match** the `variation_attribute` + `variation_value` pairs in `project_parts`. If you restructure a WooCommerce product's variation attributes (rename, add, remove), the mappings table goes stale and orders will silently fail to deduct variable parts (fixed parts still deduct correctly). Fix: delete the stale rows and re-enter correct mappings via the project's Variation Mappings UI.

### Variable product parent stock management
WooCommerce parent products must have `manage_stock: false` — otherwise the parent-level stock overrides all variation availability. The sync code (`wc_sync_project`) automatically PUTs `manage_stock: false` on the parent before pushing per-variation stock.

### Safari content blocker workaround
Safari blocks requests to URLs containing "woocommerce". All WC sync calls from the UI go through `api.php` (actions: `wc_status`, `wc_sync`, `wc_sync_all`) to avoid this. The raw `woocommerce_webhook.php` endpoint still exists for WooCommerce's own webhook deliveries and for SSH-based manual syncs.

### Manual sync via SSH (when UI isn't usable)
```bash
ssh dreamhost-sota "curl -s 'https://ki6cr.com/projects/woocommerce_webhook.php?action=sync&project_id=X'"
```

### Debugging a missed deduction
If inventory doesn't deduct after an order:
1. Check `orders` table — was a row created? Is `inventory_deducted = 1`? What is `variation_combo_key`?
2. Check `project_variation_mappings` — does the combo_key on the order match any row? Does it match the actual `variation_attribute`/`variation_value` in `project_parts`?
3. Check the WooCommerce webhook delivery log (WC Admin → Settings → Advanced → Webhooks → Edit → Recent Deliveries) — the response body contains a full `item_log` with per-part deduction results.

---

## WooCommerce Integration — Roadmap / TODO

### Shipping Strategy
- **Domestic:** Free shipping for US orders (decided).
- **International:** Live Shippo carrier rates (UPS, USPS, DHL — confirmed working).
- **Low-margin items:** If cheapest shipping is $5–8 (e.g. CEC cable adaptor), that kills margin. Confirm whether USPS First Class Package (~$3–5) is available in WooCommerce/Shippo for these SKUs.

### Replacement Parts Store
- Add a WooCommerce Variable Product per kit for replacement parts
- Each variation = a specific BOM part
- Contact-only for now (no cart) — price at $0 or use Request a Quote plugin
- No inventory sync needed for replacement parts

### Shippo Webhook End-to-End Test
- Create a WooCommerce order → print a label in Shippo → confirm:
  - Tracker order updates with tracking number and status = shipped
  - WooCommerce order moves to Completed
  - Customer receives tracking note
- After confirmed: remove debug logging from `shippo_webhook.php` if any remains

---

## What Was Removed

- **USPS API integration** — fully removed in May 2026. Shipping weight/dimension fields on `projects` were kept. `mail_service` and `tracking_number` on `orders` kept for manual entry.
- **`order_webhook.php`** — old order intake webhook, superseded by `woocommerce_webhook.php`.
