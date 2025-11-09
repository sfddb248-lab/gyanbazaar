<?php
// Render.com Database Configuration
// Render uses PostgreSQL (not MySQL) on free tier

define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'gyanbazaar');
define('DB_PORT', getenv('DB_PORT') ?: '5432');

// PostgreSQL connection string
$conn_string = "host=" . DB_HOST . " port=" . DB_PORT . " dbname=" . DB_NAME . " user=" . DB_USER . " password=" . DB_PASS;

try {
    // Use PDO for PostgreSQL
    $conn = new PDO("pgsql:$conn_string");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->exec("SET NAMES 'UTF8'");
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
