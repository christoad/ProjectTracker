# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What This Is

KI6CR Inventory Manager — a single-operator ham radio kit business tool for tracking parts inventory, kit projects (BOMs), customer orders, and business P&L. Built in PHP/MySQL, deployed to a shared DreamHost server.

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

## Architecture

All files are flat in the project root — no subdirectory structure.

| File | Role |
|------|------|
| `index.php` | Single-page app: all HTML, CSS (~800 lines), and JS (~1700 lines). No external JS frameworks. |
| `api.php` | Central AJAX backend. All `action=` requests are dispatched via `if ($action === '...')` chains — no routing framework. |
| `config.php` | DB connection (`getDB()`), session bootstrap, auth helpers (`isLoggedIn()`, `requireLogin()`, `jsonResponse()`). No USPS constants — those were removed. |
| `business_metrics.php` | Separate P&L/metrics endpoint — queried by `index.php` for the Business tab. |
| `order_webhook.php` | Receives POST from external order forms (CORS-open). Will be repurposed or replaced for WooCommerce. |
| `quick_order.php` | Auth-required UI for parsing WPForms notification emails and manually entering orders. |
| `get_active_projects.php` | Public endpoint returning active projects. Will be extended for WooCommerce stock sync. |
| `send_customer_email.php` | Sends order confirmation emails to customers. |
| `javascript_additions.js` / `sortable_tables.js` | JS snippets — check if these are currently wired into `index.php` before editing. |

## Data Model (Key Tables)

- `projects` — kit products with `retail_price`, `status` (active/archived)
- `parts` — shared inventory with `current_stock`, `weighted_avg_cost`, `min_stock_level`
- `project_parts` — BOM: which parts (and `quantity_required`) belong to each project
- `part_sources` — supplier options per part; one marked `is_preferred`
- `orders` — customer orders linked to a project; includes shipping, tracking, status
- `inventory_checkins` — stock receipt log; drives `weighted_avg_cost` via weighted average
- `project_expenses` — research/dev costs per project (subtracted in net profit calc)
- `users` — single-user auth with `password_hash`

## Cost / Profit Logic

`weighted_avg_cost` on `parts` is recalculated on each `checkin_inventory` action (new stock received). BOM cost uses weighted avg cost first, falling back to preferred supplier price, then lowest supplier price. Buildable kit count is `floor(min(current_stock / quantity_required))` across all BOM parts. Profit = revenue from sold orders minus COGS and `project_expenses`.

## BOM CSV Export

`exportBOM()` in `index.php` generates a CSV client-side from the loaded project data. The filename format is `BOM_<project_slug>_<date>.csv`.

## Auth

Session-based. `requireLogin()` returns 401 JSON if not authenticated — the frontend JS (`checkAuth()`) redirects to the login overlay on 401.

## WooCommerce Integration — Roadmap

The customer-facing storefront will be a WooCommerce store on **KI6CR-labs.com**. WooCommerce has not been installed yet (as of 2026-05-08). This inventory tool will serve as the back-end source of truth for inventory and orders; WooCommerce will be the public-facing store.

**Architecture intent:**
- This tool is the inventory source of truth. WooCommerce does not understand BOMs or parts — only buildable kit counts pushed to it.
- WooCommerce receives customer orders and payments; it pushes new orders into this tool via webhook.
- When orders are fulfilled here (status → shipped/completed), inventory is deducted and updated stock counts are pushed back to WooCommerce.

**Planned API work (not yet built):**

| Phase | What | Notes |
|-------|------|-------|
| 1 | `wc_get_stock` endpoint | Returns buildable kit count per project. WooCommerce polls or is notified. Secured with shared API key in `.env`. |
| 2 | `wc_new_order` endpoint | Receives new WooCommerce orders and creates records in the `orders` table. Replaces old `order_webhook.php` flow. |
| 3 | Push stock updates to WooCommerce | After check-in or fulfillment, call WooCommerce REST API to sync product stock. Requires `wc_product_id` field added to `projects` table. |

**When WooCommerce is installed**, add to `.env`:
- `WC_SITE_URL` — base URL of the WooCommerce store
- `WC_API_KEY` / `WC_API_SECRET` — WooCommerce REST API credentials
- `WC_WEBHOOK_SECRET` — shared secret to validate inbound WooCommerce webhooks

**Projects table will need:** `wc_product_id` column to map each kit to its WooCommerce product.

## UI Design System

The site uses a consistent design system across all pages. When adding new pages or UI components, match these specifications exactly.

### Fonts (Google Fonts)
```html
<link href="https://fonts.googleapis.com/css2?family=Figtree:wght@300;400;500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
```
- **UI text / labels / buttons:** `Figtree, sans-serif`
- **Data / numbers / order IDs / callsigns / part numbers:** `IBM Plex Mono, monospace`

### Color Tokens (copy this `:root` block into any new page)
```css
:root {
  --bg-body: #e8f0fe;           /* page background */
  --bg-card: #f4f8ff;           /* card / panel */
  --bg-card-header: #eef3fd;    /* card header strip & table head */
  --bg-card-alt-row: #f8fafe;   /* alternating table row */
  --bg-light: #c7d9fb;          /* secondary button bg, tints */
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

### Page Structure (every page)
Every page — including new ones — must have:
1. A gradient app header (`<header class="app-header">`) with the diamond logo block on the left and navigation/actions on the right.
2. Content area with `padding: 20px 32px` and `max-width: 1400px` (or use `.page-body` for narrower pages like order_detail).

**Header HTML pattern:**
```html
<header class="app-header">
  <div class="app-logo-block">
    <div class="app-logo-icon"><div class="app-logo-diamond"></div></div>
    <div>
      <div class="app-logo-callsign">KI6CR</div>
      <div class="app-logo-subtitle">Inventory Manager</div>
    </div>
  </div>
  <div class="user-info">
    <!-- nav buttons / logout -->
  </div>
</header>
```

### Cards
Cards have `padding: 0`. Use `.card-header` (with `.card-title`) for the header strip and `.card-body` for content. Tables sit directly inside `.card` inside a `.table-container` — no extra padding.

### Badges
Use translucent backgrounds, not solid colors:
- `.badge-success`: `rgba(16,185,129,0.13)` / `#10b981`
- `.badge-warning`: `rgba(245,158,11,0.13)` / `#f59e0b`
- `.badge-danger`: `rgba(239,68,68,0.13)` / `#ef4444`
- `.badge-info`: `rgba(59,130,246,0.13)` / `#3b82f6`

### Files that implement the design system
| File | Notes |
|------|-------|
| `index.php` | Full implementation — use this as the reference |
| `order_detail.php` | Standalone page pattern (narrow `.page-body`) |
| `quick_order.php` | Standalone page with card + form pattern |
| `invoice.php` | Print-focused; fonts updated, gradient header |

---

## What Was Removed (USPS)

The USPS API integration (address validation, rate lookup, tracking, label generation) was fully removed in May 2026. The shipping weight and dimension fields (`ship_weight_oz`, `pkg_length`, `pkg_width`, `pkg_height`) on the `projects` table were kept — they remain useful for shipping calculations. The `mail_service` and `tracking_number` fields on `orders` were also kept for manual entry.
