<?php
require_once 'config/config.php';

echo "<h1>🔄 Reset & Fresh Setup - Affiliate System</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .warning { color: orange; font-weight: bold; }
    .info { color: blue; font-weight: bold; }
    .section { margin: 20px 0; padding: 20px; background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    h2 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
    .code { background: #f8f9fa; padding: 10px; border-left: 4px solid #007bff; margin: 10px 0; font-family: monospace; }
</style>";

// STEP 1: Clear all affiliate data
echo "<div class='section'>";
echo "<h2>Step 1: Clearing All Affiliate Data</h2>";

$tables = [
    'affiliate_commissions' => 'Commissions',
    'affiliate_referrals' => 'Referrals',
    'affiliate_clicks' => 'Clicks',
    'affiliate_payouts' => 'Payouts'
];

foreach ($tables as $table => $name) {
    try {
        $result = $conn->query("TRUNCATE TABLE `$table`");
        if ($result) {
            echo "<p class='success'>✓ Cleared $name table</p>";
        }
    } catch (Exception $e) {
        echo "<p class='warning'>⚠ Table $table: " . $e->getMessage() . "</p>";
    }
}

// Reset affiliate statistics
$conn->query("UPDATE affiliates SET total_referrals = 0, total_sales = 0, total_earnings = 0, pending_earnings = 0, paid_earnings = 0");
echo "<p class='success'>✓ Reset all affiliate statistics</p>";

// Clear referred_by from users
$conn->query("UPDATE users SET referred_by = NULL WHERE role = 'user'");
echo "<p class='success'>✓ Cleared referred_by from all users</p>";

echo "</div>";

// STEP 2: Ensure database structure is correct
echo "<div class='section'>";
echo "<h2>Step 2: Verify Database Structure</h2>";

// Check referred_by column
$columns = $conn->query("SHOW COLUMNS FROM users LIKE 'referred_by'");
if ($columns->num_rows == 0) {
    $conn->query("ALTER TABLE users ADD COLUMN referred_by INT NULL AFTER role");
    echo "<p class='success'>✓ Added referred_by column to users table</p>";
} else {
    echo "<p class='success'>✓ referred_by column exists</p>";
}

// Check affiliate_referrals table structure
$checkPurchaseMade = $conn->query("SHOW COLUMNS FROM affiliate_referrals LIKE 'purchase_made'");
if ($checkPurchaseMade->num_rows == 0) {
    $conn->query("ALTER TABLE affiliate_referrals ADD COLUMN purchase_made BOOLEAN DEFAULT FALSE AFTER converted");
    echo "<p class='success'>✓ Added purchase_made column to affiliate_referrals</p>";
} else {
    echo "<p class='success'>✓ purchase_made column exists</p>";
}

$checkFirstPurchase = $conn->query("SHOW COLUMNS FROM affiliate_referrals LIKE 'first_purchase_date'");
if ($checkFirstPurchase->num_rows == 0) {
    $conn->query("ALTER TABLE affiliate_referrals ADD COLUMN first_purchase_date TIMESTAMP NULL AFTER conversion_date");
    echo "<p class='success'>✓ Added first_purchase_date column to affiliate_referrals</p>";
} else {
    echo "<p class='success'>✓ first_purchase_date column exists</p>";
}

echo "</div>";

// STEP 3: Get existing affiliates
echo "<div class='section'>";
echo "<h2>Step 3: Existing Affiliate Accounts</h2>";

$affiliates = $conn->query("
    SELECT a.*, u.name, u.email 
    FROM affiliates a 
    JOIN users u ON a.user_id = u.id 
    WHERE a.status = 'active'
    ORDER BY a.id ASC
")->fetch_all(MYSQLI_ASSOC);

if (count($affiliates) > 0) {
    echo "<p class='info'>Found " . count($affiliates) . " active affiliate account(s):</p>";
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
    echo "<tr style='background: #007bff; color: white;'><th>ID</th><th>Name</th><th>Email</th><th>Referral Code</th><th>Commission</th><th>Referral Link</th></tr>";
    
    foreach ($affiliates as $aff) {
        $referralLink = SITE_URL . "/?ref=" . $aff['referral_code'];
        echo "<tr>";
        echo "<td>{$aff['id']}</td>";
        echo "<td>{$aff['name']}</td>";
        echo "<td>{$aff['email']}</td>";
        echo "<td><strong style='color: #007bff;'>{$aff['referral_code']}</strong></td>";
        echo "<td>{$aff['commission_value']}%</td>";
        echo "<td><input type='text' value='$referralLink' style='width: 100%; padding: 5px;' readonly onclick='this.select()'></td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p class='warning'>⚠ No affiliate accounts found. You need to create one first!</p>";
    echo "<p>Go to: <a href='affiliate-dashboard.php'>Affiliate Dashboard</a> to create an affiliate account.</p>";
}

echo "</div>";

// STEP 4: Test the referral flow
echo "<div class='section'>";
echo "<h2>Step 4: Testing Instructions</h2>";

if (count($affiliates) > 0) {
    $testAffiliate = $affiliates[0];
    $testLink = SITE_URL . "/?ref=" . $testAffiliate['referral_code'];
    
    echo "<div class='code'>";
    echo "<strong>🧪 Test Referral Flow:</strong><br><br>";
    echo "1. <strong>Copy this referral link:</strong><br>";
    echo "<input type='text' value='$testLink' style='width: 100%; padding: 8px; margin: 5px 0;' readonly onclick='this.select()'><br><br>";
    
    echo "2. <strong>Open in Incognito/Private Window</strong><br>";
    echo "   - Chrome: Ctrl+Shift+N<br>";
    echo "   - Firefox: Ctrl+Shift+P<br><br>";
    
    echo "3. <strong>Paste the link and press Enter</strong><br>";
    echo "   - Cookie will be set automatically<br>";
    echo "   - You'll be redirected to homepage<br><br>";
    
    echo "4. <strong>Sign up a new user</strong><br>";
    echo "   - Click 'Sign Up' or go to: " . SITE_URL . "/signup.php<br>";
    echo "   - Create a test account (e.g., test@example.com)<br><br>";
    
    echo "5. <strong>Make a purchase</strong><br>";
    echo "   - Add a product to cart<br>";
    echo "   - Complete checkout<br>";
    echo "   - Use any payment method<br><br>";
    
    echo "6. <strong>Check results</strong><br>";
    echo "   - Go to: <a href='affiliate-dashboard.php' target='_blank'>Affiliate Dashboard</a><br>";
    echo "   - You should see 1 referral and 1 commission<br>";
    echo "</div>";
} else {
    echo "<p class='warning'>Create an affiliate account first, then refresh this page for testing instructions.</p>";
}

echo "</div>";

// STEP 5: Verify system files
echo "<div class='section'>";
echo "<h2>Step 5: System Files Verification</h2>";

$requiredFiles = [
    'config/config.php' => 'Main configuration',
    'includes/affiliate-functions.php' => 'Affiliate functions',
    'signup.php' => 'User registration',
    'checkout.php' => 'Order checkout',
    'index.php' => 'Homepage (referral tracking)',
    'affiliate-dashboard.php' => 'Affiliate dashboard'
];

$allFilesExist = true;
foreach ($requiredFiles as $file => $description) {
    if (file_exists($file)) {
        echo "<p class='success'>✓ $description ($file)</p>";
    } else {
        echo "<p class='error'>✗ Missing: $description ($file)</p>";
        $allFilesExist = false;
    }
}

if ($allFilesExist) {
    echo "<p class='success'>✓ All required files exist</p>";
}

echo "</div>";

// STEP 6: Check functions
echo "<div class='section'>";
echo "<h2>Step 6: Function Availability</h2>";

$requiredFunctions = [
    'getAffiliateByCode',
    'trackAffiliateReferral',
    'createAffiliateCommission',
    'markReferralConverted',
    'getAffiliateById'
];

$allFunctionsExist = true;
foreach ($requiredFunctions as $func) {
    if (function_exists($func)) {
        echo "<p class='success'>✓ Function: $func()</p>";
    } else {
        echo "<p class='error'>✗ Missing function: $func()</p>";
        $allFunctionsExist = false;
    }
}

if ($allFunctionsExist) {
    echo "<p class='success'>✓ All required functions are loaded</p>";
}

echo "</div>";

// STEP 7: Quick diagnostic
echo "<div class='section'>";
echo "<h2>Step 7: Current System Status</h2>";

$stats = [
    'Total Users' => $conn->query("SELECT COUNT(*) as count FROM users WHERE role='user'")->fetch_assoc()['count'],
    'Total Affiliates' => $conn->query("SELECT COUNT(*) as count FROM affiliates WHERE status='active'")->fetch_assoc()['count'],
    'Total Products' => $conn->query("SELECT COUNT(*) as count FROM products WHERE status='active'")->fetch_assoc()['count'],
    'Total Orders' => $conn->query("SELECT COUNT(*) as count FROM orders WHERE payment_status='completed'")->fetch_assoc()['count'],
    'Referrals' => $conn->query("SELECT COUNT(*) as count FROM affiliate_referrals")->fetch_assoc()['count'],
    'Commissions' => $conn->query("SELECT COUNT(*) as count FROM affiliate_commissions")->fetch_assoc()['count']
];

echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background: #f8f9fa;'><th>Metric</th><th>Count</th></tr>";
foreach ($stats as $metric => $count) {
    echo "<tr><td><strong>$metric</strong></td><td>$count</td></tr>";
}
echo "</table>";

echo "</div>";

// FINAL SUMMARY
echo "<div class='section' style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;'>";
echo "<h2 style='color: white; border-bottom: 2px solid white;'>✅ System Reset Complete!</h2>";

echo "<h3 style='color: white;'>What was done:</h3>";
echo "<ul>";
echo "<li>✓ Cleared all affiliate data (referrals, commissions, clicks)</li>";
echo "<li>✓ Reset all affiliate statistics to zero</li>";
echo "<li>✓ Cleared referred_by from all users</li>";
echo "<li>✓ Verified database structure</li>";
echo "<li>✓ Checked all required files and functions</li>";
echo "</ul>";

echo "<h3 style='color: white;'>Next Steps:</h3>";
echo "<ol>";
echo "<li><strong>Create/Verify Affiliate Account</strong> - Go to <a href='affiliate-dashboard.php' style='color: #ffd700;'>Affiliate Dashboard</a></li>";
echo "<li><strong>Get Your Referral Link</strong> - Copy it from the table above</li>";
echo "<li><strong>Test the Flow</strong> - Follow the testing instructions in Step 4</li>";
echo "<li><strong>Monitor Results</strong> - Check the affiliate dashboard after test purchase</li>";
echo "</ol>";

echo "<div style='background: rgba(255,255,255,0.2); padding: 15px; border-radius: 5px; margin-top: 20px;'>";
echo "<strong>🎯 Expected Result After Test:</strong><br>";
echo "• 1 new user signed up via referral<br>";
echo "• 1 referral record created<br>";
echo "• 1 order completed<br>";
echo "• 1 commission generated<br>";
echo "• Affiliate dashboard shows the referral and earnings<br>";
echo "</div>";

echo "</div>";

// Debug mode option
echo "<div class='section'>";
echo "<h2>🔍 Debug Mode</h2>";
echo "<p>If the system still doesn't work after testing, run these diagnostic scripts:</p>";
echo "<p>";
echo "<a href='diagnose-referral-system.php' style='padding: 10px 20px; background: #28a745; color: white; text-decoration: none; border-radius: 5px; display: inline-block; margin: 5px;'>Run Full Diagnostic</a>";
echo "<a href='affiliate-dashboard.php' style='padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; display: inline-block; margin: 5px;'>Open Dashboard</a>";
echo "</p>";
echo "</div>";

?>
