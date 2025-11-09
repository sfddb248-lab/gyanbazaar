<?php
/**
 * Automatic Database Import Script
 * Visit this page once after deployment to import the database
 * URL: https://your-site.onrender.com/import-db.php
 */

// Security: Only allow import once
$lock_file = 'db_imported.lock';
if (file_exists($lock_file)) {
    die('✅ Database already imported! Delete db_imported.lock file to import again.');
}

// Get database credentials from environment
$host = getenv('DB_HOST') ?: 'localhost';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';
$name = getenv('DB_NAME') ?: 'gyanbazaar';

echo "<h1>🚀 GyanBazaar Database Import</h1>";
echo "<p>Starting database import...</p>";

// Connect to database
try {
    $conn = new mysqli($host, $user, $pass, $name);
    
    if ($conn->connect_error) {
        die("❌ Connection failed: " . $conn->connect_error);
    }
    
    echo "<p>✅ Connected to database successfully!</p>";
    
    // Read SQL file
    $sql_file = 'database-fixed.sql';
    
    if (!file_exists($sql_file)) {
        die("❌ SQL file not found: $sql_file");
    }
    
    echo "<p>📄 Reading SQL file...</p>";
    $sql = file_get_contents($sql_file);
    
    if ($sql === false) {
        die("❌ Failed to read SQL file");
    }
    
    echo "<p>📊 Importing database (this may take 30-60 seconds)...</p>";
    flush();
    
    // Split SQL into individual queries
    $queries = array_filter(array_map('trim', explode(';', $sql)));
    
    $success_count = 0;
    $error_count = 0;
    
    foreach ($queries as $query) {
        if (empty($query)) continue;
        
        if ($conn->query($query) === TRUE) {
            $success_count++;
        } else {
            $error_count++;
            echo "<p style='color: orange;'>⚠️ Warning: " . $conn->error . "</p>";
        }
    }
    
    echo "<h2>✅ Database Import Complete!</h2>";
    echo "<p>✓ Executed $success_count queries successfully</p>";
    
    if ($error_count > 0) {
        echo "<p style='color: orange;'>⚠️ $error_count queries had warnings (usually safe to ignore)</p>";
    }
    
    // Verify tables were created
    $result = $conn->query("SHOW TABLES");
    $table_count = $result->num_rows;
    
    echo "<p>✓ Database has $table_count tables</p>";
    echo "<h3>Tables created:</h3><ul>";
    
    while ($row = $result->fetch_array()) {
        echo "<li>" . $row[0] . "</li>";
    }
    
    echo "</ul>";
    
    // Create lock file to prevent re-import
    file_put_contents($lock_file, date('Y-m-d H:i:s'));
    
    echo "<hr>";
    echo "<h3>🎉 Success! Your database is ready!</h3>";
    echo "<p><strong>Next steps:</strong></p>";
    echo "<ul>";
    echo "<li>✓ Database imported successfully</li>";
    echo "<li>→ <a href='index.php'>Visit your homepage</a></li>";
    echo "<li>→ <a href='admin/login.php'>Login to admin panel</a> (admin/admin123)</li>";
    echo "<li>→ Delete this file (import-db.php) for security</li>";
    echo "</ul>";
    
    $conn->close();
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 800px;
    margin: 50px auto;
    padding: 20px;
    background: #f5f5f5;
}
h1 { color: #2c3e50; }
h2 { color: #27ae60; }
p { line-height: 1.6; }
ul { background: white; padding: 20px; border-radius: 5px; }
a { color: #3498db; text-decoration: none; }
a:hover { text-decoration: underline; }
</style>
