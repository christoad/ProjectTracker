<?php
/**
 * KI6CR Inventory Management System - Configuration
 * 
 * IMPORTANT: Update these settings with your actual database credentials
 * after uploading to your server.
 */

// Database Configuration — loaded from .env (never hardcode credentials here)
$_envFile = __DIR__ . '/.env';
if (!file_exists($_envFile)) {
    die('Missing .env file. Copy .env.example to .env and fill in your credentials.');
}
$_env = parse_ini_file($_envFile);
define('DB_HOST',    $_env['DB_HOST']);
define('DB_NAME',    $_env['DB_NAME']);
define('DB_USER',    $_env['DB_USER']);
define('DB_PASS',    $_env['DB_PASS']);
define('DB_CHARSET', 'utf8mb4');
define('USPS_CLIENT_ID',     $_env['USPS_CLIENT_ID'] ?? '');
define('USPS_CLIENT_SECRET', $_env['USPS_CLIENT_SECRET'] ?? '');
define('ORIGIN_ZIP',         $_env['ORIGIN_ZIP'] ?? '');
define('FROM_NAME',    $_env['FROM_NAME']    ?? '');
define('FROM_COMPANY', $_env['FROM_COMPANY'] ?? '');
define('FROM_STREET',  $_env['FROM_STREET']  ?? '');
define('FROM_CITY',    $_env['FROM_CITY']    ?? '');
define('FROM_STATE',   $_env['FROM_STATE']   ?? '');
define('FROM_ZIP',     $_env['FROM_ZIP']     ?? '');

// Application Settings
define('APP_NAME', 'KI6CR Inventory Manager');
define('TIMEZONE', 'America/Los_Angeles'); // Adjust to your timezone

// Session Configuration
define('SESSION_LIFETIME', 86400); // 24 hours in seconds

// Security Settings
define('PASSWORD_SALT', 'change-this-to-random-string-' . md5(__DIR__)); // Change this!

// Set timezone
date_default_timezone_set(TIMEZONE);

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Get database connection
 */
function getDB() {
    static $db = null;
    
    if ($db === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $db = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }
    
    return $db;
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['username']);
}

/**
 * Require login - redirect if not logged in
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('HTTP/1.1 401 Unauthorized');
        echo json_encode(['error' => 'Not authenticated']);
        exit;
    }
}

/**
 * Send JSON response
 */
function jsonResponse($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}
?>
