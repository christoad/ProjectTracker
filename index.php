<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KI6CR Inventory Manager</title>
    <link href="https://fonts.googleapis.com/css2?family=Figtree:wght@300;400;500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        :root {
          --bg-body:            #e8f0fe;
          --bg-card:            #f4f8ff;
          --bg-card-header:     #eef3fd;
          --bg-card-alt-row:    #f8fafe;
          --bg-dark:            #e8f0fe;
          --bg-medium:          #f4f8ff;
          --bg-light:           #c7d9fb;
          --header-gradient:    linear-gradient(135deg, #1a56db 0%, #0680c6 100%);
          --header-height:      56px;
          --nav-bg:             #162038;
          --nav-border-bottom:  #1a56db;
          --nav-tab-active-bg:  #1a56db;
          --nav-tab-active-color: #ffffff;
          --nav-tab-color:      #5d729e;
          --accent-primary:     #1a56db;
          --accent-primary-dim: #1240a8;
          --accent-secondary:   #0680c6;
          --border-color:       #c7d9fb;
          --border-card:        #c7d9fb;
          --border-table-head:  #1a56db;
          --text-primary:       #0f1c3f;
          --text-secondary:     #6b7280;
          --text-dim:           #9ca3af;
          --success:            #10b981;
          --warning:            #f59e0b;
          --danger:             #ef4444;
          --info:               #3b82f6;
          --shadow:             rgba(10, 30, 100, 0.08);
          --shadow-card:        0 2px 8px rgba(10, 30, 100, 0.06);
          --shadow-header:      0 2px 16px rgba(15, 28, 63, 0.22);
          --shadow-modal:       0 20px 60px rgba(0, 0, 0, 0.30);
          --font-body:          'Figtree', sans-serif;
          --font-mono:          'IBM Plex Mono', monospace;
          --radius-sm:          3px;
          --radius-md:          4px;
          --radius-card:        6px;
          --radius-modal:       8px;
          --z-header:           100;
          --z-modal:            1000;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: var(--font-body);
            background: var(--bg-body);
            color: var(--text-primary);
            line-height: 1.6;
        }

        /* Header */
        .app-header {
            background: var(--header-gradient);
            border-bottom: none;
            padding: 0 32px;
            height: var(--header-height);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: var(--z-header);
            box-shadow: var(--shadow-header);
        }

        .app-logo-block {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .app-logo-icon {
            width: 32px; height: 32px;
            border: 2px solid rgba(255,255,255,0.35);
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }

        .app-logo-diamond {
            width: 12px; height: 12px;
            background: #fff;
            transform: rotate(45deg);
            border-radius: 2px;
            opacity: 0.92;
        }

        .app-logo-callsign {
            font-family: var(--font-mono);
            font-size: 16px; font-weight: 700;
            color: #fff;
            letter-spacing: 2.5px; line-height: 1.1;
        }

        .app-logo-subtitle {
            font-size: 10px;
            color: rgba(255,255,255,0.58);
            letter-spacing: 0.8px;
            text-transform: uppercase;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-callsign {
            font-family: var(--font-mono);
            font-size: 12px;
            color: rgba(255,255,255,0.68);
        }

        .btn-ghost {
            padding: 5px 14px;
            background: rgba(255,255,255,0.10);
            border: 1px solid rgba(255,255,255,0.22);
            color: rgba(255,255,255,0.82);
            border-radius: var(--radius-md);
            font-size: 12px; cursor: pointer;
            font-family: var(--font-body); font-weight: 500;
        }

        .btn-ghost:hover {
            background: rgba(255,255,255,0.18);
        }

        /* Navigation */
        .nav-tabs {
            background: var(--nav-bg);
            padding: 0 32px;
            display: flex;
            gap: 0;
            border-bottom: 2px solid var(--nav-border-bottom);
        }

        .nav-tab {
            padding: 11px 18px;
            background: transparent;
            border: none;
            border-right: 1px solid rgba(255,255,255,0.04);
            color: var(--nav-tab-color);
            cursor: pointer;
            font-family: var(--font-body);
            font-size: 13px;
            font-weight: 400;
            letter-spacing: 0.2px;
            text-transform: none;
            transition: all 0.15s;
        }

        .nav-tab:hover {
            background: rgba(255,255,255,0.05);
            color: rgba(255,255,255,0.75);
        }

        .nav-tab.active {
            background: var(--nav-tab-active-bg);
            color: var(--nav-tab-active-color);
            font-weight: 600;
        }

        /* Main Content */
        .main-content {
            padding: 20px 32px;
            max-width: 1400px;
            margin: 0 auto;
        }

        .section {
            display: none;
        }

        .section.active {
            display: block;
            animation: fadeIn 0.25s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Cards */
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
            margin-bottom: 0;
        }

        .card-title {
            font-size: 11.5px;
            font-weight: 600;
            color: var(--text-primary);
            text-transform: uppercase;
            letter-spacing: 0.9px;
        }

        .card-body {
            padding: 18px 20px;
        }

        .parts-header-controls {
            display: flex;
            gap: 0.5rem;
            align-items: center;
            flex-wrap: wrap;
        }
        #partsProjectFilter { width: 200px; }
        #partsSearchInput   { width: 220px; }

        /* Order number and date should never wrap */
        #ordersTable td:nth-child(1),
        #ordersTable td:nth-child(2) { white-space: nowrap; }

        /* Buttons */
        .btn {
            padding: 5px 12px;
            border: 1px solid var(--border-card);
            background: #eef3fd;
            color: var(--accent-primary);
            cursor: pointer;
            font-family: var(--font-body);
            font-size: 11px;
            font-weight: 500;
            border-radius: var(--radius-sm);
            transition: all 0.15s;
            text-transform: none;
            letter-spacing: 0;
        }

        .btn:hover {
            background: var(--bg-light);
            border-color: var(--accent-primary);
        }

        .btn-primary {
            padding: 6px 15px;
            background: var(--accent-primary);
            border-color: var(--accent-primary);
            color: #fff;
            font-size: 12px;
            font-weight: 600;
            border-radius: var(--radius-md);
        }

        .btn-primary:hover {
            background: var(--accent-primary-dim);
            border-color: var(--accent-primary-dim);
            color: #fff;
        }

        .btn-danger {
            background: rgba(239,68,68,0.10);
            border: 1px solid rgba(239,68,68,0.27);
            color: var(--danger);
        }

        .btn-danger:hover {
            background: rgba(239,68,68,0.18);
        }

        .btn-small {
            padding: 4px 9px;
            font-size: 11px;
        }
        
        /* Forms */
        .form-group {
            margin-bottom: 1rem;
        }

        .form-label {
            display: block;
            margin-bottom: 5px;
            color: var(--text-secondary);
            font-size: 10.5px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            font-family: var(--font-body);
        }

        .form-input,
        .form-select,
        .form-textarea {
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

        .form-input:focus,
        .form-select:focus,
        .form-textarea:focus {
            outline: none;
            border-color: var(--accent-primary);
            box-shadow: 0 0 0 3px rgba(74,124,56,0.12);
        }

        .form-textarea {
            resize: vertical;
            min-height: 80px;
        }

        /* Tables */
        .table-container {
            overflow-x: auto;
            border-radius: 0 0 var(--radius-card) var(--radius-card);
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12.5px;
        }

        .data-table thead {
            background: var(--bg-card-header);
        }

        .data-table th {
            padding: 9px 16px;
            text-align: left;
            color: var(--text-secondary);
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.9px;
            border-bottom: 2px solid var(--border-table-head);
        }

        .data-table td {
            padding: 10px 16px;
            border-bottom: 1px solid #eef3fd;
        }

        .data-table tbody tr:nth-child(even) { background: #fff; }
        .data-table tbody tr:nth-child(odd)  { background: var(--bg-card-alt-row); }
        .data-table tbody tr:hover           { background: var(--bg-body); }
        .data-table tbody tr:last-child td   { border-bottom: none; }

        .cell-mono { font-family: var(--font-mono); }
        .cell-pn   { font-family: var(--font-mono); font-weight: 500; color: var(--accent-primary); font-size: 12px; }
        .cell-callsign { font-family: var(--font-mono); color: var(--text-dim); font-size: 11.5px; }
        .cell-amount   { font-family: var(--font-mono); font-weight: 700; color: var(--text-primary); }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--bg-card);
            border: 1px solid var(--border-card);
            border-radius: var(--radius-card);
            padding: 18px 20px;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
            background: var(--accent-primary);
        }

        .stat-card.stat-parts::before  { background: var(--accent-secondary); }
        .stat-card.stat-low::before    { background: var(--warning); }
        .stat-card.stat-orders::before { background: var(--success); }

        .stat-value {
            font-family: var(--font-mono);
            font-size: 36px;
            font-weight: 700;
            color: var(--text-primary);
            line-height: 1.05;
            margin-top: 4px;
        }

        .stat-label {
            color: var(--text-secondary);
            font-size: 10.5px;
            text-transform: uppercase;
            letter-spacing: 0.9px;
            margin-top: 7px;
        }

        /* Login Screen */
        .login-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: linear-gradient(160deg, #0a1628 0%, #0f1c3f 60%, #162038 100%);
        }

        .login-box {
            background: var(--bg-card);
            border: 2px solid var(--accent-primary);
            border-radius: var(--radius-modal);
            padding: 40px 36px;
            width: 100%;
            max-width: 360px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.45);
        }

        .login-title {
            font-family: var(--font-mono);
            font-size: 22px;
            font-weight: 700;
            color: var(--accent-primary);
            letter-spacing: 3px;
            margin-bottom: 6px;
        }

        .login-logo-icon {
            width: 48px; height: 48px;
            background: var(--header-gradient);
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 12px;
        }

        .login-logo-icon .app-logo-diamond {
            width: 18px; height: 18px;
        }

        .login-btn {
            width: 100%;
            padding: 10px;
            font-size: 13px;
            background: var(--header-gradient);
            border: none;
            border-radius: var(--radius-md);
        }

        /* Badges */
        .badge {
            display: inline-block;
            padding: 2px 7px;
            font-size: 10px;
            font-weight: 700;
            border-radius: var(--radius-sm);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-success   { background: rgba(16,185,129,0.13); color: var(--success); }
        .badge-warning   { background: rgba(245,158,11,0.13);  color: var(--warning); }
        .badge-danger    { background: rgba(239,68,68,0.13);   color: var(--danger); }
        .badge-info      { background: rgba(59,130,246,0.13);  color: var(--info); }
        .badge-secondary { background: rgba(156,163,175,0.13); color: var(--text-dim); }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.72);
            backdrop-filter: blur(4px);
            z-index: var(--z-modal);
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: var(--bg-card);
            border: 2px solid var(--accent-primary);
            border-radius: var(--radius-modal);
            padding: 28px 32px;
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: var(--shadow-modal);
        }

        .modal-content.modal-wide {
            max-width: 1000px;
        }

        .modal-content.modal-expanded {
            max-width: 95vw;
            max-height: 95vh;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 18px;
            padding-bottom: 14px;
            border-bottom: 1px solid var(--border-card);
        }

        .modal-title {
            font-size: 14px;
            font-weight: 600;
            color: var(--accent-primary);
            text-transform: uppercase;
            letter-spacing: 0.8px;
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
            font-family: var(--font-mono);
        }

        .stock-ok {
            color: var(--success);
            font-family: var(--font-mono);
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
                padding: 0 1rem;
            }

            /* Nav: scroll horizontally, never wrap tab text */
            .nav-tabs {
                overflow-x: auto;
                padding: 0 0.5rem;
                scrollbar-width: none;
            }
            .nav-tabs::-webkit-scrollbar { display: none; }
            .nav-tab {
                white-space: nowrap;
                font-size: 12px;
                padding: 10px 12px;
            }

            /* Tables: enforce minimum widths so columns stay readable;
               containers already have overflow-x:auto — this makes them scroll */
            .table-container {
                -webkit-overflow-scrolling: touch;
            }
            #partsTable    { min-width: 660px; }
            #projectsTable { min-width: 600px; }
            #ordersTable   { min-width: 620px; }

            /* Card headers: allow wrapping when controls don't fit */
            .card-header {
                flex-wrap: wrap;
                gap: 0.5rem;
            }

            /* Parts header controls: go full-width and let items fill the row */
            .parts-header-controls {
                width: 100%;
            }
            #partsProjectFilter,
            #partsSearchInput {
                flex: 1;
                min-width: 120px;
                width: auto;
            }

            /* Reduce card body padding */
            .card-body {
                padding: 12px 14px;
            }

            /* App header: tighten logo subtitle on small screens */
            .app-logo-subtitle {
                display: none;
            }

            /* Business section header: stack title and period picker */
            .section-header {
                flex-direction: column !important;
                align-items: flex-start !important;
                gap: 0.75rem;
                margin-bottom: 1rem !important;
            }
            .section-header select {
                width: 100%;
            }
        }

        /* ── Tasks ─────────────────────────────────── */
        .task-project-bar {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 18px;
        }

        .task-project-bar select {
            flex: 1;
            max-width: 340px;
            padding: 7px 10px;
            border: 1px solid var(--border-card);
            border-radius: var(--radius-md);
            background: var(--bg-card);
            color: var(--text-primary);
            font-family: var(--font-body);
            font-size: 13px;
        }

        .task-list { list-style: none; }

        .task-item {
            background: var(--bg-card);
            border: 1px solid var(--border-card);
            border-radius: var(--radius-md);
            margin-bottom: 6px;
            transition: box-shadow 0.15s;
        }

        .task-item.dragging {
            opacity: 0.45;
            box-shadow: 0 4px 16px rgba(74,124,56,0.18);
        }

        .task-item.drag-over {
            border-color: var(--accent-primary);
            box-shadow: 0 0 0 2px rgba(74,124,56,0.18);
        }

        .task-row {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 9px 12px;
        }

        .task-drag-handle {
            cursor: grab;
            color: var(--text-dim);
            font-size: 14px;
            line-height: 1;
            padding: 2px 4px;
            flex-shrink: 0;
            user-select: none;
        }

        .task-drag-handle:active { cursor: grabbing; }

        .task-checkbox {
            width: 15px;
            height: 15px;
            flex-shrink: 0;
            cursor: pointer;
            accent-color: var(--accent-primary);
        }

        .task-title-text {
            flex: 1;
            font-size: 13.5px;
            color: var(--text-primary);
            cursor: text;
            word-break: break-word;
        }

        .task-title-text.done {
            text-decoration: line-through;
            color: var(--text-dim);
        }

        .task-title-input {
            flex: 1;
            padding: 2px 6px;
            border: 1px solid var(--accent-primary);
            border-radius: var(--radius-sm);
            font-family: var(--font-body);
            font-size: 13.5px;
            color: var(--text-primary);
            background: #fff;
            outline: none;
        }

        .task-actions {
            display: flex;
            gap: 4px;
            flex-shrink: 0;
            opacity: 0;
            transition: opacity 0.1s;
        }

        .task-row:hover .task-actions { opacity: 1; }

        .task-action-btn {
            padding: 2px 7px;
            font-size: 11px;
            border: 1px solid var(--border-card);
            border-radius: var(--radius-sm);
            background: var(--bg-body);
            color: var(--text-secondary);
            cursor: pointer;
            font-family: var(--font-body);
            white-space: nowrap;
        }

        .task-action-btn:hover {
            background: var(--bg-light);
            border-color: var(--accent-primary);
            color: var(--accent-primary);
        }

        .task-action-btn.del:hover {
            background: rgba(239,68,68,0.08);
            border-color: rgba(239,68,68,0.4);
            color: var(--danger);
        }

        /* Sub-tasks */
        .subtask-list {
            list-style: none;
            margin: 0 12px 8px 36px;
        }

        .task-item.subtask {
            border-style: dashed;
            background: var(--bg-body);
        }

        .task-item.subtask .task-row { padding: 7px 10px; }
        .task-item.subtask .task-title-text { font-size: 13px; }

        /* Sub-sub-tasks */
        .task-item.subsubtask {
            border-style: dotted;
            background: var(--bg-card-alt-row);
        }

        .task-item.subsubtask .task-row { padding: 5px 10px; }
        .task-item.subsubtask .task-title-text { font-size: 12px; }

        .task-item.subtask .subtask-list { margin: 0 12px 8px 24px; }

        /* Add-task inline form */
        .add-task-row {
            display: flex;
            gap: 8px;
            margin-top: 10px;
            padding: 0 2px;
        }

        .add-task-row input {
            flex: 1;
            padding: 7px 10px;
            border: 1px solid var(--border-card);
            border-radius: var(--radius-md);
            font-family: var(--font-body);
            font-size: 13px;
            color: var(--text-primary);
            background: var(--bg-card);
        }

        .add-task-row input:focus {
            outline: none;
            border-color: var(--accent-primary);
        }

        .task-empty {
            padding: 32px 20px;
            text-align: center;
            color: var(--text-dim);
            font-size: 13px;
        }

        .task-progress-bar {
            height: 4px;
            background: var(--border-card);
            border-radius: 2px;
            margin-bottom: 16px;
            overflow: hidden;
        }

        .task-progress-fill {
            height: 100%;
            background: var(--accent-primary);
            border-radius: 2px;
            transition: width 0.3s;
        }
    </style>
</head>
<body>
    <!-- Login Screen -->
    <div id="loginScreen" class="login-container">
        <div class="login-box">
            <div style="text-align: center; margin-bottom: 24px;">
                <img src="KI6CR-Labs-stacked.svg" alt="KI6CR Labs" style="width:150px;margin-bottom:14px;">
                <div style="font-size: 10px; color: var(--text-dim); letter-spacing: 0.8px; text-transform: uppercase;">Inventory Manager</div>
            </div>
            <form id="loginForm">
                <div class="form-group">
                    <label class="form-label">Username</label>
                    <input type="text" id="loginUsername" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" id="loginPassword" class="form-input" required>
                </div>
                <button type="submit" class="btn btn-primary login-btn">Login</button>
                <div id="loginError" class="hidden" style="margin-top: 1rem; color: var(--danger); text-align: center;"></div>
            </form>
        </div>
    </div>

    <!-- Main Application -->
    <div id="mainApp" class="hidden">
        <header class="app-header">
            <div class="app-logo-block">
                <img src="KI6CR-Labs-horizontal.svg" alt="KI6CR Labs" style="height:34px;filter:brightness(0) invert(1);opacity:0.92;">
            </div>
            <div class="user-info">
                <span id="username" class="user-callsign"></span>
                <button class="btn-ghost" onclick="logout()">Logout</button>
            </div>
        </header>

        <nav class="nav-tabs">
            <button class="nav-tab active" onclick="showSection('dashboard')">Dashboard</button>
            <button class="nav-tab" onclick="showSection('projects')">Projects</button>
            <button class="nav-tab" onclick="showSection('parts')">Parts Inventory</button>
            <button class="nav-tab" onclick="showSection('orders')">Orders</button>
            <button class="nav-tab" onclick="showSection('business')">📊 Business</button>
            <button class="nav-tab" onclick="showSection('tasks')">Tasks</button>
            <button class="nav-tab" onclick="showSection('settings')">Settings</button>
            <button class="nav-tab" onclick="showSection('beta-feedback')">Beta Feedback</button>
        </nav>

        <main class="main-content">
            <!-- Dashboard Section -->
            <section id="dashboard" class="section active">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-value" id="statProjects">0</div>
                        <div class="stat-label">Active Projects</div>
                    </div>
                    <div class="stat-card stat-parts">
                        <div class="stat-value" id="statParts">0</div>
                        <div class="stat-label">Total Parts</div>
                    </div>
                    <div class="stat-card stat-low">
                        <div class="stat-value" id="statLowStock">0</div>
                        <div class="stat-label">Low Stock Alerts</div>
                    </div>
                    <div class="stat-card stat-orders">
                        <div class="stat-value" id="statOrders">0</div>
                        <div class="stat-label">Pending Orders</div>
                    </div>
                </div>

                <div class="grid-2">
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">Low Stock Parts</h2>
                        </div>
                        <div id="lowStockList" class="card-body"></div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">Recent Orders</h2>
                        </div>
                        <div id="recentOrdersList" class="card-body"></div>
                    </div>
                </div>

                <div class="card" style="margin-top:1.5rem;">
                    <div class="card-header" style="flex-wrap:wrap;gap:8px;">
                        <div>
                            <h2 class="card-title">Inventory Order Planner</h2>
                            <span style="font-size:0.8rem;color:var(--text-secondary);font-weight:400;">Every BOM part ranked by how many kits you can build — see what to order</span>
                        </div>
                        <div style="display:flex;align-items:center;gap:6px;flex-shrink:0;flex-wrap:wrap;">
                            <span style="font-size:0.82rem;color:var(--text-secondary);">Order to:</span>
                            <button class="btn btn-small" data-bt-preset="25" onclick="setBottleneckTarget(25)">25 kits</button>
                            <button class="btn btn-small" data-bt-preset="50" onclick="setBottleneckTarget(50)" style="background:var(--accent-primary);color:white;border-color:var(--accent-primary);">50 kits</button>
                            <button class="btn btn-small" data-bt-preset="100" onclick="setBottleneckTarget(100)">100 kits</button>
                            <button class="btn btn-small" data-bt-preset="max" onclick="setBottleneckTarget('max')">Match Max</button>
                        </div>
                    </div>
                    <div id="bottleneckInsights"></div>
                </div>
            </section>

            <!-- Projects Section -->
            <section id="projects" class="section">
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Projects / Kits</h2>
                        <div style="display:flex;gap:0.5rem;align-items:center;flex-wrap:wrap;">
                            <button class="btn btn-small" onclick="wcCheckStatus()" style="background:var(--info);color:white;border-color:var(--info);">Check WC Status</button>
                            <button class="btn btn-small" onclick="wcSyncAll()" style="background:var(--accent-secondary);color:white;border-color:var(--accent-secondary);">Sync All to WooCommerce</button>
                            <button class="btn btn-small" onclick="wcViewLog()" style="background:var(--bg-light);border-color:var(--border-card);">Sync Log</button>
                            <button class="btn btn-primary" onclick="openProjectModal()">+ New Project</button>
                        </div>
                    </div>
                    <div id="wcSyncResult" style="display:none;padding:12px 16px;border-bottom:1px solid var(--border-card);background:var(--bg-card-alt-row);font-size:0.9rem;"></div>
                    <div class="table-container" id="projectsTableContainer">
                        <table class="data-table" id="projectsTable">
                            <thead>
                                <tr>
                                    <th style="cursor: pointer; user-select: none;" onclick="sortTable('projects', 'project_name')" title="Click to sort">Project Name <span id="sort-projects-project_name"></span></th>
                                    <th style="cursor: pointer; user-select: none;" onclick="sortTable('projects', 'description')" title="Click to sort">Description <span id="sort-projects-description"></span></th>
                                    <th style="cursor: pointer; user-select: none;" onclick="sortTable('projects', 'status')" title="Click to sort">Status <span id="sort-projects-status"></span></th>
                                    <th style="cursor: pointer; user-select: none;" onclick="sortTable('projects', 'parts_count')" title="Click to sort">Parts <span id="sort-projects-parts_count"></span></th>
                                    <th style="cursor: pointer; user-select: none;" onclick="sortTable('projects', 'buildable_kits')" title="Click to sort">Buildable <span id="sort-projects-buildable_kits"></span></th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>

                <div id="trashedProjectsCard" class="card" style="margin-top:1.5rem;display:none;">
                    <div class="card-header" style="cursor:pointer;" onclick="toggleTrashedProjects()">
                        <h2 class="card-title" style="color:var(--text-secondary);">🗑 Trash <span id="trashedCount" style="font-size:0.8rem;font-weight:400;"></span></h2>
                        <span id="trashedToggleLabel" style="font-size:0.82rem;color:var(--text-secondary);">Show</span>
                    </div>
                    <div id="trashedProjectsList" style="display:none;"></div>
                </div>
            </section>

            <!-- Parts Section -->
            <section id="parts" class="section">
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Parts Inventory</h2>
                        <div class="parts-header-controls">
                            <select id="partsProjectFilter" class="form-input" onchange="onPartsProjectFilterChange()">
                                <option value="">All Projects</option>
                                <option value="__unassigned__">Unassigned to a project</option>
                            </select>
                            <input type="text" id="partsSearchInput" class="form-input" placeholder="Search parts..." oninput="filterPartsTable(this.value)">
                            <button class="btn btn-primary" onclick="openPartModal()">+ New Part</button>
                        </div>
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
                        <div class="card-body">
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
                    </div>

                    <div class="card">
                        <div class="card-body">
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
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
                    <div class="card">
                        <div class="card-body">
                        <h3 style="margin-bottom: 1rem;">Orders by Status</h3>
                        <div id="ordersByStatus">Loading...</div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body">
                        <h3 style="margin-bottom: 1rem;">Top Selling Projects</h3>
                        <div id="topProjects">Loading...</div>
                        </div>
                    </div>
                </div>

                <!-- Business Expenses Card -->
                <div class="card" style="margin-bottom: 1.5rem;">
                    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;">
                        <h3 class="card-title">Overhead &amp; Business Expenses</h3>
                        <button class="btn btn-primary btn-small" onclick="document.getElementById('addBizExpenseForm').style.display='block';this.style.display='none';if(!document.getElementById('bizExpDate').value)document.getElementById('bizExpDate').value=new Date().toISOString().split('T')[0];">+ Add Expense</button>
                    </div>
                    <div class="card-body">
                        <div id="addBizExpenseForm" style="display:none;margin-bottom:1rem;padding:1rem;border:1px solid var(--border-card);border-radius:var(--radius-md);background:var(--bg-card-header);">
                            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:0.75rem;margin-bottom:0.75rem;">
                                <div class="form-group" style="margin:0;">
                                    <label class="form-label">Description</label>
                                    <input type="text" id="bizExpDesc" class="form-input" placeholder="e.g. Thermal label printer">
                                </div>
                                <div class="form-group" style="margin:0;">
                                    <label class="form-label">Category</label>
                                    <select id="bizExpCategory" class="form-select">
                                        <option>Equipment</option>
                                        <option>Supplies</option>
                                        <option>Software</option>
                                        <option>Packaging</option>
                                        <option>Fees</option>
                                        <option>Shipping Supplies</option>
                                        <option>Other</option>
                                    </select>
                                </div>
                                <div class="form-group" style="margin:0;">
                                    <label class="form-label">Date</label>
                                    <input type="date" id="bizExpDate" class="form-input">
                                </div>
                            </div>
                            <div style="display:grid;grid-template-columns:1fr 2fr;gap:0.75rem;margin-bottom:0.75rem;">
                                <div class="form-group" style="margin:0;">
                                    <label class="form-label">Amount ($)</label>
                                    <input type="number" id="bizExpCost" class="form-input" placeholder="0.00" step="0.01" min="0">
                                </div>
                                <div class="form-group" style="margin:0;">
                                    <label class="form-label">Notes (optional)</label>
                                    <input type="text" id="bizExpNotes" class="form-input" placeholder="">
                                </div>
                            </div>
                            <div class="flex flex-gap">
                                <button class="btn btn-primary btn-small" onclick="saveBizExpense()">Save Expense</button>
                                <button class="btn btn-small" onclick="document.getElementById('addBizExpenseForm').style.display='none';document.querySelector('[onclick*=addBizExpenseForm]').style.display='';">Cancel</button>
                            </div>
                        </div>
                        <div id="bizExpenseList">Loading...</div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
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
                        <tr>
                            <td style="padding-left: 2rem;">- Overhead &amp; Business Expenses</td>
                            <td style="text-align: right;" id="plOverhead">$0</td>
                        </tr>
                        <tr style="border-top: 2px solid var(--border-color);">
                            <td><strong>Net Profit</strong></td>
                            <td style="text-align: right; font-weight: bold; color: var(--accent-primary);" id="plNet">$0</td>
                        </tr>
                    </table>
                    </div>
                </div>
            </section>

            <!-- Settings Section -->
            <section id="settings" class="section">
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Settings</h2>
                    </div>
                    <div class="card-body">
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
                </div>
            </section>

            <section id="tasks" class="section">
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Project Tasks</h2>
                        <span id="taskProgressLabel" style="font-size:11px;color:var(--text-dim);"></span>
                    </div>
                    <div class="card-body">
                        <div class="task-project-bar">
                            <select id="taskProjectSelect" onchange="loadTasks()">
                                <option value="">— Select a project —</option>
                            </select>
                        </div>
                        <div id="taskProgressBar" class="task-progress-bar" style="display:none;">
                            <div id="taskProgressFill" class="task-progress-fill" style="width:0%"></div>
                        </div>
                        <ul id="taskList" class="task-list"></ul>
                        <div id="addRootTaskRow" class="add-task-row" style="display:none;">
                            <input type="text" id="newRootTaskInput" placeholder="New task… (press Enter)" onkeydown="handleAddRootTask(event)">
                            <button class="btn btn-primary" onclick="submitNewRootTask()">Add Task</button>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Beta Feedback Section -->
            <section id="beta-feedback" class="section">
                <div class="card" style="margin-bottom:16px;">
                    <div class="card-header" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;">
                        <h2 class="card-title">KH1 Beta Builder Feedback</h2>
                        <div style="display:flex;gap:8px;align-items:center;">
                            <a href="kh1_qr.php" target="_blank" class="btn btn-secondary" style="text-decoration:none;font-size:0.8rem;">QR Code</a>
                            <a href="kh1_feedback.php" target="_blank" class="btn btn-secondary" style="text-decoration:none;font-size:0.8rem;">View Form</a>
                        </div>
                    </div>
                    <div style="padding:0 1rem 0.5rem;">
                        <div id="betaSummaryCards" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:10px;margin-bottom:14px;"></div>
                        <div id="betaPackagingAlert" style="display:none;background:#fef2f2;border:1px solid #fca5a5;border-radius:6px;padding:10px 14px;font-size:0.84rem;color:#991b1b;margin-bottom:10px;"></div>
                        <div id="betaStepIssues" style="display:none;margin-bottom:10px;"></div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Builder Responses</h2>
                    </div>
                    <div style="overflow-x:auto;">
                        <table class="data-table" id="betaBuilderTable">
                            <thead>
                                <tr>
                                    <th>Callsign</th>
                                    <th>Steps Saved</th>
                                    <th>Issues Flagged</th>
                                    <th>Last Active</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="betaBuilderBody">
                                <tr><td colspan="5" style="text-align:center;padding:2rem;color:var(--text-dim);">Loading…</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Builder detail modal -->
                <div id="betaDetailModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:1100;align-items:center;justify-content:center;">
                    <div style="background:#fff;border-radius:10px;max-width:640px;width:94%;max-height:86vh;display:flex;flex-direction:column;box-shadow:0 20px 60px rgba(0,0,0,0.3);">
                        <div style="padding:16px 20px;border-bottom:1px solid var(--border-card);display:flex;align-items:center;justify-content:space-between;">
                            <div>
                                <div style="font-family:var(--font-mono);font-size:0.7rem;letter-spacing:0.12em;color:var(--text-dim);text-transform:uppercase;">Beta Builder</div>
                                <div id="betaDetailCallsign" style="font-family:var(--font-mono);font-size:1.1rem;font-weight:600;color:var(--text-primary);"></div>
                            </div>
                            <button onclick="closeBetaDetail()" style="background:none;border:none;font-size:1.5rem;cursor:pointer;color:var(--text-dim);padding:4px 8px;">×</button>
                        </div>
                        <div id="betaDetailBody" style="overflow-y:auto;padding:16px 20px;flex:1;"></div>
                    </div>
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
        let bottleneckInsightsData = [];
        let bottleneckTarget = 50;
        let bottleneckExpandedProjects = new Set();
        let bottleneckInitialized = false;

        // BOM view state (project modal)
        let bomSortState = { column: 'part_number', direction: 'asc' };
        let bomSearchQuery = '';
        let bomDragMode = false;
        let bomDragSrcIdx = null;

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
            if (sectionId === 'projects') { loadProjects(); loadTrashedProjects(); }
            if (sectionId === 'parts') loadParts();
            if (sectionId === 'orders') loadOrders();
            if (sectionId === 'business') {
                // Small delay to ensure DOM is rendered
                setTimeout(() => loadBusinessMetrics(), 100);
            }
            if (sectionId === 'tasks') loadTasksSection();
            if (sectionId === 'beta-feedback') loadBetaFeedback();
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

                // Bottleneck insights — store data and render with current target
                bottleneckInsightsData = data.bottleneck_insights || [];
                bottleneckExpandedProjects = new Set();
                bottleneckInitialized = false;
                renderBottleneckInsights();
            } catch (error) {
                console.error('Error loading dashboard:', error);
            }
        }

        function setBottleneckTarget(val) {
            bottleneckTarget = val;
            document.querySelectorAll('[data-bt-preset]').forEach(btn => {
                const active = btn.dataset.btPreset == String(val);
                btn.style.background = active ? 'var(--accent-primary)' : '';
                btn.style.color = active ? 'white' : '';
                btn.style.borderColor = active ? 'var(--accent-primary)' : '';
            });
            renderBottleneckInsights();
        }

        function toggleBottleneckProject(projectId) {
            const body = document.getElementById(`bt-body-${projectId}`);
            const arrow = document.getElementById(`bt-arrow-${projectId}`);
            if (!body) return;
            const expanding = body.style.display === 'none';
            body.style.display = expanding ? '' : 'none';
            if (arrow) arrow.textContent = expanding ? '▾' : '▸';
            if (expanding) bottleneckExpandedProjects.add(projectId);
            else bottleneckExpandedProjects.delete(projectId);
        }

        function renderBottleneckInsights() {
            const container = document.getElementById('bottleneckInsights');
            const insights = bottleneckInsightsData;
            if (!insights.length) {
                container.innerHTML = '<div style="padding:1rem;color:var(--text-dim)">No active projects with BOM data.</div>';
                return;
            }

            // On first render, auto-expand projects that have parts needing ordering
            if (!bottleneckInitialized) {
                insights.forEach(proj => {
                    const t = bottleneckTarget === 'max' ? proj.max_buildable : bottleneckTarget;
                    if (proj.all_fixed_parts.some(p => p.buildable < t)) {
                        bottleneckExpandedProjects.add(proj.project_id);
                    }
                });
                bottleneckInitialized = true;
            }

            const html = insights.map(proj => {
                const effectiveTarget = bottleneckTarget === 'max' ? proj.max_buildable : bottleneckTarget;
                const b = proj.current_buildable;
                const parts = proj.all_fixed_parts; // sorted ascending by buildable
                const barCeiling = Math.max(proj.max_buildable, effectiveTarget, 1);
                const badgeColor = b === 0 ? 'var(--danger)' : b < 10 ? 'var(--warning)' : b < 30 ? 'var(--info)' : 'var(--success)';
                const isExpanded = bottleneckExpandedProjects.has(proj.project_id);

                const partsRows = parts.map(part => {
                    const isBottleneck = part.buildable === b;
                    const needsOrder = part.buildable < effectiveTarget;
                    const unitsNeeded = needsOrder ? Math.max(0, effectiveTarget * part.quantity_required - part.current_stock) : 0;
                    const barPct = Math.min(100, (part.buildable / barCeiling) * 100).toFixed(1);
                    const targetPct = Math.min(100, (effectiveTarget / barCeiling) * 100).toFixed(1);
                    const barColor = isBottleneck ? '#ef4444' : needsOrder ? '#f59e0b' : '#10b981';
                    const rowBg = isBottleneck ? 'rgba(239,68,68,0.04)' : '';

                    let orderCell;
                    if (!needsOrder) {
                        orderCell = `<span style="color:var(--success);font-size:0.82rem;">✓ ok</span>`;
                    } else {
                        const costStr = part.unit_cost > 0
                            ? ` <span style="color:var(--text-secondary);font-size:0.78rem;">~$${(unitsNeeded * part.unit_cost).toFixed(2)}</span>` : '';
                        const urgStyle = isBottleneck
                            ? 'color:var(--danger);font-weight:700;'
                            : 'color:var(--warning);font-weight:600;';
                        orderCell = `<span style="${urgStyle}">${unitsNeeded.toLocaleString()} units</span>${costStr}`;
                    }

                    const partNameStyle = needsOrder
                        ? `font-weight:${isBottleneck ? '700' : '500'};color:${isBottleneck ? 'var(--danger)' : 'var(--text-primary)'};cursor:pointer;text-decoration:underline;text-decoration-color:rgba(0,0,0,0.15);`
                        : 'cursor:pointer;text-decoration:underline;text-decoration-color:rgba(0,0,0,0.15);color:var(--text-primary);';

                    return `
                    <tr style="background:${rowBg};">
                        <td style="padding:5px 8px;font-size:0.875rem;${partNameStyle}"
                            onclick="viewPart(${part.part_id})"
                            onmouseover="this.style.color='var(--accent-primary)'"
                            onmouseout="this.style.color='${isBottleneck ? 'var(--danger)' : 'var(--text-primary)'}'">${part.part_name}</td>
                        <td style="padding:5px 8px;font-family:var(--font-mono);font-size:0.78rem;color:var(--text-dim);">${part.part_number}</td>
                        <td style="padding:5px 8px;text-align:center;font-family:var(--font-mono);font-size:0.82rem;color:var(--text-secondary);">${part.quantity_required}/kit</td>
                        <td style="padding:5px 8px;text-align:right;font-family:var(--font-mono);font-size:0.82rem;">${part.current_stock.toLocaleString()}</td>
                        <td style="padding:5px 12px 5px 8px;">
                            <div style="position:relative;height:10px;background:var(--bg-light);border-radius:5px;width:130px;">
                                <div style="position:absolute;left:0;top:0;height:100%;width:${barPct}%;background:${barColor};border-radius:5px;"></div>
                                <div style="position:absolute;top:-3px;height:16px;width:2px;background:var(--accent-primary);opacity:0.65;left:${targetPct}%;transform:translateX(-50%);"></div>
                            </div>
                        </td>
                        <td style="padding:5px 8px;text-align:center;font-family:var(--font-mono);font-size:0.88rem;font-weight:${isBottleneck ? '700' : '500'};color:${isBottleneck ? 'var(--danger)' : 'var(--text-primary)'};">${part.buildable}</td>
                        <td style="padding:5px 8px;">${orderCell}</td>
                    </tr>`;
                }).join('');

                const neededParts = parts.filter(p => p.buildable < effectiveTarget);
                const totalCost = neededParts.reduce((sum, p) => {
                    if (p.unit_cost > 0) sum += Math.max(0, effectiveTarget * p.quantity_required - p.current_stock) * p.unit_cost;
                    return sum;
                }, 0);
                const targetLabel = bottleneckTarget === 'max' ? 'max' : `${effectiveTarget} kits`;
                const costSummary = totalCost > 0 && neededParts.length > 0
                    ? `<span style="font-size:0.82rem;color:var(--text-secondary);margin-left:8px;">~$${totalCost.toFixed(2)} to stock to ${targetLabel}</span>` : '';
                const partsNeededLabel = neededParts.length > 0
                    ? `<span style="font-size:0.82rem;color:var(--warning);margin-left:4px;">${neededParts.length} part${neededParts.length !== 1 ? 's' : ''} to order</span>`
                    : `<span style="font-size:0.82rem;color:var(--success);margin-left:4px;">all stocked</span>`;

                return `
                <div style="border-bottom:1px solid var(--border-card);">
                    <div style="display:flex;align-items:center;gap:8px;padding:11px 16px;cursor:pointer;user-select:none;"
                         onclick="toggleBottleneckProject(${proj.project_id})">
                        <span id="bt-arrow-${proj.project_id}" style="font-size:0.85rem;color:var(--text-dim);flex-shrink:0;width:14px;">${isExpanded ? '▾' : '▸'}</span>
                        <span style="font-weight:700;font-size:0.95rem;color:var(--accent-primary);"
                              onclick="event.stopPropagation();viewProject(${proj.project_id})">${proj.project_name}</span>
                        <span style="background:${badgeColor};color:white;border-radius:10px;padding:2px 9px;font-size:0.77rem;font-family:var(--font-mono);white-space:nowrap;flex-shrink:0;">${b} buildable</span>
                        ${proj.retail_price > 0 ? `<span style="font-size:0.82rem;color:var(--text-dim);">$${parseFloat(proj.retail_price).toFixed(0)} retail</span>` : ''}
                        ${partsNeededLabel}${costSummary}
                    </div>
                    <div id="bt-body-${proj.project_id}" style="display:${isExpanded ? '' : 'none'};">
                        <div style="overflow-x:auto;padding:0 16px;">
                        <table style="width:100%;border-collapse:collapse;margin-bottom:14px;min-width:520px;">
                            <thead>
                                <tr style="border-bottom:1px solid var(--bg-light);">
                                    <th style="padding:3px 8px;text-align:left;font-size:0.72rem;color:var(--text-dim);font-weight:500;text-transform:uppercase;letter-spacing:.05em;">Part</th>
                                    <th style="padding:3px 8px;text-align:left;font-size:0.72rem;color:var(--text-dim);font-weight:500;text-transform:uppercase;letter-spacing:.05em;">Part #</th>
                                    <th style="padding:3px 8px;text-align:center;font-size:0.72rem;color:var(--text-dim);font-weight:500;text-transform:uppercase;letter-spacing:.05em;">Qty/Kit</th>
                                    <th style="padding:3px 8px;text-align:right;font-size:0.72rem;color:var(--text-dim);font-weight:500;text-transform:uppercase;letter-spacing:.05em;">In Stock</th>
                                    <th style="padding:3px 8px;font-size:0.72rem;color:var(--text-dim);font-weight:500;text-transform:uppercase;letter-spacing:.05em;min-width:150px;">Abundance <span style="font-weight:400;opacity:0.7;">(│= target)</span></th>
                                    <th style="padding:3px 8px;text-align:center;font-size:0.72rem;color:var(--text-dim);font-weight:500;text-transform:uppercase;letter-spacing:.05em;">Can Build</th>
                                    <th style="padding:3px 8px;text-align:left;font-size:0.72rem;color:var(--text-dim);font-weight:500;text-transform:uppercase;letter-spacing:.05em;">To Reach ${targetLabel}</th>
                                </tr>
                            </thead>
                            <tbody>${partsRows}</tbody>
                        </table>
                        </div>
                    </div>
                </div>`;
            }).join('');

            container.innerHTML = html;
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
                tbody.innerHTML = projects.map(p => {
                    const buildable = p.buildable_kits ?? 0;
                    const buildableClass = buildable === 0 ? 'stock-low' : 'stock-ok';
                    return `
                    <tr>
                        <td style="cursor:pointer;color:var(--accent-primary);font-weight:600;" onclick="viewProject(${p.id})">${p.project_name}</td>
                        <td>${p.description || '-'}</td>
                        <td><span class="badge badge-${p.status === 'active' ? 'success' : 'secondary'}">${p.status}</span></td>
                        <td>${p.parts_count || 0}</td>
                        <td class="${buildableClass}" style="font-family:var(--font-mono);">${buildable}</td>
                        <td>
                            <button class="btn btn-small" onclick="viewProject(${p.id})">View</button>
                            <button class="btn btn-small" onclick="editProject(${p.id})">Edit</button>
                            <button class="btn btn-small" onclick="copyProject(${p.id})" style="background:var(--bg-light);border-color:var(--border-card);">Copy</button>
                            ${p.woocommerce_product_id ? `<button class="btn btn-small" onclick="wcSyncProject(${p.id}, this)" style="background:var(--accent-secondary);color:white;border-color:var(--accent-secondary);">Sync WC</button>` : ''}
                            <button class="btn btn-small btn-danger" onclick="deleteProject(${p.id})">Trash</button>
                        </td>
                    </tr>
                `;
                }).join('');
            } catch (error) {
                console.error('Error loading projects:', error);
            }
        }

        // WooCommerce sync buttons — routed through api.php to avoid content blocker false positives
        const WC_WEBHOOK = 'api.php';

        function wcShowResult(html) {
            const el = document.getElementById('wcSyncResult');
            el.innerHTML = html;
            el.style.display = 'block';
        }

        async function wcSyncAll() {
            wcShowResult('<em>Syncing all projects to WooCommerce…</em>');
            try {
                const r = await fetch(`${WC_WEBHOOK}?action=wc_sync_all`);
                const data = await r.json();
                if (data.error) { wcShowResult(`<span style="color:var(--danger)">Error: ${data.error}</span>`); return; }
                const rows = (data.results || []).map(p => {
                    if (p.skipped) return `<tr><td style="padding:3px 8px;color:var(--text-secondary)">${p.project_id}</td><td colspan="2" style="padding:3px 8px;color:var(--text-secondary)">skipped — ${p.reason}</td></tr>`;
                    if (p.variable) {
                        const varLines = (p.variations || []).map(v => {
                            if (v.skipped) return `${v.combo}: skipped — ${v.reason}`;
                            if (!v.success) return `<span style="color:var(--danger)">${v.combo}: ✗ ${v.error}</span>`;
                            const wc = v.new_stock !== null && v.new_stock !== undefined ? ` (WC: ${v.new_stock})` : '';
                            return `<span style="color:var(--success)">${v.combo}: ✓ ${v.calculated_qty}${wc}</span>`;
                        }).join('<br>');
                        return `<tr><td style="padding:3px 8px;font-weight:600">${p.project_name}</td><td style="padding:3px 8px">variable</td><td style="padding:3px 8px">${varLines}</td></tr>`;
                    }
                    if (p.success) {
                        const wc = p.new_stock !== null && p.new_stock !== undefined ? ` (WC: ${p.new_stock})` : '';
                        return `<tr><td style="padding:3px 8px;font-weight:600">${p.project_name}</td><td style="padding:3px 8px;color:var(--success)">✓ synced</td><td style="padding:3px 8px;font-family:var(--font-mono)">${p.calculated_qty}${wc}</td></tr>`;
                    }
                    return `<tr><td style="padding:3px 8px;font-weight:600">${p.project_name}</td><td style="padding:3px 8px;color:var(--danger)">✗ error</td><td style="padding:3px 8px">${p.error || 'Unknown error'}</td></tr>`;
                }).join('');
                wcShowResult(`<strong>Sync All — ${data.synced} project(s) pushed</strong>
                    <table style="margin-top:8px;width:100%;border-collapse:collapse;font-size:0.85rem;">
                        <thead><tr style="color:var(--text-secondary);text-align:left;border-bottom:1px solid var(--border-card)">
                            <th style="padding:3px 8px">Project</th><th style="padding:3px 8px">Result</th><th style="padding:3px 8px">Detail</th>
                        </tr></thead>
                        <tbody>${rows}</tbody>
                    </table>`);
            } catch(e) {
                wcShowResult(`<span style="color:var(--danger)">Request failed: ${e.message}</span>`);
            }
        }

        async function wcCheckStatus() {
            wcShowResult('<em>Fetching WooCommerce stock status…</em>');
            try {
                const r = await fetch(`${WC_WEBHOOK}?action=wc_status`);
                const data = await r.json();
                if (!Array.isArray(data) || data.length === 0) {
                    wcShowResult('<span style="color:var(--text-secondary)">No projects are mapped to WooCommerce products yet.</span>');
                    return;
                }

                let anyMismatch = false;
                const rows = data.flatMap(p => {
                    if (p.variable) {
                        return (p.variations || []).map((v, i) => {
                            if (!v.match) anyMismatch = true;
                            const icon  = v.match ? '✓' : '⚠';
                            const color = v.match ? 'var(--success)' : 'var(--warning)';
                            const wcVal = v.wc_qty !== null && v.wc_qty !== undefined ? v.wc_qty : '?';
                            const qtyColor = v.tracker_qty > 0 ? 'var(--success)' : 'var(--danger)';
                            return `<tr style="${i === 0 ? 'border-top:1px solid var(--border-card)' : ''}">
                                <td style="padding:6px 8px;font-weight:600;${i > 0 ? 'color:transparent;font-size:0px;padding-top:0' : ''}">${i === 0 ? p.project_name : ''}</td>
                                <td style="padding:6px 8px;color:var(--text-secondary);font-size:11px;">${v.combo}</td>
                                <td style="padding:6px 8px;font-family:var(--font-mono);color:${qtyColor}">${v.tracker_qty}</td>
                                <td style="padding:6px 8px;font-family:var(--font-mono);color:var(--text-secondary)">${wcVal}</td>
                                <td style="padding:6px 8px;color:${color};font-weight:700">${icon}</td>
                                <td style="padding:6px 8px">${!v.match ? `<button class="btn btn-small" onclick="wcSyncProject(${p.project_id})" style="font-size:10px;">Sync</button>` : ''}</td>
                            </tr>`;
                        });
                    } else {
                        if (!p.match) anyMismatch = true;
                        const icon  = p.match ? '✓' : '⚠';
                        const color = p.match ? 'var(--success)' : 'var(--warning)';
                        const wcVal = p.wc_stock_qty !== null && p.wc_stock_qty !== undefined ? p.wc_stock_qty : '?';
                        const qtyColor = p.calculated_available_qty > 0 ? 'var(--success)' : 'var(--danger)';
                        return [`<tr style="border-top:1px solid var(--border-card)">
                            <td style="padding:6px 8px;font-weight:600">${p.project_name}</td>
                            <td style="padding:6px 8px;color:var(--text-secondary);font-size:11px;">—</td>
                            <td style="padding:6px 8px;font-family:var(--font-mono);color:${qtyColor}">${p.calculated_available_qty}</td>
                            <td style="padding:6px 8px;font-family:var(--font-mono);color:var(--text-secondary)">${wcVal}</td>
                            <td style="padding:6px 8px;color:${color};font-weight:700">${icon}</td>
                            <td style="padding:6px 8px">${!p.match ? `<button class="btn btn-small" onclick="wcSyncProject(${p.project_id})" style="font-size:10px;">Sync</button>` : ''}</td>
                        </tr>`];
                    }
                }).join('');

                wcShowResult(`<strong>WooCommerce Stock Status</strong>
                    <table style="margin-top:8px;width:100%;border-collapse:collapse;font-size:0.85rem;">
                        <thead>
                            <tr style="color:var(--text-secondary);text-align:left;border-bottom:2px solid var(--border-table-head);">
                                <th style="padding:4px 8px;font-size:10px;text-transform:uppercase;letter-spacing:0.8px;">Project</th>
                                <th style="padding:4px 8px;font-size:10px;text-transform:uppercase;letter-spacing:0.8px;">Variation</th>
                                <th style="padding:4px 8px;font-size:10px;text-transform:uppercase;letter-spacing:0.8px;">Tracker</th>
                                <th style="padding:4px 8px;font-size:10px;text-transform:uppercase;letter-spacing:0.8px;">WooCommerce</th>
                                <th style="padding:4px 8px;font-size:10px;text-transform:uppercase;letter-spacing:0.8px;">Sync</th>
                                <th style="padding:4px 8px;"></th>
                            </tr>
                        </thead>
                        <tbody>${rows}</tbody>
                    </table>
                    ${anyMismatch ? '<div style="padding:8px 12px;margin-top:4px;background:rgba(196,125,26,0.08);border-radius:4px;font-size:12px;color:var(--warning);">⚠ Some quantities are out of sync — use the Sync buttons above, or Sync All.</div>' : '<div style="padding:6px 0;font-size:12px;color:var(--success);">✓ All quantities match WooCommerce.</div>'}`);
            } catch(e) {
                wcShowResult(`<span style="color:var(--danger)">Request failed: ${e.message}</span>`);
            }
        }

        async function wcSyncProject(projectId, btn) {
            const origText  = btn ? btn.textContent : '';
            const origStyle = btn ? btn.getAttribute('style') : '';
            function resetBtn(text, style) {
                if (!btn) return;
                btn.disabled = false;
                btn.textContent = text;
                btn.setAttribute('style', style);
            }
            if (btn) { btn.disabled = true; btn.textContent = 'Syncing…'; }
            try {
                const r = await fetch(`${WC_WEBHOOK}?action=wc_sync&project_id=${projectId}`);
                const data = await r.json();

                if (!btn) return;
                btn.disabled = false;

                if (data.skipped) {
                    btn.textContent = '— Skipped';
                    setTimeout(() => resetBtn(origText, origStyle), 3000);
                    return;
                }

                const isError = data.variable
                    ? (data.variations || []).some(v => v.error)
                    : !data.success;

                if (isError) {
                    btn.textContent = '✗ Failed — see log';
                    btn.setAttribute('style', 'background:var(--danger);color:white;border-color:var(--danger);');
                    setTimeout(() => resetBtn(origText, origStyle), 5000);
                } else if (data.variable) {
                    btn.textContent = '✓ Synced';
                    btn.setAttribute('style', 'background:var(--success);color:white;border-color:var(--success);');
                    setTimeout(() => resetBtn(origText, origStyle), 4000);
                } else {
                    btn.textContent = `✓ Pushed ${data.calculated_qty}`;
                    btn.setAttribute('style', 'background:var(--success);color:white;border-color:var(--success);');
                    setTimeout(() => resetBtn(origText, origStyle), 4000);
                }
            } catch(e) {
                if (btn) {
                    btn.textContent = '✗ Error';
                    btn.setAttribute('style', 'background:var(--danger);color:white;border-color:var(--danger);');
                    setTimeout(() => resetBtn(origText, origStyle), 4000);
                }
            }
        }

        async function wcViewLog() {
            const modal = createModal('WooCommerce Sync Log', '<div style="text-align:center;padding:2rem;color:var(--text-dim);">Loading…</div>', null, true);
            try {
                const r = await fetch('api.php?action=wc_sync_log');
                const data = await r.json();
                const entries = data.entries || [];
                if (!entries.length) {
                    modal.querySelector('.modal-content').innerHTML += '';
                    modal.querySelector('div[style*="Loading"]').textContent = 'No sync log entries yet.';
                    return;
                }
                const rows = entries.map(e => {
                    if (!e) return '';
                    const isError = e.level === 'error';
                    const ctx = e.context || {};
                    let detail = '';
                    if (ctx.variations) {
                        detail = ctx.variations.map(v => {
                            const ok = v.success;
                            const color = ok ? 'var(--success)' : 'var(--danger)';
                            const info = ok ? `qty ${v.qty}` : (v.error || '?');
                            const extra = !ok && (v.http_code || v.wc_code || v.raw_body)
                                ? ` <span style="color:var(--text-dim);font-size:0.8em;">[HTTP ${v.http_code || '?'}${v.wc_code ? ' · ' + v.wc_code : ''}]</span>` : '';
                            const raw = !ok && v.raw_body
                                ? `<pre style="margin:2px 0 0;font-size:0.72rem;background:#f8f8f8;padding:4px;border-radius:2px;overflow-x:auto;white-space:pre-wrap;word-break:break-all;max-height:80px;">${v.raw_body.substring(0, 400)}</pre>` : '';
                            return `<div style="color:${color};margin-bottom:2px;">${escHtml(v.combo || '')}: ${escHtml(info)}${extra}${raw}</div>`;
                        }).join('');
                    } else {
                        const ok = ctx.success;
                        const info = ok ? `qty ${ctx.calculated_qty}` : (ctx.error || '?');
                        const extra = !ok && (ctx.http_code || ctx.wc_code)
                            ? ` <span style="color:var(--text-dim);font-size:0.8em;">[HTTP ${ctx.http_code || '?'}${ctx.wc_code ? ' · ' + ctx.wc_code : ''}]</span>` : '';
                        const raw = !ok && ctx.raw_body
                            ? `<pre style="margin:2px 0 0;font-size:0.72rem;background:#f8f8f8;padding:4px;border-radius:2px;overflow-x:auto;white-space:pre-wrap;word-break:break-all;max-height:80px;">${ctx.raw_body.substring(0, 400)}</pre>` : '';
                        detail = `<span style="color:${ok ? 'var(--success)' : 'var(--danger)'};">${escHtml(info)}${extra}</span>${raw}`;
                    }
                    return `<tr style="vertical-align:top;border-bottom:1px solid var(--border-card);">
                        <td style="padding:6px 8px;white-space:nowrap;color:var(--text-dim);font-size:0.8rem;font-family:var(--font-mono);">${escHtml(e.time)}</td>
                        <td style="padding:6px 8px;font-weight:600;${isError ? 'color:var(--danger)' : ''}">${escHtml(e.message)}</td>
                        <td style="padding:6px 8px;font-size:0.85rem;">${detail}</td>
                    </tr>`;
                }).join('');
                const body = modal.querySelector('.modal-content');
                body.innerHTML = `
                    <div style="display:flex;justify-content:flex-end;padding:8px 0 4px;gap:8px;">
                        <button class="btn btn-small btn-danger" onclick="wcClearLog(this)">Clear Log</button>
                    </div>
                    <div style="overflow-x:auto;">
                        <table style="width:100%;border-collapse:collapse;font-size:0.85rem;">
                            <thead><tr style="text-align:left;border-bottom:2px solid var(--border-table-head);">
                                <th style="padding:4px 8px;">Time</th>
                                <th style="padding:4px 8px;">Event</th>
                                <th style="padding:4px 8px;">Detail</th>
                            </tr></thead>
                            <tbody>${rows}</tbody>
                        </table>
                    </div>
                    <div style="padding:8px 0 4px;">
                        <button class="btn" onclick="this.closest('.modal').remove()">Close</button>
                    </div>`;
            } catch(e) {
                modal.querySelector('.modal-content').innerHTML = `<p style="color:var(--danger)">Failed to load log: ${e.message}</p><button class="btn" onclick="this.closest('.modal').remove()">Close</button>`;
            }
        }

        async function wcClearLog(btn) {
            if (!confirm('Clear the entire sync log?')) return;
            await fetch('api.php?action=wc_sync_log_clear');
            btn.closest('.modal').remove();
        }

        // Parts
        let allPartsCache = [];
        let partsProjectPartIds = null; // Set of part IDs for the selected project filter, or null for all

        async function loadParts() {
            try {
                const response = await fetch('api.php?action=get_parts');
                allPartsCache = await response.json();
                parts = allPartsCache;

                // Populate the project dropdown (fetch projects if not yet loaded)
                const sel = document.getElementById('partsProjectFilter');
                if (sel && !sel.dataset.populated) {
                    sel.dataset.populated = '1';
                    const pResp = await fetch('api.php?action=get_projects');
                    const pList = await pResp.json();
                    pList.forEach(p => {
                        const opt = document.createElement('option');
                        opt.value = p.id;
                        opt.textContent = p.project_name;
                        sel.appendChild(opt);
                    });
                }

                const currentSearch = document.getElementById('partsSearchInput')?.value || '';
                renderPartsTable(currentSearch);
            } catch (error) {
                console.error('Error loading parts:', error);
            }
        }

        async function onPartsProjectFilterChange() {
            const projectId = document.getElementById('partsProjectFilter').value;
            if (!projectId) {
                partsProjectPartIds = null;
            } else if (projectId === '__unassigned__') {
                const resp = await fetch('api.php?action=get_unassigned_part_ids');
                const ids = await resp.json();
                partsProjectPartIds = new Set(ids);
            } else {
                const resp = await fetch(`api.php?action=get_project&id=${projectId}`);
                const project = await resp.json();
                partsProjectPartIds = new Set((project.parts || []).map(p => parseInt(p.part_id)));
            }
            renderPartsTable(document.getElementById('partsSearchInput')?.value || '');
        }

        function filterPartsTable(query) {
            renderPartsTable(query);
        }

        function renderPartsTable(searchQuery) {
            let filtered = allPartsCache;

            if (partsProjectPartIds !== null) {
                filtered = filtered.filter(p => partsProjectPartIds.has(parseInt(p.id)));
            }

            if (searchQuery && searchQuery.trim()) {
                const q = searchQuery.trim().toLowerCase();
                filtered = filtered.filter(p =>
                    (p.part_number || '').toLowerCase().includes(q) ||
                    (p.part_name || '').toLowerCase().includes(q) ||
                    (p.category || '').toLowerCase().includes(q)
                );
            }
            filtered = sortData(filtered, sortState.parts.column, sortState.parts.direction);

            const tbody = document.querySelector('#partsTable tbody');
            tbody.innerHTML = filtered.map(p => {
                const stockClass = p.current_stock <= p.min_stock_level ? 'stock-low' : 'stock-ok';
                return `
                    <tr>
                        <td><strong>${p.part_number}</strong></td>
                        <td style="cursor:pointer;color:var(--accent-primary);" onclick="viewPart(${p.id})">${p.part_name}</td>
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
                        <td><a href="order_detail.php?id=${o.id}" style="color:var(--accent-primary);font-weight:bold;text-decoration:none;">${o.order_number}</a></td>
                        <td>${o.order_date}</td>
                        <td>${o.customer_name}</td>
                        <td>${o.customer_callsign || '-'}</td>
                        <td>${o.project_name}</td>
                        <td>${o.quantity}</td>
                        <td>$${parseFloat(o.price_paid).toFixed(2)}</td>
                        <td><span class="badge badge-${getStatusColor(o.status)}">${o.status}</span></td>
                        <td>
                            <a href="order_detail.php?id=${o.id}" class="btn btn-small">Open</a>
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
                            <label class="form-label">Packed Ship Weight (oz)</label>
                            <input type="number" id="projectShipWeight" class="form-input" value="${project.ship_weight_oz || ''}" step="0.1" min="0" placeholder="e.g. 8.5">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Package Dimensions (inches) — L × W × H</label>
                            <div class="grid-2" style="grid-template-columns: 1fr 1fr 1fr; gap: 0.5rem;">
                                <input type="number" id="projectPkgLength" class="form-input" value="${project.pkg_length || ''}" step="0.1" min="0" placeholder="Length">
                                <input type="number" id="projectPkgWidth"  class="form-input" value="${project.pkg_width  || ''}" step="0.1" min="0" placeholder="Width">
                                <input type="number" id="projectPkgHeight" class="form-input" value="${project.pkg_height || ''}" step="0.1" min="0" placeholder="Height">
                            </div>
                            <small style="color: var(--text-secondary); font-size: 0.875rem;">Used for shipping calculations</small>
                        </div>
                        <div class="form-group">
                            <label class="form-label">WooCommerce Product ID</label>
                            <input type="number" id="projectWooId" class="form-input" value="${project.woocommerce_product_id || ''}" placeholder="Leave blank if not linked to WooCommerce" min="1" style="font-family:var(--font-mono);">
                            <small style="color:var(--text-secondary);font-size:0.875rem;">The numeric product ID from your WooCommerce store — needed for inventory sync.</small>
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
                formData.append('ship_weight_oz', document.getElementById('projectShipWeight').value);
                formData.append('pkg_length',     document.getElementById('projectPkgLength').value);
                formData.append('pkg_width',       document.getElementById('projectPkgWidth').value);
                formData.append('pkg_height',      document.getElementById('projectPkgHeight').value);
                formData.append('woocommerce_product_id', document.getElementById('projectWooId').value);

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

                // Store project data globally for export function and BOM rendering
                window.currentProjectData = project;
                // Reset BOM state each time a project is opened
                bomSortState = { column: 'part_number', direction: 'asc' };
                bomSearchQuery = '';
                bomDragMode = false;

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
                                <strong>Description:</strong> ${project.description || 'N/A'}<br>
                                ${project.woocommerce_product_id ? `<strong>WooCommerce ID:</strong> <span style="font-family:var(--font-mono);">${project.woocommerce_product_id}</span>` : '<strong>WooCommerce:</strong> <span style="color:var(--text-dim);">Not linked</span>'}
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

                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                            <h4>Bill of Materials</h4>
                            <div style="display: flex; gap: 0.5rem; align-items: center;">
                                <input type="text" id="bomSearchInput" class="form-input" placeholder="Search BOM..." style="width: 200px;" oninput="bomSearchQuery = this.value; renderBOMTable();">
                                <button class="btn btn-small" id="bomReorderBtn" onclick="toggleBOMDragMode()" style="background: var(--bg-light); border-color: var(--border-card);">&#8801; Reorder</button>
                                <button class="btn btn-small" onclick="exportBOM()" style="background: var(--success); color: white; border-color: var(--success);">&#128229; Export BOM</button>
                                <button class="btn btn-primary btn-small" onclick="addFixedPartToProject(${project.id})">+ Fixed Part</button>
                                <button class="btn btn-small" style="background: var(--info); color: white; border-color: var(--info);" onclick="addVariablePartToProject(${project.id})">+ Variable Part</button>
                            </div>
                        </div>

                        <div id="bom-table-container"></div>

                        <div id="variations-panel-container"></div>

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

                // Now render the BOM table and load the variations panel
                renderBOMTable();
                renderVariationsPanel(project.id);
            } catch (error) {
                alert('Error loading project details');
            }
        }

        function renderBOMTable() {
            const container = document.getElementById('bom-table-container');
            if (!container) return;
            const project = window.currentProjectData;
            if (!project || !project.parts || project.parts.length === 0) {
                container.innerHTML = '<p style="color: var(--text-dim);">No parts assigned to this project yet.</p>';
                return;
            }

            // In drag mode: show all parts in saved order, no search/sort
            let filtered = project.parts;
            if (!bomDragMode) {
                if (bomSearchQuery && bomSearchQuery.trim()) {
                    const q = bomSearchQuery.trim().toLowerCase();
                    filtered = project.parts.filter(p =>
                        (p.part_number || '').toLowerCase().includes(q) ||
                        (p.part_name || '').toLowerCase().includes(q) ||
                        (p.category || '').toLowerCase().includes(q)
                    );
                }
                filtered = sortData(filtered, bomSortState.column, bomSortState.direction);
            }

            const arrow = (col) => (!bomDragMode && bomSortState.column === col) ? (bomSortState.direction === 'asc' ? ' &#9650;' : ' &#9660;') : '';
            const thStyle = bomDragMode ? '' : 'cursor: pointer; user-select: none;';
            const thClick = (col) => bomDragMode ? '' : `onclick="sortBOM('${col}')" title="Sort"`;

            const rows = filtered.map((p, i) => {
                const isVariable = p.variation_attribute && p.variation_attribute !== '';
                const variationLabel = isVariable
                    ? `<span style="font-size:0.78em;color:var(--accent-secondary);font-weight:600;background:rgba(26,86,219,0.09);padding:2px 7px;border-radius:3px;">${p.variation_attribute}: ${p.variation_value}</span>`
                    : `<span style="font-size:0.78em;color:var(--text-dim);">Fixed</span>`;

                const dragAttrs = bomDragMode
                    ? `draggable="true" ondragstart="bomDragSrcIdx=${i}" ondragover="event.preventDefault();this.style.outline='2px solid var(--accent-primary)'" ondragleave="this.style.outline=''" ondrop="event.preventDefault();this.style.outline='';bomDropRow(${i})"`
                    : '';
                const dragHandle = bomDragMode
                    ? `<td style="cursor:grab;text-align:center;color:var(--text-dim);font-size:1.1em;padding:0 6px;">&#8942;&#8942;</td>`
                    : '';

                return `
                <tr ${dragAttrs}>
                    ${dragHandle}
                    <td>${p.part_number}</td>
                    <td>${p.part_name}</td>
                    <td>${p.category || '-'}</td>
                    <td>${variationLabel}</td>
                    <td>${p.quantity_required}</td>
                    <td>$${parseFloat(p.unit_cost || 0).toFixed(2)}</td>
                    <td>$${parseFloat(p.line_total || 0).toFixed(2)}</td>
                    <td class="${p.current_stock >= p.quantity_required ? 'stock-ok' : 'stock-low'}">${p.current_stock}</td>
                    <td>
                        <button class="btn btn-small" onclick="viewPart(${p.part_id})">View</button>
                        <button class="btn btn-small" onclick="editProjectPart(this)"
                            data-project-id="${project.id}"
                            data-part-id="${p.id}"
                            data-qty="${p.quantity_required}"
                            data-attr="${(p.variation_attribute||'').replace(/"/g,'&quot;')}"
                            data-val="${(p.variation_value||'').replace(/"/g,'&quot;')}"
                            data-is-variable="${isVariable ? '1' : '0'}">Edit</button>
                        <button class="btn btn-small btn-danger" onclick="removeProjectPart(${p.id}, ${project.id})">Remove</button>
                    </td>
                </tr>`;
            }).join('');

            const totalRow = (bomDragMode || bomSearchQuery.trim())
                ? ''
                : `<tr style="font-weight: bold; background: var(--bg-light);">
                       <td colspan="6" style="text-align: right;">Fixed Parts BOM Cost:</td>
                       <td colspan="3">$${parseFloat(project.total_bom_cost || 0).toFixed(2)}</td>
                   </tr>`;

            const colSpanEmpty = bomDragMode ? 10 : 9;
            const dragHandleTh = bomDragMode ? '<th style="width:28px;"></th>' : '';

            container.innerHTML = `
                <table class="data-table">
                    <thead>
                        <tr>
                            ${dragHandleTh}
                            <th style="${thStyle}" ${thClick('part_number')}>Part Number${arrow('part_number')}</th>
                            <th style="${thStyle}" ${thClick('part_name')}>Part Name${arrow('part_name')}</th>
                            <th style="${thStyle}" ${thClick('category')}>Category${arrow('category')}</th>
                            <th>Variation</th>
                            <th style="${thStyle}" ${thClick('quantity_required')}>Qty Req'd${arrow('quantity_required')}</th>
                            <th style="${thStyle}" ${thClick('unit_cost')}>Unit Cost${arrow('unit_cost')}</th>
                            <th style="${thStyle}" ${thClick('line_total')}>Line Total${arrow('line_total')}</th>
                            <th style="${thStyle}" ${thClick('current_stock')}>In Stock${arrow('current_stock')}</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${filtered.length > 0 ? rows : `<tr><td colspan="${colSpanEmpty}" style="color: var(--text-dim); text-align: center;">No parts match your search.</td></tr>`}
                        ${totalRow}
                    </tbody>
                </table>
            `;
        }

        function sortBOM(column) {
            if (bomDragMode) return;
            if (bomSortState.column === column) {
                bomSortState.direction = bomSortState.direction === 'asc' ? 'desc' : 'asc';
            } else {
                bomSortState.column = column;
                bomSortState.direction = 'asc';
            }
            renderBOMTable();
        }

        function toggleBOMDragMode() {
            bomDragMode = !bomDragMode;
            if (bomDragMode) {
                // Clear search so all parts are visible for reordering
                bomSearchQuery = '';
                const searchInput = document.getElementById('bomSearchInput');
                if (searchInput) searchInput.value = '';
            }
            const btn = document.getElementById('bomReorderBtn');
            if (btn) {
                if (bomDragMode) {
                    btn.textContent = '✓ Done Reordering';
                    btn.style.background = 'var(--warning)';
                    btn.style.color = 'white';
                    btn.style.borderColor = 'var(--warning)';
                } else {
                    btn.textContent = '≡ Reorder';
                    btn.style.background = 'var(--bg-light)';
                    btn.style.color = '';
                    btn.style.borderColor = 'var(--border-card)';
                }
            }
            renderBOMTable();
        }

        function bomDropRow(targetIdx) {
            if (bomDragSrcIdx === null || bomDragSrcIdx === targetIdx) { bomDragSrcIdx = null; return; }
            const parts = window.currentProjectData.parts;
            const dragged = parts.splice(bomDragSrcIdx, 1)[0];
            parts.splice(targetIdx, 0, dragged);
            bomDragSrcIdx = null;
            renderBOMTable();
            // Persist the new order
            const items = parts.map((p, i) => ({ id: p.id, sort_order: i + 1 }));
            fetch('api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=reorder_bom&items=' + encodeURIComponent(JSON.stringify(items))
            });
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

        function addFixedPartToProject(projectId) {
            if (parts.length === 0) {
                loadParts().then(() => openAddFixedPartModal(projectId));
            } else {
                openAddFixedPartModal(projectId);
            }
        }

        function addVariablePartToProject(projectId) {
            if (parts.length === 0) {
                loadParts().then(() => openAddVariablePartModal(projectId));
            } else {
                openAddVariablePartModal(projectId);
            }
        }

        function openAddFixedPartModal(projectId) {
            // Exclude parts already in the BOM as fixed (variation_attribute='') — same part can still be added as a variable part
            const fixedPartIds = new Set(
                (window.currentProjectData?.parts || [])
                    .filter(p => !p.variation_attribute)
                    .map(p => p.part_id)
            );
            const availableParts = parts.filter(p => !fixedPartIds.has(p.id));

            const modal = createModal(
                'Add Fixed Part to BOM',
                `
                    <p style="color: var(--text-secondary); margin-bottom: 1rem; font-size: 0.9em;">Fixed parts are shared across all product variations (or the whole product if there are no variations).</p>
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
                            <button type="submit" class="btn btn-primary">Add Fixed Part</button>
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
                // variation_attribute and variation_value default to '' (fixed part)

                try {
                    await fetch('api.php', { method: 'POST', body: formData });
                    modal.remove();
                    document.querySelector('.modal.active')?.remove();
                    viewProject(projectId);
                } catch (error) {
                    alert('Error adding part to project');
                }
            });
        }

        function openAddVariablePartModal(projectId) {
            // Collect existing attribute names for the datalist suggestion
            const existingAttrs = [...new Set(
                (window.currentProjectData?.parts || [])
                    .filter(p => p.variation_attribute)
                    .map(p => p.variation_attribute)
            )];

            const modal = createModal(
                'Add Variable Part to BOM',
                `
                    <p style="color: var(--text-secondary); margin-bottom: 1rem; font-size: 0.9em;">
                        Variable parts differ per product variation. Each attribute name (e.g. "Connector") can have multiple options — one part per option value (e.g. "Male", "Female").
                    </p>
                    <form id="addVarPartForm">
                        <div class="form-group">
                            <label class="form-label">Select Part</label>
                            <select id="varPartSelect" class="form-select" required>
                                <option value="">Choose a part...</option>
                                ${parts.map(p => `<option value="${p.id}">${p.part_number} - ${p.part_name}</option>`).join('')}
                            </select>
                        </div>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;">
                            <div class="form-group">
                                <label class="form-label">Attribute Name</label>
                                <input type="text" id="varAttrName" class="form-input" placeholder="e.g. Connector" list="attrNameList" required>
                                <datalist id="attrNameList">
                                    ${existingAttrs.map(a => `<option value="${a}">`).join('')}
                                </datalist>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Attribute Value</label>
                                <input type="text" id="varAttrValue" class="form-input" placeholder="e.g. Male pigtail" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Quantity Required per Kit</label>
                            <input type="number" id="varPartQty" class="form-input" min="1" value="1" required>
                        </div>
                        <div class="flex flex-gap">
                            <button type="submit" class="btn btn-primary">Add Variable Part</button>
                            <button type="button" class="btn" onclick="this.closest('.modal').remove()">Cancel</button>
                        </div>
                    </form>
                `
            );

            document.getElementById('addVarPartForm').addEventListener('submit', async (e) => {
                e.preventDefault();
                const formData = new FormData();
                formData.append('action', 'add_project_part');
                formData.append('project_id', projectId);
                formData.append('part_id', document.getElementById('varPartSelect').value);
                formData.append('quantity_required', document.getElementById('varPartQty').value);
                formData.append('variation_attribute', document.getElementById('varAttrName').value.trim());
                formData.append('variation_value', document.getElementById('varAttrValue').value.trim());

                try {
                    await fetch('api.php', { method: 'POST', body: formData });
                    modal.remove();
                    document.querySelector('.modal.active')?.remove();
                    viewProject(projectId);
                } catch (error) {
                    alert('Error adding variable part');
                }
            });
        }

        function editProjectPart(btn) {
            const projectId   = btn.dataset.projectId;
            const partId      = btn.dataset.partId;
            const currentQty  = btn.dataset.qty;
            const isVariable  = btn.dataset.isVariable === '1';
            const currentAttr = btn.dataset.attr || '';
            const currentVal  = btn.dataset.val  || '';

            const variationFields = isVariable ? `
                <div class="form-group">
                    <label class="form-label">Variation Attribute <span style="color:var(--text-dim);font-weight:400;">(e.g. "Connector")</span></label>
                    <input type="text" id="editPartAttr" class="form-input" value="${currentAttr.replace(/"/g,'&quot;')}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Variation Value <span style="color:var(--text-dim);font-weight:400;">(e.g. "Male")</span></label>
                    <input type="text" id="editPartVal" class="form-input" value="${currentVal.replace(/"/g,'&quot;')}" required>
                </div>` : '';

            const modal = createModal(
                isVariable ? 'Edit Variable Part' : 'Edit Part Quantity',
                `<form id="editPartQtyForm">
                    ${variationFields}
                    <div class="form-group">
                        <label class="form-label">Quantity Required per Kit</label>
                        <input type="number" id="editPartQty" class="form-input" min="1" value="${currentQty}" required>
                    </div>
                    <div class="flex flex-gap">
                        <button type="submit" class="btn btn-primary">Update</button>
                        <button type="button" class="btn" onclick="this.closest('.modal').remove()">Cancel</button>
                    </div>
                </form>`
            );

            document.getElementById('editPartQtyForm').addEventListener('submit', async (e) => {
                e.preventDefault();
                const formData = new FormData();
                formData.append('action', 'update_project_part');
                formData.append('id', partId);
                formData.append('quantity_required', document.getElementById('editPartQty').value);
                if (isVariable) {
                    formData.append('variation_attribute', document.getElementById('editPartAttr').value.trim());
                    formData.append('variation_value', document.getElementById('editPartVal').value.trim());
                }
                try {
                    await fetch('api.php', { method: 'POST', body: formData });
                    modal.remove();
                    viewProject(projectId);
                } catch (error) {
                    alert('Error updating part');
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

        async function renderVariationsPanel(projectId) {
            const container = document.getElementById('variations-panel-container');
            if (!container) return;

            try {
                const resp = await fetch(`api.php?action=get_project_variations&project_id=${projectId}`);
                const data = await resp.json();

                if (!data.has_variations) {
                    container.innerHTML = '';
                    return;
                }

                const attrSummary = Object.entries(data.attributes)
                    .map(([attr, vals]) => `<strong>${attr}</strong> (${vals.length} option${vals.length > 1 ? 's' : ''})`)
                    .join(', ');

                const rows = data.combos.map(c => {
                    const label = Object.entries(c.combo).map(([a, v]) => `${a}: ${v}`).join(' + ');
                    const stock = c.buildable;
                    const stockColor = stock > 0 ? 'var(--success)' : 'var(--danger)';
                    const wcId = c.wc_variation_id ?? '';
                    return `
                        <tr>
                            <td>${label}</td>
                            <td style="font-family: var(--font-mono); color: ${stockColor}; font-weight: 600;">${stock}</td>
                            <td>
                                <div style="display:flex;gap:0.5rem;align-items:center;">
                                    <input type="number" class="form-input" style="width:130px;font-family:var(--font-mono);"
                                        placeholder="WC variation ID"
                                        id="wcvar_${c.combo_key.replace(/[^a-z0-9]/gi,'_')}"
                                        value="${wcId}">
                                    <button class="btn btn-small btn-primary"
                                        onclick="saveVariationMapping(${projectId}, '${c.combo_key.replace(/'/g, "\\'")}', 'wcvar_${c.combo_key.replace(/[^a-z0-9]/gi,'_')}')">
                                        Save
                                    </button>
                                </div>
                            </td>
                        </tr>`;
                }).join('');

                container.innerHTML = `
                    <hr style="margin: 1.5rem 0; border-color: var(--border-color);">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.75rem;">
                        <h4>Variations</h4>
                        <button class="btn btn-small" onclick="renderVariationsPanel(${projectId})">&#8635; Refresh</button>
                    </div>
                    <p style="color:var(--text-secondary);font-size:0.88em;margin-bottom:0.75rem;">
                        ${attrSummary} &mdash; ${data.combos.length} combination${data.combos.length > 1 ? 's' : ''} generated.
                        Enter the WooCommerce variation ID for each combination to enable stock sync.
                    </p>
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Combination</th>
                                    <th>Buildable</th>
                                    <th>WooCommerce Variation ID</th>
                                </tr>
                            </thead>
                            <tbody>${rows}</tbody>
                        </table>
                    </div>
                `;
            } catch (err) {
                container.innerHTML = '';
            }
        }

        async function saveVariationMapping(projectId, comboKey, inputId) {
            const val = document.getElementById(inputId)?.value ?? '';
            const formData = new FormData();
            formData.append('action', 'save_variation_mapping');
            formData.append('project_id', projectId);
            formData.append('combo_key', comboKey);
            formData.append('wc_variation_id', val);

            try {
                const resp = await fetch('api.php', { method: 'POST', body: formData });
                const data = await resp.json();
                if (data.success) {
                    const btn = document.querySelector(`[onclick*="${inputId}"]`);
                    if (btn) {
                        const orig = btn.textContent;
                        btn.textContent = '✓ Saved';
                        btn.style.background = 'var(--success)';
                        setTimeout(() => { btn.textContent = orig; btn.style.background = ''; }, 1500);
                    }
                }
            } catch (err) {
                alert('Error saving variation mapping');
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

        async function copyProject(id) {
            const proj = projects.find(p => p.id == id);
            const name = proj ? proj.project_name : 'this project';
            if (!confirm(`Duplicate "${name}"?\n\nA copy will be created with the same BOM. WooCommerce ID, orders, and expenses are not copied.`)) return;
            const formData = new FormData();
            formData.append('action', 'copy_project');
            formData.append('id', id);
            try {
                const r = await fetch('api.php', { method: 'POST', body: formData });
                const data = await r.json();
                if (!data.success) { alert('Error copying project'); return; }
                loadProjects();
            } catch (error) {
                alert('Error copying project');
            }
        }

        async function deleteProject(id) {
            const proj = projects.find(p => p.id == id);
            const name = proj ? proj.project_name : 'this project';
            if (!confirm(`Move "${name}" to trash?\n\nAll project data and BOM will be preserved — you can restore it from the trash bin.`)) return;
            const formData = new FormData();
            formData.append('action', 'delete_project');
            formData.append('id', id);
            try {
                const r = await fetch('api.php', { method: 'POST', body: formData });
                const data = await r.json();
                if (!data.success) { alert('Error moving project to trash'); return; }
                loadProjects();
                loadTrashedProjects();
            } catch (error) {
                alert('Error moving project to trash');
            }
        }

        async function restoreProject(id, btn) {
            const name = btn.closest('tr').querySelector('td').textContent.trim();
            if (!confirm(`Restore "${name}" to active projects?`)) return;
            const formData = new FormData();
            formData.append('action', 'restore_project');
            formData.append('id', id);
            try {
                const r = await fetch('api.php', { method: 'POST', body: formData });
                const data = await r.json();
                if (!data.success) { alert('Error restoring project'); return; }
                loadProjects();
                loadTrashedProjects();
            } catch (error) {
                alert('Error restoring project');
            }
        }

        let trashedVisible = false;
        function toggleTrashedProjects() {
            trashedVisible = !trashedVisible;
            document.getElementById('trashedProjectsList').style.display = trashedVisible ? 'block' : 'none';
            document.getElementById('trashedToggleLabel').textContent = trashedVisible ? 'Hide' : 'Show';
        }

        async function loadTrashedProjects() {
            try {
                const r = await fetch('api.php?action=get_trashed_projects');
                const data = await r.json();
                const card = document.getElementById('trashedProjectsCard');
                if (!data.length) { card.style.display = 'none'; return; }
                card.style.display = 'block';
                document.getElementById('trashedCount').textContent = `(${data.length})`;
                document.getElementById('trashedProjectsList').innerHTML = `
                    <table class="data-table" style="margin:0;">
                        <thead><tr>
                            <th>Project Name</th><th>Description</th><th></th>
                        </tr></thead>
                        <tbody>${data.map(p => `
                            <tr>
                                <td style="color:var(--text-secondary);font-style:italic;">${p.project_name}</td>
                                <td style="color:var(--text-dim)">${p.description || '—'}</td>
                                <td><button class="btn btn-small" onclick="restoreProject(${p.id}, this)">Restore</button></td>
                            </tr>`).join('')}
                        </tbody>
                    </table>`;
            } catch(e) { /* silent */ }
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
            const part = isEdit ? parts.find(p => Number(p.id) === Number(partId)) : {};

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
                                                <button class="btn btn-small" onclick="cloneCheckin(${c.id}, ${part.id})">Clone</button>
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
                    await loadParts();
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
                    
                    const unitCost = document.getElementById('editCheckinUnitCost').value;
                    const grossTotal = document.getElementById('editCheckinGrossTotal').value;
                    if (!unitCost && !grossTotal) {
                        alert('Please enter either Unit Cost or Gross Total');
                        return;
                    }

                    const formData = new FormData();
                    formData.append('action', 'edit_checkin');
                    formData.append('id', checkinId);
                    formData.append('part_id', partId);
                    formData.append('quantity', document.getElementById('editCheckinQty').value);
                    if (unitCost) formData.append('unit_cost', unitCost);
                    if (grossTotal) formData.append('gross_total', grossTotal);
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
            const checkinTitle = part ? `Check-in Inventory: ${part.part_name}` : 'Check-in Inventory';

            const modal = createModal(
                checkinTitle,
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
                            <strong><span id="calcCostValue"></span></strong>
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
                    const perUnit = grossTotal / qty;
                    calcValue.textContent = 'Calculated unit cost: $' + perUnit.toFixed(4) + ' per unit';
                    calcDisplay.style.display = 'block';
                } else if (qty > 0 && unitCost > 0) {
                    const total = qty * unitCost;
                    calcValue.textContent = 'Calculated gross total: $' + total.toFixed(2);
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
                    viewPart(partId);
                    if (window.currentProjectData) {
                        fetch(`api.php?action=get_project&id=${window.currentProjectData.id}`)
                            .then(r => r.json())
                            .then(project => { window.currentProjectData = project; renderBOMTable(); });
                    }
                } catch (error) {
                    alert('Error checking in inventory');
                }
            });
        }

        async function cloneCheckin(checkinId, partId) {
            try {
                const response = await fetch(`api.php?action=get_part&id=${partId}`);
                const part = await response.json();
                const checkin = part.checkins.find(c => c.id === checkinId);
                if (!checkin) { alert('Could not load check-in data'); return; }

                const modal = createModal(
                    `Clone Check-in: ${part.part_name}`,
                    `
                        <p style="color: var(--text-secondary); margin-bottom: 1rem;">
                            Cloned from ${checkin.purchase_date} — edit any fields before saving.
                        </p>
                        <form id="cloneCheckinForm">
                            <div class="form-group">
                                <label class="form-label">Quantity Received</label>
                                <input type="number" id="cloneCheckinQty" class="form-input" min="1" value="${checkin.quantity}" required>
                            </div>
                            <div style="background: #f0f9ff; padding: 1rem; border-radius: 4px; margin-bottom: 1rem;">
                                <strong style="color: var(--accent-primary);">💡 Enter EITHER unit cost OR gross total:</strong>
                            </div>
                            <div class="grid-2">
                                <div class="form-group">
                                    <label class="form-label">Unit Cost ($)</label>
                                    <input type="number" id="cloneCheckinUnitCost" class="form-input" step="0.0001" min="0" value="${parseFloat(checkin.unit_cost).toFixed(4)}">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Gross Total ($)</label>
                                    <input type="number" id="cloneCheckinGrossTotal" class="form-input" step="0.01" min="0" value="${parseFloat(checkin.total_cost).toFixed(2)}">
                                </div>
                            </div>
                            <div id="cloneCalcDisplay" style="background: #ecfdf5; padding: 0.75rem; border-radius: 4px; margin-bottom: 1rem;">
                                <strong>Unit Cost: <span id="cloneCalcValue">$${parseFloat(checkin.unit_cost).toFixed(4)}</span></strong>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Supplier Name</label>
                                <input type="text" id="cloneCheckinSupplier" class="form-input" value="${(checkin.supplier_name || '').replace(/"/g, '&quot;')}">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Purchase Date</label>
                                <input type="date" id="cloneCheckinDate" class="form-input" value="${new Date().toISOString().split('T')[0]}" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Notes</label>
                                <textarea id="cloneCheckinNotes" class="form-textarea">${checkin.notes || ''}</textarea>
                            </div>
                            <div class="flex flex-gap">
                                <button type="submit" class="btn btn-primary">Check-in</button>
                                <button type="button" class="btn" onclick="this.closest('.modal').remove()">Cancel</button>
                            </div>
                        </form>
                    `
                );

                const qtyInput = document.getElementById('cloneCheckinQty');
                const unitCostInput = document.getElementById('cloneCheckinUnitCost');
                const grossTotalInput = document.getElementById('cloneCheckinGrossTotal');
                const calcDisplay = document.getElementById('cloneCalcDisplay');
                const calcValue = document.getElementById('cloneCalcValue');

                function updateCalc() {
                    const qty = parseFloat(qtyInput.value) || 0;
                    const gross = parseFloat(grossTotalInput.value) || 0;
                    const unit = parseFloat(unitCostInput.value) || 0;
                    if (qty > 0 && gross > 0) {
                        calcValue.textContent = '$' + (gross / qty).toFixed(4);
                        calcDisplay.style.display = 'block';
                    } else if (unit > 0) {
                        calcValue.textContent = '$' + unit.toFixed(4);
                        calcDisplay.style.display = 'block';
                    } else {
                        calcDisplay.style.display = 'none';
                    }
                }
                qtyInput.addEventListener('input', updateCalc);
                unitCostInput.addEventListener('input', () => { if (unitCostInput.value) grossTotalInput.value = ''; updateCalc(); });
                grossTotalInput.addEventListener('input', () => { if (grossTotalInput.value) unitCostInput.value = ''; updateCalc(); });

                document.getElementById('cloneCheckinForm').addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const unitCost = unitCostInput.value;
                    const grossTotal = grossTotalInput.value;
                    if (!unitCost && !grossTotal) { alert('Please enter either Unit Cost or Gross Total'); return; }

                    const formData = new FormData();
                    formData.append('action', 'checkin_inventory');
                    formData.append('part_id', partId);
                    formData.append('quantity', qtyInput.value);
                    if (unitCost) formData.append('unit_cost', unitCost);
                    if (grossTotal) formData.append('gross_total', grossTotal);
                    formData.append('supplier_name', document.getElementById('cloneCheckinSupplier').value);
                    formData.append('purchase_date', document.getElementById('cloneCheckinDate').value);
                    formData.append('notes', document.getElementById('cloneCheckinNotes').value);

                    try {
                        await fetch('api.php', { method: 'POST', body: formData });
                        modal.remove();
                        loadParts();
                        loadDashboard();
                    } catch (err) {
                        alert('Error saving check-in');
                    }
                });
            } catch (err) {
                alert('Error loading check-in data');
            }
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
                case 'parts': {
                    const currentSearch = document.getElementById('partsSearchInput')?.value || '';
                    renderPartsTable(currentSearch);
                    break;
                }
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
                document.getElementById('plOverhead').textContent = '$' + (businessMetrics.profit?.overhead_expenses || 0).toLocaleString();
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
                
                // Load the expenses list alongside metrics
                loadBizExpenses();

            } catch (error) {
                console.error('Error loading business metrics:', error);
                alert('Error loading business metrics. Make sure business_metrics.php is uploaded.');
            }
        }

        async function loadBizExpenses() {
            const container = document.getElementById('bizExpenseList');
            if (!container) return;
            try {
                const resp = await fetch('api.php?action=get_business_expenses');
                const expenses = await resp.json();

                if (!expenses.length) {
                    container.innerHTML = '<p style="color:var(--text-dim);">No overhead expenses recorded yet.</p>';
                    return;
                }

                const total = expenses.reduce((sum, e) => sum + parseFloat(e.cost), 0);
                const rows = expenses.map(e => `
                    <tr>
                        <td style="font-family:var(--font-mono);">${e.expense_date}</td>
                        <td>${e.description}</td>
                        <td><span class="badge badge-info">${e.category}</span></td>
                        <td style="font-family:var(--font-mono);text-align:right;">$${parseFloat(e.cost).toFixed(2)}</td>
                        <td style="color:var(--text-dim);font-size:0.85em;">${e.notes || ''}</td>
                        <td><button class="btn btn-small btn-danger" onclick="deleteBizExpense(${e.id})">Delete</button></td>
                    </tr>`).join('');

                container.innerHTML = `
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Description</th>
                                <th>Category</th>
                                <th style="text-align:right;">Amount</th>
                                <th>Notes</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            ${rows}
                            <tr style="font-weight:bold;background:var(--bg-light);">
                                <td colspan="3" style="text-align:right;">Total:</td>
                                <td style="font-family:var(--font-mono);text-align:right;">$${total.toFixed(2)}</td>
                                <td colspan="2"></td>
                            </tr>
                        </tbody>
                    </table>`;
            } catch (err) {
                container.innerHTML = '<p style="color:var(--danger);">Error loading expenses.</p>';
            }
        }

        async function saveBizExpense() {
            const desc = document.getElementById('bizExpDesc').value.trim();
            const cost = document.getElementById('bizExpCost').value;
            const category = document.getElementById('bizExpCategory').value;
            const date = document.getElementById('bizExpDate').value;
            const notes = document.getElementById('bizExpNotes').value.trim();

            if (!desc || !cost || !date) {
                alert('Description, amount, and date are required.');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'save_business_expense');
            formData.append('description', desc);
            formData.append('cost', cost);
            formData.append('category', category);
            formData.append('expense_date', date);
            formData.append('notes', notes);

            try {
                await fetch('api.php', { method: 'POST', body: formData });
                // Reset form and hide it
                document.getElementById('bizExpDesc').value = '';
                document.getElementById('bizExpCost').value = '';
                document.getElementById('bizExpNotes').value = '';
                document.getElementById('addBizExpenseForm').style.display = 'none';
                document.querySelector('[onclick*="addBizExpenseForm"]').style.display = '';
                // Reload both the expense list and metrics (to update P&L totals)
                loadBizExpenses();
                loadBusinessMetrics();
            } catch (err) {
                alert('Error saving expense.');
            }
        }

        async function deleteBizExpense(id) {
            if (!confirm('Delete this expense?')) return;
            const formData = new FormData();
            formData.append('action', 'delete_business_expense');
            formData.append('id', id);
            try {
                await fetch('api.php', { method: 'POST', body: formData });
                loadBizExpenses();
                loadBusinessMetrics();
            } catch (err) {
                alert('Error deleting expense.');
            }
        }

        // ── Tasks ──────────────────────────────────────────────────────────

        let taskProjectId = null;
        let taskData = [];          // flat array from server
        let dragSrcItem = null;     // dragged DOM element
        let dragSrcId   = null;

        async function loadTasksSection() {
            // Ensure projects are loaded (may not be if Tasks tab opened first)
            if (!projects || projects.length === 0) {
                try {
                    const r = await fetch('api.php?action=get_projects');
                    projects = await r.json();
                } catch(e) { console.error(e); }
            }
            const sel = document.getElementById('taskProjectSelect');
            const prev = sel.value;
            sel.innerHTML = '<option value="">— Select a project —</option>';
            (projects || []).filter(p => p.status === 'active').forEach(p => {
                const opt = document.createElement('option');
                opt.value = p.id;
                opt.textContent = p.project_name;
                sel.appendChild(opt);
            });
            if (prev) sel.value = prev;
            if (sel.value) {
                taskProjectId = parseInt(sel.value);
                await loadTasks();
            }
        }

        async function loadTasks() {
            const sel = document.getElementById('taskProjectSelect');
            taskProjectId = sel.value ? parseInt(sel.value) : null;
            document.getElementById('addRootTaskRow').style.display = taskProjectId ? 'flex' : 'none';
            document.getElementById('newRootTaskInput').value = '';
            if (!taskProjectId) {
                document.getElementById('taskList').innerHTML = '';
                document.getElementById('taskProgressBar').style.display = 'none';
                document.getElementById('taskProgressLabel').textContent = '';
                return;
            }
            try {
                const resp = await fetch('api.php?action=get_tasks&project_id=' + taskProjectId);
                taskData = await resp.json();
                renderTasks();
            } catch (e) {
                console.error(e);
            }
        }

        function renderTasks() {
            const roots = taskData.filter(t => !t.parent_id).sort((a,b) => a.sort_order - b.sort_order);
            const list  = document.getElementById('taskList');

            // Progress
            const total = taskData.length;
            const done  = taskData.filter(t => t.is_done == 1).length;
            const bar   = document.getElementById('taskProgressBar');
            const fill  = document.getElementById('taskProgressFill');
            const label = document.getElementById('taskProgressLabel');
            if (total > 0) {
                bar.style.display = 'block';
                fill.style.width  = Math.round(done/total*100) + '%';
                label.textContent = done + ' of ' + total + ' done';
            } else {
                bar.style.display = 'none';
                label.textContent = '';
            }

            if (roots.length === 0) {
                list.innerHTML = '<li class="task-empty">No tasks yet — add one below.</li>';
                return;
            }

            list.innerHTML = '';
            roots.forEach(t => list.appendChild(buildTaskEl(t, 0)));
        }

        function buildTaskEl(task, depth) {
            const children = taskData.filter(t => t.parent_id == task.id).sort((a,b) => a.sort_order - b.sort_order);
            const li = document.createElement('li');
            const depthClass = depth === 1 ? ' subtask' : depth === 2 ? ' subsubtask' : '';
            li.className = 'task-item' + depthClass;
            li.dataset.id       = task.id;
            li.dataset.parentId = task.parent_id || '';
            li.draggable = true;

            const canHaveChildren = depth < 2;
            const addBtnLabel    = depth === 0 ? '+ Sub-task' : '+ Sub-sub';
            const addPlaceholder = depth === 0 ? 'Sub-task… (Enter to add)' : 'Sub-sub-task… (Enter to add)';
            const addIndent      = depth === 0 ? '36px' : '24px';

            li.innerHTML = `
                <div class="task-row">
                    <span class="task-drag-handle" title="Drag to reorder">⠿</span>
                    <input type="checkbox" class="task-checkbox" ${task.is_done == 1 ? 'checked' : ''}
                        onchange="toggleTask(${task.id}, this.checked)">
                    <span class="task-title-text ${task.is_done == 1 ? 'done' : ''}"
                        ondblclick="startEditTask(this, ${task.id})">${escHtml(task.title)}</span>
                    <input type="text" class="task-title-input" style="display:none"
                        value="${escHtml(task.title)}"
                        onblur="commitEditTask(this, ${task.id})"
                        onkeydown="editTaskKeydown(event, this, ${task.id})">
                    <div class="task-actions">
                        ${canHaveChildren ? `<button class="task-action-btn" onclick="showAddSubtask(${task.id})">${addBtnLabel}</button>` : ''}
                        <button class="task-action-btn del" onclick="deleteTask(${task.id})">✕</button>
                    </div>
                </div>
                ${canHaveChildren ? `<ul class="subtask-list" id="subtasks-${task.id}"></ul>
                <div class="add-task-row" id="addSub-${task.id}" style="display:none;margin:0 12px 10px ${addIndent};">
                    <input type="text" placeholder="${addPlaceholder}"
                        onkeydown="handleAddSubtask(event, ${task.id})">
                    <button class="btn" onclick="this.previousElementSibling.dispatchEvent(new KeyboardEvent('keydown',{key:'Enter',bubbles:true}))">Add</button>
                </div>` : ''}
            `;

            // Drag events
            li.addEventListener('dragstart', onDragStart);
            li.addEventListener('dragover',  onDragOver);
            li.addEventListener('dragleave', onDragLeave);
            li.addEventListener('drop',      onDrop);
            li.addEventListener('dragend',   onDragEnd);

            // Render children into their slot
            if (canHaveChildren && children.length) {
                const subList = li.querySelector(`#subtasks-${task.id}`);
                children.forEach(c => subList.appendChild(buildTaskEl(c, depth + 1)));
            }

            return li;
        }

        // ── Editing ────────────────────────────────

        function startEditTask(spanEl, id) {
            const input = spanEl.nextElementSibling;
            spanEl.style.display = 'none';
            input.style.display  = 'block';
            input.focus();
            input.select();
        }

        function editTaskKeydown(e, input, id) {
            if (e.key === 'Enter')  { input.blur(); }
            if (e.key === 'Escape') {
                const span = input.previousElementSibling;
                input.style.display = 'none';
                span.style.display  = '';
            }
        }

        async function commitEditTask(input, id) {
            const span  = input.previousElementSibling;
            const title = input.value.trim();
            if (!title) { input.style.display='none'; span.style.display=''; return; }

            const task = taskData.find(t => t.id == id);
            if (title === task.title) { input.style.display='none'; span.style.display=''; return; }

            const fd = new FormData();
            fd.append('action','save_task');
            fd.append('id', id);
            fd.append('project_id', taskProjectId);
            fd.append('parent_id', task.parent_id ?? '');
            fd.append('title', title);
            fd.append('notes', task.notes ?? '');
            await fetch('api.php', {method:'POST', body:fd});
            await loadTasks();
        }

        // ── Toggle / Delete ────────────────────────

        async function toggleTask(id, checked) {
            const fd = new FormData();
            fd.append('action','toggle_task');
            fd.append('id', id);
            fd.append('is_done', checked ? 1 : 0);
            await fetch('api.php', {method:'POST', body:fd});
            // Update local state and re-render without full server round-trip
            const t = taskData.find(t => t.id == id);
            if (t) t.is_done = checked ? 1 : 0;
            renderTasks();
        }

        async function deleteTask(id) {
            if (!confirm('Delete this task and all its sub-tasks?')) return;
            const fd = new FormData();
            fd.append('action','delete_task');
            fd.append('id', id);
            await fetch('api.php', {method:'POST', body:fd});
            await loadTasks();
        }

        // ── Add tasks ─────────────────────────────

        function handleAddRootTask(e) { if (e.key === 'Enter') submitNewRootTask(); }

        async function submitNewRootTask() {
            const input = document.getElementById('newRootTaskInput');
            const title = input.value.trim();
            if (!title || !taskProjectId) return;
            const fd = new FormData();
            fd.append('action','save_task');
            fd.append('project_id', taskProjectId);
            fd.append('parent_id', '');
            fd.append('title', title);
            fd.append('notes', '');
            await fetch('api.php', {method:'POST', body:fd});
            input.value = '';
            await loadTasks();
        }

        function showAddSubtask(parentId) {
            const row = document.getElementById('addSub-' + parentId);
            if (!row) return;
            row.style.display = row.style.display === 'none' ? 'flex' : 'none';
            if (row.style.display === 'flex') row.querySelector('input').focus();
        }

        async function handleAddSubtask(e, parentId) {
            if (e.key !== 'Enter') return;
            const input = e.target;
            const title = input.value.trim();
            if (!title) return;
            const fd = new FormData();
            fd.append('action','save_task');
            fd.append('project_id', taskProjectId);
            fd.append('parent_id', parentId);
            fd.append('title', title);
            fd.append('notes', '');
            await fetch('api.php', {method:'POST', body:fd});
            input.value = '';
            await loadTasks();
            // Re-open the add-sub row so user can keep adding
            showAddSubtask(parentId);
        }

        // ── Drag-and-drop reorder ──────────────────

        function onDragStart(e) {
            dragSrcItem = this;
            dragSrcId   = parseInt(this.dataset.id);
            this.classList.add('dragging');
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', this.dataset.id);
        }

        function onDragOver(e) {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            // Only allow drops within same level (same parent)
            if (this !== dragSrcItem && this.dataset.parentId === dragSrcItem.dataset.parentId) {
                this.classList.add('drag-over');
            }
        }

        function onDragLeave() { this.classList.remove('drag-over'); }
        function onDragEnd()   { document.querySelectorAll('.task-item').forEach(el => { el.classList.remove('dragging','drag-over'); }); }

        async function onDrop(e) {
            e.preventDefault();
            this.classList.remove('drag-over');
            if (this === dragSrcItem) return;
            if (this.dataset.parentId !== dragSrcItem.dataset.parentId) return; // different level, ignore

            const parent = this.parentElement;
            const items  = Array.from(parent.children).filter(el => el.classList.contains('task-item'));
            const srcIdx = items.indexOf(dragSrcItem);
            const dstIdx = items.indexOf(this);
            if (srcIdx === -1 || dstIdx === -1) return;

            // Reorder DOM
            if (srcIdx < dstIdx) {
                parent.insertBefore(dragSrcItem, this.nextSibling);
            } else {
                parent.insertBefore(dragSrcItem, this);
            }

            // Persist new order
            const reordered = Array.from(parent.children)
                .filter(el => el.classList.contains('task-item'))
                .map((el, i) => ({ id: parseInt(el.dataset.id), sort_order: i }));

            const fd = new FormData();
            fd.append('action','reorder_tasks');
            fd.append('items', JSON.stringify(reordered));
            await fetch('api.php', {method:'POST', body:fd});

            // Update local taskData sort_order to match
            reordered.forEach(r => {
                const t = taskData.find(t => t.id === r.id);
                if (t) t.sort_order = r.sort_order;
            });
        }

        // ── Utility ───────────────────────────────

        function escHtml(s) {
            return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
        }

        // ── Beta Feedback Admin ───────────────────────────────────────────────

        const KH1_STEPS = {
            packaging:'Packaging Check', step01:'01 Unpack & Inventory', step02:'02 Magnet Wire Prep',
            step03:'03 Threading Paddles', step04:'04 The Wire Loop', step05:'05 Contact Set Screws',
            step06:'06 Continuity Check', step07:'07 Secure Bearings', step08:'08 Glue Cure (1st)',
            step09:'09 Center Lug', step10:'10 Opposing Magnets', step11:'11 Glue Cure (2nd)',
            step12:'12 Stress-Relief Loop', step13:'13 Install Set Screws', step14:'14 Mechanical Stack',
            step15:'15 3.5mm Jack & PCB', step16:'16 Wiring & Soldering', step17:'17 Calibration',
            general:'General Feedback'
        };
        const TOTAL_STEPS = 19;
        const RATING_LABEL = ['','👍 All Good','💬 Had Questions','⚠️ Had Trouble'];
        const RATING_COLOR = ['','#10b981','#f59e0b','#ef4444'];

        async function loadBetaFeedback() {
            const resp = await fetch('api.php?action=kh1_beta_list');
            const data = await resp.json();

            // Summary cards
            const builders = data.builders || [];
            const totalBuilders = builders.length;
            const totalIssues   = builders.reduce((s,b) => s + parseInt(b.trouble_count||0), 0);
            const avgCompletion = totalBuilders
                ? Math.round(builders.reduce((s,b) => s + parseInt(b.steps_saved||0), 0) / totalBuilders / TOTAL_STEPS * 100)
                : 0;

            document.getElementById('betaSummaryCards').innerHTML = `
                <div class="stat-card"><div class="stat-value">${totalBuilders}</div><div class="stat-label">Builders</div></div>
                <div class="stat-card"><div class="stat-value">${avgCompletion}%</div><div class="stat-label">Avg. Completion</div></div>
                <div class="stat-card stat-low"><div class="stat-value">${totalIssues}</div><div class="stat-label">Steps w/ Trouble</div></div>
            `;

            // Packaging alert
            const pkg = data.packaging || {};
            const pkgAlerts = [];
            if (parseInt(pkg.damaged_pkg||0) > 0)    pkgAlerts.push(pkg.damaged_pkg + ' damaged package(s)');
            if (parseInt(pkg.missing_tools||0) > 0)   pkgAlerts.push(pkg.missing_tools + ' missing tools report(s)');
            if (parseInt(pkg.damaged_parts||0) > 0)   pkgAlerts.push(pkg.damaged_parts + ' damaged part(s)');
            if (pkgAlerts.length) {
                document.getElementById('betaPackagingAlert').style.display = 'block';
                document.getElementById('betaPackagingAlert').innerHTML = '⚠️ Packaging issues reported: ' + pkgAlerts.join(' · ');
            }

            // Step issues summary
            const stepIssues = (data.step_issues || []).filter(s => parseInt(s.trouble_builders) > 0);
            if (stepIssues.length) {
                document.getElementById('betaStepIssues').style.display = 'block';
                document.getElementById('betaStepIssues').innerHTML = `
                    <div style="font-size:0.78rem;font-weight:600;letter-spacing:0.08em;text-transform:uppercase;color:var(--text-dim);margin-bottom:8px;">Steps with reported trouble</div>
                    <div style="display:flex;flex-wrap:wrap;gap:6px;">
                        ${stepIssues.map(s => `
                            <span style="background:#fef2f2;border:1px solid #fca5a5;border-radius:4px;padding:3px 10px;font-size:0.8rem;color:#991b1b;">
                                ${escHtml(KH1_STEPS[s.step_key]||s.step_key)} (${s.trouble_builders})
                            </span>
                        `).join('')}
                    </div>`;
            }

            // Builder table
            if (!builders.length) {
                document.getElementById('betaBuilderBody').innerHTML =
                    '<tr><td colspan="5" style="text-align:center;padding:2rem;color:var(--text-dim);">No beta builders have submitted feedback yet.</td></tr>';
                return;
            }
            document.getElementById('betaBuilderBody').innerHTML = builders.map(b => {
                const issues = parseInt(b.trouble_count||0);
                const saved  = parseInt(b.steps_saved||0);
                const lastAgo = b.last_active ? timeAgo(b.last_active) : '—';
                return `<tr>
                    <td style="font-family:var(--font-mono);font-weight:600;">${escHtml(b.callsign)}</td>
                    <td>${saved} / ${TOTAL_STEPS}</td>
                    <td>${issues > 0
                        ? `<span style="color:#ef4444;font-weight:600;">⚠️ ${issues}</span>`
                        : `<span style="color:#10b981;">✓ 0</span>`}</td>
                    <td style="color:var(--text-dim);font-size:0.85rem;">${escHtml(lastAgo)}</td>
                    <td><button class="btn btn-secondary" style="font-size:0.78rem;padding:4px 10px;"
                        onclick="openBetaDetail('${escHtml(b.callsign)}')">View →</button></td>
                </tr>`;
            }).join('');
        }

        async function openBetaDetail(callsign) {
            document.getElementById('betaDetailCallsign').textContent = callsign;
            document.getElementById('betaDetailBody').innerHTML = '<div style="text-align:center;padding:2rem;color:var(--text-dim);">Loading…</div>';
            document.getElementById('betaDetailModal').style.display = 'flex';

            const resp = await fetch('api.php?action=kh1_beta_detail&callsign=' + encodeURIComponent(callsign));
            const data = await resp.json();
            const responses = data.responses || [];
            const photosByStep = data.photos || {};

            if (!responses.length && !Object.keys(photosByStep).length) {
                document.getElementById('betaDetailBody').innerHTML = '<div style="color:var(--text-dim);">No responses recorded yet.</div>';
                return;
            }

            const byKey = {};
            responses.forEach(r => byKey[r.step_key] = r);

            const stepOrder = ['packaging','step01','step02','step03','step04','step05','step06','step07',
                'step08','step09','step10','step11','step12','step13','step14','step15','step16','step17','general'];

            let html = '';
            stepOrder.forEach(key => {
                const r = byKey[key];
                const photos = photosByStep[key] || [];
                if (!r && !photos.length) return;
                const rating = parseInt((r||{}).rating||0);
                const label  = KH1_STEPS[key] || key;
                let detail = '';

                if (key === 'packaging' && r) {
                    const yn = v => v == null ? '—' : (parseInt(v) === 1 ? '✅ Yes' : '❌ No');
                    detail = `<div style="font-size:0.82rem;color:var(--text-dim);margin-top:4px;">
                        Pkg intact: ${yn(r.packaging_intact)} &nbsp;·&nbsp;
                        Tools in box: ${yn(r.tools_in_box)} &nbsp;·&nbsp;
                        Parts OK: ${yn(r.parts_undamaged)}
                    </div>`;
                } else if (rating) {
                    detail = `<div style="font-size:0.82rem;color:${RATING_COLOR[rating]};margin-top:4px;">${RATING_LABEL[rating]}</div>`;
                }

                const photosHtml = photos.length ? `<div style="display:flex;flex-wrap:wrap;gap:6px;margin-top:8px;">${
                    photos.map(p => p.type === 'video'
                        ? `<a href="${escHtml(p.url)}" target="_blank" style="position:relative;display:block;width:72px;height:72px;border-radius:6px;overflow:hidden;border:1px solid var(--border-card);background:#1f2937;flex-shrink:0;">
                             <video src="${escHtml(p.url)}" muted preload="metadata" playsinline style="width:100%;height:100%;object-fit:cover;pointer-events:none;display:block;"></video>
                             <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;pointer-events:none;"><span style="font-size:1.2rem;filter:drop-shadow(0 1px 3px rgba(0,0,0,0.7));">▶</span></div>
                           </a>`
                        : `<a href="${escHtml(p.url)}" target="_blank" style="display:block;width:72px;height:72px;border-radius:6px;overflow:hidden;border:1px solid var(--border-card);flex-shrink:0;">
                             <img src="${escHtml(p.url)}" style="width:100%;height:100%;object-fit:cover;display:block;">
                           </a>`
                    ).join('')
                }</div>` : '';

                html += `<div style="padding:10px 0;border-bottom:1px solid var(--border-card);">
                    <div style="font-size:0.84rem;font-weight:600;color:var(--text-primary);">${escHtml(label)}</div>
                    ${detail}
                    ${r && r.feedback ? `<div style="font-size:0.84rem;color:var(--text-secondary);margin-top:5px;font-style:italic;">"${escHtml(r.feedback)}"</div>` : ''}
                    ${photosHtml}
                </div>`;
            });

            document.getElementById('betaDetailBody').innerHTML = html;
        }

        function closeBetaDetail() {
            document.getElementById('betaDetailModal').style.display = 'none';
        }

        function timeAgo(ts) {
            const secs = Math.floor((Date.now() - new Date(ts)) / 1000);
            if (secs < 60)   return 'just now';
            if (secs < 3600) return Math.floor(secs/60) + 'm ago';
            if (secs < 86400)return Math.floor(secs/3600) + 'h ago';
            return Math.floor(secs/86400) + 'd ago';
        }
    </script>
</body>
</html>
