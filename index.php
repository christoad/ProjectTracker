<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KI6CR Inventory Manager</title>
    <style>
        :root {
            --bg-dark: #f8f9fa;
            --bg-medium: #ffffff;
            --bg-light: #f1f3f5;
            --accent-primary: #2563eb;
            --accent-primary-dim: #1d4ed8;
            --accent-secondary: #0891b2;
            --text-primary: #1f2937;
            --text-secondary: #6b7280;
            --text-dim: #9ca3af;
            --border-color: #e5e7eb;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #3b82f6;
            --shadow: rgba(0, 0, 0, 0.1);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Courier New', 'Monaco', monospace;
            background: var(--bg-dark);
            color: var(--text-primary);
            line-height: 1.6;
        }
        
        /* Header */
        .app-header {
            background: var(--bg-medium);
            border-bottom: 2px solid var(--accent-primary);
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }
        
        .app-logo {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--accent-primary);
            letter-spacing: 2px;
            text-transform: uppercase;
        }
        
        .app-logo::before {
            content: '◆ ';
            color: var(--accent-secondary);
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            color: var(--text-secondary);
        }
        
        /* Navigation */
        .nav-tabs {
            background: var(--bg-medium);
            padding: 0 2rem;
            display: flex;
            gap: 0.5rem;
            border-bottom: 1px solid var(--border-color);
        }
        
        .nav-tab {
            padding: 0.75rem 1.5rem;
            background: transparent;
            border: none;
            color: var(--text-secondary);
            cursor: pointer;
            transition: all 0.2s;
            border-bottom: 2px solid transparent;
            font-family: inherit;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .nav-tab:hover {
            color: var(--text-primary);
            background: var(--bg-light);
        }
        
        .nav-tab.active {
            color: var(--accent-primary);
            border-bottom-color: var(--accent-primary);
        }
        
        /* Main Content */
        .main-content {
            padding: 2rem;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .section {
            display: none;
        }
        
        .section.active {
            display: block;
            animation: fadeIn 0.3s;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Cards */
        .card {
            background: var(--bg-medium);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.2);
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
        }
        
        .card-title {
            font-size: 1.25rem;
            color: var(--accent-primary);
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        /* Buttons */
        .btn {
            padding: 0.6rem 1.2rem;
            border: 1px solid var(--border-color);
            background: var(--bg-light);
            color: var(--text-primary);
            cursor: pointer;
            font-family: inherit;
            font-size: 0.9rem;
            border-radius: 4px;
            transition: all 0.2s;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .btn:hover {
            background: var(--bg-medium);
            border-color: var(--accent-primary);
            color: var(--accent-primary);
        }
        
        .btn-primary {
            background: var(--accent-primary);
            border-color: var(--accent-primary);
            color: var(--bg-dark);
        }
        
        .btn-primary:hover {
            background: var(--accent-primary-dim);
            border-color: var(--accent-primary-dim);
            color: var(--bg-dark);
        }
        
        .btn-danger {
            background: var(--danger);
            border-color: var(--danger);
            color: white;
        }
        
        .btn-danger:hover {
            background: #dc2626;
            border-color: #dc2626;
        }
        
        .btn-small {
            padding: 0.4rem 0.8rem;
            font-size: 0.8rem;
        }
        
        /* Forms */
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-secondary);
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .form-input,
        .form-select,
        .form-textarea {
            width: 100%;
            padding: 0.6rem;
            background: var(--bg-dark);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            font-family: inherit;
            border-radius: 4px;
            transition: all 0.2s;
        }
        
        .form-input:focus,
        .form-select:focus,
        .form-textarea:focus {
            outline: none;
            border-color: var(--accent-primary);
            box-shadow: 0 0 0 2px rgba(255, 149, 0, 0.1);
        }
        
        .form-textarea {
            resize: vertical;
            min-height: 80px;
        }
        
        /* Tables */
        .table-container {
            overflow-x: auto;
            border: 1px solid var(--border-color);
            border-radius: 4px;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }
        
        .data-table thead {
            background: var(--bg-light);
        }
        
        .data-table th {
            padding: 0.75rem;
            text-align: left;
            color: var(--text-secondary);
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
            border-bottom: 2px solid var(--accent-primary);
        }
        
        .data-table td {
            padding: 0.75rem;
            border-bottom: 1px solid var(--border-color);
        }
        
        .data-table tbody tr:hover {
            background: var(--bg-light);
        }
        
        .data-table tbody tr:last-child td {
            border-bottom: none;
        }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: var(--bg-medium);
            border: 1px solid var(--border-color);
            border-left: 4px solid var(--accent-primary);
            padding: 1.5rem;
            border-radius: 4px;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: var(--accent-primary);
            font-family: 'Courier New', monospace;
        }
        
        .stat-label {
            color: var(--text-secondary);
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 1px;
            margin-top: 0.5rem;
        }
        
        /* Login Screen */
        .login-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: linear-gradient(135deg, var(--bg-dark) 0%, #0a0a0a 100%);
        }
        
        .login-box {
            background: var(--bg-medium);
            border: 2px solid var(--accent-primary);
            border-radius: 8px;
            padding: 3rem;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.5);
        }
        
        .login-title {
            text-align: center;
            font-size: 2rem;
            color: var(--accent-primary);
            margin-bottom: 2rem;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        
        /* Badges */
        .badge {
            display: inline-block;
            padding: 0.25rem 0.6rem;
            font-size: 0.75rem;
            border-radius: 3px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .badge-success { background: var(--success); color: var(--bg-dark); }
        .badge-warning { background: var(--warning); color: var(--bg-dark); }
        .badge-danger { background: var(--danger); color: white; }
        .badge-info { background: var(--info); color: white; }
        .badge-secondary { background: var(--bg-light); color: var(--text-secondary); }
        
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.8);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: var(--bg-medium);
            border: 2px solid var(--accent-primary);
            border-radius: 8px;
            padding: 2rem;
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            transition: all 0.3s ease;
        }
        
        /* Wide modal for parts, inventory, etc. */
        .modal-content.modal-wide {
            max-width: 1000px;
        }
        
        /* Expanded modal takes most of screen */
        .modal-content.modal-expanded {
            max-width: 95vw;
            max-height: 95vh;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
        }
        
        .modal-title {
            font-size: 1.25rem;
            color: var(--accent-primary);
        }
        
        .close-modal {
            background: none;
            border: none;
            color: var(--text-secondary);
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .close-modal:hover {
            color: var(--accent-primary);
        }
        
        .expand-modal {
            background: none;
            border: none;
            color: var(--text-secondary);
            font-size: 1.2rem;
            cursor: pointer;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 0.5rem;
        }
        
        .expand-modal:hover {
            color: var(--accent-primary);
        }
        
        /* Utility Classes */
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .mt-1 { margin-top: 1rem; }
        .mb-1 { margin-bottom: 1rem; }
        .flex { display: flex; }
        .flex-between { display: flex; justify-content: space-between; }
        .flex-gap { gap: 1rem; }
        .hidden { display: none !important; }
        
        .stock-low {
            color: var(--warning);
            font-weight: bold;
        }
        
        .stock-ok {
            color: var(--success);
        }
        
        .clickable {
            cursor: pointer;
        }
        
        .clickable:hover {
            color: var(--accent-primary);
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
            
            .main-content {
                padding: 1rem;
            }
            
            .app-header {
                padding: 1rem;
            }
            
            .nav-tabs {
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
    <!-- Login Screen -->
    <div id="loginScreen" class="login-container">
        <div class="login-box">
            <div class="login-title">◆ KI6CR</div>
            <form id="loginForm">
                <div class="form-group">
                    <label class="form-label">Username</label>
                    <input type="text" id="loginUsername" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" id="loginPassword" class="form-input" required>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%;">Login</button>
                <div id="loginError" class="hidden" style="margin-top: 1rem; color: var(--danger); text-align: center;"></div>
            </form>
        </div>
    </div>

    <!-- Main Application -->
    <div id="mainApp" class="hidden">
        <header class="app-header">
            <div class="app-logo">KI6CR Inventory</div>
            <div class="user-info">
                <span id="username"></span>
                <button class="btn btn-small" onclick="logout()">Logout</button>
            </div>
        </header>

        <nav class="nav-tabs">
            <button class="nav-tab active" onclick="showSection('dashboard')">Dashboard</button>
            <button class="nav-tab" onclick="showSection('projects')">Projects</button>
            <button class="nav-tab" onclick="showSection('parts')">Parts Inventory</button>
            <button class="nav-tab" onclick="showSection('orders')">Orders</button>
            <button class="nav-tab" onclick="window.location='quick_order.php'">Quick Order</button>
            <button class="nav-tab" onclick="showSection('business')">📊 Business</button>
            <button class="nav-tab" onclick="showSection('settings')">Settings</button>
        </nav>

        <main class="main-content">
            <!-- Dashboard Section -->
            <section id="dashboard" class="section active">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-value" id="statProjects">0</div>
                        <div class="stat-label">Active Projects</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value" id="statParts">0</div>
                        <div class="stat-label">Total Parts</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value" id="statLowStock">0</div>
                        <div class="stat-label">Low Stock Alerts</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value" id="statOrders">0</div>
                        <div class="stat-label">Pending Orders</div>
                    </div>
                </div>

                <div class="grid-2">
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">Low Stock Parts</h2>
                        </div>
                        <div id="lowStockList"></div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">Recent Orders</h2>
                        </div>
                        <div id="recentOrdersList"></div>
                    </div>
                </div>
            </section>

            <!-- Projects Section -->
            <section id="projects" class="section">
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Projects / Kits</h2>
                        <button class="btn btn-primary" onclick="openProjectModal()">+ New Project</button>
                    </div>
                    <div class="table-container">
                        <table class="data-table" id="projectsTable">
                            <thead>
                                <tr>
                                    <th style="cursor: pointer; user-select: none;" onclick="sortTable('projects', 'project_name')" title="Click to sort">Project Name <span id="sort-projects-project_name"></span></th>
                                    <th style="cursor: pointer; user-select: none;" onclick="sortTable('projects', 'description')" title="Click to sort">Description <span id="sort-projects-description"></span></th>
                                    <th style="cursor: pointer; user-select: none;" onclick="sortTable('projects', 'status')" title="Click to sort">Status <span id="sort-projects-status"></span></th>
                                    <th style="cursor: pointer; user-select: none;" onclick="sortTable('projects', 'parts_count')" title="Click to sort">Parts <span id="sort-projects-parts_count"></span></th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- Parts Section -->
            <section id="parts" class="section">
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Parts Inventory</h2>
                        <button class="btn btn-primary" onclick="openPartModal()">+ New Part</button>
                    </div>
                    <div class="table-container">
                        <table class="data-table" id="partsTable">
                            <thead>
                                <tr>
                                    <th style="cursor: pointer; user-select: none;" onclick="sortTable('parts', 'part_number')" title="Click to sort">Part Number <span id="sort-parts-part_number"></span></th>
                                    <th style="cursor: pointer; user-select: none;" onclick="sortTable('parts', 'part_name')" title="Click to sort">Name <span id="sort-parts-part_name"></span></th>
                                    <th style="cursor: pointer; user-select: none;" onclick="sortTable('parts', 'category')" title="Click to sort">Category <span id="sort-parts-category"></span></th>
                                    <th style="cursor: pointer; user-select: none;" onclick="sortTable('parts', 'current_stock')" title="Click to sort">Stock <span id="sort-parts-current_stock"></span></th>
                                    <th style="cursor: pointer; user-select: none;" onclick="sortTable('parts', 'min_stock_level')" title="Click to sort">Min Stock <span id="sort-parts-min_stock_level"></span></th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- Orders Section -->
            <section id="orders" class="section">
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Customer Orders</h2>
                        <button class="btn btn-primary" onclick="openOrderModal()">+ New Order</button>
                    </div>
                    <div class="table-container">
                        <table class="data-table" id="ordersTable">
                            <thead>
                                <tr>
                                    <th style="cursor: pointer; user-select: none;" onclick="sortTable('orders', 'order_number')" title="Click to sort">Order # <span id="sort-orders-order_number"></span></th>
                                    <th style="cursor: pointer; user-select: none;" onclick="sortTable('orders', 'order_date')" title="Click to sort">Date <span id="sort-orders-order_date"></span></th>
                                    <th style="cursor: pointer; user-select: none;" onclick="sortTable('orders', 'customer_name')" title="Click to sort">Customer <span id="sort-orders-customer_name"></span></th>
                                    <th style="cursor: pointer; user-select: none;" onclick="sortTable('orders', 'customer_callsign')" title="Click to sort">Callsign <span id="sort-orders-customer_callsign"></span></th>
                                    <th style="cursor: pointer; user-select: none;" onclick="sortTable('orders', 'project_name')" title="Click to sort">Project <span id="sort-orders-project_name"></span></th>
                                    <th style="cursor: pointer; user-select: none;" onclick="sortTable('orders', 'quantity')" title="Click to sort">Qty <span id="sort-orders-quantity"></span></th>
                                    <th style="cursor: pointer; user-select: none;" onclick="sortTable('orders', 'price_paid')" title="Click to sort">Amount <span id="sort-orders-price_paid"></span></th>
                                    <th style="cursor: pointer; user-select: none;" onclick="sortTable('orders', 'status')" title="Click to sort">Status <span id="sort-orders-status"></span></th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- Business Section -->
            <section id="business" class="section">
                <div class="section-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                    <h2>📊 Business Dashboard</h2>
                    <select id="businessPeriod" style="padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 4px; font-size: 1rem; font-family: inherit;" onchange="loadBusinessMetrics()">
                        <option value="all">All Time</option>
                        <option value="trailing">Last 12 Months</option>
                        <option value="2026">2026</option>
                        <option value="2025">2025</option>
                        <option value="2024">2024</option>
                    </select>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
                    <div style="background: linear-gradient(135deg, #2563eb, #1d4ed8); color: white; padding: 1.5rem; border-radius: 8px;">
                        <div style="font-size: 0.875rem; opacity: 0.9; margin-bottom: 0.5rem;">Total Revenue</div>
                        <div style="font-size: 2rem; font-weight: bold;" id="statRevenue">$0</div>
                    </div>
                    <div style="background: linear-gradient(135deg, #10b981, #059669); color: white; padding: 1.5rem; border-radius: 8px;">
                        <div style="font-size: 0.875rem; opacity: 0.9; margin-bottom: 0.5rem;">Gross Profit</div>
                        <div style="font-size: 2rem; font-weight: bold;" id="statGrossProfit">$0</div>
                    </div>
                    <div style="background: linear-gradient(135deg, #8b5cf6, #7c3aed); color: white; padding: 1.5rem; border-radius: 8px;">
                        <div style="font-size: 0.875rem; opacity: 0.9; margin-bottom: 0.5rem;">Net Profit</div>
                        <div style="font-size: 2rem; font-weight: bold;" id="statNetProfit">$0</div>
                    </div>
                    <div style="background: linear-gradient(135deg, #f59e0b, #d97706); color: white; padding: 1.5rem; border-radius: 8px;">
                        <div style="font-size: 0.875rem; opacity: 0.9; margin-bottom: 0.5rem;">Profit Margin</div>
                        <div style="font-size: 2rem; font-weight: bold;" id="statMargin">0%</div>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
                    <div class="card">
                        <h3 style="margin-bottom: 1rem;">Inventory</h3>
                        <div style="display: flex; justify-content: space-between; padding: 0.75rem 0; border-bottom: 1px solid var(--border-color);">
                            <span>Cost of Inventory on Hand:</span>
                            <strong id="metricInventoryCost">$0</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; padding: 0.75rem 0;">
                            <span>Unrealized Revenue (Potential):</span>
                            <strong id="metricUnrealizedRevenue">$0</strong>
                        </div>
                    </div>

                    <div class="card">
                        <h3 style="margin-bottom: 1rem;">Orders</h3>
                        <div style="display: flex; justify-content: space-between; padding: 0.75rem 0; border-bottom: 1px solid var(--border-color);">
                            <span>Total Orders:</span>
                            <strong id="metricOrderCount">0</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; padding: 0.75rem 0; border-bottom: 1px solid var(--border-color);">
                            <span>Cost of Goods Sold:</span>
                            <strong id="metricCOGS">$0</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; padding: 0.75rem 0;">
                            <span>Total Shipping Costs:</span>
                            <strong id="metricShipping">$0</strong>
                        </div>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
                    <div class="card">
                        <h3 style="margin-bottom: 1rem;">Orders by Status</h3>
                        <div id="ordersByStatus">Loading...</div>
                    </div>

                    <div class="card">
                        <h3 style="margin-bottom: 1rem;">Top Selling Projects</h3>
                        <div id="topProjects">Loading...</div>
                    </div>
                </div>

                <div class="card">
                    <h3 style="margin-bottom: 1rem;">P&L Breakdown</h3>
                    <table class="data-table">
                        <tr>
                            <td><strong>Revenue</strong></td>
                            <td style="text-align: right;" id="plRevenue">$0</td>
                        </tr>
                        <tr>
                            <td style="padding-left: 2rem;">- Cost of Goods Sold</td>
                            <td style="text-align: right;" id="plCOGS">$0</td>
                        </tr>
                        <tr style="border-top: 1px solid var(--border-color);">
                            <td><strong>Gross Profit</strong></td>
                            <td style="text-align: right;" id="plGross">$0</td>
                        </tr>
                        <tr>
                            <td style="padding-left: 2rem;">- Shipping Costs</td>
                            <td style="text-align: right;" id="plShipping">$0</td>
                        </tr>
                        <tr>
                            <td style="padding-left: 2rem;">- Research &amp; Misc Expenses</td>
                            <td style="text-align: right;" id="plResearch">$0</td>
                        </tr>
                        <tr style="border-top: 2px solid var(--border-color);">
                            <td><strong>Net Profit</strong></td>
                            <td style="text-align: right; font-weight: bold; color: var(--accent-primary);" id="plNet">$0</td>
                        </tr>
                    </table>
                </div>
            </section>

            <!-- Settings Section -->
            <section id="settings" class="section">
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Settings</h2>
                    </div>
                    <form id="passwordForm">
                        <div class="form-group">
                            <label class="form-label">Current Password</label>
                            <input type="password" id="currentPassword" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">New Password</label>
                            <input type="password" id="newPassword" class="form-input" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Change Password</button>
                    </form>
                </div>
            </section>
        </main>
    </div>

    <!-- Modals will be added via JavaScript -->
    <div id="modalContainer"></div>

    <script>
        // Application State
        let currentUser = null;
        let projects = [];
        let parts = [];
        let orders = [];

        // Category → part number prefix map
        // 42 is reserved for 3D printed parts per KI6CR convention
        const CATEGORY_PREFIXES = {
            '3D Printed':   '42',
            'Connector':    'CONN',
            'Hardware':     'HW',
            'Mechanical':   'MECH',
            'Electronics':  'ELEC',
            'PCB':          'PCB',
            'Packaging':    'PKG',
            'Tool':         'TOOL',
            'Other':        'MISC'
        };

        // Initialize
        document.addEventListener('DOMContentLoaded', () => {
            checkAuth();
        });

        // Authentication
        async function checkAuth() {
            try {
                const response = await fetch('api.php?action=check_auth');
                const data = await response.json();
                if (data.authenticated) {
                    showApp();
                    loadDashboard();
                } else {
                    showLogin();
                }
            } catch (error) {
                showLogin();
            }
        }

        document.getElementById('loginForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData();
            formData.append('action', 'login');
            formData.append('username', document.getElementById('loginUsername').value);
            formData.append('password', document.getElementById('loginPassword').value);

            try {
                const response = await fetch('api.php', { method: 'POST', body: formData });
                const data = await response.json();
                if (data.success) {
                    currentUser = data.username;
                    showApp();
                    loadDashboard();
                } else {
                    document.getElementById('loginError').textContent = data.error || 'Login failed';
                    document.getElementById('loginError').classList.remove('hidden');
                }
            } catch (error) {
                document.getElementById('loginError').textContent = 'Connection error';
                document.getElementById('loginError').classList.remove('hidden');
            }
        });

        async function logout() {
            await fetch('api.php?action=logout');
            location.reload();
        }

        function showLogin() {
            document.getElementById('loginScreen').classList.remove('hidden');
            document.getElementById('mainApp').classList.add('hidden');
        }

        function showApp() {
            document.getElementById('loginScreen').classList.add('hidden');
            document.getElementById('mainApp').classList.remove('hidden');
            document.getElementById('username').textContent = currentUser || 'User';
        }

        // Navigation
        function showSection(sectionId) {
            document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
            document.querySelectorAll('.nav-tab').forEach(t => t.classList.remove('active'));
            document.getElementById(sectionId).classList.add('active');
            event.target.classList.add('active');

            // Load data when section is shown
            if (sectionId === 'dashboard') loadDashboard();
            if (sectionId === 'projects') loadProjects();
            if (sectionId === 'parts') loadParts();
            if (sectionId === 'orders') loadOrders();
            if (sectionId === 'business') {
                // Small delay to ensure DOM is rendered
                setTimeout(() => loadBusinessMetrics(), 100);
            }
        }

        // Dashboard
        async function loadDashboard() {
            try {
                const response = await fetch('api.php?action=get_dashboard');
                const data = await response.json();
                
                document.getElementById('statProjects').textContent = data.total_projects || 0;
                document.getElementById('statParts').textContent = data.total_parts || 0;
                document.getElementById('statLowStock').textContent = data.low_stock_count || 0;
                document.getElementById('statOrders').textContent = data.pending_orders || 0;

                // Low stock parts
                const lowStockHtml = data.low_stock_parts && data.low_stock_parts.length > 0
                    ? data.low_stock_parts.map(p => `
                        <div style="padding: 0.5rem; border-bottom: 1px solid var(--border-color);">
                            <strong>${p.part_name}</strong> (${p.part_number})<br>
                            <span class="stock-low">Stock: ${p.current_stock} / Min: ${p.min_stock_level}</span>
                        </div>
                    `).join('')
                    : '<div style="padding: 1rem; color: var(--text-dim);">All parts adequately stocked</div>';
                document.getElementById('lowStockList').innerHTML = lowStockHtml;

                // Recent orders
                const ordersHtml = data.recent_orders && data.recent_orders.length > 0
                    ? data.recent_orders.map(o => `
                        <div style="padding: 0.5rem; border-bottom: 1px solid var(--border-color);">
                            <strong>${o.customer_name}</strong> - ${o.project_name}<br>
                            <span class="badge badge-${getStatusColor(o.status)}">${o.status}</span>
                            $${parseFloat(o.price_paid).toFixed(2)}
                        </div>
                    `).join('')
                    : '<div style="padding: 1rem; color: var(--text-dim);">No recent orders</div>';
                document.getElementById('recentOrdersList').innerHTML = ordersHtml;
            } catch (error) {
                console.error('Error loading dashboard:', error);
            }
        }

        // Projects
        async function loadProjects() {
            try {
                const response = await fetch('api.php?action=get_projects');
                let allProjects = await response.json();
                
                // Apply sorting
                allProjects = sortData(allProjects, sortState.projects.column, sortState.projects.direction);
                projects = allProjects;
                
                const tbody = document.querySelector('#projectsTable tbody');
                tbody.innerHTML = projects.map(p => `
                    <tr>
                        <td><strong>${p.project_name}</strong></td>
                        <td>${p.description || '-'}</td>
                        <td><span class="badge badge-${p.status === 'active' ? 'success' : 'secondary'}">${p.status}</span></td>
                        <td>${p.parts_count || 0}</td>
                        <td>
                            <button class="btn btn-small" onclick="viewProject(${p.id})">View</button>
                            <button class="btn btn-small" onclick="editProject(${p.id})">Edit</button>
                            <button class="btn btn-small btn-danger" onclick="deleteProject(${p.id})">Delete</button>
                        </td>
                    </tr>
                `).join('');
            } catch (error) {
                console.error('Error loading projects:', error);
            }
        }

        // Parts
        async function loadParts() {
            try {
                const response = await fetch('api.php?action=get_parts');
                let allParts = await response.json();
                
                // Apply sorting
                allParts = sortData(allParts, sortState.parts.column, sortState.parts.direction);
                parts = allParts;
                
                const tbody = document.querySelector('#partsTable tbody');
                tbody.innerHTML = parts.map(p => {
                    const stockClass = p.current_stock <= p.min_stock_level ? 'stock-low' : 'stock-ok';
                    return `
                        <tr>
                            <td><strong>${p.part_number}</strong></td>
                            <td>${p.part_name}</td>
                            <td>${p.category || '-'}</td>
                            <td class="${stockClass}">${p.current_stock}</td>
                            <td>${p.min_stock_level}</td>
                            <td>
                                <button class="btn btn-small" onclick="viewPart(${p.id})">View</button>
                                <button class="btn btn-small" onclick="editPart(${p.id})">Edit</button>
                                <button class="btn btn-small" onclick="checkinInventory(${p.id})">Check-in</button>
                                <button class="btn btn-small" onclick="copyPart(${p.id})">Copy</button>
                                <button class="btn btn-small btn-danger" onclick="deletePart(${p.id})">Delete</button>
                            </td>
                        </tr>
                    `;
                }).join('');
            } catch (error) {
                console.error('Error loading parts:', error);
            }
        }

        // Orders
        async function loadOrders() {
            try {
                const response = await fetch('api.php?action=get_orders');
                let allOrders = await response.json();
                
                // Apply sorting
                allOrders = sortData(allOrders, sortState.orders.column, sortState.orders.direction);
                orders = allOrders;
                
                const tbody = document.querySelector('#ordersTable tbody');
                tbody.innerHTML = orders.map(o => `
                    <tr>
                        <td><strong>${o.order_number}</strong></td>
                        <td>${o.order_date}</td>
                        <td>${o.customer_name}</td>
                        <td>${o.customer_callsign || '-'}</td>
                        <td>${o.project_name}</td>
                        <td>${o.quantity}</td>
                        <td>$${parseFloat(o.price_paid).toFixed(2)}</td>
                        <td><span class="badge badge-${getStatusColor(o.status)}">${o.status}</span></td>
                        <td>
                            <button class="btn btn-small" onclick="editOrder(${o.id})">Edit</button>
                            <button class="btn btn-small btn-danger" onclick="deleteOrder(${o.id})">Delete</button>
                        </td>
                    </tr>
                `).join('');
            } catch (error) {
                console.error('Error loading orders:', error);
            }
        }

        // Helper Functions
        function getStatusColor(status) {
            const colors = {
                'active': 'success',
                'pending': 'warning',
                'paid': 'info',
                'shipped': 'info',
                'completed': 'success',
                'cancelled': 'danger',
                'archived': 'secondary'
            };
            return colors[status] || 'secondary';
        }

        function createModal(title, content, onSave, isWide = false) {
            const modal = document.createElement('div');
            modal.className = 'modal active';
            const modalContentClass = isWide ? 'modal-content modal-wide' : 'modal-content';
            modal.innerHTML = `
                <div class="${modalContentClass}">
                    <div class="modal-header">
                        <h3 class="modal-title">${title}</h3>
                        <div style="display: flex; align-items: center;">
                            <button class="expand-modal" onclick="toggleModalExpand(this)" title="Expand/Collapse">⛶</button>
                            <button class="close-modal" onclick="this.closest('.modal').remove()">×</button>
                        </div>
                    </div>
                    ${content}
                </div>
            `;
            document.getElementById('modalContainer').appendChild(modal);
            return modal;
        }
        
        function toggleModalExpand(button) {
            const modalContent = button.closest('.modal-content');
            modalContent.classList.toggle('modal-expanded');
            // Update button to show current state
            button.textContent = modalContent.classList.contains('modal-expanded') ? '⛶' : '⛶';
            button.title = modalContent.classList.contains('modal-expanded') ? 'Collapse' : 'Expand';
        }

        // Project Modal Functions
        function openProjectModal(projectId = null) {
            const isEdit = projectId !== null;
            const project = isEdit ? projects.find(p => p.id === projectId) : {};
            
            const modal = createModal(
                isEdit ? 'Edit Project' : 'New Project',
                `
                    <form id="projectForm" enctype="multipart/form-data">
                        <input type="hidden" id="projectId" value="${project.id || ''}">
                        <div class="form-group">
                            <label class="form-label">Project Name</label>
                            <input type="text" id="projectName" class="form-input" value="${project.project_name || ''}" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Description</label>
                            <textarea id="projectDescription" class="form-textarea">${project.description || ''}</textarea>
                        </div>
                        <div class="grid-2">
                            <div class="form-group">
                                <label class="form-label">Retail Price ($)</label>
                                <input type="number" id="projectRetailPrice" class="form-input" value="${project.retail_price || 0}" step="0.01" min="0">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Status</label>
                                <select id="projectStatus" class="form-select">
                                    <option value="active" ${project.status === 'active' ? 'selected' : ''}>Active</option>
                                    <option value="planning" ${project.status === 'planning' ? 'selected' : ''}>Planning</option>
                                    <option value="archived" ${project.status === 'archived' ? 'selected' : ''}>Archived</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Project Image</label>
                            <input type="file" id="projectImage" class="form-input" accept="image/*">
                            ${project.image_path ? `<div style="margin-top: 0.5rem;"><img src="${project.image_path}" style="max-width: 200px; border: 1px solid var(--border-color);"></div>` : ''}
                        </div>
                        <div class="flex flex-gap">
                            <button type="submit" class="btn btn-primary">Save</button>
                            <button type="button" class="btn" onclick="this.closest('.modal').remove()">Cancel</button>
                        </div>
                    </form>
                `
            );

            document.getElementById('projectForm').addEventListener('submit', async (e) => {
                e.preventDefault();
                const formData = new FormData();
                formData.append('action', 'save_project');
                if (projectId) formData.append('id', projectId);
                formData.append('project_name', document.getElementById('projectName').value);
                formData.append('description', document.getElementById('projectDescription').value);
                formData.append('retail_price', document.getElementById('projectRetailPrice').value);
                formData.append('status', document.getElementById('projectStatus').value);
                
                const imageFile = document.getElementById('projectImage').files[0];
                if (imageFile) {
                    formData.append('project_image', imageFile);
                }

                try {
                    await fetch('api.php', { method: 'POST', body: formData });
                    modal.remove();
                    loadProjects();
                } catch (error) {
                    alert('Error saving project');
                }
            });
        }

        async function viewProject(id) {
            try {
                const response = await fetch(`api.php?action=get_project&id=${id}`);
                const project = await response.json();
                
                // Store project data globally for export function
                window.currentProjectData = project;
                
                const partsHtml = project.parts && project.parts.length > 0
                    ? `
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Part Number</th>
                                    <th>Part Name</th>
                                    <th>Qty Req'd</th>
                                    <th>Unit Cost</th>
                                    <th>Line Total</th>
                                    <th>In Stock</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${project.parts.map(p => `
                                    <tr>
                                        <td>${p.part_number}</td>
                                        <td>${p.part_name}</td>
                                        <td>${p.quantity_required}</td>
                                        <td>$${parseFloat(p.unit_cost || 0).toFixed(2)}</td>
                                        <td>$${parseFloat(p.line_total || 0).toFixed(2)}</td>
                                        <td class="${p.current_stock >= p.quantity_required ? 'stock-ok' : 'stock-low'}">
                                            ${p.current_stock}
                                        </td>
                                        <td>
                                            <button class="btn btn-small" onclick="editProjectPart(${project.id}, ${p.id}, ${p.part_id}, ${p.quantity_required})">Edit</button>
                                            <button class="btn btn-small btn-danger" onclick="removeProjectPart(${p.id}, ${project.id})">Remove</button>
                                        </td>
                                    </tr>
                                `).join('')}
                                <tr style="font-weight: bold; background: var(--bg-light);">
                                    <td colspan="4" style="text-align: right;">Total BOM Cost:</td>
                                    <td colspan="3">$${parseFloat(project.total_bom_cost || 0).toFixed(2)}</td>
                                </tr>
                            </tbody>
                        </table>
                    `
                    : '<p style="color: var(--text-dim);">No parts assigned to this project yet.</p>';

                const imageHtml = project.image_path
                    ? `<img src="${project.image_path}" style="max-width: 100%; border: 1px solid var(--border-color); border-radius: 4px; margin-bottom: 1rem;">`
                    : '';

                const expensesHtml = project.expenses && project.expenses.length > 0
                    ? `
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Description</th>
                                    <th>Date</th>
                                    <th>Cost</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${project.expenses.map(e => `
                                    <tr>
                                        <td>${e.description}</td>
                                        <td>${e.expense_date || '-'}</td>
                                        <td>$${parseFloat(e.cost).toFixed(2)}</td>
                                        <td><button class="btn btn-small btn-danger" onclick="deleteProjectExpense(${e.id}, ${project.id})">Delete</button></td>
                                    </tr>
                                `).join('')}
                                <tr style="font-weight: bold; background: var(--bg-light);">
                                    <td colspan="2" style="text-align: right;">Total Research Expenses:</td>
                                    <td colspan="2">$${parseFloat(project.total_expenses || 0).toFixed(2)}</td>
                                </tr>
                            </tbody>
                        </table>
                    `
                    : '<p style="color: var(--text-dim);">No research expenses recorded.</p>';

                const modal = createModal(
                    project.project_name,
                    `
                        ${imageHtml}
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
                            <div>
                                <strong>Status:</strong> <span class="badge badge-${getStatusColor(project.status)}">${project.status}</span><br>
                                <strong>Description:</strong> ${project.description || 'N/A'}
                            </div>
                            <div>
                                <strong>Retail Price:</strong> $${parseFloat(project.retail_price || 0).toFixed(2)}<br>
                                <strong>BOM Cost:</strong> $${parseFloat(project.total_bom_cost || 0).toFixed(2)}<br>
                                <strong>Profit per Kit:</strong> <span style="color: var(--success);">$${(parseFloat(project.retail_price || 0) - parseFloat(project.total_bom_cost || 0)).toFixed(2)}</span><br>
                                <strong>Margin:</strong> ${parseFloat(project.profit_margin_percent || 0).toFixed(1)}%
                            </div>
                        </div>

                        <div class="stats-grid" style="margin-bottom: 1.5rem;">
                            <div class="stat-card" style="border-left-color: var(--accent-secondary);">
                                <div class="stat-value" style="color: var(--accent-secondary);">${project.buildable_kits || 0}</div>
                                <div class="stat-label">Kits Buildable</div>
                            </div>
                            <div class="stat-card" style="border-left-color: var(--warning);">
                                <div class="stat-value" style="color: var(--warning);">$${parseFloat(project.total_inventory_value || 0).toFixed(2)}</div>
                                <div class="stat-label">Inventory Value</div>
                            </div>
                            <div class="stat-card" style="border-left-color: var(--info);">
                                <div class="stat-value" style="color: var(--info);">$${parseFloat(project.projected_revenue || 0).toFixed(2)}</div>
                                <div class="stat-label">Projected Revenue</div>
                            </div>
                            <div class="stat-card" style="border-left-color: var(--success);">
                                <div class="stat-value" style="color: var(--success);">$${parseFloat(project.projected_profit || 0).toFixed(2)}</div>
                                <div class="stat-label">Projected Profit</div>
                            </div>
                        </div>

                        <hr style="margin: 1.5rem 0; border-color: var(--border-color);">
                        
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                            <h4>Bill of Materials</h4>
                            <div style="display: flex; gap: 0.5rem;">
                                <button class="btn btn-small" onclick="exportBOM()" style="background: var(--success); color: white; border-color: var(--success);">📥 Export BOM</button>
                                <button class="btn btn-primary btn-small" onclick="addPartToProject(${project.id})">+ Add Part</button>
                            </div>
                        </div>
                        
                        ${partsHtml}

                        <hr style="margin: 1.5rem 0; border-color: var(--border-color);">

                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                            <h4>Research &amp; Misc Expenses</h4>
                            <button class="btn btn-primary btn-small" onclick="document.getElementById('addExpenseForm-${project.id}').style.display='block'; this.style.display='none';">+ Add Expense</button>
                        </div>

                        <div id="addExpenseForm-${project.id}" style="display: none; margin-bottom: 1rem; padding: 1rem; border: 1px solid var(--border-color); border-radius: 4px;">
                            <div class="grid-2" style="margin-bottom: 0.5rem;">
                                <div class="form-group">
                                    <label class="form-label">Description</label>
                                    <input type="text" id="newExpenseDesc-${project.id}" class="form-input" placeholder="e.g. Prototype parts, test equipment">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Date (optional)</label>
                                    <input type="date" id="newExpenseDate-${project.id}" class="form-input">
                                </div>
                            </div>
                            <div class="form-group" style="margin-bottom: 0.5rem;">
                                <label class="form-label">Cost ($)</label>
                                <input type="number" id="newExpenseCost-${project.id}" class="form-input" placeholder="0.00" step="0.01" min="0" style="max-width: 200px;">
                            </div>
                            <div class="flex flex-gap">
                                <button class="btn btn-primary btn-small" onclick="saveProjectExpense(${project.id})">Save Expense</button>
                                <button class="btn btn-small" onclick="document.getElementById('addExpenseForm-${project.id}').style.display='none'; document.querySelector('[onclick*=\\'addExpenseForm-${project.id}\\']').style.display='';">Cancel</button>
                            </div>
                        </div>

                        ${expensesHtml}

                        <div class="mt-1 flex flex-gap">
                            <button class="btn btn-primary" onclick="editProject(${project.id}); this.closest('.modal').remove();">Edit Project</button>
                            <button class="btn" onclick="this.closest('.modal').remove()">Close</button>
                        </div>
                    `,
                    null,
                    true  // isWide = true
                );
            } catch (error) {
                alert('Error loading project details');
            }
        }

        function exportBOM() {
            if (!window.currentProjectData || !window.currentProjectData.parts) {
                alert('No BOM data available to export');
                return;
            }
            
            const project = window.currentProjectData;
            const parts = project.parts;
            
            if (parts.length === 0) {
                alert('This project has no parts in the BOM');
                return;
            }
            
            // CSV headers
            let csv = 'Part Number,Part Name,Description,Quantity Required,Unit Cost,Line Total,Supplier,Supplier Part Number,Manufacturer Part Number,Product Link\n';
            
            // Add each part as a row
            parts.forEach(part => {
                const partNumber = (part.part_number || '').replace(/"/g, '""');
                const partName = (part.part_name || '').replace(/"/g, '""');
                const description = (part.description || '').replace(/"/g, '""');
                const qtyRequired = part.quantity_required || 0;
                const unitCost = parseFloat(part.unit_cost || 0).toFixed(4);
                const lineTotal = parseFloat(part.line_total || 0).toFixed(2);
                const supplier = (part.preferred_supplier || 'No preferred supplier').replace(/"/g, '""');
                const supplierPN = (part.preferred_supplier_pn || '').replace(/"/g, '""');
                const mfrPN = (part.preferred_mfr_pn || '').replace(/"/g, '""');
                const url = part.preferred_url || '';
                
                csv += `"${partNumber}","${partName}","${description}",${qtyRequired},${unitCost},${lineTotal},"${supplier}","${supplierPN}","${mfrPN}","${url}"\n`;
            });
            
            // Add summary row
            csv += `\n"TOTAL BOM COST",,,,,$${parseFloat(project.total_bom_cost || 0).toFixed(2)}\n`;
            
            // Create blob and download
            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            
            // Clean project name for filename
            const projectName = project.project_name.replace(/[^a-z0-9]/gi, '_').toLowerCase();
            const filename = `BOM_${projectName}_${new Date().toISOString().split('T')[0]}.csv`;
            
            link.setAttribute('href', url);
            link.setAttribute('download', filename);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        function addPartToProject(projectId) {
            // Load parts first if not already loaded
            if (parts.length === 0) {
                loadParts().then(() => openAddPartModal(projectId));
            } else {
                openAddPartModal(projectId);
            }
        }

        function openAddPartModal(projectId) {
            const existingPartIds = new Set(
                (window.currentProjectData?.parts || []).map(p => p.part_id)
            );
            const availableParts = parts.filter(p => !existingPartIds.has(p.id));

            const modal = createModal(
                'Add Part to Project',
                `
                    <form id="addPartForm">
                        <div class="form-group">
                            <label class="form-label">Select Part</label>
                            <select id="partSelect" class="form-select" required>
                                <option value="">Choose a part...</option>
                                ${availableParts.map(p => `<option value="${p.id}">${p.part_number} - ${p.part_name}</option>`).join('')}
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Quantity Required per Kit</label>
                            <input type="number" id="partQty" class="form-input" min="1" value="1" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Notes (optional)</label>
                            <textarea id="partNotes" class="form-textarea"></textarea>
                        </div>
                        <div class="flex flex-gap">
                            <button type="submit" class="btn btn-primary">Add to BOM</button>
                            <button type="button" class="btn" onclick="this.closest('.modal').remove()">Cancel</button>
                        </div>
                    </form>
                `
            );

            document.getElementById('addPartForm').addEventListener('submit', async (e) => {
                e.preventDefault();
                const formData = new FormData();
                formData.append('action', 'add_project_part');
                formData.append('project_id', projectId);
                formData.append('part_id', document.getElementById('partSelect').value);
                formData.append('quantity_required', document.getElementById('partQty').value);
                formData.append('notes', document.getElementById('partNotes').value);

                try {
                    await fetch('api.php', { method: 'POST', body: formData });
                    modal.remove();
                    // Reload the project view
                    document.querySelector('.modal.active')?.remove();
                    viewProject(projectId);
                } catch (error) {
                    alert('Error adding part to project');
                }
            });
        }

        function editProjectPart(projectId, projectPartId, partId, currentQty) {
            const modal = createModal(
                'Edit Part Quantity',
                `
                    <form id="editPartQtyForm">
                        <div class="form-group">
                            <label class="form-label">Quantity Required per Kit</label>
                            <input type="number" id="editPartQty" class="form-input" min="1" value="${currentQty}" required>
                        </div>
                        <div class="flex flex-gap">
                            <button type="submit" class="btn btn-primary">Update</button>
                            <button type="button" class="btn" onclick="this.closest('.modal').remove()">Cancel</button>
                        </div>
                    </form>
                `
            );

            document.getElementById('editPartQtyForm').addEventListener('submit', async (e) => {
                e.preventDefault();
                const formData = new FormData();
                formData.append('action', 'add_project_part');
                formData.append('project_id', projectId);
                formData.append('part_id', partId);
                formData.append('quantity_required', document.getElementById('editPartQty').value);

                try {
                    await fetch('api.php', { method: 'POST', body: formData });
                    modal.remove();
                    document.querySelector('.modal.active')?.remove();
                    viewProject(projectId);
                } catch (error) {
                    alert('Error updating part quantity');
                }
            });
        }

        async function removeProjectPart(projectPartId, projectId) {
            if (!confirm('Remove this part from the project?')) return;
            
            const formData = new FormData();
            formData.append('action', 'delete_project_part');
            formData.append('id', projectPartId);

            try {
                await fetch('api.php', { method: 'POST', body: formData });
                document.querySelector('.modal.active')?.remove();
                viewProject(projectId);
            } catch (error) {
                alert('Error removing part');
            }
        }

        async function saveProjectExpense(projectId) {
            const desc = document.getElementById(`newExpenseDesc-${projectId}`).value.trim();
            const cost = document.getElementById(`newExpenseCost-${projectId}`).value;
            const date = document.getElementById(`newExpenseDate-${projectId}`).value;

            if (!desc || !cost) {
                alert('Please enter a description and cost.');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'save_project_expense');
            formData.append('project_id', projectId);
            formData.append('description', desc);
            formData.append('cost', cost);
            if (date) formData.append('expense_date', date);

            try {
                await fetch('api.php', { method: 'POST', body: formData });
                document.querySelector('.modal.active')?.remove();
                viewProject(projectId);
            } catch (error) {
                alert('Error saving expense');
            }
        }

        async function deleteProjectExpense(expenseId, projectId) {
            if (!confirm('Delete this expense?')) return;

            const formData = new FormData();
            formData.append('action', 'delete_project_expense');
            formData.append('id', expenseId);

            try {
                await fetch('api.php', { method: 'POST', body: formData });
                document.querySelector('.modal.active')?.remove();
                viewProject(projectId);
            } catch (error) {
                alert('Error deleting expense');
            }
        }

        function editProject(id) {
            openProjectModal(id);
        }

        async function deleteProject(id) {
            if (!confirm('Are you sure you want to delete this project?')) return;
            
            const formData = new FormData();
            formData.append('action', 'delete_project');
            formData.append('id', id);

            try {
                await fetch('api.php', { method: 'POST', body: formData });
                loadProjects();
            } catch (error) {
                alert('Error deleting project');
            }
        }

        async function autoFillPartNumber() {
            const cat = document.getElementById('partCategory').value;
            if (!cat || !CATEGORY_PREFIXES[cat]) return;
            const prefix = CATEGORY_PREFIXES[cat];
            try {
                const res = await fetch(`api.php?action=get_next_part_number&prefix=${encodeURIComponent(prefix)}`);
                const data = await res.json();
                if (data.next_part_number) {
                    document.getElementById('partNumber').value = data.next_part_number;
                }
            } catch (e) {
                // leave the field blank if the fetch fails — user can type manually
            }
        }

        // Part Modal Functions (similar pattern to projects)
        function openPartModal(partId = null) {
            const isEdit = partId !== null;
            const part = isEdit ? parts.find(p => p.id === partId) : {};

            const categoryOptions = Object.keys(CATEGORY_PREFIXES).map(cat =>
                `<option value="${cat}" ${part.category === cat ? 'selected' : ''}>${cat} (${CATEGORY_PREFIXES[cat]}-)</option>`
            ).join('');

            const modal = createModal(
                isEdit ? 'Edit Part' : 'New Part',
                `
                    <form id="partForm">
                        <input type="hidden" id="partId" value="${part.id || ''}">
                        <div class="form-group">
                            <label class="form-label">Category</label>
                            <select id="partCategory" class="form-select" ${isEdit ? '' : 'onchange="autoFillPartNumber()"'}>
                                <option value="">-- Select category --</option>
                                ${categoryOptions}
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Part Number ${isEdit ? '' : '<span style="color:var(--text-dim);font-weight:normal;">(auto-filled, editable)</span>'}</label>
                            <input type="text" id="partNumber" class="form-input" value="${part.part_number || ''}" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Part Name</label>
                            <input type="text" id="partName" class="form-input" value="${part.part_name || ''}" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Description</label>
                            <textarea id="partDescription" class="form-textarea">${part.description || ''}</textarea>
                        </div>
                        <div class="grid-2">
                            <div class="form-group">
                                <label class="form-label">Current Stock</label>
                                <input type="number" id="partStock" class="form-input" value="${part.current_stock || 0}" min="0">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Min Stock Level</label>
                                <input type="number" id="partMinStock" class="form-input" value="${part.min_stock_level || 0}" min="0">
                            </div>
                        </div>
                        <div class="flex flex-gap">
                            <button type="submit" class="btn btn-primary">Save</button>
                            <button type="button" class="btn" onclick="this.closest('.modal').remove()">Cancel</button>
                        </div>
                    </form>
                `
            );

            document.getElementById('partForm').addEventListener('submit', async (e) => {
                e.preventDefault();
                const formData = new FormData();
                formData.append('action', 'save_part');
                if (partId) formData.append('id', partId);
                formData.append('part_number', document.getElementById('partNumber').value);
                formData.append('part_name', document.getElementById('partName').value);
                formData.append('description', document.getElementById('partDescription').value);
                formData.append('category', document.getElementById('partCategory').value);
                formData.append('current_stock', document.getElementById('partStock').value);
                formData.append('min_stock_level', document.getElementById('partMinStock').value);

                try {
                    await fetch('api.php', { method: 'POST', body: formData });
                    modal.remove();
                    loadParts();
                } catch (error) {
                    alert('Error saving part');
                }
            });
        }

        async function viewPart(id) {
            try {
                const response = await fetch(`api.php?action=get_part&id=${id}`);
                const part = await response.json();
                
                const sourcesHtml = part.sources && part.sources.length > 0
                    ? `
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Supplier</th>
                                    <th>Supplier Part #</th>
                                    <th>Mfr Part #</th>
                                    <th>Cost</th>
                                    <th>Link</th>
                                    <th>Preferred</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${part.sources.map(s => `
                                    <tr>
                                        <td>${s.supplier_name}</td>
                                        <td>${s.supplier_part_number || '-'}</td>
                                        <td>${s.manufacturer_part_number || '-'}</td>
                                        <td>$${parseFloat(s.cost).toFixed(2)}</td>
                                        <td>${s.url ? `<a href="${s.url}" target="_blank" style="color: var(--accent-secondary);">Link</a>` : '-'}</td>
                                        <td>${s.is_preferred ? '⭐' : ''}</td>
                                        <td>
                                            <button class="btn btn-small" onclick="editSource(${s.id}, ${part.id})">Edit</button>
                                            <button class="btn btn-small btn-danger" onclick="deleteSource(${s.id}, ${part.id})">Delete</button>
                                        </td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    `
                    : '<p style="color: var(--text-dim);">No sources configured.</p>';

                const modal = createModal(
                    part.part_name,
                    `
                        <p><strong>Part Number:</strong> ${part.part_number}</p>
                        <p><strong>Category:</strong> ${part.category || 'N/A'}</p>
                        <p><strong>Description:</strong> ${part.description || 'N/A'}</p>
                        <p><strong>Stock:</strong> <span class="${part.current_stock <= part.min_stock_level ? 'stock-low' : 'stock-ok'}">${part.current_stock}</span> / Min: ${part.min_stock_level}</p>
                        ${part.weighted_avg_cost > 0 ? `<p><strong>Weighted Avg Cost:</strong> $${parseFloat(part.weighted_avg_cost).toFixed(4)} <small style="color: var(--text-secondary);">(from actual purchases)</small></p>` : ''}
                        <hr style="margin: 1.5rem 0; border-color: var(--border-color);">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                            <h4>Supplier Sources</h4>
                            <button class="btn btn-primary btn-small" onclick="addSource(${part.id})">+ Add Source</button>
                        </div>
                        ${sourcesHtml}
                        <hr style="margin: 1.5rem 0; border-color: var(--border-color);">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                            <h4>Purchase History</h4>
                            <button class="btn btn-primary btn-small" onclick="checkinInventory(${part.id}); document.querySelector('.modal.active')?.remove();">+ Check-in Inventory</button>
                        </div>
                        ${part.checkins && part.checkins.length > 0 ? `
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Supplier</th>
                                        <th>Quantity</th>
                                        <th>Unit Cost</th>
                                        <th>Total Cost</th>
                                        <th>Notes</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${part.checkins.map(c => `
                                        <tr>
                                            <td>${c.purchase_date}</td>
                                            <td>${c.supplier_name || '-'}</td>
                                            <td>${c.quantity}</td>
                                            <td>$${parseFloat(c.unit_cost).toFixed(4)}</td>
                                            <td>$${parseFloat(c.total_cost).toFixed(2)}</td>
                                            <td style="max-width: 200px; overflow: hidden; text-overflow: ellipsis;">${c.notes || '-'}</td>
                                            <td>
                                                <button class="btn btn-small" onclick="editCheckin(${c.id}, ${part.id})">Edit</button>
                                                <button class="btn btn-small btn-danger" onclick="deleteCheckin(${c.id}, ${part.id})">Delete</button>
                                            </td>
                                        </tr>
                                    `).join('')}
                                    <tr style="font-weight: bold; background: var(--bg-light);">
                                        <td colspan="5" style="text-align: right;">Total Spent:</td>
                                        <td colspan="2">$${part.checkins.reduce((sum, c) => sum + parseFloat(c.total_cost), 0).toFixed(2)}</td>
                                    </tr>
                                </tbody>
                            </table>
                        ` : '<p style="color: var(--text-dim); text-align: center; padding: 2rem;">No purchase history yet. Click "Check-in Inventory" to record your first purchase.</p>'}
                        <div class="mt-1">
                            <button class="btn" onclick="this.closest('.modal').remove()">Close</button>
                        </div>
                    `,
                    null,
                    true  // isWide = true
                );
            } catch (error) {
                alert('Error loading part details');
            }
        }

        function addSource(partId) {
            openSourceModal(null, partId);
        }

        function editSource(sourceId, partId) {
            openSourceModal(sourceId, partId);
        }

        async function deleteSource(sourceId, partId) {
            if (!confirm('Delete this source?')) return;
            
            const formData = new FormData();
            formData.append('action', 'delete_source');
            formData.append('id', sourceId);

            try {
                await fetch('api.php', { method: 'POST', body: formData });
                document.querySelector('.modal.active')?.remove();
                viewPart(partId);
            } catch (error) {
                alert('Error deleting source');
            }
        }

        async function openSourceModal(sourceId = null, partId) {
            const isEdit = sourceId !== null;
            let source = {};
            
            if (isEdit) {
                // Fetch the source data
                const response = await fetch(`api.php?action=get_part&id=${partId}`);
                const part = await response.json();
                source = part.sources.find(s => s.id === sourceId) || {};
            }
            
            const modal = createModal(
                isEdit ? 'Edit Source' : 'Add Source',
                `
                    <form id="sourceForm">
                        <input type="hidden" id="sourceId" value="${sourceId || ''}">
                        <input type="hidden" id="sourcePartId" value="${partId}">
                        <div class="form-group">
                            <label class="form-label">Supplier Name</label>
                            <input type="text" id="sourceName" class="form-input" value="${source.supplier_name || ''}" required>
                        </div>
                        <div class="grid-2">
                            <div class="form-group">
                                <label class="form-label">Supplier Part Number</label>
                                <input type="text" id="sourceSupplierPN" class="form-input" value="${source.supplier_part_number || ''}">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Manufacturer Part Number</label>
                                <input type="text" id="sourceMfrPN" class="form-input" value="${source.manufacturer_part_number || ''}">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Unit Cost ($)</label>
                            <input type="number" id="sourceCost" class="form-input" value="${source.cost || 0}" step="0.01" min="0" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Supplier Product URL</label>
                            <input type="url" id="sourceUrl" class="form-input" value="${source.url || ''}" placeholder="https://...">
                        </div>
                        <div class="form-group">
                            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                                <input type="checkbox" id="sourcePreferred" ${source.is_preferred ? 'checked' : ''}>
                                <span class="form-label" style="margin: 0;">Preferred Source</span>
                            </label>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Notes</label>
                            <textarea id="sourceNotes" class="form-textarea">${source.notes || ''}</textarea>
                        </div>
                        <div class="flex flex-gap">
                            <button type="submit" class="btn btn-primary">Save</button>
                            <button type="button" class="btn" onclick="this.closest('.modal').remove()">Cancel</button>
                        </div>
                    </form>
                `
            );

            document.getElementById('sourceForm').addEventListener('submit', async (e) => {
                e.preventDefault();
                const formData = new FormData();
                formData.append('action', 'save_source');
                if (sourceId) formData.append('id', sourceId);
                formData.append('part_id', partId);
                formData.append('supplier_name', document.getElementById('sourceName').value);
                formData.append('supplier_part_number', document.getElementById('sourceSupplierPN').value);
                formData.append('manufacturer_part_number', document.getElementById('sourceMfrPN').value);
                formData.append('cost', document.getElementById('sourceCost').value);
                formData.append('url', document.getElementById('sourceUrl').value);
                if (document.getElementById('sourcePreferred').checked) {
                    formData.append('is_preferred', '1');
                }
                formData.append('notes', document.getElementById('sourceNotes').value);

                try {
                    await fetch('api.php', { method: 'POST', body: formData });
                    modal.remove();
                    document.querySelector('.modal.active')?.remove();
                    viewPart(partId);
                } catch (error) {
                    alert('Error saving source');
                }
            });
        }

        function editPart(id) {
            openPartModal(id);
        }

        async function deletePart(id) {
            if (!confirm('Are you sure you want to delete this part?')) return;
            
            const formData = new FormData();
            formData.append('action', 'delete_part');
            formData.append('id', id);

            try {
                await fetch('api.php', { method: 'POST', body: formData });
                loadParts();
            } catch (error) {
                alert('Error deleting part');
            }
        }

        async function copyPart(id) {
            if (!confirm('Create a copy of this part?')) return;
            
            const formData = new FormData();
            formData.append('action', 'copy_part');
            formData.append('id', id);

            try {
                const response = await fetch('api.php', { method: 'POST', body: formData });
                const result = await response.json();
                if (result.success) {
                    alert('Part copied! You can now edit it.');
                    loadParts();
                    editPart(result.id);
                } else {
                    alert('Error copying part');
                }
            } catch (error) {
                alert('Error copying part');
            }
        }

        async function deleteCheckin(checkinId, partId) {
            if (!confirm('Delete this check-in? This will reduce inventory by the checked-in quantity and recalculate weighted average cost.')) return;
            
            const formData = new FormData();
            formData.append('action', 'delete_checkin');
            formData.append('id', checkinId);
            formData.append('part_id', partId);

            try {
                await fetch('api.php', { method: 'POST', body: formData });
                document.querySelector('.modal.active')?.remove();
                viewPart(partId);
            } catch (error) {
                alert('Error deleting check-in');
            }
        }

        async function editCheckin(checkinId, partId) {
            try {
                // Get the part with checkins to find the specific checkin
                const response = await fetch(`api.php?action=get_part&id=${partId}`);
                const part = await response.json();
                const checkin = part.checkins.find(c => c.id === checkinId);
                
                if (!checkin) {
                    alert('Check-in not found');
                    return;
                }
                
                const modal = createModal(
                    `Edit Check-in for ${part.part_name}`,
                    `
                        <form id="editCheckinForm">
                            <div class="form-group">
                                <label class="form-label">Quantity Received</label>
                                <input type="number" id="editCheckinQty" class="form-input" min="1" value="${checkin.quantity}" required>
                            </div>
                            
                            <div style="background: #f0f9ff; padding: 1rem; border-radius: 4px; margin-bottom: 1rem;">
                                <strong style="color: var(--accent-primary);">💡 Edit EITHER unit cost OR gross total:</strong>
                                <p style="margin: 0.5rem 0 0 0; font-size: 0.9rem; color: var(--text-secondary);">
                                    Current values will auto-populate. Change what you need.
                                </p>
                            </div>
                            
                            <div class="grid-2">
                                <div class="form-group">
                                    <label class="form-label">Unit Cost ($)</label>
                                    <input type="number" id="editCheckinUnitCost" class="form-input" step="0.0001" min="0" value="${parseFloat(checkin.unit_cost).toFixed(4)}">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Gross Total ($)</label>
                                    <input type="number" id="editCheckinGrossTotal" class="form-input" step="0.01" min="0" value="${parseFloat(checkin.total_cost).toFixed(2)}">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Supplier Name</label>
                                <input type="text" id="editCheckinSupplier" class="form-input" value="${checkin.supplier_name || ''}">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Purchase Date</label>
                                <input type="date" id="editCheckinDate" class="form-input" value="${checkin.purchase_date}" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Notes</label>
                                <textarea id="editCheckinNotes" class="form-textarea">${checkin.notes || ''}</textarea>
                            </div>
                            <div class="flex flex-gap">
                                <button type="submit" class="btn btn-primary">Save Changes</button>
                                <button type="button" class="btn" onclick="this.closest('.modal').remove()">Cancel</button>
                            </div>
                        </form>
                    `
                );
                
                // Handle form submission
                document.getElementById('editCheckinForm').addEventListener('submit', async (e) => {
                    e.preventDefault();
                    
                    const formData = new FormData();
                    formData.append('action', 'edit_checkin');
                    formData.append('id', checkinId);
                    formData.append('part_id', partId);
                    formData.append('quantity', document.getElementById('editCheckinQty').value);
                    formData.append('gross_total', document.getElementById('editCheckinGrossTotal').value);
                    formData.append('supplier_name', document.getElementById('editCheckinSupplier').value);
                    formData.append('purchase_date', document.getElementById('editCheckinDate').value);
                    formData.append('notes', document.getElementById('editCheckinNotes').value);

                    try {
                        await fetch('api.php', { method: 'POST', body: formData });
                        modal.remove();
                        document.querySelector('.modal.active')?.remove();
                        viewPart(partId);
                        loadParts();
                    } catch (error) {
                        alert('Error updating check-in');
                    }
                });
            } catch (error) {
                alert('Error loading check-in data');
            }
        }

        function checkinInventory(partId) {
            const part = parts.find(p => p.id === partId);
            
            const modal = createModal(
                `Check-in Inventory: ${part.part_name}`,
                `
                    <form id="checkinForm">
                        <div class="form-group">
                            <label class="form-label">Quantity Received</label>
                            <input type="number" id="checkinQty" class="form-input" min="1" required>
                        </div>
                        
                        <div style="background: #f0f9ff; padding: 1rem; border-radius: 4px; margin-bottom: 1rem;">
                            <strong style="color: var(--accent-primary);">💡 Enter EITHER unit cost OR gross total:</strong>
                            <p style="margin: 0.5rem 0 0 0; font-size: 0.9rem; color: var(--text-secondary);">
                                Gross total should include shipping, tax, and all other charges.<br>
                                The system will calculate the actual cost per unit.
                            </p>
                        </div>
                        
                        <div class="grid-2">
                            <div class="form-group">
                                <label class="form-label">Unit Cost ($)</label>
                                <input type="number" id="checkinUnitCost" class="form-input" step="0.0001" min="0" placeholder="e.g., 0.1250">
                                <small style="color: var(--text-secondary);">Per part cost</small>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Gross Total ($)</label>
                                <input type="number" id="checkinGrossTotal" class="form-input" step="0.01" min="0" placeholder="e.g., 25.50">
                                <small style="color: var(--text-secondary);">Total order cost (incl. shipping/tax)</small>
                            </div>
                        </div>
                        
                        <div id="calculatedCost" style="background: #ecfdf5; padding: 0.75rem; border-radius: 4px; margin-bottom: 1rem; display: none;">
                            <strong>Calculated Unit Cost: <span id="calcCostValue">$0.0000</span></strong>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Supplier Name</label>
                            <input type="text" id="checkinSupplier" class="form-input">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Purchase Date</label>
                            <input type="date" id="checkinDate" class="form-input" value="${new Date().toISOString().split('T')[0]}" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Notes</label>
                            <textarea id="checkinNotes" class="form-textarea" placeholder="e.g., Mouser order #12345, included $5 shipping"></textarea>
                        </div>
                        <div class="flex flex-gap">
                            <button type="submit" class="btn btn-primary">Check-in</button>
                            <button type="button" class="btn" onclick="this.closest('.modal').remove()">Cancel</button>
                        </div>
                    </form>
                `
            );
            
            // Auto-calculate when either field changes
            const qtyInput = document.getElementById('checkinQty');
            const unitCostInput = document.getElementById('checkinUnitCost');
            const grossTotalInput = document.getElementById('checkinGrossTotal');
            const calcDisplay = document.getElementById('calculatedCost');
            const calcValue = document.getElementById('calcCostValue');
            
            function updateCalculation() {
                const qty = parseFloat(qtyInput.value) || 0;
                const unitCost = parseFloat(unitCostInput.value) || 0;
                const grossTotal = parseFloat(grossTotalInput.value) || 0;
                
                if (qty > 0 && grossTotal > 0) {
                    const calculated = grossTotal / qty;
                    calcValue.textContent = '$' + calculated.toFixed(4);
                    calcDisplay.style.display = 'block';
                } else if (qty > 0 && unitCost > 0) {
                    const calculated = qty * unitCost;
                    calcValue.textContent = '$' + calculated.toFixed(4) + ' per unit (Total: $' + calculated.toFixed(2) + ')';
                    calcDisplay.style.display = 'block';
                } else {
                    calcDisplay.style.display = 'none';
                }
            }
            
            qtyInput.addEventListener('input', updateCalculation);
            unitCostInput.addEventListener('input', () => {
                if (unitCostInput.value) grossTotalInput.value = '';
                updateCalculation();
            });
            grossTotalInput.addEventListener('input', () => {
                if (grossTotalInput.value) unitCostInput.value = '';
                updateCalculation();
            });

            document.getElementById('checkinForm').addEventListener('submit', async (e) => {
                e.preventDefault();
                
                const qty = qtyInput.value;
                const unitCost = unitCostInput.value;
                const grossTotal = grossTotalInput.value;
                
                if (!unitCost && !grossTotal) {
                    alert('Please enter either Unit Cost or Gross Total');
                    return;
                }
                
                const formData = new FormData();
                formData.append('action', 'checkin_inventory');
                formData.append('part_id', partId);
                formData.append('quantity', qty);
                if (unitCost) formData.append('unit_cost', unitCost);
                if (grossTotal) formData.append('gross_total', grossTotal);
                formData.append('supplier_name', document.getElementById('checkinSupplier').value);
                formData.append('purchase_date', document.getElementById('checkinDate').value);
                formData.append('notes', document.getElementById('checkinNotes').value);

                try {
                    await fetch('api.php', { method: 'POST', body: formData });
                    modal.remove();
                    loadParts();
                    loadDashboard();
                } catch (error) {
                    alert('Error checking in inventory');
                }
            });
        }

        // Order Modal Functions
        async function openOrderModal(orderId = null) {
            // Ensure projects are loaded before opening modal
            if (!projects || projects.length === 0) {
                await loadProjects();
            }
            
            const isEdit = orderId !== null;
            const order = isEdit ? orders.find(o => o.id === orderId) : {};
            
            const projectOptions = projects.map(p => 
                `<option value="${p.id}" ${order.project_id == p.id ? 'selected' : ''}>${p.project_name}</option>`
            ).join('');
            
            const modal = createModal(
                isEdit ? 'Edit Order' : 'New Order',
                `
                    <form id="orderForm">
                        <input type="hidden" id="orderId" value="${order.id || ''}">
                        <div class="form-group">
                            <label class="form-label">Order Number</label>
                            <input type="text" id="orderNumber" class="form-input" value="${order.order_number || 'ORD-' + Date.now()}" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Project/Kit</label>
                            <select id="orderProject" class="form-select" required>
                                <option value="">Select project...</option>
                                ${projectOptions}
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Customer Name</label>
                            <input type="text" id="orderCustomer" class="form-input" value="${order.customer_name || ''}" required>
                        </div>
                        <div class="grid-2">
                            <div class="form-group">
                                <label class="form-label">Email</label>
                                <input type="email" id="orderEmail" class="form-input" value="${order.customer_email || ''}">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Phone</label>
                                <input type="tel" id="orderPhone" class="form-input" value="${order.customer_phone || ''}">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Callsign</label>
                            <input type="text" id="orderCallsign" class="form-input" value="${order.customer_callsign || ''}">
                        </div>
                        <div class="grid-2">
                            <div class="form-group">
                                <label class="form-label">Quantity</label>
                                <input type="number" id="orderQty" class="form-input" value="${order.quantity || 1}" min="1" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Price Paid ($)</label>
                                <input type="number" id="orderPrice" class="form-input" value="${order.price_paid || 0}" step="0.01" min="0" required>
                            </div>
                        </div>
                        <div class="grid-2">
                            <div class="form-group">
                                <label class="form-label">Order Date</label>
                                <input type="date" id="orderDate" class="form-input" value="${order.order_date || new Date().toISOString().split('T')[0]}" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Status</label>
                                <select id="orderStatus" class="form-select">
                                    <option value="pending" ${order.status === 'pending' ? 'selected' : ''}>Pending</option>
                                    <option value="paid" ${order.status === 'paid' ? 'selected' : ''}>Paid</option>
                                    <option value="shipped" ${order.status === 'shipped' ? 'selected' : ''}>Shipped</option>
                                    <option value="completed" ${order.status === 'completed' ? 'selected' : ''}>Completed</option>
                                    <option value="cancelled" ${order.status === 'cancelled' ? 'selected' : ''}>Cancelled</option>
                                </select>
                            </div>
                        </div>
                        <div id="trackingNumberGroup" class="form-group" style="display: none;">
                            <label class="form-label">Tracking Number</label>
                            <input type="text" id="orderTracking" class="form-input" value="${order.tracking_number || ''}" placeholder="e.g., 1Z999AA10123456784">
                            <small style="color: var(--text-secondary); font-size: 0.875rem;">Enter tracking number for shipped orders</small>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Shipping Charge ($)</label>
                            <input type="number" id="orderShippingCharge" class="form-input" value="${order.shipping_charge || 0}" step="0.01" min="0">
                            <small style="color: var(--text-secondary); font-size: 0.875rem;">Actual shipping cost paid for P&L tracking</small>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Shipping Address</label>
                            <textarea id="orderAddress" class="form-textarea">${order.shipping_address || ''}</textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Notes</label>
                            <textarea id="orderNotes" class="form-textarea">${order.notes || ''}</textarea>
                        </div>
                        <div class="flex flex-gap">
                            <button type="submit" class="btn btn-primary">Save Order</button>
                            ${isEdit ? '<button type="button" class="btn btn-secondary" onclick="sendCustomerEmail(' + orderId + ')">Send Status Email to Customer</button>' : ''}
                            <button type="button" class="btn" onclick="this.closest(\'.modal\').remove()">Cancel</button>
                        </div>
                    </form>
                `
            );

            // Load projects for the dropdown
            loadProjects();
            
            // Show/hide tracking number based on status
            const statusSelect = document.getElementById('orderStatus');
            const trackingGroup = document.getElementById('trackingNumberGroup');
            
            function updateTrackingVisibility() {
                const status = statusSelect.value;
                if (status === 'shipped' || status === 'completed') {
                    trackingGroup.style.display = 'block';
                } else {
                    trackingGroup.style.display = 'none';
                }
            }
            
            statusSelect.addEventListener('change', updateTrackingVisibility);
            updateTrackingVisibility(); // Check initial state

            document.getElementById('orderForm').addEventListener('submit', async (e) => {
                e.preventDefault();
                const formData = new FormData();
                formData.append('action', 'save_order');
                if (orderId) formData.append('id', orderId);
                formData.append('order_number', document.getElementById('orderNumber').value);
                formData.append('project_id', document.getElementById('orderProject').value);
                formData.append('customer_name', document.getElementById('orderCustomer').value);
                formData.append('customer_email', document.getElementById('orderEmail').value);
                formData.append('customer_phone', document.getElementById('orderPhone').value);
                formData.append('customer_callsign', document.getElementById('orderCallsign').value);
                formData.append('quantity', document.getElementById('orderQty').value);
                formData.append('price_paid', document.getElementById('orderPrice').value);
                formData.append('order_date', document.getElementById('orderDate').value);
                formData.append('status', document.getElementById('orderStatus').value);
                formData.append('tracking_number', document.getElementById('orderTracking').value);
                formData.append('shipping_charge', document.getElementById('orderShippingCharge').value);
                formData.append('shipping_address', document.getElementById('orderAddress').value);
                formData.append('notes', document.getElementById('orderNotes').value);

                try {
                    await fetch('api.php', { method: 'POST', body: formData });
                    modal.remove();
                    loadOrders();
                    loadDashboard();
                } catch (error) {
                    alert('Error saving order');
                }
            });
        }

        function editOrder(id) {
            openOrderModal(id);
        }

        async function deleteOrder(id) {
            if (!confirm('Are you sure you want to delete this order?')) return;
            
            const formData = new FormData();
            formData.append('action', 'delete_order');
            formData.append('id', id);

            try {
                await fetch('api.php', { method: 'POST', body: formData });
                loadOrders();
                loadDashboard();
            } catch (error) {
                alert('Error deleting order');
            }
        }

        async function sendCustomerEmail(orderId) {
            if (!confirm('Send status update email to customer?')) return;
            
            const formData = new FormData();
            formData.append('action', 'send_customer_email');
            formData.append('order_id', orderId);
            
            try {
                const response = await fetch('send_customer_email.php', { method: 'POST', body: formData });
                const result = await response.json();
                if (result.success) {
                    alert('✓ Email sent to customer!');
                } else {
                    alert('Error sending email: ' + (result.error || 'Unknown error'));
                }
            } catch (error) {
                alert('Error sending email');
            }
        }

        // Settings
        document.getElementById('passwordForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData();
            formData.append('action', 'change_password');
            formData.append('current_password', document.getElementById('currentPassword').value);
            formData.append('new_password', document.getElementById('newPassword').value);

            try {
                const response = await fetch('api.php', { method: 'POST', body: formData });
                const data = await response.json();
                if (data.success) {
                    alert('Password changed successfully!');
                    document.getElementById('passwordForm').reset();
                } else {
                    alert(data.error || 'Error changing password');
                }
            } catch (error) {
                alert('Error changing password');
            }
        });

        // ============================================
        // SORTABLE COLUMNS
        // ============================================

        const sortState = {
            parts: { column: 'part_number', direction: 'asc' },
            projects: { column: 'status', direction: 'desc' },  // desc puts 'active' before 'archived'
            orders: { column: 'order_date', direction: 'desc' }
        };

        function sortData(data, column, direction) {
            return [...data].sort((a, b) => {
                let aVal = a[column];
                let bVal = b[column];
                
                if (aVal === null || aVal === undefined) aVal = '';
                if (bVal === null || bVal === undefined) bVal = '';
                
                if (typeof aVal === 'number' && typeof bVal === 'number') {
                    return direction === 'asc' ? aVal - bVal : bVal - aVal;
                }
                
                aVal = String(aVal).toLowerCase();
                bVal = String(bVal).toLowerCase();
                
                if (direction === 'asc') {
                    return aVal < bVal ? -1 : aVal > bVal ? 1 : 0;
                } else {
                    return aVal > bVal ? -1 : aVal < bVal ? 1 : 0;
                }
            });
        }

        function createSortableHeader(text, column, table) {
            const currentSort = sortState[table];
            const isActive = currentSort.column === column;
            const arrow = isActive ? (currentSort.direction === 'asc' ? ' ▲' : ' ▼') : '';
            
            return `<th style="cursor: pointer; user-select: none;" onclick="sortTable('${table}', '${column}')" title="Click to sort">
                ${text}${arrow}
            </th>`;
        }

        function sortTable(table, column) {
            const state = sortState[table];
            
            if (state.column === column) {
                state.direction = state.direction === 'asc' ? 'desc' : 'asc';
            } else {
                state.column = column;
                state.direction = 'asc';
            }
            
            // Update arrow indicators
            updateSortArrows(table);
            
            switch(table) {
                case 'parts':
                    loadParts();
                    break;
                case 'projects':
                    loadProjects();
                    break;
                case 'orders':
                    loadOrders();
                    break;
            }
        }
        
        function updateSortArrows(table) {
            const state = sortState[table];
            // Clear all arrows for this table
            document.querySelectorAll(`[id^="sort-${table}-"]`).forEach(span => span.textContent = '');
            // Set arrow for active column
            const activeSpan = document.getElementById(`sort-${table}-${state.column}`);
            if (activeSpan) {
                activeSpan.textContent = state.direction === 'asc' ? ' ▲' : ' ▼';
            }
        }

        // ============================================
        // BUSINESS METRICS
        // ============================================

        let businessMetrics = {};

        async function loadBusinessMetrics() {
            const periodDropdown = document.getElementById('businessPeriod');
            if (!periodDropdown) {
                console.error('Business period dropdown not found');
                return;
            }
            
            const period = periodDropdown.value;
            console.log('Loading business metrics for period:', period);
            
            try {
                const response = await fetch(`business_metrics.php?action=get_business_metrics&year=${period}`);
                businessMetrics = await response.json();
                console.log('Business metrics received:', businessMetrics);
                
                // Update main stats
                document.getElementById('statRevenue').textContent = '$' + (businessMetrics.orders?.revenue || 0).toLocaleString();
                document.getElementById('statGrossProfit').textContent = '$' + (businessMetrics.profit?.gross || 0).toLocaleString();
                document.getElementById('statNetProfit').textContent = '$' + (businessMetrics.profit?.net || 0).toLocaleString();
                document.getElementById('statMargin').textContent = (businessMetrics.profit?.margin || 0).toFixed(1) + '%';
                
                // Update inventory metrics
                document.getElementById('metricInventoryCost').textContent = '$' + (businessMetrics.inventory?.cost || 0).toLocaleString();
                document.getElementById('metricUnrealizedRevenue').textContent = '$' + (businessMetrics.inventory?.unrealized_revenue || 0).toLocaleString();
                
                // Update order metrics
                document.getElementById('metricOrderCount').textContent = businessMetrics.orders?.count || 0;
                document.getElementById('metricCOGS').textContent = '$' + (businessMetrics.orders?.cogs || 0).toLocaleString();
                document.getElementById('metricShipping').textContent = '$' + (businessMetrics.orders?.shipping || 0).toLocaleString();
                
                // Update P&L breakdown
                document.getElementById('plRevenue').textContent = '$' + (businessMetrics.orders?.revenue || 0).toLocaleString();
                document.getElementById('plCOGS').textContent = '$' + (businessMetrics.orders?.cogs || 0).toLocaleString();
                document.getElementById('plGross').textContent = '$' + (businessMetrics.profit?.gross || 0).toLocaleString();
                document.getElementById('plShipping').textContent = '$' + (businessMetrics.orders?.shipping || 0).toLocaleString();
                document.getElementById('plResearch').textContent = '$' + (businessMetrics.profit?.research_expenses || 0).toLocaleString();
                document.getElementById('plNet').textContent = '$' + (businessMetrics.profit?.net || 0).toLocaleString();
                
                // Orders by status
                let statusHtml = '<table class="data-table">';
                if (businessMetrics.orders_by_status && businessMetrics.orders_by_status.length > 0) {
                    businessMetrics.orders_by_status.forEach(s => {
                        statusHtml += `<tr><td>${s.status}</td><td>${s.count} orders</td><td style="text-align: right;">$${parseFloat(s.revenue).toLocaleString()}</td></tr>`;
                    });
                } else {
                    statusHtml += '<tr><td colspan="3">No orders yet</td></tr>';
                }
                statusHtml += '</table>';
                document.getElementById('ordersByStatus').innerHTML = statusHtml;
                
                // Top projects
                let projectsHtml = '<table class="data-table">';
                if (businessMetrics.top_projects && businessMetrics.top_projects.length > 0) {
                    businessMetrics.top_projects.forEach(p => {
                        projectsHtml += `<tr><td>${p.project_name}</td><td>${p.units_sold} units</td><td style="text-align: right;">$${parseFloat(p.revenue).toLocaleString()}</td></tr>`;
                    });
                } else {
                    projectsHtml += '<tr><td colspan="3">No sales yet</td></tr>';
                }
                projectsHtml += '</table>';
                document.getElementById('topProjects').innerHTML = projectsHtml;
                
            } catch (error) {
                console.error('Error loading business metrics:', error);
                alert('Error loading business metrics. Make sure business_metrics.php is uploaded.');
            }
        }
    </script>
</body>
</html>
