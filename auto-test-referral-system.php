<?php
require_once 'config/config.php';

echo "<h1>🤖 Automated Referral System Test</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .warning { color: orange; font-weight: bold; }
    .section { margin: 20px 0; padding: 20px; background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    h2 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
    table { border-collapse: collapse; width: 100%; margin: 10px 0; }
    th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
    th { background: #007bff; color: white; }
    .step { background: #e7f3ff; padding: 15px; margin: 10px 0; border-left: 4px solid #007bff; }
</style>";

// Must be logged in
if (!isset($_SESSION['user_id'])) {
    die("<p class='error'>Please login first!</p>");
}

$userId = $_SESSION['user_id'];

// STEP 1: Get or create affiliate account
echo "<div class='section'>";
echo "<h2>Step 1: Affiliate Account Setup</h2>";

$affiliate = $conn->query("SELECT * FROM affiliates WHERE user_id = $userId")->fetch_assoc();

if (!$affiliate) {
    // Create affiliate account
    $referralCode = strtoupper(substr(md5(uniqid($userId, true)), 0, 8));
    $stmt = $conn->prepare("INSERT INTO affiliates (user_id, referral_code, commission_type, commission_value, status) VALUES (?, ?, 'percentage', 10, 'active')");
    $stmt->bind_param("is", $userId, $referralCode);
    $stmt->execute();
    $affiliateId = $conn->insert_id;
    echo "<p class='success'>✓ Created affiliate account with code: <strong>$referralCode</strong></p>";
} else {
    $affiliateId = $affiliate['id'];
    $referralCode = $affiliate['referral_code'];
    echo "<p class='success'>✓ Using existing affiliate account: <strong>$referralCode</strong></p>";
}

echo "</div>";

// STEP 2: Create test user
echo "<div class='section'>";
echo "<h2>Step 2: Create Test User</h2>";

$testName = "Test User " . rand(1000, 9999);
$testEmail = "test" . rand(1000, 9999) . "@example.com";
$testPassword = password_hash("password123", PASSWORD_DEFAULT);

$stmt = $conn->prepare("INSERT INTO users (name, email, password, status, email_verified, referred_by, role) VALUES (?, ?, ?, 'active', TRUE, ?, 'user')");
$stmt->bind_param("sssi", $testName, $testEmail, $testPassword, $affiliateId);

if ($stmt->execute()) {
    $testUserId = $conn->insert_id;
    echo "<div class='step'>";
    echo "<p class='success'>✓ Created test user</p>";
    echo "<table>";
    echo "<tr><th>User ID</th><td>$testUserId</td></tr>";
    echo "<tr><th>Name</th><td>$testName</td></tr>";
    echo "<tr><th>Email</th><td>$testEmail</td></tr>";
    echo "<tr><th>referred_by</th><td>$affiliateId</td></tr>";
    echo "</table>";
    echo "</div>";
} else {
    die("<p class='error'>✗ Failed to create user: " . $conn->error . "</p>");
}

echo "</div>";

// STEP 3: Create referral record
echo "<div class='section'>";
echo "<h2>Step 3: Create Referral Record</h2>";

$ipAddress = $_SERVER['REMOTE_ADDR'];
$userAgent = $_SERVER['HTTP_USER_AGENT'];

$stmt = $conn->prepare("INSERT INTO affiliate_referrals (affiliate_id, referred_user_id, referral_code, ip_address, user_agent, converted, purchase_made) VALUES (?, ?, ?, ?, ?, 0, 0)");
$stmt->bind_param("iisss", $affiliateId, $testUserId, $referralCode, $ipAddress, $userAgent);

if ($stmt->execute()) {
    $referralId = $conn->insert_id;
    echo "<p class='success'>✓ Created referral record #$referralId</p>";
} else {
    echo "<p class='error'>✗ Failed to create referral: " . $conn->error . "</p>";
}

echo "</div>";

// STEP 4: Create test product if needed
echo "<div class='section'>";
echo "<h2>Step 4: Get Test Product</h2>";

$product = $conn->query("SELECT * FROM products WHERE status = 'active' LIMIT 1")->fetch_assoc();

if (!$product) {
    // Create a test product
    $conn->query("INSERT INTO products (title, description, price, status, product_type) VALUES ('Test Product', 'Test Description', 100, 'active', 'ebook')");
    $product = $conn->query("SELECT * FROM products WHERE status = 'active' LIMIT 1")->fetch_assoc();
}

if ($product) {
    echo "<p class='success'>✓ Using product: {$product['title']} (₹{$product['price']})</p>";
} else {
    die("<p class='error'>✗ No products available</p>");
}

echo "</div>";

// STEP 5: Create test order
echo "<div class='section'>";
echo "<h2>Step 5: Create Test Order</h2>";

$orderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
$amount = $product['price'];
$tax = 0;
$total = $amount + $tax;

$stmt = $conn->prepare("INSERT INTO orders (user_id, order_number, total_amount, discount_amount, tax_amount, final_amount, payment_method, payment_status) VALUES (?, ?, ?, 0, ?, ?, 'cod', 'completed')");
$stmt->bind_param("isddd", $testUserId, $orderNumber, $amount, $tax, $total);

if ($stmt->execute()) {
    $orderId = $conn->insert_id;
    
    echo "<div class='step'>";
    echo "<p class='success'>✓ Created order</p>";
    echo "<table>";
    echo "<tr><th>Order ID</th><td>$orderId</td></tr>";
    echo "<tr><th>Order Number</th><td>$orderNumber</td></tr>";
    echo "<tr><th>Amount</th><td>₹" . number_format($total, 2) . "</td></tr>";
    echo "<tr><th>Status</th><td>completed</td></tr>";
    echo "</table>";
    echo "</div>";
    
    // Add order item
    $expiryDate = date('Y-m-d H:i:s', strtotime('+365 days'));
    $itemStmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, price, download_expiry) VALUES (?, ?, ?, ?)");
    $itemStmt->bind_param("iids", $orderId, $product['id'], $product['price'], $expiryDate);
    $itemStmt->execute();
    
} else {
    die("<p class='error'>✗ Failed to create order: " . $conn->error . "</p>");
}

echo "</div>";

// STEP 6: Create commission
echo "<div class='section'>";
echo "<h2>Step 6: Create Commission</h2>";

// Get affiliate commission settings
$affData = $conn->query("SELECT commission_type, commission_value FROM affiliates WHERE id = $affiliateId")->fetch_assoc();

if ($affData['commission_type'] == 'percentage') {
    $commissionAmount = ($total * $affData['commission_value']) / 100;
    $commissionRate = $affData['commission_value'];
} else {
    $commissionAmount = $affData['commission_value'];
    $commissionRate = 0;
}

$stmt = $conn->prepare("INSERT INTO affiliate_commissions (affiliate_id, order_id, commission_amount, commission_type, commission_rate, order_amount, status, level) VALUES (?, ?, ?, ?, ?, ?, 'pending', 1)");
$stmt->bind_param("iidsdd", $affiliateId, $orderId, $commissionAmount, $affData['commission_type'], $commissionRate, $total);

if ($stmt->execute()) {
    $commissionId = $conn->insert_id;
    
    echo "<div class='step'>";
    echo "<p class='success'>✓ Created commission</p>";
    echo "<table>";
    echo "<tr><th>Commission ID</th><td>$commissionId</td></tr>";
    echo "<tr><th>Order Amount</th><td>₹" . number_format($total, 2) . "</td></tr>";
    echo "<tr><th>Commission Rate</th><td>{$commissionRate}%</td></tr>";
    echo "<tr><th>Commission Amount</th><td style='color: green; font-size: 18px;'>₹" . number_format($commissionAmount, 2) . "</td></tr>";
    echo "</table>";
    echo "</div>";
} else {
    die("<p class='error'>✗ Failed to create commission: " . $conn->error . "</p>");
}

echo "</div>";

// STEP 7: Update affiliate stats
echo "<div class='section'>";
echo "<h2>Step 7: Update Affiliate Statistics</h2>";

$conn->query("UPDATE affiliates SET pending_earnings = pending_earnings + $commissionAmount, total_earnings = total_earnings + $commissionAmount, total_sales = total_sales + 1 WHERE id = $affiliateId");

echo "<p class='success'>✓ Updated affiliate earnings</p>";

// Mark referral as converted
$conn->query("UPDATE affiliate_referrals SET converted = 1, purchase_made = 1, conversion_date = NOW(), first_purchase_date = NOW() WHERE id = $referralId");

echo "<p class='success'>✓ Marked referral as converted</p>";

// Update referral count
$conn->query("UPDATE affiliates SET total_referrals = (SELECT COUNT(*) FROM affiliate_referrals WHERE affiliate_id = $affiliateId AND purchase_made = 1) WHERE id = $affiliateId");

echo "<p class='success'>✓ Updated referral count</p>";

echo "</div>";

// STEP 8: Verify results
echo "<div class='section'>";
echo "<h2>Step 8: Verification</h2>";

// Check affiliate stats
$affiliate = $conn->query("SELECT * FROM affiliates WHERE id = $affiliateId")->fetch_assoc();

echo "<h3>Your Affiliate Stats:</h3>";
echo "<table>";
echo "<tr><th>Total Referrals</th><td>{$affiliate['total_referrals']}</td></tr>";
echo "<tr><th>Total Sales</th><td>{$affiliate['total_sales']}</td></tr>";
echo "<tr><th>Total Earnings</th><td style='color: green; font-weight: bold;'>₹" . number_format($affiliate['total_earnings'], 2) . "</td></tr>";
echo "<tr><th>Pending Earnings</th><td>₹" . number_format($affiliate['pending_earnings'], 2) . "</td></tr>";
echo "</table>";

// Check dashboard query
echo "<h3>Dashboard Query Test:</h3>";
$dashboardQuery = "
    SELECT 
        u.id as user_id,
        u.name as user_name,
        u.email as user_email,
        u.created_at as joined_date,
        COUNT(DISTINCT o.id) as total_orders,
        COALESCE(SUM(o.final_amount), 0) as total_spent,
        COALESCE(SUM(ac.commission_amount), 0) as total_commission_earned
    FROM users u
    LEFT JOIN orders o ON u.id = o.user_id AND o.payment_status = 'completed'
    LEFT JOIN affiliate_commissions ac ON o.id = ac.order_id AND ac.affiliate_id = $affiliateId
    WHERE u.referred_by = $affiliateId 
    AND u.id != $userId 
    AND u.role = 'user'
    GROUP BY u.id
    ORDER BY u.created_at DESC
";

$dashboardResult = $conn->query($dashboardQuery);

if ($dashboardResult && $dashboardResult->num_rows > 0) {
    echo "<p class='success'>✓ Dashboard query returns {$dashboardResult->num_rows} referral(s)</p>";
    echo "<table>";
    echo "<tr><th>Name</th><th>Email</th><th>Orders</th><th>Spent</th><th>Commission</th></tr>";
    while ($row = $dashboardResult->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['user_name']}</td>";
        echo "<td>{$row['user_email']}</td>";
        echo "<td>{$row['total_orders']}</td>";
        echo "<td>₹" . number_format($row['total_spent'], 2) . "</td>";
        echo "<td style='color: green;'>₹" . number_format($row['total_commission_earned'], 2) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p class='error'>✗ Dashboard query returns 0 results!</p>";
    echo "<p>This means the dashboard won't show your referrals.</p>";
}

echo "</div>";

// FINAL RESULT
echo "<div class='section' style='background: #d4edda; border: 2px solid #28a745;'>";
echo "<h2>✅ Test Complete!</h2>";

echo "<h3>What was created:</h3>";
echo "<ul>";
echo "<li>✓ 1 Test User (referred by you)</li>";
echo "<li>✓ 1 Referral Record</li>";
echo "<li>✓ 1 Completed Order</li>";
echo "<li>✓ 1 Commission (₹" . number_format($commissionAmount, 2) . ")</li>";
echo "</ul>";

echo "<h3>Next Steps:</h3>";
echo "<p><a href='affiliate-dashboard.php' style='padding: 12px 24px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; display: inline-block; margin: 10px 5px;'>📊 View Affiliate Dashboard</a></p>";

echo "<p>If the dashboard still shows 0 referrals, there's an issue with the dashboard query or display logic.</p>";

echo "</div>";

?>
