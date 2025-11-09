<?php
session_start();

// Site Configuration
define('SITE_URL', 'http://localhost/GyanBazaar');
define('UPLOAD_PATH', __DIR__ . '/../assets/uploads/');
define('UPLOAD_URL', SITE_URL . '/assets/uploads/');

// Security
define('HASH_ALGO', PASSWORD_BCRYPT);
define('HASH_COST', 10);

// Pagination
define('PRODUCTS_PER_PAGE', 12);

// Download Settings
define('DOWNLOAD_EXPIRY_DAYS', 365);
define('MAX_DOWNLOAD_COUNT', 10);

// Include database
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/otp-functions.php';
require_once __DIR__ . '/../includes/affiliate-functions.php';

// Get settings from database
function getSetting($key, $default = '') {
    global $conn;
    $stmt = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        return $row['setting_value'];
    }
    return $default;
}
?>
