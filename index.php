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
        @keyframes spin {
            to { transform: rotate(360deg); }
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
        #partsCategoryFilter { width: 180px; }
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
            #partsCategoryFilter,
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
                            <span style="font-size:0.8rem;color:var(--text-secondary);font-weight:400;">Every BOM part ranked by how many kits you can build. See what to order.</span>
                        </div>
                        <div style="display:flex;align-items:center;gap:6px;flex-shrink:0;flex-wrap:wrap;">
                            <span style="font-size:0.82rem;color:var(--text-secondary);">Order to:</span>
                            <select id="bottleneckTargetSelect" class="form-input" style="width:auto;padding:4px 8px;font-size:0.85rem;" onchange="setBottleneckTarget(parseInt(this.value))"><option value="50" selected>50 kits</option><option value="100">100 kits</option><option value="150">150 kits</option><option value="200">200 kits</option><option value="250">250 kits</option><option value="300">300 kits</option><option value="350">350 kits</option><option value="400">400 kits</option><option value="450">450 kits</option><option value="500">500 kits</option></select>
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
                            <button class="btn btn-small" id="wcCheckStatusBtn" onclick="wcCheckStatus()" style="background:var(--info);color:white;border-color:var(--info);">Check WC Status</button>
                            <button class="btn btn-small" id="wcSyncAllBtn" onclick="wcSyncAll()" style="background:var(--accent-secondary);color:white;border-color:var(--accent-secondary);">Sync All to WooCommerce</button>
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
                            <select id="partsCategoryFilter" class="form-input" onchange="renderPartsTable(document.getElementById('partsSearchInput')?.value || '')">
                                <option value="">All Categories</option>
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
                        <div class="flex flex-gap">
                            <button class="btn" id="wcReconcileBtn" onclick="wcReconcileOrders()" style="background:var(--accent-secondary);color:white;border-color:var(--accent-secondary);">Refresh from WooCommerce</button>
                            <button class="btn btn-primary" onclick="openOrderModal()">+ New Order</button>
                        </div>
                    </div>
                    <div id="wcReconcileResult" style="padding:0 1rem;"></div>
                    <div class="table-container">
                        <table class="data-table" id="ordersTable">
                            <thead>
                                <tr>
                                    <th style="cursor: pointer; user-select: none;" onclick="sortTable('orders', 'display_number')" title="Click to sort">Order # <span id="sort-orders-display_number"></span></th>
                                    <th style="cursor: pointer; user-select: none;" onclick="sortTable('orders', 'order_date')" title="Click to sort">Date <span id="sort-orders-order_date"></span></th>
                                    <th style="cursor: pointer; user-select: none;" onclick="sortTable('orders', 'customer_name')" title="Click to sort">Customer <span id="sort-orders-customer_name"></span></th>
                                    <th style="cursor: pointer; user-select: none;" onclick="sortTable('orders', 'customer_callsign')" title="Click to sort">Callsign <span id="sort-orders-customer_callsign"></span></th>
                                    <th style="cursor: pointer; user-select: none;" onclick="sortTable('orders', 'items_summary')" title="Click to sort">Items <span id="sort-orders-items_summary"></span></th>
                                    <th style="cursor: pointer; user-select: none;" onclick="sortTable('orders', 'total_quantity')" title="Click to sort">Qty <span id="sort-orders-total_quantity"></span></th>
                                    <th style="cursor: pointer; user-select: none;" onclick="sortTable('orders', 'total_price')" title="Click to sort">Amount <span id="sort-orders-total_price"></span></th>
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

                <div class="card" style="margin-bottom: 2rem;">
                    <div class="card-body" style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap;">
                        <h3 style="margin:0;">Per-Project P&amp;L</h3>
                        <select id="businessProjectSelect" class="form-select" style="max-width:340px;flex:1;min-width:220px;">
                            <option value="">Loading projects…</option>
                        </select>
                        <button class="btn btn-primary btn-small" onclick="openProjectPLModal()">View P&amp;L</button>
                        <span style="color:var(--text-dim);font-size:0.85em;">Uses the period selected above.</span>
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
                                    <th>Review Status</th>
                                    <th>Last Active</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="betaBuilderBody">
                                <tr><td colspan="6" style="text-align:center;padding:2rem;color:var(--text-dim);">Loading…</td></tr>
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
                            <div style="display:flex;align-items:center;gap:6px;">
                                <button class="btn btn-secondary" style="font-size:0.75rem;padding:4px 10px;" onclick="markAllBetaReviewed()">✓ Mark All Reviewed</button>
                                <button onclick="closeBetaDetail()" style="background:none;border:none;font-size:1.5rem;cursor:pointer;color:var(--text-dim);padding:4px 8px;">×</button>
                            </div>
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
        let variationAttrOptions = {};
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

        // Cost fields on parts orders accept pasted values like "$1,234.50": strip
        // the $ and commas as they're typed/pasted. Capture phase so the cleaned
        // value is in place before each form's own input listeners recalculate.
        document.addEventListener('input', e => {
            if (!e.target.classList || !e.target.classList.contains('money-input')) return;
            const cleaned = e.target.value.replace(/[^0-9.]/g, '');
            if (cleaned !== e.target.value) e.target.value = cleaned;
        }, true);

        // Dashboard
        // Re-pulls the dashboard (incl. the Inventory Order Planner) after a BOM or
        // project change, but only when the dashboard is the tab underneath. Keeps
        // whichever planner projects were expanded so the view doesn't jump around.
        function refreshDashboardIfVisible() {
            if (document.getElementById('dashboard')?.classList.contains('active')) {
                loadDashboard({ keepExpanded: true });
            }
        }

        async function loadDashboard(opts = {}) {
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
                            <strong>${o.customer_name}</strong> - ${o.items_summary || 'No items'}<br>
                            <span class="badge badge-${getStatusColor(o.status)}">${o.status}</span>
                            $${parseFloat(o.total_price || 0).toFixed(2)}
                        </div>
                    `).join('')
                    : '<div style="padding: 1rem; color: var(--text-dim);">No recent orders</div>';
                document.getElementById('recentOrdersList').innerHTML = ordersHtml;

                // Bottleneck insights — store data and render with current target
                bottleneckInsightsData = data.bottleneck_insights || [];
                if (!opts.keepExpanded) {
                    bottleneckExpandedProjects = new Set();
                    bottleneckInitialized = false;
                }
                renderBottleneckInsights();
            } catch (error) {
                console.error('Error loading dashboard:', error);
            }
        }

        function setBottleneckTarget(val) {
            bottleneckTarget = val;
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

        // A part's stock plus whatever's already on order from a supplier but not yet received —
        // parts inbound shouldn't trigger "order more" alarms.
        function bottleneckEffectiveBuildable(part) {
            if (!part.quantity_required) return Infinity;
            return Math.floor((part.current_stock + (part.pending_qty || 0)) / part.quantity_required);
        }

        function renderBottleneckInsights() {
            const container = document.getElementById('bottleneckInsights');
            const insights = bottleneckInsightsData;
            if (!insights.length) {
                container.innerHTML = '<div style="padding:1rem;color:var(--text-dim)">No active projects with BOM data.</div>';
                return;
            }

            // On first render, auto-expand projects that have parts needing ordering
            // (accounting for stock already on the way from a supplier)
            if (!bottleneckInitialized) {
                insights.forEach(proj => {
                    const t = bottleneckTarget;
                    if (proj.all_fixed_parts.some(p => bottleneckEffectiveBuildable(p) < t)) {
                        bottleneckExpandedProjects.add(proj.project_id);
                    }
                });
                bottleneckInitialized = true;
            }

            const html = insights.map(proj => {
                const effectiveTarget = bottleneckTarget;
                const b = proj.current_buildable;
                const parts = proj.all_fixed_parts; // sorted ascending by buildable
                const barCeiling = Math.max(proj.max_buildable, effectiveTarget, 1);
                const badgeColor = b === 0 ? 'var(--danger)' : b < 10 ? 'var(--warning)' : b < 30 ? 'var(--info)' : 'var(--success)';
                const isExpanded = bottleneckExpandedProjects.has(proj.project_id);

                const partsRows = parts.map(part => {
                    const isBottleneck = part.buildable === b;
                    const pendingQty = part.pending_qty || 0;
                    const effectiveBuildable = bottleneckEffectiveBuildable(part);
                    const needsOrder = effectiveBuildable < effectiveTarget;
                    const unitsNeeded = needsOrder
                        ? Math.max(0, effectiveTarget * part.quantity_required - part.current_stock - pendingQty)
                        : 0;
                    const barPct = Math.min(100, (part.buildable / barCeiling) * 100).toFixed(1);
                    const targetPct = Math.min(100, (effectiveTarget / barCeiling) * 100).toFixed(1);
                    const barColor = isBottleneck ? '#ef4444' : needsOrder ? '#f59e0b' : '#10b981';
                    const rowBg = isBottleneck ? 'rgba(239,68,68,0.04)' : '';

                    const pendingNote = pendingQty > 0
                        ? `<br><span style="color:var(--info);font-size:0.72rem;">+${pendingQty.toLocaleString()} on order</span>` : '';

                    let orderCell;
                    if (!needsOrder) {
                        orderCell = pendingQty > 0 && part.buildable < effectiveTarget
                            ? `<span style="color:var(--success);font-size:0.82rem;">✓ covered by order</span>${pendingNote}`
                            : `<span style="color:var(--success);font-size:0.82rem;">✓ ok</span>`;
                    } else {
                        const costStr = part.unit_cost > 0
                            ? ` <span style="color:var(--text-secondary);font-size:0.78rem;">~$${(unitsNeeded * part.unit_cost).toFixed(2)}</span>` : '';
                        const urgStyle = isBottleneck
                            ? 'color:var(--danger);font-weight:700;'
                            : 'color:var(--warning);font-weight:600;';
                        orderCell = `<span style="${urgStyle}">${unitsNeeded.toLocaleString()} units</span>${costStr}${pendingNote}`;
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
                        <td style="padding:5px 8px;font-size:0.78rem;color:var(--text-secondary);">${part.variation ? part.variation : '<span style="color:var(--text-dim);">— shared —</span>'}</td>
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

                const neededParts = parts.filter(p => bottleneckEffectiveBuildable(p) < effectiveTarget);
                const totalCost = neededParts.reduce((sum, p) => {
                    if (p.unit_cost > 0) sum += Math.max(0, effectiveTarget * p.quantity_required - p.current_stock - (p.pending_qty || 0)) * p.unit_cost;
                    return sum;
                }, 0);
                const targetLabel = `${effectiveTarget} kits`;
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
                        <table style="width:100%;border-collapse:collapse;margin-bottom:14px;min-width:640px;">
                            <thead>
                                <tr style="border-bottom:1px solid var(--bg-light);">
                                    <th style="padding:3px 8px;text-align:left;font-size:0.72rem;color:var(--text-dim);font-weight:500;text-transform:uppercase;letter-spacing:.05em;">Part</th>
                                    <th style="padding:3px 8px;text-align:left;font-size:0.72rem;color:var(--text-dim);font-weight:500;text-transform:uppercase;letter-spacing:.05em;">Variation</th>
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
                            <button class="btn btn-small" onclick="openPromoModal(${p.id})" style="background:var(--warning);color:white;border-color:var(--warning);">Promo</button>
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
            const btn = document.getElementById('wcSyncAllBtn');
            const spinner = '<span style="display:inline-block;width:12px;height:12px;border:2px solid rgba(255,255,255,0.4);border-top-color:#fff;border-radius:50%;animation:spin 0.7s linear infinite;vertical-align:middle;margin-right:4px;"></span>';
            if (btn) { btn.disabled = true; btn.innerHTML = spinner + 'Syncing…'; }
            wcShowResult(spinner.replace('#fff', 'var(--accent-secondary)').replace('rgba(255,255,255,0.4)', 'rgba(0,0,0,0.15)') + '<em>Syncing all projects to WooCommerce. This pushes live stock updates one project at a time, so it can take a while…</em>');
            try {
                const r = await fetch(`${WC_WEBHOOK}?action=wc_sync_all`);
                const data = await r.json();
                if (data.error) { wcShowResult(`<span style="color:var(--danger)">Error: ${data.error}</span>`); return; }
                const rows = (data.results || []).map(p => {
                    if (p.skipped) return `<tr><td style="padding:3px 8px;color:var(--text-secondary)">${p.project_id}</td><td colspan="2" style="padding:3px 8px;color:var(--text-secondary)">skipped: ${p.reason}</td></tr>`;
                    if (p.variable) {
                        const varLines = (p.variations || []).map(v => {
                            if (v.skipped) return `${v.combo}: skipped (${v.reason})`;
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
                wcShowResult(`<strong>Sync All: ${data.synced} project(s) pushed</strong>
                    <table style="margin-top:8px;width:100%;border-collapse:collapse;font-size:0.85rem;">
                        <thead><tr style="color:var(--text-secondary);text-align:left;border-bottom:1px solid var(--border-card)">
                            <th style="padding:3px 8px">Project</th><th style="padding:3px 8px">Result</th><th style="padding:3px 8px">Detail</th>
                        </tr></thead>
                        <tbody>${rows}</tbody>
                    </table>`);
            } catch(e) {
                wcShowResult(`<span style="color:var(--danger)">Request failed: ${e.message}</span>`);
            } finally {
                if (btn) { btn.disabled = false; btn.textContent = 'Sync All to WooCommerce'; }
                loadProjects();
            }
        }

        async function wcCheckStatus() {
            const btn = document.getElementById('wcCheckStatusBtn');
            const spinner = '<span style="display:inline-block;width:12px;height:12px;border:2px solid rgba(255,255,255,0.4);border-top-color:#fff;border-radius:50%;animation:spin 0.7s linear infinite;vertical-align:middle;margin-right:4px;"></span>';
            if (btn) { btn.disabled = true; btn.innerHTML = spinner + 'Checking…'; }
            wcShowResult(spinner.replace('#fff', 'var(--info)').replace('rgba(255,255,255,0.4)', 'rgba(0,0,0,0.15)') + '<em>Fetching WooCommerce stock status. This checks every variation live, so it can take 20-40 seconds…</em>');
            try {
                const r = await fetch(`${WC_WEBHOOK}?action=wc_status`);
                const data = await r.json();
                if (!Array.isArray(data) || data.length === 0) {
                    wcShowResult('<span style="color:var(--text-secondary)">No projects are mapped to WooCommerce products yet.</span>');
                    return;
                }

                let anyMismatch = false;
                let anyPriceMismatch = false;

                function priceLine(p) {
                    if (!p.price_match) anyPriceMismatch = true;
                    const cellId  = `wcprice_${p.project_id}`;
                    const wcVal   = p.wc_price !== null && p.wc_price !== undefined ? '$' + Number(p.wc_price).toFixed(2) : '?';
                    const trkVal  = '$' + Number(p.tracker_price || 0).toFixed(2);
                    const icon    = p.price_match ? '✓' : '⚠';
                    const color   = p.price_match ? 'var(--success)' : 'var(--warning)';
                    const pullBtn = !p.price_match && p.wc_price !== null
                        ? `<button class="btn btn-small" onclick="wcPullPrice(${p.project_id}, '${cellId}')" style="font-size:9px;padding:1px 5px;margin-left:4px;">Pull from WC</button>`
                        : '';
                    return `<div id="${cellId}" style="font-weight:400;font-size:10.5px;color:var(--text-secondary);margin-top:2px;">
                        Price: <span style="font-family:var(--font-mono);">${trkVal}</span> tracker vs <span style="font-family:var(--font-mono);">${wcVal}</span> WC
                        <span style="color:${color};font-weight:700;">${icon}</span>${pullBtn}
                    </div>`;
                }

                const rows = data.flatMap(p => {
                    if (p.variable) {
                        return (p.variations || []).map((v, i) => {
                            if (!v.match) anyMismatch = true;
                            const icon  = v.match ? '✓' : '⚠';
                            const color = v.match ? 'var(--success)' : 'var(--warning)';
                            const wcVal = v.wc_qty !== null && v.wc_qty !== undefined ? v.wc_qty : '?';
                            const qtyColor = v.tracker_qty > 0 ? 'var(--success)' : 'var(--danger)';
                            const cellId = `wcqty_${p.project_id}_${v.variation_id}`;
                            return `<tr style="${i === 0 ? 'border-top:1px solid var(--border-card)' : ''}">
                                <td style="padding:6px 8px;font-weight:600;${i > 0 ? 'color:transparent;font-size:0px;padding-top:0' : ''}">${i === 0 ? p.project_name + priceLine(p) : ''}</td>
                                <td style="padding:6px 8px;color:var(--text-secondary);font-size:11px;">${v.combo}</td>
                                <td style="padding:6px 8px;font-family:var(--font-mono);color:${qtyColor}">${v.tracker_qty}</td>
                                <td id="${cellId}" style="padding:6px 8px;font-family:var(--font-mono);color:var(--text-secondary)">${wcVal}</td>
                                <td style="padding:6px 8px;color:${color};font-weight:700">${icon}</td>
                                <td style="padding:6px 8px;white-space:nowrap;">${!v.match ? `<button class="btn btn-small" onclick="wcSyncProject(${p.project_id}, this)" style="font-size:10px;">Sync</button> ` : ''}<button class="btn btn-small" onclick="wcEditStock(${p.project_id}, ${v.variation_id}, '${cellId}', ${wcVal === '?' ? 0 : wcVal})" style="font-size:10px;">Edit</button></td>
                            </tr>`;
                        });
                    } else {
                        if (!p.match) anyMismatch = true;
                        const icon  = p.match ? '✓' : '⚠';
                        const color = p.match ? 'var(--success)' : 'var(--warning)';
                        const wcVal = p.wc_stock_qty !== null && p.wc_stock_qty !== undefined ? p.wc_stock_qty : '?';
                        const qtyColor = p.calculated_available_qty > 0 ? 'var(--success)' : 'var(--danger)';
                        const cellId = `wcqty_${p.project_id}_0`;
                        return [`<tr style="border-top:1px solid var(--border-card)">
                            <td style="padding:6px 8px;font-weight:600">${p.project_name}${priceLine(p)}</td>
                            <td style="padding:6px 8px;color:var(--text-secondary);font-size:11px;">—</td>
                            <td style="padding:6px 8px;font-family:var(--font-mono);color:${qtyColor}">${p.calculated_available_qty}</td>
                            <td id="${cellId}" style="padding:6px 8px;font-family:var(--font-mono);color:var(--text-secondary)">${wcVal}</td>
                            <td style="padding:6px 8px;color:${color};font-weight:700">${icon}</td>
                            <td style="padding:6px 8px;white-space:nowrap;">${!p.match ? `<button class="btn btn-small" onclick="wcSyncProject(${p.project_id}, this)" style="font-size:10px;">Sync</button> ` : ''}<button class="btn btn-small" onclick="wcEditStock(${p.project_id}, null, '${cellId}', ${wcVal === '?' ? 0 : wcVal})" style="font-size:10px;">Edit</button></td>
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
                    ${anyMismatch ? '<div style="padding:8px 12px;margin-top:4px;background:rgba(196,125,26,0.08);border-radius:4px;font-size:12px;color:var(--warning);">⚠ Some stock quantities are out of sync. Use the Sync buttons above, or Sync All.</div>' : '<div style="padding:6px 0;font-size:12px;color:var(--success);">✓ All quantities match WooCommerce.</div>'}
                    ${anyPriceMismatch ? '<div style="padding:8px 12px;margin-top:4px;background:rgba(196,125,26,0.08);border-radius:4px;font-size:12px;color:var(--warning);">⚠ Some retail prices do not match WooCommerce. Use "Pull from WC" under the project name to update the tracker.</div>' : '<div style="padding:6px 0;font-size:12px;color:var(--success);">✓ All tracker retail prices match WooCommerce.</div>'}
                    <div style="padding:6px 0 0;font-size:11px;color:var(--text-dim);">Edit lets you type a stock number and push it straight to WooCommerce; it does not change the tracker's calculated quantity. Pull from WC copies WooCommerce's live price into the tracker's retail price (used for margin and unrealized-revenue calculations, and as the default on manual orders).</div>`);
            } catch(e) {
                wcShowResult(`<span style="color:var(--danger)">Request failed: ${e.message}</span>`);
            } finally {
                if (btn) { btn.disabled = false; btn.textContent = 'Check WC Status'; }
            }
        }

        function wcEditStock(projectId, variationId, cellId, currentQty) {
            const cell = document.getElementById(cellId);
            if (!cell) return;
            const inputId = cellId + '_input';
            cell.dataset.orig = cell.innerHTML;
            cell.innerHTML = `<input type="number" min="0" step="1" id="${inputId}" value="${currentQty}" style="width:60px;font-family:var(--font-mono);padding:2px 4px;border:1px solid var(--border-card);border-radius:3px;">
                <button class="btn btn-small" onclick="wcSaveStock(${projectId}, ${variationId === null ? 'null' : variationId}, '${cellId}')" style="font-size:10px;">Save</button>
                <button class="btn btn-small" onclick="wcCancelEditStock('${cellId}')" style="font-size:10px;">✕</button>`;
            const input = document.getElementById(inputId);
            if (input) { input.focus(); input.select(); }
        }

        function wcCancelEditStock(cellId) {
            const cell = document.getElementById(cellId);
            if (cell && cell.dataset.orig !== undefined) { cell.innerHTML = cell.dataset.orig; }
        }

        async function wcSaveStock(projectId, variationId, cellId) {
            const cell = document.getElementById(cellId);
            const input = document.getElementById(cellId + '_input');
            if (!cell || !input) return;
            const qty = parseInt(input.value, 10);
            if (isNaN(qty) || qty < 0) { alert('Enter a valid quantity (0 or higher).'); return; }

            cell.innerHTML = '<span style="color:var(--text-dim);">Saving…</span>';
            try {
                const formData = new FormData();
                formData.append('action', 'wc_push_manual_stock');
                formData.append('project_id', projectId);
                if (variationId !== null) formData.append('variation_id', variationId);
                formData.append('qty', qty);
                const r = await fetch('api.php', { method: 'POST', body: formData });
                const data = await r.json();
                if (data.success) {
                    cell.style.color = 'var(--success)';
                    cell.textContent = data.new_stock ?? qty;
                } else {
                    cell.style.color = 'var(--danger)';
                    cell.textContent = '⚠ ' + (data.error || 'Failed');
                }
            } catch(e) {
                cell.style.color = 'var(--danger)';
                cell.textContent = '⚠ ' + e.message;
            }
        }

        async function wcPullPrice(projectId, cellId) {
            const cell = document.getElementById(cellId);
            if (!cell) return;
            const orig = cell.innerHTML;
            cell.innerHTML = '<span style="color:var(--text-dim);">Pulling…</span>';
            try {
                const formData = new FormData();
                formData.append('action', 'wc_pull_price');
                formData.append('project_id', projectId);
                const r = await fetch('api.php', { method: 'POST', body: formData });
                const data = await r.json();
                if (data.success) {
                    cell.innerHTML = `Price: <span style="font-family:var(--font-mono);">$${Number(data.new_price).toFixed(2)}</span> tracker vs <span style="font-family:var(--font-mono);">$${Number(data.new_price).toFixed(2)}</span> WC <span style="color:var(--success);font-weight:700;">✓</span>`;
                    loadProjects();
                } else {
                    cell.innerHTML = orig;
                    alert('Could not pull price: ' + (data.error || 'unknown error'));
                }
            } catch(e) {
                cell.innerHTML = orig;
                alert('Could not pull price: ' + e.message);
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
                    btn.textContent = '✗ Failed, see log';
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
                if (!isError) loadProjects();
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

                populatePartsCategoryFilter();

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

        // Rebuilt from the parts themselves on every load so new categories appear
        // automatically; keeps the current selection if that category still exists.
        function populatePartsCategoryFilter() {
            const sel = document.getElementById('partsCategoryFilter');
            if (!sel) return;
            const current = sel.value;
            const cats = [...new Set(allPartsCache.map(p => (p.category || '').trim()).filter(Boolean))]
                .sort((a, b) => a.localeCompare(b, undefined, { sensitivity: 'base' }));
            const hasBlank = allPartsCache.some(p => !(p.category || '').trim());
            sel.innerHTML = '<option value="">All Categories</option>'
                + cats.map(c => `<option value="${escHtml(c)}">${escHtml(c)}</option>`).join('')
                + (hasBlank ? '<option value="__none__">No category</option>' : '');
            sel.value = [...sel.options].some(o => o.value === current) ? current : '';
        }

        function filterPartsTable(query) {
            renderPartsTable(query);
        }

        function renderPartsTable(searchQuery) {
            let filtered = allPartsCache;

            if (partsProjectPartIds !== null) {
                filtered = filtered.filter(p => partsProjectPartIds.has(parseInt(p.id)));
            }

            const category = document.getElementById('partsCategoryFilter')?.value || '';
            if (category === '__none__') {
                filtered = filtered.filter(p => !(p.category || '').trim());
            } else if (category) {
                filtered = filtered.filter(p => (p.category || '').trim() === category);
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
            if (!tbody) return;
            tbody.innerHTML = filtered.map(p => {
                const stockClass = p.current_stock <= p.min_stock_level ? 'stock-low' : 'stock-ok';
                return `
                    <tr>
                        <td><strong>${p.part_number}</strong></td>
                        <td style="cursor:pointer;color:var(--accent-primary);" onclick="viewPart(${p.id})">${p.part_name}</td>
                        <td>${p.category || '-'}</td>
                        <td class="${stockClass}">
                            ${p.current_stock}
                            ${p.pending_order_qty > 0 ? `<br><small style="color:var(--warning);font-size:0.75em;">+${p.pending_order_qty} on order</small>` : ''}
                        </td>
                        <td>${p.min_stock_level}</td>
                        <td>
                            <button class="btn btn-small" onclick="viewPart(${p.id})">View</button>
                            <button class="btn btn-small" onclick="editPart(${p.id})">Edit</button>
                            <button class="btn btn-small" onclick="checkinInventory(${p.id})">Order Parts</button>
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
                allOrders.forEach(o => { o.display_number = o.wc_display_number || o.order_number; });
                
                // Apply sorting
                allOrders = sortData(allOrders, sortState.orders.column, sortState.orders.direction);
                orders = allOrders;
                updateSortArrows('orders');
                
                const tbody = document.querySelector('#ordersTable tbody');
                tbody.innerHTML = orders.map(o => `
                    <tr>
                        <td><a href="order_detail.php?id=${o.id}" style="color:var(--accent-primary);font-weight:bold;text-decoration:none;">${o.wc_display_number || o.order_number}</a></td>
                        <td>${o.order_date}</td>
                        <td>${o.customer_name}</td>
                        <td>${o.customer_callsign || '-'}</td>
                        <td>${o.items_summary || '-'}</td>
                        <td>${o.total_quantity ?? 0}</td>
                        <td>$${parseFloat(o.total_price || 0).toFixed(2)}</td>
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

        async function wcReconcileOrders() {
            const btn = document.getElementById('wcReconcileBtn');
            const result = document.getElementById('wcReconcileResult');
            btn.disabled = true;
            btn.textContent = 'Refreshing…';
            result.innerHTML = '<div style="padding:0.5rem 0;color:var(--text-secondary);font-size:0.85rem;">Pulling order history from WooCommerce. This can take a little while for a full store history…</div>';
            try {
                const r = await fetch('api.php?action=wc_reconcile_orders');
                const data = await r.json();
                if (data.error) {
                    result.innerHTML = `<div style="padding:0.5rem 0;color:var(--danger);font-size:0.85rem;">${data.error}</div>`;
                } else {
                    const errCount = (data.errors || []).length;
                    result.innerHTML = `<div style="padding:0.5rem 0;color:var(--success);font-size:0.85rem;">✓ Refreshed ${data.orders_processed} order(s) from WooCommerce.${errCount ? ` ${errCount} error(s), check wc_sync.log.` : ''}</div>`;
                    loadOrders();
                }
            } catch (e) {
                result.innerHTML = '<div style="padding:0.5rem 0;color:var(--danger);font-size:0.85rem;">Request failed.</div>';
            } finally {
                btn.disabled = false;
                btn.textContent = 'Refresh from WooCommerce';
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
                            <label class="form-label">Package Dimensions (inches): L × W × H</label>
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
                            <small style="color:var(--text-secondary);font-size:0.875rem;">The numeric product ID from your WooCommerce store, needed for inventory sync.</small>
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
                    refreshDashboardIfVisible();
                } catch (error) {
                    alert('Error saving project');
                }
            });
        }

        // Promo / Freebie Giveaway Functions

        function formatPromoCombo(comboKey) {
            if (!comboKey) return '—';
            return comboKey.split('|').map(pair => {
                const [attr, val] = pair.split(':');
                return `${attr}: ${val}`;
            }).join(', ');
        }

        function renderPromoHistoryRows(history) {
            if (!history || !history.length) {
                return `<tr><td colspan="5" style="padding:8px;color:var(--text-secondary);text-align:center;">No promo giveaways logged yet.</td></tr>`;
            }
            return history.map(h => `
                <tr>
                    <td style="padding:5px 8px;font-family:var(--font-mono);font-size:0.8rem;white-space:nowrap;">${new Date(h.created_at).toLocaleDateString()}</td>
                    <td style="padding:5px 8px;font-size:0.8rem;">${formatPromoCombo(h.variation_combo_key)}</td>
                    <td style="padding:5px 8px;font-family:var(--font-mono);text-align:center;">${h.quantity}</td>
                    <td style="padding:5px 8px;font-size:0.8rem;color:var(--text-secondary);">${h.note || ''}</td>
                    <td style="padding:5px 8px;text-align:right;"><button type="button" class="btn btn-small btn-danger" onclick="deletePromo(${h.id}, ${h.project_id_for_undo})">Undo</button></td>
                </tr>
            `).join('');
        }

        async function refreshPromoHistory(projectId) {
            const r = await fetch(`api.php?action=get_promo_history&project_id=${projectId}`);
            const history = await r.json();
            (history || []).forEach(h => h.project_id_for_undo = projectId);
            const body = document.getElementById('promoHistoryBody');
            if (body) body.innerHTML = renderPromoHistoryRows(history);
        }

        async function deletePromo(promoId, projectId) {
            if (!confirm('Undo this promo? The deducted parts will be restored to inventory.')) return;
            const formData = new FormData();
            formData.append('action', 'delete_promo');
            formData.append('id', promoId);
            await fetch('api.php', { method: 'POST', body: formData });
            refreshPromoHistory(projectId);
            loadProjects();
        }

        async function openPromoModal(projectId) {
            const project = projects.find(p => p.id === projectId);
            const projectName = project ? project.project_name : '';

            const [variationsRes, historyRes] = await Promise.all([
                fetch(`api.php?action=get_project_variations&project_id=${projectId}`),
                fetch(`api.php?action=get_promo_history&project_id=${projectId}`)
            ]);
            const variationData = await variationsRes.json();
            const history = await historyRes.json();
            (history || []).forEach(h => h.project_id_for_undo = projectId);

            const comboOptionsHtml = variationData.has_variations
                ? variationData.combos.map(c => `<option value="${c.combo_key}">${formatPromoCombo(c.combo_key)}: ${c.buildable} buildable</option>`).join('')
                : '';

            const modal = createModal(
                `Promo / Freebie: ${projectName}`,
                `
                    <form id="promoForm">
                        <p style="color:var(--text-secondary);font-size:0.85rem;margin-top:0;">
                            Log a kit given away for free (contest prize, demo unit, hamfest freebie, etc). This deducts the BOM parts from inventory exactly like a sale, without creating an order or affecting revenue.
                        </p>
                        ${variationData.has_variations ? `
                        <div class="form-group">
                            <label class="form-label">Variation</label>
                            <select id="promoCombo" class="form-select" required>
                                ${comboOptionsHtml}
                            </select>
                        </div>
                        ` : `<input type="hidden" id="promoCombo" value="">`}
                        <div class="form-group">
                            <label class="form-label">Quantity</label>
                            <input type="number" id="promoQty" class="form-input" value="1" min="1" step="1" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Reason / Note (optional)</label>
                            <input type="text" id="promoNote" class="form-input" placeholder="e.g. Field Day prize, YouTube review unit">
                        </div>
                        <div class="flex flex-gap">
                            <button type="submit" class="btn btn-primary" style="background:var(--warning);border-color:var(--warning);">Deduct Inventory</button>
                            <button type="button" class="btn" onclick="this.closest('.modal').remove()">Cancel</button>
                        </div>
                    </form>
                    <div id="promoResult" style="margin-top:0.75rem;"></div>
                    <h4 style="margin:1.25rem 0 0.5rem;font-size:0.9rem;color:var(--text-secondary);">Recent Promo Giveaways</h4>
                    <div style="max-height:200px;overflow-y:auto;border:1px solid var(--border-card);border-radius:var(--radius-md);">
                        <table style="width:100%;border-collapse:collapse;">
                            <thead>
                                <tr style="background:var(--bg-card-header);">
                                    <th style="padding:5px 8px;text-align:left;font-size:0.78rem;">Date</th>
                                    <th style="padding:5px 8px;text-align:left;font-size:0.78rem;">Variation</th>
                                    <th style="padding:5px 8px;text-align:center;font-size:0.78rem;">Qty</th>
                                    <th style="padding:5px 8px;text-align:left;font-size:0.78rem;">Note</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="promoHistoryBody">${renderPromoHistoryRows(history)}</tbody>
                        </table>
                    </div>
                `
            );

            document.getElementById('promoForm').addEventListener('submit', async (e) => {
                e.preventDefault();
                const comboKey = document.getElementById('promoCombo').value;
                const qty = parseInt(document.getElementById('promoQty').value, 10);
                const note = document.getElementById('promoNote').value;

                if (!confirm(`Deduct BOM parts for ${qty} promo unit(s) of "${projectName}"? Use Undo in the history list below if you make a mistake.`)) {
                    return;
                }

                const formData = new FormData();
                formData.append('action', 'save_promo');
                formData.append('project_id', projectId);
                formData.append('combo_key', comboKey);
                formData.append('quantity', qty);
                formData.append('note', note);

                const submitBtn = e.target.querySelector('button[type="submit"]');
                submitBtn.disabled = true;
                submitBtn.textContent = 'Deducting…';

                try {
                    const r = await fetch('api.php', { method: 'POST', body: formData });
                    const data = await r.json();
                    if (data.error) {
                        document.getElementById('promoResult').innerHTML = `<div style="color:var(--danger);font-size:0.85rem;">${data.error}</div>`;
                        return;
                    }
                    const logHtml = (data.log || []).map(l => `
                        <div style="font-size:0.8rem;color:var(--text-secondary);">${l.part_name}: -${l.deducted} (now ${l.new_stock})</div>
                    `).join('');
                    document.getElementById('promoResult').innerHTML = `
                        <div style="background:var(--bg-card-alt-row);border:1px solid var(--border-card);border-radius:var(--radius-md);padding:8px 10px;">
                            <div style="color:var(--success);font-weight:600;font-size:0.85rem;margin-bottom:4px;">✓ Deducted ${qty} unit(s) from inventory</div>
                            ${logHtml}
                        </div>
                    `;
                    document.getElementById('promoForm').reset();
                    document.getElementById('promoQty').value = 1;
                    refreshPromoHistory(projectId);
                    loadProjects();
                } catch (error) {
                    document.getElementById('promoResult').innerHTML = `<div style="color:var(--danger);font-size:0.85rem;">Error logging promo giveaway.</div>`;
                } finally {
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Deduct Inventory';
                }
            });
        }

        async function viewProject(id) {
            try {
                const response = await fetch(`api.php?action=get_project&id=${id}`);
                const project = await response.json();

                // Store project data globally for export function and BOM rendering
                window.currentProjectData = project;
                // Kick off (don't block on) loading variation attribute/value suggestions
                // so the "Add/Edit Variable Part" dropdowns are ready when opened.
                ensureVariationOptionsLoaded();
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
                                <strong>Retail Price:</strong> $${parseFloat(project.retail_price || 0).toFixed(2)}${project.variation_costs && project.variation_costs.length ? ' <span style="color:var(--text-dim);">(base; see per-variation prices below)</span>' : ''}<br>
                                <strong>BOM Cost${project.variation_costs && project.variation_costs.length ? ' (avg., all variations)' : ''}:</strong> $${parseFloat(project.total_bom_cost || 0).toFixed(2)}<br>
                                <strong>Profit per Kit${project.variation_costs && project.variation_costs.length ? ' (avg.)' : ''}:</strong> <span style="color: ${parseFloat(project.profit_per_kit || 0) >= 0 ? 'var(--success)' : 'var(--danger)'};">$${parseFloat(project.profit_per_kit || 0).toFixed(2)}</span><br>
                                <strong>Margin${project.variation_costs && project.variation_costs.length ? ' (avg.)' : ''}:</strong> ${parseFloat(project.profit_margin_percent || 0).toFixed(1)}%
                            </div>
                        </div>

                        ${(project.variation_costs && project.variation_costs.length) ? `
                        <div style="margin-bottom: 1.5rem;">
                            <table class="data-table" style="margin:0;">
                                <thead>
                                    <tr>
                                        <th>Variation</th>
                                        <th>Price</th>
                                        <th>Kit Cost</th>
                                        <th>Profit per Kit</th>
                                        <th>Margin</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${project.variation_costs.map(vc => `
                                        <tr>
                                            <td>${escHtml(vc.label)}</td>
                                            <td>$${parseFloat(vc.price).toFixed(2)}${!vc.price_is_live ? ' <span style="color:var(--text-dim);font-size:0.85em;" title="No WooCommerce variation mapping found for this combination, so using the project\'s base retail price instead of a live variation price.">(base price)</span>' : ''}</td>
                                            <td>$${parseFloat(vc.cost).toFixed(2)}</td>
                                            <td style="color: ${vc.profit >= 0 ? 'var(--success)' : 'var(--danger)'};">$${parseFloat(vc.profit).toFixed(2)}</td>
                                            <td style="color: ${vc.margin_percent >= 0 ? 'var(--success)' : 'var(--danger)'};">${parseFloat(vc.margin_percent).toFixed(1)}%</td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                            <p style="color: var(--text-dim); font-size: 0.8em; margin-top: 4px;">Price is pulled live from each variation's mapped WooCommerce listing, since variations can be priced differently. "(base price)" means no WooCommerce variation mapping was found, so the project's shared retail price was used instead. "Profit per Kit" and "Margin" above the table are averaged across all variations for a single at-a-glance number; use this table for the real per-variation figures.</p>
                        </div>
                        ` : ''}

                        <div class="stats-grid" style="margin-bottom: 1.5rem;">
                            <div class="stat-card" style="border-left-color: var(--accent-secondary);">
                                <div class="stat-value" style="color: var(--accent-secondary);">${project.buildable_kits || 0}</div>
                                <div class="stat-label">Kits Buildable</div>
                            </div>
                            <div class="stat-card" style="border-left-color: var(--warning);">
                                <div class="stat-value" style="color: var(--warning);">$${parseFloat(project.total_inventory_value || 0).toFixed(2)}</div>
                                <div class="stat-label">Buildable Kit Value</div>
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
                    <td>$${parseFloat(p.unit_cost || 0).toFixed(2)}${p.cost_is_overridden
                        ? ` <span title="Manually overridden. Automatic cost is $${parseFloat(p.auto_unit_cost || 0).toFixed(2)}" style="font-size:0.72em;color:var(--warning);font-weight:600;">(override)</span>`
                        : ((p.pending_qty || 0) > 0 ? ` <span title="Blended with ${p.pending_qty} unit(s) on order not yet received, so this reflects their real ordered cost" style="font-size:0.72em;color:var(--info);font-weight:600;">(incl. pending)</span>` : '')}</td>
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
                            data-is-variable="${isVariable ? '1' : '0'}"
                            data-cost-override="${p.cost_is_overridden ? parseFloat(p.unit_cost).toFixed(4) : ''}"
                            data-auto-cost="${parseFloat(p.auto_unit_cost || 0).toFixed(4)}">Edit</button>
                        <button class="btn btn-small btn-danger" onclick="removeProjectPart(${p.id}, ${project.id})">Remove</button>
                    </td>
                </tr>`;
            }).join('');

            const totalRow = (bomDragMode || bomSearchQuery.trim())
                ? ''
                : `<tr style="font-weight: bold; background: var(--bg-light);">
                       <td colspan="6" style="text-align: right;">Fixed Parts BOM Cost:</td>
                       <td colspan="3">$${parseFloat(project.fixed_bom_cost ?? project.total_bom_cost ?? 0).toFixed(2)}</td>
                   </tr>`;

            // Realistic per-kit cost (fixed parts + that variation's parts) for each variation combo
            const variationCostRows = (bomDragMode || bomSearchQuery.trim() || !(project.variation_costs || []).length)
                ? ''
                : project.variation_costs.map(vc => `
                    <tr style="background: var(--bg-card-alt-row);">
                        <td colspan="6" style="text-align: right;">Kit Cost, ${escHtml(vc.label)}:</td>
                        <td colspan="3">$${parseFloat(vc.cost).toFixed(2)}</td>
                    </tr>`).join('');

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
                        ${variationCostRows}
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
            
            // Add summary rows
            csv += `\n"FIXED PARTS BOM COST",,,,,$${parseFloat(project.fixed_bom_cost ?? project.total_bom_cost ?? 0).toFixed(2)}\n`;
            (project.variation_costs || []).forEach(vc => {
                const label = (vc.label || '').replace(/"/g, '""');
                csv += `"KIT COST: ${label}",,,,,$${parseFloat(vc.cost).toFixed(2)}\n`;
            });
            
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

        async function ensureVariationOptionsLoaded() {
            try {
                const resp = await fetch('api.php?action=get_variation_options');
                const data = await resp.json();
                variationAttrOptions = data.attributes || {};
            } catch (error) {}
            return variationAttrOptions;
        }

        // Values already used for a given attribute name (case-insensitive match);
        // falls back to every value ever used if the attribute is blank/unrecognized.
        function variationValueOptionsFor(attrName) {
            const match = Object.keys(variationAttrOptions).find(
                k => k.toLowerCase() === (attrName || '').trim().toLowerCase()
            );
            const values = match ? variationAttrOptions[match] : Object.values(variationAttrOptions).flat();
            return [...new Set(values)].sort();
        }

        function refreshVarValueDatalist(attrInputId, datalistId) {
            const attrVal = document.getElementById(attrInputId).value;
            const dl = document.getElementById(datalistId);
            if (dl) dl.innerHTML = variationValueOptionsFor(attrVal).map(v => `<option value="${escHtml(v)}">`).join('');
        }

        async function addVariablePartToProject(projectId) {
            const ensureParts = parts.length === 0 ? loadParts() : Promise.resolve();
            await Promise.all([ensureParts, ensureVariationOptionsLoaded()]);
            openAddVariablePartModal(projectId);
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
                            <label class="form-label">Cost Override <span style="color:var(--text-dim);font-weight:400;">(optional; leave blank to use the part's average cost automatically)</span></label>
                            <input type="number" id="partCostOverride" class="form-input" min="0" step="0.0001" placeholder="Auto">
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
                formData.append('cost_override', document.getElementById('partCostOverride').value);
                formData.append('notes', document.getElementById('partNotes').value);
                // variation_attribute and variation_value default to '' (fixed part)

                try {
                    await fetch('api.php', { method: 'POST', body: formData });
                    modal.remove();
                    document.querySelector('.modal.active')?.remove();
                    viewProject(projectId);
                    refreshDashboardIfVisible();
                } catch (error) {
                    alert('Error adding part to project');
                }
            });
        }

        function openAddVariablePartModal(projectId) {
            const existingAttrs = Object.keys(variationAttrOptions).sort();

            const modal = createModal(
                'Add Variable Part to BOM',
                `
                    <p style="color: var(--text-secondary); margin-bottom: 1rem; font-size: 0.9em;">
                        Variable parts differ per product variation. Each attribute name (e.g. "Connector") can have multiple options, one part per option value (e.g. "Male", "Female").
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
                                <input type="text" id="varAttrName" class="form-input" placeholder="e.g. Connector" list="attrNameList" required
                                    oninput="refreshVarValueDatalist('varAttrName','attrValueList')">
                                <datalist id="attrNameList">
                                    ${existingAttrs.map(a => `<option value="${escHtml(a)}">`).join('')}
                                </datalist>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Attribute Value</label>
                                <input type="text" id="varAttrValue" class="form-input" placeholder="e.g. Male pigtail" list="attrValueList" required>
                                <datalist id="attrValueList">
                                    ${variationValueOptionsFor('').map(v => `<option value="${escHtml(v)}">`).join('')}
                                </datalist>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Quantity Required per Kit</label>
                            <input type="number" id="varPartQty" class="form-input" min="1" value="1" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Cost Override <span style="color:var(--text-dim);font-weight:400;">(optional; leave blank to use the part's average cost automatically)</span></label>
                            <input type="number" id="varPartCostOverride" class="form-input" min="0" step="0.0001" placeholder="Auto">
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
                formData.append('cost_override', document.getElementById('varPartCostOverride').value);
                formData.append('variation_attribute', document.getElementById('varAttrName').value.trim());
                formData.append('variation_value', document.getElementById('varAttrValue').value.trim());

                try {
                    await fetch('api.php', { method: 'POST', body: formData });
                    modal.remove();
                    document.querySelector('.modal.active')?.remove();
                    viewProject(projectId);
                    refreshDashboardIfVisible();
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
            const currentOverride = btn.dataset.costOverride || '';
            const autoCost = parseFloat(btn.dataset.autoCost || '0');

            const variationFields = isVariable ? `
                <div class="form-group">
                    <label class="form-label">Variation Attribute <span style="color:var(--text-dim);font-weight:400;">(e.g. "Connector")</span></label>
                    <input type="text" id="editPartAttr" class="form-input" value="${currentAttr.replace(/"/g,'&quot;')}" list="editAttrNameList" required
                        oninput="refreshVarValueDatalist('editPartAttr','editAttrValueList')">
                    <datalist id="editAttrNameList">
                        ${Object.keys(variationAttrOptions).sort().map(a => `<option value="${escHtml(a)}">`).join('')}
                    </datalist>
                </div>
                <div class="form-group">
                    <label class="form-label">Variation Value <span style="color:var(--text-dim);font-weight:400;">(e.g. "Male")</span></label>
                    <input type="text" id="editPartVal" class="form-input" value="${currentVal.replace(/"/g,'&quot;')}" list="editAttrValueList" required>
                    <datalist id="editAttrValueList">
                        ${variationValueOptionsFor(currentAttr).map(v => `<option value="${escHtml(v)}">`).join('')}
                    </datalist>
                </div>` : '';

            const modal = createModal(
                isVariable ? 'Edit Variable Part' : 'Edit Part Quantity',
                `<form id="editPartQtyForm">
                    ${variationFields}
                    <div class="form-group">
                        <label class="form-label">Quantity Required per Kit</label>
                        <input type="number" id="editPartQty" class="form-input" min="1" value="${currentQty}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Cost Override <span style="color:var(--text-dim);font-weight:400;">(leave blank to use the automatic average cost, currently $${autoCost.toFixed(2)})</span></label>
                        <input type="number" id="editPartCostOverride" class="form-input" min="0" step="0.0001" placeholder="Auto ($${autoCost.toFixed(2)})" value="${currentOverride}">
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
                formData.append('cost_override', document.getElementById('editPartCostOverride').value);
                if (isVariable) {
                    formData.append('variation_attribute', document.getElementById('editPartAttr').value.trim());
                    formData.append('variation_value', document.getElementById('editPartVal').value.trim());
                }
                try {
                    await fetch('api.php', { method: 'POST', body: formData });
                    modal.remove();
                    viewProject(projectId);
                    refreshDashboardIfVisible();
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
                refreshDashboardIfVisible();
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
            if (!confirm(`Move "${name}" to trash?\n\nAll project data and BOM will be preserved. You can restore it from the trash bin.`)) return;
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

        function renderLeadTimeSection(checkins) {
            checkins = checkins || [];
            if (checkins.length === 0) return '';

            const DAY = 86400000;
            const fmtShort = d => (d.getMonth() + 1) + '/' + d.getDate();
            const plural = (n, w) => `${n} ${w}${n === 1 ? '' : 's'}`;

            const completed = checkins
                .filter(c => c.received == 1 && c.received_at)
                .map(c => {
                    const orderDate = new Date(c.purchase_date + 'T00:00:00');
                    const receivedDate = new Date(c.received_at.split(' ')[0] + 'T00:00:00');
                    const rawDays = Math.round((receivedDate - orderDate) / DAY);
                    // Marked received the instant it was entered, with an order date the same
                    // day or later (server timestamps can run a day behind the entered date):
                    // that's stock already on hand being logged, not a tracked delivery.
                    // Counting it as a 0-day order drags the average down.
                    const onHand = c.created_at && c.received_at === c.created_at && rawDays <= 0;
                    return { onHand, date: orderDate, days: Math.max(0, rawDays), supplier: c.supplier_name || '', purchaseDate: c.purchase_date, receivedDate: c.received_at.split(' ')[0], quantity: c.quantity };
                })
                .filter(c => !c.onHand)
                .sort((a, b) => a.date - b.date);

            const pending = checkins.filter(c => c.received == 0);

            if (completed.length === 0) {
                return `
                    <hr style="margin: 1.5rem 0; border-color: var(--border-color);">
                    <h4>Delivery Time</h4>
                    <p style="color: var(--text-dim); text-align: center; padding: 1rem;">No completed orders yet. Mark an order received to start tracking delivery time for this part.</p>
                `;
            }

            const avg = completed.reduce((sum, c) => sum + c.days, 0) / completed.length;
            const tip = c => `<title>Ordered ${escHtml(c.purchaseDate)}, received ${escHtml(c.receivedDate)}: ${plural(c.days, 'day')}${c.supplier ? ' (' + escHtml(c.supplier) + ')' : ''}, qty ${c.quantity}</title>`;
            const W = 640, padL = 34, padR = 16, padT = 18, padB = 26;
            const plotW = W - padL - padR;

            // ── Bar chart: last 10 orders ──
            const recent = completed.slice(-10);
            const bH = 170, bPlotH = bH - padT - padB;
            const bMax = Math.max(avg, ...recent.map(c => c.days), 1);
            const bY = d => padT + bPlotH - (d / bMax) * bPlotH;
            const slot = plotW / recent.length;
            const barW = Math.min(44, slot * 0.6);
            const barsSvg = recent.map((c, i) => {
                const cx = padL + slot * (i + 0.5);
                const y = bY(c.days);
                const h = Math.max(1.5, padT + bPlotH - y);
                return `
                    <g>
                        <rect x="${(cx - barW / 2).toFixed(1)}" y="${(padT + bPlotH - h).toFixed(1)}" width="${barW.toFixed(1)}" height="${h.toFixed(1)}" rx="3" fill="var(--accent-primary)">${tip(c)}</rect>
                        <text x="${cx.toFixed(1)}" y="${(padT + bPlotH - h - 4).toFixed(1)}" text-anchor="middle" font-size="10.5" font-weight="600" fill="var(--text-primary)" font-family="var(--font-mono)">${c.days}d</text>
                        <text x="${cx.toFixed(1)}" y="${bH - 8}" text-anchor="middle" font-size="10" fill="var(--text-dim)">${fmtShort(c.date)}</text>
                    </g>`;
            }).join('');
            const bAvgY = bY(avg);
            const barChart = `
                <div style="font-size:0.85em; font-weight:600; color:var(--text-secondary); margin:6px 0 2px;">Last ${plural(recent.length, 'order')}: days from order to receipt</div>
                <svg viewBox="0 0 ${W} ${bH}" style="width:100%; max-width:${W}px; display:block;">
                    <line x1="${padL}" y1="${padT + bPlotH}" x2="${W - padR}" y2="${padT + bPlotH}" stroke="var(--border-card)" stroke-width="1"/>
                    ${barsSvg}
                    <line x1="${padL}" y1="${bAvgY.toFixed(1)}" x2="${W - padR}" y2="${bAvgY.toFixed(1)}" stroke="var(--warning)" stroke-width="1.5" stroke-dasharray="4,3"/>
                    <text x="${W - padR}" y="${(bAvgY - 4).toFixed(1)}" text-anchor="end" font-size="10" fill="var(--warning)" font-weight="600">avg ${avg.toFixed(1)}d</text>
                </svg>`;

            // ── Line chart: trend over time (x scaled by real order date) ──
            let trendChart = '';
            if (completed.length >= 2) {
                const lH = 170, lPlotH = lH - padT - padB;
                const t0 = completed[0].date.getTime(), t1 = completed[completed.length - 1].date.getTime();
                const span = Math.max(t1 - t0, DAY);
                const lX = t => padL + ((t - t0) / span) * plotW;
                // Least-squares trend line (needs 3+ points to mean anything)
                let fit = null;
                if (completed.length >= 3) {
                    const xs = completed.map(c => (c.date.getTime() - t0) / DAY), ys = completed.map(c => c.days);
                    const mx = xs.reduce((a, b) => a + b, 0) / xs.length, my = ys.reduce((a, b) => a + b, 0) / ys.length;
                    const sxx = xs.reduce((a, x) => a + (x - mx) ** 2, 0);
                    const slope = sxx ? xs.reduce((a, x, i) => a + (x - mx) * (ys[i] - my), 0) / sxx : 0;
                    fit = { slope, a: my - slope * mx, b: my + slope * ((span / DAY) - mx) };
                }
                const lMax = Math.max(...completed.map(c => c.days), fit ? Math.max(fit.a, fit.b) : 0, 1);
                const lY = d => padT + lPlotH - (Math.max(0, d) / lMax) * lPlotH;
                const pts = completed.map(c => ({ x: lX(c.date.getTime()), y: lY(c.days), c }));
                const path = pts.map((p, i) => (i ? 'L' : 'M') + p.x.toFixed(1) + ',' + p.y.toFixed(1)).join(' ');
                const grid = [0, lMax / 2, lMax].map(v => `
                    <line x1="${padL}" y1="${lY(v).toFixed(1)}" x2="${W - padR}" y2="${lY(v).toFixed(1)}" stroke="var(--border-card)" stroke-width="1"/>
                    <text x="${padL - 6}" y="${(lY(v) + 3).toFixed(1)}" text-anchor="end" font-size="10" fill="var(--text-dim)">${Math.round(v)}</text>`).join('');
                let trendNote = 'Add a third completed order to see a trend line.';
                let fitSvg = '';
                if (fit) {
                    fitSvg = `<line x1="${padL}" y1="${lY(fit.a).toFixed(1)}" x2="${W - padR}" y2="${lY(fit.b).toFixed(1)}" stroke="var(--accent-secondary)" stroke-width="1.5" stroke-dasharray="6,4" opacity="0.8"/>`;
                    const perMonth = fit.slope * 30;
                    trendNote = Math.abs(perMonth) < 0.5
                        ? 'Trend: holding steady.'
                        : `Trend: getting ${perMonth > 0 ? 'slower' : 'faster'} by about ${Math.abs(perMonth).toFixed(1)} days per month.`;
                }
                trendChart = `
                    <div style="font-size:0.85em; font-weight:600; color:var(--text-secondary); margin:14px 0 2px;">Delivery time trend</div>
                    <svg viewBox="0 0 ${W} ${lH}" style="width:100%; max-width:${W}px; display:block;">
                        ${grid}
                        ${fitSvg}
                        <path d="${path}" fill="none" stroke="var(--accent-primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        ${pts.map(p => `<circle cx="${p.x.toFixed(1)}" cy="${p.y.toFixed(1)}" r="4" fill="var(--accent-primary)" stroke="var(--bg-card)" stroke-width="1.5">${tip(p.c)}</circle>`).join('')}
                        <text x="${padL}" y="${lH - 6}" font-size="10" fill="var(--text-dim)">${escHtml(completed[0].purchaseDate)}</text>
                        <text x="${W - padR}" y="${lH - 6}" text-anchor="end" font-size="10" fill="var(--text-dim)">${escHtml(completed[completed.length - 1].purchaseDate)}</text>
                    </svg>
                    <div style="font-size:0.8em; color:var(--text-dim);">${trendNote}</div>`;
            }

            let etaHtml = '';
            if (pending.length > 0) {
                const today = new Date(); today.setHours(0, 0, 0, 0);
                etaHtml = `
                    <div style="margin-top:10px; font-size:0.85em; color: var(--text-secondary);">
                        ${pending.map(p => {
                            const orderDate = new Date(p.purchase_date + 'T00:00:00');
                            const eta = new Date(orderDate.getTime() + Math.round(avg) * DAY);
                            const etaStr = eta.getFullYear() + '-' + String(eta.getMonth() + 1).padStart(2, '0') + '-' + String(eta.getDate()).padStart(2, '0');
                            const waited = Math.round((today - orderDate) / DAY);
                            const label = eta < today
                                ? `<strong style="color:var(--danger);">overdue</strong> (expected ${etaStr}, waiting ${plural(waited, 'day')} so far)`
                                : `est. arrival <strong>${etaStr}</strong>`;
                            return `Pending order from ${escHtml(p.purchase_date)}${p.supplier_name ? ' (' + escHtml(p.supplier_name) + ')' : ''}: ${label}, based on ${avg.toFixed(1)}d avg`;
                        }).join('<br>')}
                    </div>
                `;
            }

            return `
                <hr style="margin: 1.5rem 0; border-color: var(--border-color);">
                <h4>Delivery Time</h4>
                <p style="margin: 4px 0 10px 0; font-size: 0.9em; color: var(--text-secondary);">
                    Average <strong>${avg.toFixed(1)} day${avg === 1 ? '' : 's'}</strong> from order to receipt, based on ${plural(completed.length, 'completed order')}.
                </p>
                ${barChart}
                ${trendChart}
                ${etaHtml}
            `;
        }

        // Unit cost per order over time. unit_cost on a checkin is gross total / qty,
        // so it already includes whatever shipping/tax was folded into that order.
        function renderUnitCostSection(checkins) {
            const DAY = 86400000;
            const pts = (checkins || [])
                .filter(c => c.purchase_date && parseFloat(c.unit_cost) > 0)
                .map(c => ({
                    date: new Date(c.purchase_date + 'T00:00:00'),
                    purchaseDate: c.purchase_date,
                    cost: parseFloat(c.unit_cost),
                    qty: parseInt(c.quantity, 10) || 0,
                    supplier: c.supplier_name || '',
                    pending: c.received == 0
                }))
                .sort((a, b) => a.date - b.date);
            if (pts.length === 0) return '';

            const money = v => '$' + (v > 0 && v < 0.1 ? v.toFixed(4) : v.toFixed(2));
            const first = pts[0], last = pts[pts.length - 1];
            const totalQty = pts.reduce((s, p) => s + p.qty, 0);
            const wAvg = totalQty > 0 ? pts.reduce((s, p) => s + p.cost * p.qty, 0) / totalQty : last.cost;

            let summary = `Latest <strong>${money(last.cost)}</strong>/unit (${escHtml(last.purchaseDate)}), weighted average ${money(wAvg)} across ${pts.length} order${pts.length === 1 ? '' : 's'}.`;
            // Compare against the previous order rather than the first: early rows are often
            // homebrew prototypes at a few cents, which make a first-to-last % meaningless.
            if (pts.length >= 2) {
                const prev = pts[pts.length - 2];
                const pct = (last.cost - prev.cost) / prev.cost * 100;
                const dir = Math.abs(pct) < 0.5 ? 'unchanged' : (pct > 0 ? `<strong style="color:var(--danger);">up ${pct.toFixed(1)}%</strong>` : `<strong style="color:var(--success);">down ${Math.abs(pct).toFixed(1)}%</strong>`);
                summary += ` That's ${dir} from the previous order (${money(prev.cost)} on ${escHtml(prev.purchaseDate)}).`;
            }

            if (pts.length < 2) {
                return `
                    <hr style="margin: 1.5rem 0; border-color: var(--border-color);">
                    <h4>Cost Per Unit</h4>
                    <p style="margin: 4px 0 10px 0; font-size: 0.9em; color: var(--text-secondary);">${summary}</p>
                    <p style="color: var(--text-dim); font-size: 0.85em;">Log a second order to see the cost trend.</p>
                `;
            }

            const W = 640, H = 190, padL = 58, padR = 16, padT = 18, padB = 26;
            const plotW = W - padL - padR, plotH = H - padT - padB;
            const t0 = first.date.getTime(), span = Math.max(last.date.getTime() - t0, DAY);
            const X = p => padL + ((p.date.getTime() - t0) / span) * plotW;
            const costs = pts.map(p => p.cost);
            let lo = Math.min(...costs), hi = Math.max(...costs);
            const pad = (hi - lo) * 0.15 || hi * 0.1 || 1;
            lo = Math.max(0, lo - pad); hi = hi + pad;
            const Y = v => padT + plotH - ((v - lo) / (hi - lo)) * plotH;

            const grid = [lo, (lo + hi) / 2, hi].map(v => `
                <line x1="${padL}" y1="${Y(v).toFixed(1)}" x2="${W - padR}" y2="${Y(v).toFixed(1)}" stroke="var(--border-card)" stroke-width="1"/>
                <text x="${padL - 6}" y="${(Y(v) + 3).toFixed(1)}" text-anchor="end" font-size="10" fill="var(--text-dim)" font-family="var(--font-mono)">${money(v)}</text>`).join('');
            const coords = pts.map(p => ({ x: X(p), y: Y(p.cost), p }));
            const path = coords.map((c, i) => (i ? 'L' : 'M') + c.x.toFixed(1) + ',' + c.y.toFixed(1)).join(' ');
            const avgY = Y(wAvg);
            const tip = p => `<title>${escHtml(p.purchaseDate)}: ${money(p.cost)}/unit, qty ${p.qty}${p.supplier ? ' (' + escHtml(p.supplier) + ')' : ''}${p.pending ? ', not received yet' : ''}</title>`;
            const dots = coords.map(c => c.p.pending
                ? `<circle cx="${c.x.toFixed(1)}" cy="${c.y.toFixed(1)}" r="4.5" fill="var(--bg-card)" stroke="var(--accent-primary)" stroke-width="2">${tip(c.p)}</circle>`
                : `<circle cx="${c.x.toFixed(1)}" cy="${c.y.toFixed(1)}" r="4.5" fill="var(--accent-primary)" stroke="var(--bg-card)" stroke-width="1.5">${tip(c.p)}</circle>`).join('');
            const hasPending = pts.some(p => p.pending);

            return `
                <hr style="margin: 1.5rem 0; border-color: var(--border-color);">
                <h4>Cost Per Unit</h4>
                <p style="margin: 4px 0 10px 0; font-size: 0.9em; color: var(--text-secondary);">${summary}</p>
                <svg viewBox="0 0 ${W} ${H}" style="width:100%; max-width:${W}px; display:block;">
                    ${grid}
                    <line x1="${padL}" y1="${avgY.toFixed(1)}" x2="${W - padR}" y2="${avgY.toFixed(1)}" stroke="var(--warning)" stroke-width="1.5" stroke-dasharray="4,3"/>
                    <text x="${padL + 4}" y="${(avgY - 4).toFixed(1)}" font-size="10" fill="var(--warning)" font-weight="600">avg ${money(wAvg)}</text>
                    <path d="${path}" fill="none" stroke="var(--accent-primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    ${dots}
                    <text x="${padL}" y="${H - 6}" font-size="10" fill="var(--text-dim)">${escHtml(first.purchaseDate)}</text>
                    <text x="${W - padR}" y="${H - 6}" text-anchor="end" font-size="10" fill="var(--text-dim)">${escHtml(last.purchaseDate)}</text>
                </svg>
                <div style="font-size:0.8em; color:var(--text-dim);">Cost per unit includes any shipping and fees entered with each order.${hasPending ? ' Hollow dots are orders not received yet.' : ''} Hover a dot for details.</div>
            `;
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
                            <h4>Orders &amp; Purchase History</h4>
                            <button class="btn btn-primary btn-small" onclick="checkinInventory(${part.id}); document.querySelector('.modal.active')?.remove();">+ Record Order</button>
                        </div>
                        ${part.checkins && part.checkins.length > 0 ? `
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Order Date</th>
                                        <th>Status</th>
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
                                        <tr style="${c.received == 0 ? 'background:#fffbeb;' : ''}">
                                            <td>
                                                <div>${c.purchase_date}</div>
                                                ${c.received == 1 && c.received_at ? `<div style="font-size:0.78em;color:var(--text-dim);">Rcvd: ${c.received_at.split(' ')[0]}</div>` : ''}
                                            </td>
                                            <td>
                                                ${c.received == 1
                                                    ? '<span style="display:inline-block;padding:2px 8px;border-radius:3px;font-size:0.8em;background:#d1fae5;color:#065f46;font-weight:600;">Received</span>'
                                                    : '<span style="display:inline-block;padding:2px 8px;border-radius:3px;font-size:0.8em;background:#fef3c7;color:#92400e;font-weight:600;">Pending</span>'
                                                }
                                            </td>
                                            <td>${c.supplier_name || '-'}</td>
                                            <td>${c.quantity}</td>
                                            <td>$${parseFloat(c.unit_cost).toFixed(4)}</td>
                                            <td>$${parseFloat(c.total_cost).toFixed(2)}</td>
                                            <td style="max-width: 200px; overflow: hidden; text-overflow: ellipsis;">${c.notes || '-'}</td>
                                            <td>
                                                ${c.received == 0 ? `<button class="btn btn-small btn-primary" onclick="markReceived(${c.id}, ${part.id}, ${c.quantity}, '${escHtml(part.part_name || '').replace(/'/g, "\\'")}', '${escHtml(c.supplier_name || '').replace(/'/g, "\\'")}')">Mark Received</button>` : ''}
                                                <button class="btn btn-small" onclick="cloneCheckin(${c.id}, ${part.id})">Clone</button>
                                                <button class="btn btn-small" onclick="editCheckin(${c.id}, ${part.id})">Edit</button>
                                                <button class="btn btn-small btn-danger" onclick="deleteCheckin(${c.id}, ${part.id}, ${c.received})">Delete</button>
                                            </td>
                                        </tr>
                                    `).join('')}
                                    <tr style="font-weight: bold; background: var(--bg-light);">
                                        <td colspan="6" style="text-align: right;">Total Spent (received):</td>
                                        <td colspan="2">$${part.checkins.filter(c => c.received == 1).reduce((sum, c) => sum + parseFloat(c.total_cost), 0).toFixed(2)}</td>
                                    </tr>
                                </tbody>
                            </table>
                        ` : '<p style="color: var(--text-dim); text-align: center; padding: 2rem;">No orders yet. Click "+ Record Order" to log your first parts order.</p>'}
                        ${renderLeadTimeSection(part.checkins)}
                        ${renderUnitCostSection(part.checkins)}
                        <hr style="margin: 1.5rem 0; border-color: var(--border-color);">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                            <h4>Stock Adjustments</h4>
                            <button class="btn btn-primary btn-small" onclick="adjustStock(${part.id}); document.querySelector('.modal.active')?.remove();">+ Adjust Stock</button>
                        </div>
                        <p style="margin: -0.5rem 0 1rem 0; font-size: 0.85rem; color: var(--text-secondary);">
                            Use this for stock that leaves or returns outside a normal purchase or sale, e.g. raw parts sent to JLCPCB for PCBA and consumed there, damage/loss, or a manual count correction.
                        </p>
                        ${part.adjustments && part.adjustments.length > 0 ? `
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Reason</th>
                                        <th>Change</th>
                                        <th>Note</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${part.adjustments.map(a => `
                                        <tr>
                                            <td>${a.created_at.split(' ')[0]}</td>
                                            <td>${a.reason}</td>
                                            <td style="color: ${a.quantity_change < 0 ? 'var(--danger)' : 'var(--success)'}; font-weight: 600;">${a.quantity_change > 0 ? '+' : ''}${a.quantity_change}</td>
                                            <td style="max-width: 200px; overflow: hidden; text-overflow: ellipsis;">${a.note || '-'}</td>
                                            <td>
                                                <button class="btn btn-small btn-danger" onclick="deleteAdjustment(${a.id}, ${part.id})">Delete</button>
                                            </td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        ` : '<p style="color: var(--text-dim); text-align: center; padding: 1rem;">No adjustments recorded.</p>'}
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

        async function deleteCheckin(checkinId, partId, received) {
            const msg = (received == 1)
                ? 'Delete this received check-in? Inventory will be reduced by that quantity and weighted average cost will be recalculated.'
                : 'Delete this pending order? No inventory changes will be made.';
            if (!confirm(msg)) return;
            
            const formData = new FormData();
            formData.append('action', 'delete_checkin');
            formData.append('id', checkinId);
            formData.append('part_id', partId);

            try {
                const resp = await fetch('api.php', { method: 'POST', body: formData });
                const result = await resp.json();
                if (result.error) {
                    alert(result.error);
                    return;
                }
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
                    `Edit Order for ${part.part_name}`,
                    `
                        <form id="editCheckinForm">
                            <div class="form-group">
                                <label class="form-label">Quantity ${checkin.received == 1 ? 'Received' : 'Ordered'}</label>
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
                                    <input type="text" inputmode="decimal" id="editCheckinUnitCost" class="form-input money-input" value="${parseFloat(checkin.unit_cost).toFixed(4)}">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Gross Total ($)</label>
                                    <input type="text" inputmode="decimal" id="editCheckinGrossTotal" class="form-input money-input" value="${parseFloat(checkin.total_cost).toFixed(2)}">
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
                        const resp = await fetch('api.php', { method: 'POST', body: formData });
                        const result = await resp.json();
                        if (result.error) {
                            alert(result.error);
                            return;
                        }
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
            const checkinTitle = part ? `Record Order: ${part.part_name}` : 'Record Order';

            const modal = createModal(
                checkinTitle,
                `
                    <form id="checkinForm">
                        <div class="form-group">
                            <label class="form-label">Quantity Ordered</label>
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
                                <input type="text" inputmode="decimal" id="checkinUnitCost" class="form-input money-input" placeholder="e.g., 0.1250">
                                <small style="color: var(--text-secondary);">Per part cost</small>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Gross Total ($)</label>
                                <input type="text" inputmode="decimal" id="checkinGrossTotal" class="form-input money-input" placeholder="e.g., 25.50">
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
                            <label class="form-label">Order Date</label>
                            <input type="date" id="checkinDate" class="form-input" value="${new Date().toISOString().split('T')[0]}" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Notes</label>
                            <textarea id="checkinNotes" class="form-textarea" placeholder="e.g., Mouser order #12345, included $5 shipping"></textarea>
                        </div>
                        <div style="background: #f0fdf4; border: 1px solid #86efac; border-radius: 4px; padding: 0.75rem; margin-bottom: 1rem;">
                            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-weight: 500;">
                                <input type="checkbox" id="checkinReceived" style="width: 16px; height: 16px; cursor: pointer;">
                                Parts already received: update inventory now
                            </label>
                            <p style="margin: 0.4rem 0 0 1.5rem; font-size: 0.85rem; color: var(--text-secondary);">
                                Leave unchecked if parts are still in transit. You can mark them received later.
                            </p>
                        </div>
                        <div class="flex flex-gap">
                            <button type="submit" class="btn btn-primary">Save Order</button>
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
                formData.append('received', document.getElementById('checkinReceived').checked ? '1' : '0');

                try {
                    const resp = await fetch('api.php', { method: 'POST', body: formData });
                    const result = await resp.json();
                    if (result.error) {
                        alert(result.error);
                        return;
                    }
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

        function adjustStock(partId) {
            const part = parts.find(p => p.id === partId);
            const title = part ? `Adjust Stock: ${part.part_name}` : 'Adjust Stock';
            const reasonPresets = [
                'JLCPCB PCBA Consumption',
                'Damaged / Lost',
                'Manual Count Correction',
                'Other'
            ];

            const modal = createModal(
                title,
                `
                    <form id="adjustStockForm">
                        <div class="form-group">
                            <label class="form-label">Direction</label>
                            <select id="adjustDirection" class="form-input">
                                <option value="remove">Remove from stock</option>
                                <option value="add">Add to stock</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Quantity</label>
                            <input type="number" id="adjustQty" class="form-input" min="1" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Reason</label>
                            <select id="adjustReasonPreset" class="form-input">
                                ${reasonPresets.map(r => `<option value="${r}">${r}</option>`).join('')}
                            </select>
                        </div>
                        <div class="form-group" id="adjustReasonOtherGroup" style="display:none;">
                            <label class="form-label">Specify Reason</label>
                            <input type="text" id="adjustReasonOther" class="form-input" placeholder="e.g., Reworked into a different SKU">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Note</label>
                            <textarea id="adjustNote" class="form-textarea" placeholder="e.g., Sent 400 units to JLCPCB for KH1 mainboard PCBA order #12345"></textarea>
                        </div>
                        <div class="flex flex-gap">
                            <button type="submit" class="btn btn-primary">Save Adjustment</button>
                            <button type="button" class="btn" onclick="this.closest('.modal').remove()">Cancel</button>
                        </div>
                    </form>
                `
            );

            const reasonPresetSelect = document.getElementById('adjustReasonPreset');
            const reasonOtherGroup = document.getElementById('adjustReasonOtherGroup');
            reasonPresetSelect.addEventListener('change', () => {
                reasonOtherGroup.style.display = reasonPresetSelect.value === 'Other' ? 'block' : 'none';
            });

            document.getElementById('adjustStockForm').addEventListener('submit', async (e) => {
                e.preventDefault();

                const reason = reasonPresetSelect.value === 'Other'
                    ? document.getElementById('adjustReasonOther').value.trim()
                    : reasonPresetSelect.value;

                if (!reason) {
                    alert('Please specify a reason');
                    return;
                }

                const formData = new FormData();
                formData.append('action', 'save_inventory_adjustment');
                formData.append('part_id', partId);
                formData.append('direction', document.getElementById('adjustDirection').value);
                formData.append('quantity', document.getElementById('adjustQty').value);
                formData.append('reason', reason);
                formData.append('note', document.getElementById('adjustNote').value);

                try {
                    const response = await fetch('api.php', { method: 'POST', body: formData });
                    const result = await response.json();
                    if (result.error) {
                        alert(result.error);
                        return;
                    }
                    modal.remove();
                    loadParts();
                    loadDashboard();
                    viewPart(partId);
                } catch (error) {
                    alert('Error saving stock adjustment');
                }
            });
        }

        async function deleteAdjustment(adjustmentId, partId) {
            if (!confirm('Delete this adjustment? This will reverse its effect on current stock.')) return;

            const formData = new FormData();
            formData.append('action', 'delete_inventory_adjustment');
            formData.append('id', adjustmentId);

            try {
                const response = await fetch('api.php', { method: 'POST', body: formData });
                const result = await response.json();
                if (result.error) {
                    alert(result.error);
                    return;
                }
                loadParts();
                loadDashboard();
                viewPart(partId);
            } catch (error) {
                alert('Error deleting adjustment');
            }
        }

        async function cloneCheckin(checkinId, partId) {
            try {
                const response = await fetch(`api.php?action=get_part&id=${partId}`);
                const part = await response.json();
                const checkin = part.checkins.find(c => c.id === checkinId);
                if (!checkin) { alert('Could not load check-in data'); return; }

                const modal = createModal(
                    `Clone Order: ${part.part_name}`,
                    `
                        <p style="color: var(--text-secondary); margin-bottom: 1rem;">
                            Cloned from ${checkin.purchase_date}. Edit any fields before saving.
                        </p>
                        <form id="cloneCheckinForm">
                            <div class="form-group">
                                <label class="form-label">Quantity Ordered</label>
                                <input type="number" id="cloneCheckinQty" class="form-input" min="1" value="${checkin.quantity}" required>
                            </div>
                            <div style="background: #f0f9ff; padding: 1rem; border-radius: 4px; margin-bottom: 1rem;">
                                <strong style="color: var(--accent-primary);">💡 Enter EITHER unit cost OR gross total:</strong>
                            </div>
                            <div class="grid-2">
                                <div class="form-group">
                                    <label class="form-label">Unit Cost ($)</label>
                                    <input type="text" inputmode="decimal" id="cloneCheckinUnitCost" class="form-input money-input" value="${parseFloat(checkin.unit_cost).toFixed(4)}">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Gross Total ($)</label>
                                    <input type="text" inputmode="decimal" id="cloneCheckinGrossTotal" class="form-input money-input" value="${parseFloat(checkin.total_cost).toFixed(2)}">
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
                                <label class="form-label">Order Date</label>
                                <input type="date" id="cloneCheckinDate" class="form-input" value="${new Date().toISOString().split('T')[0]}" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Notes</label>
                                <textarea id="cloneCheckinNotes" class="form-textarea">${checkin.notes || ''}</textarea>
                            </div>
                            <div style="background: #f0fdf4; border: 1px solid #86efac; border-radius: 4px; padding: 0.75rem; margin-bottom: 1rem;">
                                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-weight: 500;">
                                    <input type="checkbox" id="cloneCheckinReceived" style="width: 16px; height: 16px; cursor: pointer;">
                                    Parts already received: update inventory now
                                </label>
                                <p style="margin: 0.4rem 0 0 1.5rem; font-size: 0.85rem; color: var(--text-secondary);">
                                    Leave unchecked if parts are still in transit.
                                </p>
                            </div>
                            <div class="flex flex-gap">
                                <button type="submit" class="btn btn-primary">Save Order</button>
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
                    formData.append('received', document.getElementById('cloneCheckinReceived').checked ? '1' : '0');

                    try {
                        const resp = await fetch('api.php', { method: 'POST', body: formData });
                        const result = await resp.json();
                        if (result.error) {
                            alert(result.error);
                            return;
                        }
                        modal.remove();
                        document.querySelector('.modal.active')?.remove();
                        viewPart(partId);
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

        function markReceived(checkinId, partId, quantity, partName, supplierName) {
            createModal(
                'Mark Order as Received',
                `
                    <div class="form-group">
                        <p>Mark this order as received?</p>
                        <p style="color:var(--text-secondary);font-size:0.9rem;">${quantity} &times; ${partName}${supplierName ? ' from ' + supplierName : ''}</p>
                        <p style="color:var(--text-secondary);font-size:0.9rem;">Inventory will be updated immediately.</p>
                    </div>
                    <div class="form-group">
                        <label style="display:flex;align-items:center;gap:0.5rem;font-weight:normal;cursor:pointer;">
                            <input type="checkbox" id="markReceivedSyncWc" checked>
                            Also push updated stock to WooCommerce
                        </label>
                    </div>
                    <div class="flex flex-gap">
                        <button type="button" class="btn btn-primary" id="markReceivedConfirmBtn" onclick="confirmMarkReceived(${checkinId}, ${partId})">Mark Received</button>
                        <button type="button" class="btn" onclick="this.closest('.modal').remove()">Cancel</button>
                    </div>
                `
            );
        }

        async function confirmMarkReceived(checkinId, partId) {
            const syncWc = document.getElementById('markReceivedSyncWc')?.checked;

            // Disable the button and show a spinner while the DB update runs
            const btn = document.getElementById('markReceivedConfirmBtn');
            const origText = btn?.innerHTML;
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<span style="display:inline-block;width:12px;height:12px;border:2px solid rgba(255,255,255,0.4);border-top-color:#fff;border-radius:50%;animation:spin 0.7s linear infinite;vertical-align:middle;margin-right:4px;"></span>Saving…';
            }

            const formData = new FormData();
            formData.append('action', 'mark_received');
            formData.append('id', checkinId);
            formData.append('part_id', partId);
            formData.append('skip_wc', '1');

            try {
                const response = await fetch('api.php', { method: 'POST', body: formData });
                const result = await response.json();
                if (result.error) {
                    if (btn) { btn.disabled = false; btn.innerHTML = origText; }
                    alert('Error: ' + result.error);
                    return;
                }

                // DB updated — close both the confirm modal and the part-detail modal behind it, then refresh
                document.querySelectorAll('.modal.active').forEach(m => m.remove());
                viewPart(partId);
                loadParts();
                loadDashboard();

                // Fire WC sync in the background for each affected project (no await), only if chosen
                if (syncWc && result.project_ids?.length) {
                    showWcSyncToast(result.project_ids.length);
                    for (const pid of result.project_ids) {
                        const fd = new FormData();
                        fd.append('action', 'wc_sync');
                        fd.append('project_id', pid);
                        fetch('api.php', { method: 'POST', body: fd }).catch(() => {});
                    }
                }
            } catch (error) {
                if (btn) { btn.disabled = false; btn.innerHTML = origText; }
                alert('Error marking order as received');
            }
        }

        function showWcSyncToast(count) {
            const existing = document.getElementById('wcSyncToast');
            if (existing) existing.remove();
            const toast = document.createElement('div');
            toast.id = 'wcSyncToast';
            toast.innerHTML = '&#x21BB; Syncing WooCommerce stock…';
            Object.assign(toast.style, {
                position: 'fixed', bottom: '20px', right: '20px', zIndex: '9999',
                background: 'var(--accent-primary)', color: '#fff',
                padding: '10px 16px', borderRadius: 'var(--radius-md)',
                fontSize: '0.85em', fontFamily: 'var(--font-body)',
                boxShadow: 'var(--shadow-modal)', opacity: '1',
                transition: 'opacity 0.5s ease'
            });
            document.body.appendChild(toast);
            setTimeout(() => { toast.style.opacity = '0'; setTimeout(() => toast.remove(), 500); }, 4000);
        }

        // Order Modal Functions
        // Manual order entry (single item). Editing existing orders — including
        // itemized WooCommerce orders — happens on order_detail.php instead.
        async function openOrderModal() {
            if (!projects || projects.length === 0) {
                await loadProjects();
            }

            const projectOptions = projects.map(p =>
                `<option value="${p.id}">${p.project_name}</option>`
            ).join('');

            const modal = createModal(
                'New Order',
                `
                    <form id="orderForm">
                        <div class="form-group">
                            <label class="form-label">Order Number</label>
                            <input type="text" id="orderNumber" class="form-input" value="ORD-${Date.now()}" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Project/Kit</label>
                            <select id="orderProject" class="form-select" required onchange="onNewOrderProjectChange(this.value)">
                                <option value="">Select project...</option>
                                ${projectOptions}
                            </select>
                        </div>
                        <div class="form-group" id="orderVariationGroup" style="display:none;">
                            <label class="form-label">Variation</label>
                            <select id="orderCombo" class="form-select"></select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Customer Name</label>
                            <input type="text" id="orderCustomer" class="form-input" value="" required>
                        </div>
                        <div class="grid-2">
                            <div class="form-group">
                                <label class="form-label">Email</label>
                                <input type="email" id="orderEmail" class="form-input" value="">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Phone</label>
                                <input type="tel" id="orderPhone" class="form-input" value="">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Callsign</label>
                            <input type="text" id="orderCallsign" class="form-input" value="">
                        </div>
                        <div class="grid-2">
                            <div class="form-group">
                                <label class="form-label">Quantity</label>
                                <input type="number" id="orderQty" class="form-input" value="1" min="1" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Price Paid ($)</label>
                                <input type="number" id="orderPrice" class="form-input" value="0" step="0.01" min="0" required>
                            </div>
                        </div>
                        <div class="grid-2">
                            <div class="form-group">
                                <label class="form-label">Order Date</label>
                                <input type="date" id="orderDate" class="form-input" value="${new Date().toISOString().split('T')[0]}" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Status</label>
                                <select id="orderStatus" class="form-select">
                                    <option value="pending">Pending</option>
                                    <option value="paid">Paid</option>
                                    <option value="shipped">Shipped</option>
                                    <option value="completed">Completed</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </div>
                        </div>
                        <div id="trackingNumberGroup" class="form-group" style="display: none;">
                            <label class="form-label">Tracking Number</label>
                            <input type="text" id="orderTracking" class="form-input" value="" placeholder="e.g., 1Z999AA10123456784">
                            <small style="color: var(--text-secondary); font-size: 0.875rem;">Enter tracking number for shipped orders</small>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Shipping Charge ($)</label>
                            <input type="number" id="orderShippingCharge" class="form-input" value="0" step="0.01" min="0">
                            <small style="color: var(--text-secondary); font-size: 0.875rem;">Actual shipping cost paid for P&L tracking</small>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Shipping Address</label>
                            <textarea id="orderAddress" class="form-textarea"></textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Notes</label>
                            <textarea id="orderNotes" class="form-textarea"></textarea>
                        </div>
                        <div class="flex flex-gap">
                            <button type="submit" class="btn btn-primary">Save Order</button>
                            <button type="button" class="btn" onclick="this.closest(\'.modal\').remove()">Cancel</button>
                        </div>
                    </form>
                `
            );

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
                formData.append('order_number', document.getElementById('orderNumber').value);
                formData.append('items', JSON.stringify([{
                    project_id: parseInt(document.getElementById('orderProject').value, 10),
                    combo_key: document.getElementById('orderCombo').value || '',
                    quantity: parseInt(document.getElementById('orderQty').value, 10) || 1,
                    price: parseFloat(document.getElementById('orderPrice').value) || 0,
                }]));
                formData.append('customer_name', document.getElementById('orderCustomer').value);
                formData.append('customer_email', document.getElementById('orderEmail').value);
                formData.append('customer_phone', document.getElementById('orderPhone').value);
                formData.append('customer_callsign', document.getElementById('orderCallsign').value);
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

        async function onNewOrderProjectChange(projectId) {
            const group = document.getElementById('orderVariationGroup');
            const select = document.getElementById('orderCombo');
            if (!projectId) { group.style.display = 'none'; select.innerHTML = ''; return; }
            try {
                const r = await fetch(`api.php?action=get_project_variations&project_id=${projectId}`);
                const data = await r.json();
                if (data.has_variations) {
                    select.innerHTML = data.combos.map(c => `<option value="${c.combo_key}">${formatPromoCombo(c.combo_key)}</option>`).join('');
                    group.style.display = 'block';
                } else {
                    select.innerHTML = '';
                    group.style.display = 'none';
                }
            } catch (e) { group.style.display = 'none'; }
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
            orders: { column: 'display_number', direction: 'desc' }
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
                
                // Number-aware so e.g. "WC-99" sorts before "WC-347"
                const cmp = String(aVal).localeCompare(String(bVal), undefined, { numeric: true, sensitivity: 'base' });
                return direction === 'asc' ? cmp : -cmp;
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
                        projectsHtml += `<tr>
                            <td>${p.project_name}</td>
                            <td>${p.units_sold} units</td>
                            <td style="text-align: right;">$${parseFloat(p.revenue).toLocaleString()}</td>
                            <td style="text-align: right;"><button class="btn btn-small" onclick="openProjectPLModal(${p.project_id})">P&amp;L</button></td>
                        </tr>`;
                    });
                } else {
                    projectsHtml += '<tr><td colspan="4">No sales yet</td></tr>';
                }
                projectsHtml += '</table>';
                document.getElementById('topProjects').innerHTML = projectsHtml;

                // Load the expenses list and the per-project selector alongside metrics
                loadBizExpenses();
                populateBusinessProjectSelect();

            } catch (error) {
                console.error('Error loading business metrics:', error);
                alert('Error loading business metrics. Make sure business_metrics.php is uploaded.');
            }
        }

        // Populates the "Per-Project P&L" dropdown on the Business tab. Fetched fresh
        // rather than relying on the global `projects` array, since a user landing
        // directly on the Business tab won't have triggered loadProjects() yet.
        async function populateBusinessProjectSelect() {
            const select = document.getElementById('businessProjectSelect');
            if (!select) return;
            const prevValue = select.value;
            try {
                const resp = await fetch('api.php?action=get_projects');
                const projs = await resp.json();
                const sorted = [...projs].sort((a, b) => a.project_name.localeCompare(b.project_name));
                select.innerHTML = sorted.map(p =>
                    `<option value="${p.id}">${p.project_name}${p.status !== 'active' ? ' (' + p.status + ')' : ''}</option>`
                ).join('');
                if (prevValue && sorted.some(p => String(p.id) === prevValue)) {
                    select.value = prevValue;
                }
            } catch (e) {
                select.innerHTML = '<option value="">Could not load projects</option>';
            }
        }

        async function openProjectPLModal(projectId) {
            if (!projectId) {
                const select = document.getElementById('businessProjectSelect');
                projectId = select ? select.value : null;
            }
            if (!projectId) {
                alert('Choose a project first.');
                return;
            }

            const period = document.getElementById('businessPeriod')?.value || 'all';
            const periodLabels = { all: 'All Time', trailing: 'Last 12 Months' };
            const periodLabel = periodLabels[period] || period;

            const modal = createModal('Project P&L', '<div style="text-align:center;padding:2rem;color:var(--text-dim);">Loading…</div>');

            try {
                const resp = await fetch(`business_metrics.php?action=get_project_pl&project_id=${projectId}&year=${period}`);
                const d = await resp.json();
                if (d.error) {
                    modal.querySelector('.modal-content').innerHTML = `
                        <div class="modal-header">
                            <h3 class="modal-title">Project P&amp;L</h3>
                            <button class="close-modal" onclick="this.closest('.modal').remove()">×</button>
                        </div>
                        <div style="color:var(--danger);padding:1rem;">Error: ${d.error}</div>
                    `;
                    return;
                }

                const fmt = (n) => '$' + Number(n || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                const marginColor = d.margin >= 0 ? 'var(--success)' : 'var(--danger)';
                const netColor = d.net_profit >= 0 ? 'var(--success)' : 'var(--danger)';

                let breakEvenHtml;
                if (d.total_invested <= 0) {
                    breakEvenHtml = `<div style="padding:0.75rem;color:var(--text-dim);font-size:0.9em;">No R&amp;D or giveaway costs recorded for this project, so nothing to break even against.</div>`;
                } else if (d.broke_even) {
                    breakEvenHtml = `<div style="padding:0.75rem;background:rgba(16,185,129,0.1);border-radius:4px;color:var(--success);font-weight:600;">✓ Broken even: gross profit has covered ${fmt(d.total_invested)} in R&amp;D/giveaway costs, with a surplus of ${fmt(d.gross_profit - d.total_invested)}.</div>`;
                } else {
                    breakEvenHtml = `<div style="padding:0.75rem;background:rgba(245,158,11,0.1);border-radius:4px;color:var(--warning);font-weight:600;">Not yet broken even. Needs ${fmt(d.breakeven_remaining)} more gross profit to cover ${fmt(d.total_invested)} in R&amp;D/giveaway costs.</div>`;
                }

                const content = `
                    <div style="margin-bottom:1rem;">
                        <div style="font-weight:600;font-size:1.1rem;">${d.project_name}</div>
                        <div style="color:var(--text-dim);font-size:0.85em;">${periodLabel} · ${d.units_sold} units sold in ${d.order_count} order${d.order_count === 1 ? '' : 's'}</div>
                    </div>
                    <table class="data-table">
                        <tr><td><strong>Revenue</strong></td><td style="text-align:right;">${fmt(d.revenue)}</td></tr>
                        <tr><td style="padding-left:2rem;">- Cost of Goods Sold</td><td style="text-align:right;">${fmt(d.cogs)}</td></tr>
                        <tr style="border-top:1px solid var(--border-color);">
                            <td><strong>Gross Profit</strong></td>
                            <td style="text-align:right;font-weight:600;color:${marginColor};">${fmt(d.gross_profit)} <span style="color:var(--text-dim);font-weight:400;font-size:0.85em;">(${d.margin.toFixed(1)}%)</span></td>
                        </tr>
                        <tr><td style="padding-left:2rem;">- Research &amp; Dev Expenses <span style="color:var(--text-dim);font-size:0.8em;">(all-time)</span></td><td style="text-align:right;">${fmt(d.research_expenses)}</td></tr>
                        <tr><td style="padding-left:2rem;">- Giveaway/Promo Cost <span style="color:var(--text-dim);font-size:0.8em;">(${d.giveaway_units} units, all-time)</span></td><td style="text-align:right;">${fmt(d.giveaway_cost)}</td></tr>
                        <tr style="border-top:2px solid var(--border-color);">
                            <td><strong>Net Profit</strong></td>
                            <td style="text-align:right;font-weight:bold;color:${netColor};">${fmt(d.net_profit)}</td>
                        </tr>
                    </table>
                    ${breakEvenHtml}
                    <div style="margin-top:0.75rem;font-size:0.8em;color:var(--text-dim);">
                        Revenue/COGS reflect the selected period; R&amp;D and giveaway costs are all-time (sunk costs). Shipping and store-wide overhead aren't attributed per project since a single order can span multiple products. See the main Business Dashboard P&amp;L for those.
                    </div>
                `;
                modal.querySelector('.modal-content').innerHTML = `
                    <div class="modal-header">
                        <h3 class="modal-title">Project P&amp;L</h3>
                        <div style="display: flex; align-items: center;">
                            <button class="expand-modal" onclick="toggleModalExpand(this)" title="Expand/Collapse">⛶</button>
                            <button class="close-modal" onclick="this.closest('.modal').remove()">×</button>
                        </div>
                    </div>
                    ${content}
                `;
            } catch (e) {
                modal.querySelector('.modal-content').innerHTML = `
                    <div class="modal-header">
                        <h3 class="modal-title">Project P&amp;L</h3>
                        <button class="close-modal" onclick="this.closest('.modal').remove()">×</button>
                    </div>
                    <div style="color:var(--danger);padding:1rem;">Request failed: ${e.message}</div>
                `;
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
                list.innerHTML = '<li class="task-empty">No tasks yet. Add one below.</li>';
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

            const totalUnreviewed = parseInt(data.total_unreviewed || 0);
            document.getElementById('betaSummaryCards').innerHTML = `
                <div class="stat-card"><div class="stat-value">${totalBuilders}</div><div class="stat-label">Builders</div></div>
                <div class="stat-card"><div class="stat-value">${avgCompletion}%</div><div class="stat-label">Avg. Completion</div></div>
                <div class="stat-card stat-low"><div class="stat-value">${totalIssues}</div><div class="stat-label">Steps w/ Trouble</div></div>
                <div class="stat-card ${totalUnreviewed > 0 ? 'stat-low' : ''}"><div class="stat-value">${totalUnreviewed}</div><div class="stat-label">Needs Review</div></div>
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
                    '<tr><td colspan="6" style="text-align:center;padding:2rem;color:var(--text-dim);">No beta builders have submitted feedback yet.</td></tr>';
                return;
            }
            document.getElementById('betaBuilderBody').innerHTML = builders.map(b => {
                const issues = parseInt(b.trouble_count||0);
                const saved  = parseInt(b.steps_saved||0);
                const unreviewed = parseInt(b.unreviewed_count||0);
                const lastAgo = b.last_active ? timeAgo(b.last_active) : '—';
                return `<tr style="${unreviewed > 0 ? 'background:#fffbeb;' : ''}">
                    <td style="font-family:var(--font-mono);font-weight:600;">${escHtml(b.callsign)}</td>
                    <td>${saved} / ${TOTAL_STEPS}</td>
                    <td>${issues > 0
                        ? `<span style="color:#ef4444;font-weight:600;">⚠️ ${issues}</span>`
                        : `<span style="color:#10b981;">✓ 0</span>`}</td>
                    <td>${unreviewed > 0
                        ? `<span style="color:#b45309;font-weight:600;">● ${unreviewed} new</span>`
                        : `<span style="color:#10b981;">✓ Reviewed</span>`}</td>
                    <td style="color:var(--text-dim);font-size:0.85rem;">${escHtml(lastAgo)}</td>
                    <td><button class="btn btn-secondary" style="font-size:0.78rem;padding:4px 10px;"
                        onclick="openBetaDetail('${escHtml(b.callsign)}')">View →</button></td>
                </tr>`;
            }).join('');
        }

        let currentBetaCallsign = null;

        async function openBetaDetail(callsign) {
            currentBetaCallsign = callsign;
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

                const buildTime = (key === 'general' && r && r.build_time_estimate)
                    ? `<div style="font-size:0.82rem;color:var(--text-dim);margin-top:4px;">⏱ Total build time: <strong style="color:var(--text-primary);">${escHtml(r.build_time_estimate)}</strong></div>`
                    : '';

                const replyVal = (r && r.admin_reply) ? r.admin_reply : '';
                const isReviewed = !!(r && parseInt(r.reviewed) === 1);

                const reviewedToggle = r ? `
                    <label style="display:flex;align-items:center;gap:5px;font-size:0.78rem;color:${isReviewed ? 'var(--success)' : '#b45309'};cursor:pointer;flex-shrink:0;white-space:nowrap;">
                        <input type="checkbox" id="reviewed_${key}" ${isReviewed ? 'checked' : ''}
                            onchange="markStepReviewed('${escHtml(callsign)}','${key}', this.checked)"
                            style="width:14px;height:14px;cursor:pointer;">
                        Reviewed
                    </label>` : '';

                html += `<div style="padding:10px 0;border-bottom:1px solid var(--border-card);">
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;">
                        <div style="font-size:0.84rem;font-weight:600;color:var(--text-primary);">${escHtml(label)}</div>
                        ${reviewedToggle}
                    </div>
                    ${detail}
                    ${buildTime}
                    ${r && r.feedback ? `<div style="font-size:0.84rem;color:var(--text-secondary);margin-top:5px;font-style:italic;">"${escHtml(r.feedback)}"</div>` : ''}
                    ${photosHtml}
                    <div style="margin-top:8px;">
                        <textarea id="reply_${key}" placeholder="Reply to ${escHtml(callsign)}…"
                            style="width:100%;min-height:50px;padding:8px 10px;border:1px solid var(--border-card);border-radius:6px;font-family:var(--font-body);font-size:0.82rem;color:var(--text-primary);background:var(--bg-card);resize:vertical;">${escHtml(replyVal)}</textarea>
                        <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:4px;align-items:center;">
                            <span id="replyStatus_${key}" style="font-size:0.72rem;color:var(--success);opacity:0;transition:opacity 0.3s;">✓ Saved</span>
                            <button class="btn btn-secondary" style="font-size:0.75rem;padding:3px 10px;"
                                onclick="saveBetaReply('${escHtml(callsign)}','${key}')">Save Reply</button>
                        </div>
                    </div>
                </div>`;
            });

            document.getElementById('betaDetailBody').innerHTML = html;
        }

        async function saveBetaReply(callsign, stepKey) {
            const ta = document.getElementById('reply_' + stepKey);
            const statusEl = document.getElementById('replyStatus_' + stepKey);
            const fd = new FormData();
            fd.append('action', 'kh1_beta_save_reply');
            fd.append('callsign', callsign);
            fd.append('step_key', stepKey);
            fd.append('reply', ta.value.trim());
            try {
                const resp = await fetch('api.php', { method: 'POST', body: fd });
                const d = await resp.json();
                if (d.success) {
                    statusEl.style.opacity = '1';
                    setTimeout(() => { statusEl.style.opacity = '0'; }, 2000);
                } else {
                    alert(d.error || 'Could not save reply.');
                }
            } catch(e) {
                alert('Could not save reply. Please try again.');
            }
        }

        async function markStepReviewed(callsign, stepKey, checked) {
            const label = document.querySelector(`#reviewed_${stepKey}`)?.closest('label');
            const fd = new FormData();
            fd.append('action', 'kh1_beta_mark_reviewed');
            fd.append('callsign', callsign);
            fd.append('step_key', stepKey);
            fd.append('reviewed', checked ? '1' : '0');
            try {
                const resp = await fetch('api.php', { method: 'POST', body: fd });
                const d = await resp.json();
                if (!d.success) { alert(d.error || 'Could not update review status.'); return; }
                if (label) label.style.color = checked ? 'var(--success)' : '#b45309';
                loadBetaFeedback();
            } catch(e) {
                alert('Could not update review status. Please try again.');
            }
        }

        async function markAllBetaReviewed() {
            if (!currentBetaCallsign) return;
            const fd = new FormData();
            fd.append('action', 'kh1_beta_mark_all_reviewed');
            fd.append('callsign', currentBetaCallsign);
            try {
                const resp = await fetch('api.php', { method: 'POST', body: fd });
                const d = await resp.json();
                if (!d.success) { alert(d.error || 'Could not mark reviewed.'); return; }
                openBetaDetail(currentBetaCallsign);
                loadBetaFeedback();
            } catch(e) {
                alert('Could not mark reviewed. Please try again.');
            }
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
