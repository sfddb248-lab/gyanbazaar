<?php
require_once 'config/config.php';

echo "<h1>Referral System Diagnostic</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; padding: 20px; }
    .success { color: green; }
    .error { color: red; }
    .warning { color: orange; }
    table { border-collapse: collapse; width: 100%; margin: 20px 0; }
    th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
    th { background-color: #4CAF50; color: white; }
    .section { margin: 30px 0; padding: 20px; background: #f5f5f5; border-radius: 5px; }
</style>";

// Test 1: Check if affiliate-functions.php is loaded
echo "<div class='section'>";
echo "<h2>1. Function Availability Check</h2>";
$functions = [
    'getAffiliateByCode',
    'trackAffiliateReferral',
    'createAffiliateCommission',
    'markReferralConverted'
];

foreach ($functions as $func) {
    if (function_exists($func)) {
        echo "<p class='success'>✓ Function '$func' exists</p>";
    } else {
        echo "<p class='error'>✗ Function '$func' NOT FOUND</p>";
    }
}
echo "</div>";

// Test 2: Check database tables
echo "<div class='section'>";
echo "<h2>2. Database Tables Check</h2>";
$tables = ['affiliates', 'affiliate_referrals', 'affiliate_commissions', 'orders', 'users'];
foreach ($tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows > 0) {
        $count = $conn->query("SELECT COUNT(*) as count FROM $table")->fetch_assoc()['count'];
        echo "<p class='success'>✓ Table '$table' exists ($count records)</p>";
    } else {
        echo "<p class='error'>✗ Table '$table' NOT FOUND</p>";
    }
}
echo "</div>";

// Test 3: Check users.referred_by column
echo "<div class='section'>";
echo "<h2>3. Users Table Structure</h2>";
$columns = $conn->query("SHOW COLUMNS FROM users LIKE 'referred_by'");
if ($columns->num_rows > 0) {
    echo "<p class='success'>✓ Column 'referred_by' exists in users table</p>";
    
    // Check users with referrals
    $referredUsers = $conn->query("SELECT COUNT(*) as count FROM users WHERE referred_by IS NOT NULL")->fetch_assoc()['count'];
    echo "<p>Users with referrals: <strong>$referredUsers</strong></p>";
} else {
    echo "<p class='error'>✗ Column 'referred_by' NOT FOUND in users table</p>";
}
echo "</div>";

// Test 4: Check affiliate accounts
echo "<div class='section'>";
echo "<h2>4. Affiliate Accounts</h2>";
$affiliates = $conn->query("
    SELECT a.*, u.name, u.email 
    FROM affiliates a 
    JOIN users u ON a.user_id = u.id 
    ORDER BY a.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

if (count($affiliates) > 0) {
    echo "<p class='success'>Found " . count($affiliates) . " affiliate account(s)</p>";
    echo "<table>";
    echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Referral Code</th><th>Status</th><th>Referrals</th><th>Sales</th><th>Earnings</th></tr>";
    foreach ($affiliates as $aff) {
        echo "<tr>";
        echo "<td>{$aff['id']}</td>";
        echo "<td>{$aff['name']}</td>";
        echo "<td>{$aff['email']}</td>";
        echo "<td><strong>{$aff['referral_code']}</strong></td>";
        echo "<td>{$aff['status']}</td>";
        echo "<td>{$aff['total_referrals']}</td>";
        echo "<td>{$aff['total_sales']}</td>";
        echo "<td>₹" . number_format($aff['total_earnings'], 2) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p class='warning'>⚠ No affiliate accounts found</p>";
}
echo "</div>";

// Test 5: Check referrals
echo "<div class='section'>";
echo "<h2>5. Referral Records</h2>";
$referrals = $conn->query("
    SELECT 
        ar.*,
        a.referral_code,
        u.name as referred_user_name,
        u.email as referred_user_email,
        u.referred_by
    FROM affiliate_referrals ar
    JOIN affiliates a ON ar.affiliate_id = a.id
    LEFT JOIN users u ON ar.referred_user_id = u.id
    ORDER BY ar.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

if (count($referrals) > 0) {
    echo "<p class='success'>Found " . count($referrals) . " referral record(s)</p>";
    echo "<table>";
    echo "<tr><th>ID</th><th>Referral Code</th><th>Referred User</th><th>Email</th><th>referred_by</th><th>Converted</th><th>Purchase Made</th><th>Date</th></tr>";
    foreach ($referrals as $ref) {
        $convertedIcon = $ref['converted'] ? '✓' : '✗';
        $purchaseIcon = $ref['purchase_made'] ? '✓' : '✗';
        echo "<tr>";
        echo "<td>{$ref['id']}</td>";
        echo "<td>{$ref['referral_code']}</td>";
        echo "<td>" . ($ref['referred_user_name'] ?? 'N/A') . "</td>";
        echo "<td>" . ($ref['referred_user_email'] ?? 'N/A') . "</td>";
        echo "<td>" . ($ref['referred_by'] ?? 'NULL') . "</td>";
        echo "<td>{$convertedIcon}</td>";
        echo "<td>{$purchaseIcon}</td>";
        echo "<td>" . date('Y-m-d H:i', strtotime($ref['created_at'])) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p class='warning'>⚠ No referral records found</p>";
}
echo "</div>";

// Test 6: Check orders
echo "<div class='section'>";
echo "<h2>6. Orders</h2>";
$orders = $conn->query("
    SELECT 
        o.*,
        u.name as user_name,
        u.email as user_email,
        u.referred_by
    FROM orders o
    JOIN users u ON o.user_id = u.id
    ORDER BY o.created_at DESC
    LIMIT 10
")->fetch_all(MYSQLI_ASSOC);

if (count($orders) > 0) {
    echo "<p class='success'>Found " . count($orders) . " recent order(s)</p>";
    echo "<table>";
    echo "<tr><th>Order #</th><th>User</th><th>Email</th><th>referred_by</th><th>Amount</th><th>Status</th><th>Date</th></tr>";
    foreach ($orders as $order) {
        echo "<tr>";
        echo "<td>{$order['order_number']}</td>";
        echo "<td>{$order['user_name']}</td>";
        echo "<td>{$order['user_email']}</td>";
        echo "<td>" . ($order['referred_by'] ?? 'NULL') . "</td>";
        echo "<td>₹" . number_format($order['final_amount'], 2) . "</td>";
        echo "<td>{$order['payment_status']}</td>";
        echo "<td>" . date('Y-m-d H:i', strtotime($order['created_at'])) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p class='warning'>⚠ No orders found</p>";
}
echo "</div>";

// Test 7: Check commissions
echo "<div class='section'>";
echo "<h2>7. Commissions</h2>";
$commissions = $conn->query("
    SELECT 
        ac.*,
        a.referral_code,
        o.order_number,
        u.name as affiliate_name
    FROM affiliate_commissions ac
    JOIN affiliates a ON ac.affiliate_id = a.id
    JOIN orders o ON ac.order_id = o.id
    JOIN users u ON a.user_id = u.id
    ORDER BY ac.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

if (count($commissions) > 0) {
    echo "<p class='success'>Found " . count($commissions) . " commission record(s)</p>";
    echo "<table>";
    echo "<tr><th>ID</th><th>Affiliate</th><th>Order #</th><th>Amount</th><th>Status</th><th>Level</th><th>Date</th></tr>";
    foreach ($commissions as $comm) {
        echo "<tr>";
        echo "<td>{$comm['id']}</td>";
        echo "<td>{$comm['affiliate_name']}</td>";
        echo "<td>{$comm['order_number']}</td>";
        echo "<td>₹" . number_format($comm['commission_amount'], 2) . "</td>";
        echo "<td>{$comm['status']}</td>";
        echo "<td>{$comm['level']}</td>";
        echo "<td>" . date('Y-m-d H:i', strtotime($comm['created_at'])) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p class='warning'>⚠ No commission records found</p>";
}
echo "</div>";

// Test 8: Check for missing commissions
echo "<div class='section'>";
echo "<h2>8. Missing Commissions Check</h2>";
$missingCommissions = $conn->query("
    SELECT 
        o.id as order_id,
        o.order_number,
        o.final_amount,
        u.name as user_name,
        u.referred_by,
        a.referral_code,
        a.id as affiliate_id
    FROM orders o
    JOIN users u ON o.user_id = u.id
    JOIN affiliates a ON u.referred_by = a.id
    LEFT JOIN affiliate_commissions ac ON o.id = ac.order_id AND ac.affiliate_id = a.id
    WHERE o.payment_status = 'completed'
    AND u.referred_by IS NOT NULL
    AND ac.id IS NULL
    ORDER BY o.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

if (count($missingCommissions) > 0) {
    echo "<p class='error'>✗ Found " . count($missingCommissions) . " order(s) missing commissions!</p>";
    echo "<table>";
    echo "<tr><th>Order #</th><th>User</th><th>Affiliate ID</th><th>Referral Code</th><th>Amount</th></tr>";
    foreach ($missingCommissions as $missing) {
        echo "<tr>";
        echo "<td>{$missing['order_number']}</td>";
        echo "<td>{$missing['user_name']}</td>";
        echo "<td>{$missing['affiliate_id']}</td>";
        echo "<td>{$missing['referral_code']}</td>";
        echo "<td>₹" . number_format($missing['final_amount'], 2) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "<p><a href='create-missing-commissions.php' style='padding: 10px 20px; background: #4CAF50; color: white; text-decoration: none; border-radius: 5px;'>Fix Missing Commissions</a></p>";
} else {
    echo "<p class='success'>✓ All completed orders have commissions</p>";
}
echo "</div>";

// Test 9: Referral Flow Test
echo "<div class='section'>";
echo "<h2>9. Referral Flow Summary</h2>";
echo "<ol>";
echo "<li><strong>Signup with referral:</strong> User clicks referral link → Cookie set → User signs up → referred_by field populated</li>";
echo "<li><strong>Purchase:</strong> User makes purchase → Commission created → Referral marked as converted</li>";
echo "<li><strong>Commission tracking:</strong> Commission recorded in affiliate_commissions table</li>";
echo "</ol>";

// Check if there are any users who signed up with referral but haven't purchased
$signedUpNoPurchase = $conn->query("
    SELECT COUNT(*) as count 
    FROM users u
    LEFT JOIN orders o ON u.id = o.user_id AND o.payment_status = 'completed'
    WHERE u.referred_by IS NOT NULL
    AND o.id IS NULL
")->fetch_assoc()['count'];

echo "<p>Users signed up via referral but haven't purchased yet: <strong>$signedUpNoPurchase</strong></p>";
echo "</div>";

echo "<div class='section'>";
echo "<h2>10. Recommendations</h2>";
echo "<ul>";

if (count($affiliates) == 0) {
    echo "<li class='error'>Create an affiliate account first</li>";
}

if (count($missingCommissions) > 0) {
    echo "<li class='error'>Run the 'create-missing-commissions.php' script to fix missing commissions</li>";
}

$columns = $conn->query("SHOW COLUMNS FROM users LIKE 'referred_by'");
if ($columns->num_rows == 0) {
    echo "<li class='error'>Run 'fix-referral-column.php' to add the referred_by column</li>";
}

echo "<li>Test the complete flow: Share referral link → New user signs up → User makes purchase → Check commission</li>";
echo "<li>Make sure cookies are enabled in your browser</li>";
echo "<li>Clear browser cookies and test with a fresh session</li>";
echo "</ul>";
echo "</div>";

?>
