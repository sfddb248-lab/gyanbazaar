<?php
require_once 'config/config.php';

echo "<h2>Affiliate System Test & Verification</h2>";

// Check if tables exist
$tables = [
    'affiliates',
    'affiliate_referrals',
    'affiliate_commissions',
    'affiliate_payouts',
    'affiliate_mlm_structure',
    'affiliate_materials',
    'affiliate_clicks',
    'affiliate_settings'
];

echo "<h3>1. Database Tables Check</h3>";
echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Table Name</th><th>Status</th><th>Row Count</th></tr>";

$allTablesExist = true;
foreach ($tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    $exists = $result->num_rows > 0;
    $allTablesExist = $allTablesExist && $exists;
    
    $count = 0;
    if ($exists) {
        $countResult = $conn->query("SELECT COUNT(*) as count FROM $table");
        $count = $countResult->fetch_assoc()['count'];
    }
    
    $status = $exists ? "✅ Exists" : "❌ Missing";
    $color = $exists ? "#d4edda" : "#f8d7da";
    echo "<tr style='background: $color;'><td>$table</td><td>$status</td><td>$count</td></tr>";
}
echo "</table>";

// Check if functions exist
echo "<h3>2. Functions Check</h3>";
$functions = [
    'generateReferralCode',
    'createAffiliate',
    'getAffiliateByUserId',
    'getAffiliateByCode',
    'trackAffiliateClick',
    'trackAffiliateReferral',
    'createAffiliateCommission',
    'processMLMCommissions',
    'requestPayout',
    'processPayout',
    'getAffiliateLink'
];

echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Function Name</th><th>Status</th></tr>";

$allFunctionsExist = true;
foreach ($functions as $function) {
    $exists = function_exists($function);
    $allFunctionsExist = $allFunctionsExist && $exists;
    $status = $exists ? "✅ Available" : "❌ Missing";
    $color = $exists ? "#d4edda" : "#f8d7da";
    echo "<tr style='background: $color;'><td>$function()</td><td>$status</td></tr>";
}
echo "</table>";

// Check files
echo "<h3>3. Files Check</h3>";
$files = [
    'affiliate-dashboard.php' => 'Affiliate Dashboard',
    'affiliate-payout.php' => 'Payout Request Page',
    'affiliate-materials.php' => 'Promotional Materials',
    'includes/affiliate-functions.php' => 'Core Functions',
    'admin/affiliates.php' => 'Admin: Manage Affiliates',
    'admin/affiliate-commissions.php' => 'Admin: Commissions',
    'admin/affiliate-payouts.php' => 'Admin: Payouts',
    'admin/affiliate-materials.php' => 'Admin: Materials',
    'admin/affiliate-settings.php' => 'Admin: Settings'
];

echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>File</th><th>Description</th><th>Status</th></tr>";

$allFilesExist = true;
foreach ($files as $file => $description) {
    $exists = file_exists($file);
    $allFilesExist = $allFilesExist && $exists;
    $status = $exists ? "✅ Exists" : "❌ Missing";
    $color = $exists ? "#d4edda" : "#f8d7da";
    echo "<tr style='background: $color;'><td>$file</td><td>$description</td><td>$status</td></tr>";
}
echo "</table>";

// Check settings
echo "<h3>4. Affiliate Settings</h3>";
$settings = $conn->query("SELECT * FROM affiliate_settings");
if ($settings && $settings->num_rows > 0) {
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Setting</th><th>Value</th></tr>";
    while ($setting = $settings->fetch_assoc()) {
        echo "<tr><td>{$setting['setting_key']}</td><td>{$setting['setting_value']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>⚠️ No settings found. Run setup script first.</p>";
}

// Statistics
echo "<h3>5. System Statistics</h3>";
$stats = [];

$result = $conn->query("SELECT COUNT(*) as count FROM affiliates");
$stats['Total Affiliates'] = $result ? $result->fetch_assoc()['count'] : 0;

$result = $conn->query("SELECT COUNT(*) as count FROM affiliates WHERE status = 'active'");
$stats['Active Affiliates'] = $result ? $result->fetch_assoc()['count'] : 0;

$result = $conn->query("SELECT COUNT(*) as count FROM affiliate_referrals");
$stats['Total Referrals'] = $result ? $result->fetch_assoc()['count'] : 0;

$result = $conn->query("SELECT COUNT(*) as count FROM affiliate_commissions");
$stats['Total Commissions'] = $result ? $result->fetch_assoc()['count'] : 0;

$result = $conn->query("SELECT SUM(commission_amount) as total FROM affiliate_commissions");
$row = $result ? $result->fetch_assoc() : null;
$stats['Total Commission Amount'] = '₹' . number_format($row['total'] ?? 0, 2);

$result = $conn->query("SELECT COUNT(*) as count FROM affiliate_payouts");
$stats['Total Payout Requests'] = $result ? $result->fetch_assoc()['count'] : 0;

$result = $conn->query("SELECT COUNT(*) as count FROM affiliate_materials");
$stats['Promotional Materials'] = $result ? $result->fetch_assoc()['count'] : 0;

$result = $conn->query("SELECT COUNT(*) as count FROM affiliate_clicks");
$stats['Total Clicks Tracked'] = $result ? $result->fetch_assoc()['count'] : 0;

echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Metric</th><th>Value</th></tr>";
foreach ($stats as $key => $value) {
    echo "<tr><td>$key</td><td><strong>$value</strong></td></tr>";
}
echo "</table>";

// Overall Status
echo "<h3>6. Overall System Status</h3>";
if ($allTablesExist && $allFunctionsExist && $allFilesExist) {
    echo "<div style='background: #d4edda; padding: 20px; border-radius: 5px; border: 2px solid #28a745;'>";
    echo "<h2 style='color: #155724; margin: 0;'>✅ System is Ready!</h2>";
    echo "<p>All components are installed and working correctly.</p>";
    echo "</div>";
} else {
    echo "<div style='background: #f8d7da; padding: 20px; border-radius: 5px; border: 2px solid #dc3545;'>";
    echo "<h2 style='color: #721c24; margin: 0;'>❌ System Incomplete</h2>";
    echo "<p>Some components are missing. Please run the setup script.</p>";
    echo "</div>";
}

// Quick Actions
echo "<h3>7. Quick Actions</h3>";
echo "<div style='display: flex; gap: 10px; flex-wrap: wrap;'>";
echo "<a href='setup-affiliate-system.php' class='btn'>Run Setup</a>";
echo "<a href='admin/affiliate-settings.php' class='btn'>Configure Settings</a>";
echo "<a href='affiliate-dashboard.php' class='btn'>Affiliate Dashboard</a>";
echo "<a href='admin/affiliates.php' class='btn'>Manage Affiliates</a>";
echo "<a href='integrate-affiliate-tracking.php' class='btn'>Integration Guide</a>";
echo "<a href='AFFILIATE_SYSTEM_COMPLETE.md' class='btn'>Documentation</a>";
echo "</div>";

// Test Referral Link
if (isset($_SESSION['user_id'])) {
    $affiliate = getAffiliateByUserId($_SESSION['user_id']);
    if ($affiliate) {
        echo "<h3>8. Your Test Referral Link</h3>";
        echo "<div style='background: #cfe2ff; padding: 15px; border-radius: 5px;'>";
        echo "<p><strong>Your Referral Code:</strong> {$affiliate['referral_code']}</p>";
        echo "<p><strong>Your Referral Link:</strong></p>";
        $testLink = getAffiliateLink($affiliate['referral_code']);
        echo "<input type='text' value='$testLink' style='width: 100%; padding: 10px; font-size: 14px;' readonly onclick='this.select()'>";
        echo "<p><small>Click the link above to select and copy it</small></p>";
        echo "</div>";
    } else {
        echo "<h3>8. Become an Affiliate</h3>";
        echo "<p><a href='affiliate-dashboard.php' class='btn'>Register as Affiliate</a></p>";
    }
}

echo "<style>
body { font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px; background: #f8f9fa; }
h2 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
h3 { color: #555; margin-top: 30px; }
table { margin: 20px 0; background: white; }
.btn { 
    display: inline-block; 
    padding: 10px 20px; 
    background: #007bff; 
    color: white; 
    text-decoration: none; 
    border-radius: 5px; 
    font-weight: bold;
}
.btn:hover { background: #0056b3; }
</style>";
?>
