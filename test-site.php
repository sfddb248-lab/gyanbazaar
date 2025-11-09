<?php
// Simple test to see what's working
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>Site Test</title></head><body>";
echo "<h1>GyanBazaar Site Test</h1>";

// Test 1: PHP is working
echo "<p style='color:green'>✅ PHP is working!</p>";

// Test 2: Database connection
echo "<h2>Database Test</h2>";
$conn = new mysqli('localhost', 'root', '', 'digitalkhazana');
if ($conn->connect_error) {
    echo "<p style='color:red'>❌ Database Error: " . $conn->connect_error . "</p>";
    echo "<p><strong>Solution:</strong> Make sure MySQL is running in XAMPP and database 'digitalkhazana' exists.</p>";
} else {
    echo "<p style='color:green'>✅ Database connected!</p>";
    
    // Check if tables exist
    $tables = ['users', 'products', 'orders', 'affiliates'];
    echo "<h3>Tables Check:</h3><ul>";
    foreach ($tables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        if ($result->num_rows > 0) {
            echo "<li style='color:green'>✅ $table exists</li>";
        } else {
            echo "<li style='color:red'>❌ $table missing</li>";
        }
    }
    echo "</ul>";
}

// Test 3: Session
echo "<h2>Session Test</h2>";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
echo "<p style='color:green'>✅ Sessions working!</p>";

// Test 4: File permissions
echo "<h2>File Permissions Test</h2>";
$dirs = ['assets/uploads', 'config'];
foreach ($dirs as $dir) {
    if (is_writable($dir)) {
        echo "<p style='color:green'>✅ $dir is writable</p>";
    } else {
        echo "<p style='color:orange'>⚠️ $dir may not be writable</p>";
    }
}

echo "<hr>";
echo "<h2>Next Steps:</h2>";
echo "<ol>";
echo "<li>If all tests pass, try accessing: <a href='index.php'>index.php</a></li>";
echo "<li>If you see errors, check the error messages above</li>";
echo "<li>Make sure XAMPP Apache and MySQL are running</li>";
echo "</ol>";

echo "</body></html>";
?>
