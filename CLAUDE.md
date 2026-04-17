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
| `api.php` | Central AJAX backend. All `action=` requests are dispatched via `if ($action === '...')` chains — no routing framework. ~750 lines. |
| `config.php` | DB connection (`getDB()`), session bootstrap, auth helpers (`isLoggedIn()`, `requireLogin()`, `jsonResponse()`). |
| `business_metrics.php` | Separate P&L/metrics endpoint — queried by `index.php` for the Business tab. |
| `order_webhook.php` | Receives POST from the standalone order form (CORS-open). Inserts orders and sends email to cr@christopherreddick.com. |
| `quick_order.php` | Auth-required UI for parsing WPForms notification emails and manually entering orders. |
| `get_active_projects.php` | Public endpoint returning active projects for the external order form. |
| `standalone_order_form.html` | Self-contained customer-facing order form (no PHP). Posts to `order_webhook.php`. |
| `wordpress_safe_order_form.html` | Same form, safe for embedding in WordPress. |
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
