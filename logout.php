<?php
require_once 'config/config.php';

// Check if user was admin before destroying session
$wasAdmin = isAdmin();

// Destroy session
session_destroy();

// Redirect based on previous role
if ($wasAdmin) {
    // Admin users go to admin login page
    header('Location: ' . SITE_URL . '/admin/login.php');
} else {
    // Regular users go to home page
    header('Location: ' . SITE_URL . '/index.php');
}
exit;
?>
