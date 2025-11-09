<?php
/**
 * Database Connection Info
 * Shows current database configuration
 */

echo "<h1>Database Configuration</h1>";
echo "<pre>";
echo "DB_HOST: " . (getenv('DB_HOST') ?: 'Not set') . "\n";
echo "DB_USER: " . (getenv('DB_USER') ?: 'Not set') . "\n";
echo "DB_NAME: " . (getenv('DB_NAME') ?: 'Not set') . "\n";
echo "DB_PORT: " . (getenv('DB_PORT') ?: 'Not set') . "\n";
echo "</pre>";

echo "<h2>Testing Connection...</h2>";

$host = getenv('DB_HOST');
$user = getenv('DB_USER');
$pass = getenv('DB_PASS');
$name = getenv('DB_NAME');
$port = getenv('DB_PORT');

if ($port == '5432') {
    echo "<p>Detected PostgreSQL (port 5432)</p>";
    try {
        $conn_string = "host=$host port=$port dbname=$name user=$user password=$pass";
        $conn = new PDO("pgsql:$conn_string");
        echo "<p style='color: green;'>✅ PostgreSQL Connection Successful!</p>";
        
        // Get PostgreSQL version
        $version = $conn->query('SELECT version()')->fetchColumn();
        echo "<p>Version: $version</p>";
        
    } catch (PDOException $e) {
        echo "<p style='color: red;'>❌ Connection Failed: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p>Detected MySQL (port 3306)</p>";
    try {
        $conn = new mysqli($host, $user, $pass, $name, $port);
        if ($conn->connect_error) {
            throw new Exception($conn->connect_error);
        }
        echo "<p style='color: green;'>✅ MySQL Connection Successful!</p>";
        echo "<p>Version: " . $conn->server_info . "</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Connection Failed: " . $e->getMessage() . "</p>";
    }
}
?>
