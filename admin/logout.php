<?php
require_once '../includes/config.php';

// Log admin logout activity
if (isset($_SESSION['user_id'])) {
    logActivity($pdo, $_SESSION['user_id'], 'admin_logout', 'Admin logged out');
}

// Destroy all session data
session_destroy();

// Clear all cookies
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
}

// Redirect to admin login with logout message
$_SESSION['success'] = 'You have been successfully logged out.';
header('Location: index.php');
exit();
?>