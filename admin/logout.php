<?php
require_once '../config/config.php';

// Destroy session
session_destroy();

// Redirect to admin login page
header('Location: ' . SITE_URL . '/admin/login.php');
exit;
?>
