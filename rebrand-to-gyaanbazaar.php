<?php
require_once 'config/config.php';

echo "<h2>🎨 Rebranding to GyaanBazaar</h2>";
echo "<p>Updating website name, tagline, and database...</p>";

$success = 0;
$errors = 0;

// Update site name in settings
$stmt = $conn->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'site_name'");
$siteName = 'GyaanBazaar';
$stmt->bind_param("s", $siteName);
if ($stmt->execute()) {
    $success++;
    echo "<p style='color: green;'>✓ Site name updated to GyaanBazaar</p>";
} else {
    $errors++;
}

// Add tagline setting
$stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('site_tagline', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
$tagline = 'ज्ञान की दुकान, सबके लिए आसान';
$stmt->bind_param("ss", $tagline, $tagline);
if ($stmt->execute()) {
    $success++;
    echo "<p style='color: green;'>✓ Tagline added: ज्ञान की दुकान, सबके लिए आसान</p>";
} else {
    $errors++;
}

// Add site description
$stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('site_description', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
$description = 'India\'s Premier Digital Education Marketplace - Quality Notes & Video Courses';
$stmt->bind_param("ss", $description, $description);
if ($stmt->execute()) {
    $success++;
    echo "<p style='color: green;'>✓ Site description added</p>";
} else {
    $errors++;
}

// Add site type
$stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('site_type', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
$siteType = 'E-Learning Marketplace';
$stmt->bind_param("ss", $siteType, $siteType);
if ($stmt->execute()) {
    $success++;
    echo "<p style='color: green;'>✓ Site type set to E-Learning Marketplace</p>";
} else {
    $errors++;
}

echo "<div style='background: #d4edda; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
echo "<h3 style='color: #155724;'>✅ Database Updated!</h3>";
echo "<p><strong>Operations:</strong> $success successful, $errors errors</p>";
echo "</div>";

echo "<div style='background: #fff3cd; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
echo "<h3 style='color: #856404;'>⚠️ Manual Steps Required:</h3>";
echo "<ol>";
echo "<li><strong>Update config.php:</strong> Change SITE_URL from 'DigitalKhazana' to 'GyaanBazaar'</li>";
echo "<li><strong>Rename folder:</strong> Rename C:\\xampp\\htdocs\\DigitalKhazana to C:\\xampp\\htdocs\\GyaanBazaar</li>";
echo "<li><strong>Update database name:</strong> Rename 'digitalkhazana' to 'gyaanbazaar' (optional)</li>";
echo "</ol>";
echo "</div>";

echo "<h3>📝 New Branding:</h3>";
echo "<div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 10px; text-align: center; margin: 20px 0;'>";
echo "<h1 style='font-size: 48px; margin: 0; color: white;'>GyaanBazaar</h1>";
echo "<p style='font-size: 24px; margin: 10px 0; color: white;'>ज्ञान की दुकान, सबके लिए आसान</p>";
echo "<p style='font-size: 16px; margin: 0; color: rgba(255,255,255,0.9);'>India's Premier Digital Education Marketplace</p>";
echo "</div>";

echo "<h3>🎯 Website Type:</h3>";
echo "<ul>";
echo "<li><strong>Primary:</strong> E-Learning Marketplace</li>";
echo "<li><strong>Category:</strong> Digital Education Platform</li>";
echo "<li><strong>Business Model:</strong> B2C + Affiliate Marketing</li>";
echo "<li><strong>Products:</strong> Notes (eBooks/PDFs) + Video Courses</li>";
echo "</ul>";

echo "<h3>✨ Key Features:</h3>";
echo "<ul>";
echo "<li>📚 Digital Notes & eBooks</li>";
echo "<li>🎥 Video Courses with Protection</li>";
echo "<li>🤝 10-Level Affiliate Marketing System</li>";
echo "<li>💳 Multiple Payment Gateways (UPI, Razorpay, etc.)</li>";
echo "<li>📱 Mobile-Friendly Design</li>";
echo "<li>🎫 Coupon System</li>";
echo "<li>📊 Advanced Analytics</li>";
echo "</ul>";

echo "<div style='margin: 30px 0;'>";
echo "<a href='update-config-gyaanbazaar.php' class='btn' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 5px;'>Update Config File</a>";
echo "<a href='index.php' class='btn' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 5px;'>View Website</a>";
echo "</div>";

echo "<style>
body { font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px; }
.btn:hover { opacity: 0.9; }
</style>";
?>
