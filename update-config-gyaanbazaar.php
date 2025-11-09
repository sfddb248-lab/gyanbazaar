<?php
echo "<h2>🔧 Updating Config File for GyaanBazaar</h2>";

$configFile = 'config/config.php';
$content = file_get_contents($configFile);

// Update SITE_URL
$content = str_replace(
    "define('SITE_URL', 'http://localhost/DigitalKhazana');",
    "define('SITE_URL', 'http://localhost/GyaanBazaar');",
    $content
);

// Write back
if (file_put_contents($configFile, $content)) {
    echo "<p style='color: green;'>✅ Config file updated successfully!</p>";
    echo "<p>SITE_URL changed to: <strong>http://localhost/GyaanBazaar</strong></p>";
} else {
    echo "<p style='color: red;'>❌ Failed to update config file</p>";
}

echo "<div style='background: #fff3cd; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
echo "<h3>⚠️ Important: Rename Your Folder</h3>";
echo "<p>You need to rename your project folder:</p>";
echo "<ol>";
echo "<li>Close XAMPP</li>";
echo "<li>Go to: <code>C:\\xampp\\htdocs\\</code></li>";
echo "<li>Rename folder: <strong>DigitalKhazana</strong> → <strong>GyaanBazaar</strong></li>";
echo "<li>Start XAMPP</li>";
echo "<li>Access: <a href='http://localhost/GyaanBazaar'>http://localhost/GyaanBazaar</a></li>";
echo "</ol>";
echo "</div>";

echo "<div style='background: #d4edda; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
echo "<h3>✅ Rebranding Complete!</h3>";
echo "<p><strong>New Name:</strong> GyaanBazaar (ज्ञान बाज़ार)</p>";
echo "<p><strong>Tagline:</strong> ज्ञान की दुकान, सबके लिए आसान</p>";
echo "<p><strong>Type:</strong> Digital Education Marketplace</p>";
echo "</div>";

echo "<style>
body { font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px; }
code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; }
</style>";
?>
