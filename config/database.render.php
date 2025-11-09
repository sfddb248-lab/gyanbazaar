<?php
// Universal Database Configuration for Railway/Render
// Works with both MySQL and PostgreSQL

define('DB_HOST', getenv('DB_HOST') ?: getenv('MYSQLHOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: getenv('MYSQLUSER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: getenv('MYSQLPASSWORD') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: getenv('MYSQLDATABASE') ?: 'gyanbazaar');
define('DB_PORT', getenv('DB_PORT') ?: getenv('MYSQLPORT') ?: '3306');

// Detect database type by port
$is_postgres = (DB_PORT == '5432');

if ($is_postgres) {
    // PostgreSQL connection (Render)
    try {
        $conn_string = "host=" . DB_HOST . " port=" . DB_PORT . " dbname=" . DB_NAME . " user=" . DB_USER . " password=" . DB_PASS;
        $pdo = new PDO("pgsql:$conn_string");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec("SET NAMES 'UTF8'");
        
        // Create mysqli-compatible wrapper for PostgreSQL
        $conn = new class($pdo) {
            private $pdo;
            public function __construct($pdo) { $this->pdo = $pdo; }
            public function query($sql) { return $this->pdo->query($sql); }
            public function prepare($sql) { return $this->pdo->prepare($sql); }
            public function real_escape_string($str) { return addslashes($str); }
            public function set_charset($charset) { return true; }
        };
    } catch (PDOException $e) {
        die("PostgreSQL Connection failed: " . $e->getMessage());
    }
} else {
    // MySQL connection (Railway, local)
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
        if ($conn->connect_error) {
            throw new Exception($conn->connect_error);
        }
        $conn->set_charset("utf8mb4");
    } catch (Exception $e) {
        die("MySQL Connection failed: " . $e->getMessage());
    }
}
?>
