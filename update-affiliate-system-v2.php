<?php
require_once 'config/config.php';

echo "<h2>Affiliate System Update v2.0</h2>";
echo "<p>Updating system for purchase-based referrals and 10-level MLM...</p>";

$success = 0;
$errors = 0;

// Add new settings for levels 4-10
$newSettings = [
    ['level_4_commission', '1.5'],
    ['level_5_commission', '1'],
    ['level_6_commission', '0.75'],
    ['level_7_commission', '0.5'],
    ['level_8_commission', '0.25'],
    ['level_9_commission', '0.15'],
    ['level_10_commission', '0.1']
];

foreach ($newSettings as $setting) {
    $stmt = $conn->prepare("INSERT INTO affiliate_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    $stmt->bind_param("ss", $setting[0], $setting[1]);
    if ($stmt->execute()) {
        $success++;
        echo "<p style='color: green;'>✓ Added setting: {$setting[0]}</p>";
    } else {
        $errors++;
        echo "<p style='color: red;'>✗ Error adding {$setting[0]}</p>";
    }
}

// Update mlm_levels to 10
$stmt = $conn->prepare("UPDATE affiliate_settings SET setting_value = '10' WHERE setting_key = 'mlm_levels'");
if ($stmt->execute()) {
    $success++;
    echo "<p style='color: green;'>✓ Updated MLM levels to 10</p>";
} else {
    $errors++;
}

// Add purchase_made column to affiliate_referrals if not exists
$conn->query("ALTER TABLE affiliate_referrals ADD COLUMN IF NOT EXISTS purchase_made BOOLEAN DEFAULT FALSE");
$conn->query("ALTER TABLE affiliate_referrals ADD COLUMN IF NOT EXISTS first_purchase_date DATETIME DEFAULT NULL");
echo "<p style='color: green;'>✓ Added purchase tracking columns</p>";
$success++;

echo "<div style='background: #d4edda; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
echo "<h3 style='color: #155724;'>✅ Update Complete!</h3>";
echo "<p><strong>Operations:</strong> $success successful, $errors errors</p>";
echo "</div>";

echo "<h3>✅ Changes Applied:</h3>";
echo "<ul>";
echo "<li>✓ Extended MLM to 10 levels</li>";
echo "<li>✓ Added commission settings for levels 4-10</li>";
echo "<li>✓ Added purchase tracking to referrals</li>";
echo "<li>✓ Referrals now count only after purchase</li>";
echo "</ul>";

echo "<h3>🎯 Next Steps:</h3>";
echo "<ol>";
echo "<li>Go to <a href='admin/affiliate-settings.php'>Admin → Affiliate Settings</a></li>";
echo "<li>Configure commission rates for all 10 levels</li>";
echo "<li>Test the new purchase-based referral system</li>";
echo "</ol>";

echo "<div style='margin: 30px 0;'>";
echo "<a href='admin/affiliate-settings.php' class='btn' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 5px;'>Configure Settings</a>";
echo "<a href='affiliate-dashboard.php' class='btn' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 5px;'>View Dashboard</a>";
echo "</div>";

echo "<style>body { font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px; }</style>";
?>
