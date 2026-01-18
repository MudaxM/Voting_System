<?php
// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Session configuration
session_start();
session_regenerate_id(true);

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'voting_system');
define('DB_CHARSET', 'utf8mb4');

// Website configuration
define('SITE_NAME', 'Student Union Voting System');
define('SITE_URL', 'http://localhost/voting-system/');
define('ADMIN_URL', 'http://localhost/voting-system/admin/');

// Security configuration
define('PEPPER', 'your-secret-pepper-string-here');
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_TIME', 900); // 15 minutes

// Election configuration
define('MAX_VOTES_PER_POSITION', 1);
define('ALLOW_VOTE_CHANGE', false);

// Database connection
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Include other configuration files
require_once 'functions.php';
require_once 'auth.php';

// Set error reporting for production/development
if ($_SERVER['SERVER_NAME'] == 'localhost' || $_SERVER['SERVER_NAME'] == '127.0.0.1') {
    // Development - show all errors
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    // Production - hide warnings
    error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
    ini_set('display_errors', 0);
}

// Rest of your config...
?>