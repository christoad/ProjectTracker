# KI6CR Kits Project & Inventory Manager

A single-operator ham radio kit business management tool for tracking parts inventory, kit projects (BOMs), customer orders, and business P&L. Built for KI6CR Labs — the kit operation behind [ki6cr-labs.com](https://ki6cr-labs.com).

**Live app:** [ki6cr.com/projects/](https://ki6cr.com/projects/)

---

## What It Does

- **Parts inventory** — master parts list with stock levels, minimum stock alerts, and weighted-average cost tracking across multiple suppliers
- **Bill of Materials (BOM)** — per-kit BOMs with support for fixed parts (same for all variations) and variable parts (specific to a color, connector type, etc.)
- **Orders** — customer order tracking with status workflow (pending → paid → shipped → completed), linked to WooCommerce
- **WooCommerce sync** — two-way inventory sync with the webstore; webhooks deduct stock automatically when an order is placed
- **Shippo integration** — when you print a shipping label in Shippo, the tracker automatically marks the order shipped with tracking info and updates WooCommerce
- **Business P&L** — revenue, cost of goods, expenses, and profit metrics per kit and overall

---

## Architecture

This project lives alongside a companion WooCommerce store but they are separate codebases with a clear division of responsibility.

### ProjectTracker (this repo)
- The inventory management app and all backend logic
- Deploys to: `ki6cr.com/projects/`
- Contains: all PHP logic, webhook handlers, AJAX API, database schema

### KI6CR-LABS-Webstore (separate folder)
- WooCommerce theme customizations and plugins only
- Deploys to: `ki6cr-labs.com/`
- Does NOT contain webhook handlers, sync logic, or `api.php`

If you find copies of `api.php`, `woocommerce_webhook.php`, `woocommerce_sync.php`, or `shippo_webhook.php` in the webstore folder, they are stale duplicates — delete them.

---

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | PHP (no framework) |
| Database | MySQL via PDO |
| Frontend | Vanilla JS + HTML/CSS (no frameworks) |
| Auth | PHP session-based |
| Hosting | DreamHost shared hosting |
| Webstore | WooCommerce on WordPress |
| Shipping | Shippo |

---

## File Reference

| File | Role |
|------|------|
| `index.php` | Single-page app: all HTML, CSS (~800 lines), JS (~1700 lines) |
| `api.php` | Central AJAX backend; all `action=` requests dispatched here |
| `config.php` | DB connection (`getDB()`), session bootstrap, auth helpers |
| `woocommerce_webhook.php` | Receives WooCommerce order webhooks (POST); also manual sync trigger (GET) |
| `woocommerce_sync.php` | All WC integration helpers: stock calc, push, deduct, restore, order management |
| `shippo_webhook.php` | Receives Shippo `transaction_created` webhooks; updates tracker + WC order |
| `business_metrics.php` | P&L/metrics endpoint queried by the Business tab |
| `order_detail.php` | Standalone order detail page |
| `quick_order.php` | Auth-required UI for manually entering orders |
| `invoice.php` | Print-focused invoice page |
| `send_customer_email.php` | Sends order confirmation emails |
| `database_schema.sql` | Full schema; import once when setting up a new instance |
| `db_migrate.php` | One-off schema migration runner (delete from server after running) |

---

## Database Schema

### Core Tables

**`projects`** — Kit products
- `id`, `project_name`, `description`, `status` (active/archived/planning)
- `retail_price`, `woocommerce_product_id`

**`parts`** — Shared component inventory
- `id`, `part_number`, `part_name`, `category`
- `current_stock`, `min_stock_level`, `weighted_avg_cost`

**`project_parts`** — Bill of Materials
- Links parts to projects with `quantity_required`
- `variation_attribute` / `variation_value` — empty = fixed part; non-empty = variation-specific part

**`project_variation_mappings`** — WooCommerce variation IDs
- Maps a `combo_key` (e.g. `Color:Blue`) to a WooCommerce variation product ID

**`part_sources`** — Supplier options per part
- `supplier_name`, `cost`, `url`, `is_preferred`

**`orders`** — Customer orders
- `order_number` uses `WC-{id}` format for WooCommerce orders
- `inventory_deducted` flag prevents double-deductions from duplicate webhooks
- `variation_combo_key` stores which variation was ordered (needed for restores on cancellation)

**`inventory_checkins`** — Stock receipt log
- Drives `weighted_avg_cost` recalculation on each check-in

**`project_expenses`** — Research/dev costs per project (used in P&L)

---

## BOM: Fixed vs Variable Parts

Every BOM row is either **fixed** (applies to all orders) or **variable** (applies only to one variation option).

| Type | `variation_attribute` | `variation_value` | When deducted |
|------|-----------------------|-------------------|---------------|
| Fixed | `''` | `''` | Every order |
| Variable | e.g. `Color` | e.g. `Blue` | Only when that variation is ordered |

**Example — CEC CW Cable Adaptor:**
- CONN-003 connector (fixed) → deducted on every order
- 42-010 Blue cable (variable, Color=Blue) → deducted only for Blue orders
- 42-009 Grey cable (variable, Color=Grey) → deducted only for Grey orders

---

## WooCommerce Integration

### How a new order flows through the system

1. Customer buys on ki6cr-labs.com
2. WooCommerce fires two webhooks nearly simultaneously (`order.created` and `order.updated`, both with status `processing`)
3. Both hit `woocommerce_webhook.php`
4. First webhook wins a database row lock (`SELECT ... FOR UPDATE`), deducts BOM inventory, inserts an order row with `inventory_deducted=1`
5. Second webhook sees the lock is released, checks `inventory_deducted=1`, and exits without touching inventory
6. Stock counts are pushed back to WooCommerce via REST API

### Order status → inventory action

| WC Status | Action |
|-----------|--------|
| `processing` | Deduct BOM inventory |
| `on-hold` | Deduct BOM inventory |
| `completed` | No action (already deducted) |
| `cancelled` | Restore inventory |
| `refunded` | Restore inventory |
| All others | Skipped |

`completed` intentionally does nothing — Shippo moves orders to `completed` when a label is printed, which fires another webhook. Deducting on `completed` would double-deduct every shipment.

### Shippo label flow

1. Label is printed in Shippo
2. Shippo fires `transaction_created` to `shippo_webhook.php`
3. Webhook resolves the WC order ID from Shippo's REST API
4. Tracker updates: `status = shipped`, tracking number, tracking URL, `shipped_at`
5. WooCommerce order moves to `completed` via REST API
6. Tracking note is added to WC order for the customer

### Manual sync (when the UI isn't usable)

```bash
ssh dreamhost-sota "curl -s 'https://ki6cr.com/projects/woocommerce_webhook.php?action=sync&project_id=X'"
```

Note: Safari blocks URLs containing "woocommerce" due to content blockers. All sync calls from the UI are routed through `api.php` actions (`wc_status`, `wc_sync`, `wc_sync_all`) to avoid this.

---

## Race Condition Protection

WooCommerce reliably delivers duplicate webhooks for every new order. Two layers of protection prevent double-deductions:

1. **Application lock** — `SELECT ... FOR UPDATE` inside a PDO transaction; the second concurrent request blocks on the lock, then sees `inventory_deducted=1` and exits
2. **Database constraint** — a UNIQUE constraint on `orders(order_number, project_id)` prevents duplicate rows even if application logic fails

---

## Cost & Profit Logic

- `weighted_avg_cost` on each part is recalculated on every inventory check-in using a weighted average formula
- BOM cost uses weighted avg cost first, then falls back to preferred supplier price, then lowest available supplier price
- Buildable kit count = `floor(min(stock / qty_required))` across all fixed BOM parts, constrained by each variation group

---

## Setup (New Instance)

1. **Create the database** — import `database_schema.sql` via phpMyAdmin or MySQL CLI
2. **Configure environment** — copy `.env.example` to `.env` and fill in credentials (DB host, name, user, password; WooCommerce REST API keys; Shippo API key)
3. **Upload files** — rsync all PHP/JS files to the server (see Deployment below)
4. **Change the default password** — log in as `admin` / `admin123` and change it immediately
5. **Register webhooks in WooCommerce** — WC Admin → Settings → Advanced → Webhooks:
   - `Order created` → `https://ki6cr.com/projects/woocommerce_webhook.php`
   - `Order updated` → `https://ki6cr.com/projects/woocommerce_webhook.php`
6. **Register webhook in Shippo** — Shippo dashboard → Webhooks → `transaction_created` → `https://ki6cr.com/projects/shippo_webhook.php`
7. **Run any pending migrations** — deploy `db_migrate.php`, hit it in the browser with the password, delete it after

### Environment variables (`.env`)

```
DB_HOST=
DB_NAME=
DB_USER=
DB_PASS=
WC_STORE_URL=https://ki6cr-labs.com
WC_CONSUMER_KEY=ck_...
WC_CONSUMER_SECRET=cs_...
SHIPPO_API_KEY=...
```

---

## Deployment

Uses rsync via the `dreamhost-sota` SSH alias (key auth configured in `~/.ssh/config`).

**Deploy a single file:**
```bash
rsync -avz "file.php" dreamhost-sota:/home/chrisr069/ki6cr.com/projects/
```

**Deploy all PHP/JS files:**
```bash
rsync -avz --include="*.php" --include="*.js" --exclude="*" . dreamhost-sota:/home/chrisr069/ki6cr.com/projects/
```

`.env` is never committed to git and must be created manually on the server.

---

## Debugging

### Inventory didn't deduct after an order

1. Check the `orders` table — was a row created? Is `inventory_deducted = 1`? What is `variation_combo_key`?
2. Check `project_variation_mappings` — does the `combo_key` on the order match any row? Does it exactly match the `variation_attribute`/`variation_value` pairs in `project_parts`?
3. Check WooCommerce webhook delivery log — WC Admin → Settings → Advanced → Webhooks → Edit → Recent Deliveries — the response body contains a full `item_log` with per-part deduction results

### Variable parts not deducting (fixed parts are fine)

This usually means stale variation mappings. The `combo_key` stored in `project_variation_mappings` must exactly match the `variation_attribute` + `variation_value` pairs in `project_parts`. Fix: delete the stale rows and re-enter via the project's Variation Mappings UI.

### Combo key rule

Combo keys are always passed as raw strings. Functions in `woocommerce_sync.php` parse them internally.

```php
// CORRECT
wc_deduct_bom_inventory($db, $project_id, $qty, 'Color:Blue');

// WRONG — variable parts will be silently skipped
$parsed = wc_parse_combo_key('Color:Blue');         // → ['Color' => 'Blue']
wc_deduct_bom_inventory($db, $project_id, $qty, $parsed); // BUG: PHP coerces array to "Array"
```

---

## Auth

Session-based. `requireLogin()` in `config.php` returns a 401 JSON response if not authenticated. The frontend `checkAuth()` function redirects to the login overlay on any 401. Sessions last 24 hours.

---

## UI Design

Single-page application in `index.php` — no build step, no JS framework, no bundler.

**Fonts (Google Fonts):**
- UI text / labels / buttons: `Figtree, sans-serif`
- Data / numbers / order IDs / part numbers: `IBM Plex Mono, monospace`

**Color palette:** warm olive/forest green theme — `#4a7c38` primary, `#251d12` nav dark, `#ede8df` body background.

---

## What Was Removed

- **USPS API integration** — removed May 2026. Weight/dimension fields on `projects` and `mail_service`/`tracking_number` on `orders` were kept for manual use.
- **`order_webhook.php`** — old order intake webhook, superseded by `woocommerce_webhook.php`.
