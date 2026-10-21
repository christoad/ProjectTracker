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

// Application Settings
define('APP_NAME', 'KI6CR Inventory Manager');
define('TIMEZONE', 'America/Los_Angeles'); // Adjust to your timezone

// Session Configuration: stay logged in for 30 days, renewed on every visit
define('SESSION_LIFETIME', 30 * 86400);

// Security Settings
define('PASSWORD_SALT', 'change-this-to-random-string-' . md5(__DIR__)); // Change this!

// Set timezone
date_default_timezone_set(TIMEZONE);

// Start session. PHP's defaults drop the login when the browser closes and purge it
// after 24 idle minutes from the server's shared /tmp (which other sites also clean),
// so sessions live in a private folder outside the web root with a 30-day lifetime.
if (session_status() === PHP_SESSION_NONE) {
    $_sessDir = dirname(__DIR__, 2) . '/.sessions/ki6cr-projects';
    if (!is_dir($_sessDir)) @mkdir($_sessDir, 0700, true);
    if (is_dir($_sessDir) && is_writable($_sessDir)) session_save_path($_sessDir);
    ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
    $_sessSecure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME,
        'path'     => '/projects/',
        'secure'   => $_sessSecure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_name('KI6CR_INV');
    session_start();
    // Sliding expiry: push the cookie's expiry out again on every request
    if (isset($_SESSION['user_id']) && !headers_sent()) {
        setcookie(session_name(), session_id(), [
            'expires'  => time() + SESSION_LIFETIME,
            'path'     => '/projects/',
            'secure'   => $_sessSecure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
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
