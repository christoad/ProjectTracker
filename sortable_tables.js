/**
 * Sortable Table Utility
 * Add this to the <script> section in index.php
 */

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
        
        // Handle null/undefined
        if (aVal === null || aVal === undefined) aVal = '';
        if (bVal === null || bVal === undefined) bVal = '';
        
        // Numeric comparison
        if (typeof aVal === 'number' && typeof bVal === 'number') {
            return direction === 'asc' ? aVal - bVal : bVal - aVal;
        }
        
        // String comparison
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
    
    // Toggle direction if same column, otherwise default to asc
    if (state.column === column) {
        state.direction = state.direction === 'asc' ? 'desc' : 'asc';
    } else {
        state.column = column;
        state.direction = 'asc';
    }
    
    // Reload the appropriate table
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
