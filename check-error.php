<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Site Diagnostic Check</h1>";

// Check 1: PHP Version
echo "<h2>1. PHP Version</h2>";
echo "PHP Version: " . phpversion() . "<br>";

// Check 2: Database Connection
echo "<h2>2. Database Connection</h2>";
try {
    require_once 'config/database.php';
    if ($conn->connect_error) {
        echo "<span style='color:red'>❌ Database Connection Failed: " . $conn->connect_error . "</span><br>";
    } else {
        echo "<span style='color:green'>✅ Database Connected Successfully</span><br>";
        echo "Database: " . DB_NAME . "<br>";
    }
} catch (Exception $e) {
    echo "<span style='color:red'>❌ Error: " . $e->getMessage() . "</span><br>";
}

// Check 3: Config File
echo "<h2>3. Config File</h2>";
try {
    require_once 'config/config.php';
    echo "<span style='color:green'>✅ Config loaded successfully</span><br>";
    echo "Site URL: " . SITE_URL . "<br>";
} catch (Exception $e) {
    echo "<span style='color:red'>❌ Config Error: " . $e->getMessage() . "</span><br>";
}

// Check 4: Functions File
echo "<h2>4. Functions File</h2>";
try {
    if (function_exists('getSetting')) {
        echo "<span style='color:green'>✅ Functions loaded successfully</span><br>";
    } else {
        echo "<span style='color:red'>❌ Functions not loaded</span><br>";
    }
} catch (Exception $e) {
    echo "<span style='color:red'>❌ Functions Error: " . $e->getMessage() . "</span><br>";
}

// Check 5: Try loading index.php
echo "<h2>5. Index.php Check</h2>";
ob_start();
try {
    include 'index.php';
    $output = ob_get_clean();
    echo "<span style='color:green'>✅ Index.php loaded successfully</span><br>";
} catch (Exception $e) {
    ob_end_clean();
    echo "<span style='color:red'>❌ Index.php Error: " . $e->getMessage() . "</span><br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<h2>6. Error Log</h2>";
echo "<pre>";
$errorLog = ini_get('error_log');
if (file_exists($errorLog)) {
    echo "Last 20 lines of error log:\n";
    echo shell_exec("tail -20 " . escapeshellarg($errorLog));
} else {
    echo "Error log not found at: $errorLog";
}
echo "</pre>";
?>
