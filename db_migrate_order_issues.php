<?php
/**
 * DB Migration: add order_issues + order_issue_tracking tables for the Order Issues tab
 * (packages shipped but not delivered, revised/replacement tracking, carrier claims and refunds).
 * Run once via browser with ?pw=sota, confirm all steps show green, then delete this file.
 */

if (($_GET['pw'] ?? '') !== 'sota') {
    http_response_code(403);
    die('Forbidden: pass ?pw=sota to run this migration.');
}

require_once 'config.php';
$db = getDB();

header('Content-Type: text/plain');
echo "Order Issues Migration\n======================\n\n";

try {
    $db->exec("
        CREATE TABLE IF NOT EXISTS order_issues (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            issue_type VARCHAR(30) NOT NULL DEFAULT 'not_delivered',
            status VARCHAR(20) NOT NULL DEFAULT 'open',
            description TEXT NULL,
            opened_date DATE NOT NULL,
            resolved_date DATE NULL,
            resolution VARCHAR(30) NULL,
            resolution_note TEXT NULL,
            claim_status VARCHAR(20) NOT NULL DEFAULT 'none',
            claim_number VARCHAR(100) NULL,
            claim_filed_date DATE NULL,
            claim_amount DECIMAL(10,2) NULL,
            claim_paid_amount DECIMAL(10,2) NULL,
            claim_paid_date DATE NULL,
            claim_notes TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_order (order_id),
            INDEX idx_status (status)
        ) ENGINE=InnoDB
    ");
    echo "✓ Created (or already existed) order_issues table.\n";
} catch (Exception $e) {
    echo "✗ Error creating order_issues: " . $e->getMessage() . "\n";
}

try {
    $db->exec("
        CREATE TABLE IF NOT EXISTS order_issue_tracking (
            id INT AUTO_INCREMENT PRIMARY KEY,
            issue_id INT NOT NULL,
            tracking_number VARCHAR(100) NOT NULL,
            carrier VARCHAR(30) NOT NULL DEFAULT 'usps',
            tracking_url VARCHAR(500) NULL,
            kind VARCHAR(20) NOT NULL DEFAULT 'original',
            note VARCHAR(500) NULL,
            tracking_status VARCHAR(30) NULL,
            status_detail VARCHAR(255) NULL,
            status_date DATETIME NULL,
            eta DATETIME NULL,
            delivered_at DATETIME NULL,
            history MEDIUMTEXT NULL,
            last_checked_at DATETIME NULL,
            last_error VARCHAR(255) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_issue_tracking (issue_id, tracking_number),
            INDEX idx_issue (issue_id)
        ) ENGINE=InnoDB
    ");
    echo "✓ Created (or already existed) order_issue_tracking table.\n";
} catch (Exception $e) {
    echo "✗ Error creating order_issue_tracking: " . $e->getMessage() . "\n";
}

echo "\nDone. Delete this file from the server now.\n";
