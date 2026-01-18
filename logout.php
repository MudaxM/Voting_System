<?php
require_once 'includes/config.php';

// Destroy all session data
session_destroy();

// Clear remember me cookie
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
}

// Redirect to login page with logout message
$_SESSION['success'] = 'You have been successfully logged out.';
header('Location: login.php');
exit();
?>