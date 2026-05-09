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

## What Was Removed (USPS)

The USPS API integration (address validation, rate lookup, tracking, label generation) was fully removed in May 2026. The shipping weight and dimension fields (`ship_weight_oz`, `pkg_length`, `pkg_width`, `pkg_height`) on the `projects` table were kept — they remain useful for shipping calculations. The `mail_service` and `tracking_number` fields on `orders` were also kept for manual entry.
