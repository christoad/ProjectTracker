/**
 * ADD THESE JAVASCRIPT FUNCTIONS TO YOUR index.php <script> SECTION
 * Place before the closing </script> tag
 */

// ============================================
// SORTABLE TABLES
// ============================================

// Global sort state
const sortState = {
    parts: { column: 'part_number', direction: 'asc' },
    projects: { column: 'project_name', direction: 'asc' },
    orders: { column: 'order_date', direction: 'desc' }
};

// Sort array of objects by column
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

// Create sortable header
function createSortableHeader(text, column, table) {
    const currentSort = sortState[table];
    const isActive = currentSort.column === column;
    const arrow = isActive ? (currentSort.direction === 'asc' ? ' ▲' : ' ▼') : '';
    
    return `<th style="cursor: pointer; user-select: none;" onclick="sortTable('${table}', '${column}')" title="Click to sort">
        ${text}${arrow}
    </th>`;
}

// Sort table and reload
function sortTable(table, column) {
    const state = sortState[table];
    
    if (state.column === column) {
        state.direction = state.direction === 'asc' ? 'desc' : 'asc';
    } else {
        state.column = column;
        state.direction = 'asc';
    }
    
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

// ============================================
// CUSTOMER EMAIL NOTIFICATION
// ============================================

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

// ============================================
// BUSINESS METRICS DASHBOARD
// ============================================

let businessMetrics = {};

async function loadBusinessMetrics() {
    const period = document.getElementById('businessPeriod').value;
    
    try {
        const response = await fetch(`business_metrics.php?action=get_business_metrics&year=${period}`);
        businessMetrics = await response.json();
        
        // Update main stats
        document.getElementById('statRevenue').textContent = '$' + businessMetrics.orders.revenue.toLocaleString();
        document.getElementById('statGrossProfit').textContent = '$' + businessMetrics.profit.gross.toLocaleString();
        document.getElementById('statNetProfit').textContent = '$' + businessMetrics.profit.net.toLocaleString();
        document.getElementById('statMargin').textContent = businessMetrics.profit.margin.toFixed(1) + '%';
        
        // Update inventory metrics
        document.getElementById('metricInventoryCost').textContent = '$' + businessMetrics.inventory.cost.toLocaleString();
        document.getElementById('metricUnrealizedRevenue').textContent = '$' + businessMetrics.inventory.unrealized_revenue.toLocaleString();
        
        // Update order metrics
        document.getElementById('metricOrderCount').textContent = businessMetrics.orders.count;
        document.getElementById('metricCOGS').textContent = '$' + businessMetrics.orders.cogs.toLocaleString();
        document.getElementById('metricShipping').textContent = '$' + businessMetrics.orders.shipping.toLocaleString();
        
        // Update P&L breakdown
        document.getElementById('plRevenue').textContent = '$' + businessMetrics.orders.revenue.toLocaleString();
        document.getElementById('plCOGS').textContent = '$' + businessMetrics.orders.cogs.toLocaleString();
        document.getElementById('plGross').textContent = '$' + businessMetrics.profit.gross.toLocaleString();
        document.getElementById('plShipping').textContent = '$' + businessMetrics.orders.shipping.toLocaleString();
        document.getElementById('plNet').textContent = '$' + businessMetrics.profit.net.toLocaleString();
        
        // Orders by status
        let statusHtml = '<table class="data-table">';
        businessMetrics.orders_by_status.forEach(s => {
            statusHtml += `<tr><td>${s.status}</td><td>${s.count} orders</td><td>$${parseFloat(s.revenue).toLocaleString()}</td></tr>`;
        });
        statusHtml += '</table>';
        document.getElementById('ordersByStatus').innerHTML = statusHtml;
        
        // Top projects
        let projectsHtml = '<table class="data-table">';
        businessMetrics.top_projects.forEach(p => {
            projectsHtml += `<tr><td>${p.project_name}</td><td>${p.units_sold} units</td><td>$${parseFloat(p.revenue).toLocaleString()}</td></tr>`;
        });
        projectsHtml += '</table>';
        document.getElementById('topProjects').innerHTML = projectsHtml;
        
    } catch (error) {
        console.error('Error loading business metrics:', error);
    }
}

// ============================================
// UPDATE EXISTING FUNCTIONS FOR SORTING
// ============================================

/**
 * MODIFY YOUR loadParts() function:
 * Add this line after fetching parts and before rendering:
 */
// allParts = sortData(allParts, sortState.parts.column, sortState.parts.direction);

/**
 * MODIFY YOUR loadProjects() function:
 * Add this line after fetching projects and before rendering:
 */
// projects = sortData(projects, sortState.projects.column, sortState.projects.direction);

/**
 * MODIFY YOUR loadOrders() function:
 * Add this line after fetching orders and before rendering:
 */
// orders = sortData(orders, sortState.orders.column, sortState.orders.direction);

/**
 * UPDATE TABLE HEADERS:
 * Replace existing <th> tags with createSortableHeader() calls
 * 
 * Example for Parts table:
 * OLD: <th>Part Number</th>
 * NEW: ${createSortableHeader('Part Number', 'part_number', 'parts')}
 */
