<?php
require_once 'config/config.php';

echo "<h1>🧪 Complete Referral Flow Test</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .warning { color: orange; font-weight: bold; }
    .info { color: blue; font-weight: bold; }
    .section { margin: 20px 0; padding: 20px; background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    h2 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
    .step { background: #e7f3ff; padding: 15px; margin: 10px 0; border-left: 4px solid #007bff; }
    table { border-collapse: collapse; width: 100%; margin: 10px 0; }
    th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
    th { background: #007bff; color: white; }
    .btn { padding: 12px 24px; background: #28a745; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; text-decoration: none; display: inline-block; margin: 10px 5px; }
    .btn:hover { opacity: 0.9; }
</style>";

// Get or create affiliate account
$affiliateId = null;
$affiliateCode = null;
$affiliateName = null;

if (isset($_SESSION['user_id'])) {
    // Check if current user has affiliate account
    $stmt = $conn->prepare("SELECT * FROM affiliates WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $affiliate = $stmt->get_result()->fetch_assoc();
    
    if ($affiliate) {
        $affiliateId = $affiliate['id'];
        $affiliateCode = $affiliate['referral_code'];
        $affiliateName = $_SESSION['user_name'];
    }
}

// STEP 1: Show affiliate info
echo "<div class='section'>";
echo "<h2>Step 1: Affiliate Account</h2>";

if ($affiliateId) {
    echo "<p class='success'>✓ You have an affiliate account</p>";
    echo "<table>";
    echo "<tr><th>Affiliate ID</th><td>$affiliateId</td></tr>";
    echo "<tr><th>Your Name</th><td>$affiliateName</td></tr>";
    echo "<tr><th>Referral Code</th><td><strong style='color: #007bff; font-size: 18px;'>$affiliateCode</strong></td></tr>";
    echo "<tr><th>Referral Link</th><td><input type='text' value='" . SITE_URL . "/?ref=$affiliateCode' style='width: 100%; padding: 8px;' readonly onclick='this.select()'></td></tr>";
    echo "</table>";
} else {
    echo "<p class='error'>✗ You don't have an affiliate account</p>";
    echo "<p>Please go to <a href='affiliate-dashboard.php'>Affiliate Dashboard</a> to create one first.</p>";
    echo "</div>";
    exit;
}

echo "</div>";

// STEP 2: Create test user
echo "<div class='section'>";
echo "<h2>Step 2: Create Test User</h2>";

if (isset($_POST['create_user'])) {
    $testName = "Test User " . rand(1000, 9999);
    $testEmail = "test" . rand(1000, 9999) . "@example.com";
    $testPassword = password_hash("password123", PASSWORD_DEFAULT);
    
    // Insert test user with referred_by
    $stmt = $conn->prepare("INSERT INTO users (name, email, password, status, email_verified, referred_by, role) VALUES (?, ?, ?, 'active', TRUE, ?, 'user')");
    $stmt->bind_param("sssi", $testName, $testEmail, $testPassword, $affiliateId);
    
    if ($stmt->execute()) {
        $testUserId = $conn->insert_id;
        
        // Create referral record
        $ipAddress = $_SERVER['REMOTE_ADDR'];
        $userAgent = $_SERVER['HTTP_USER_AGENT'];
        $refStmt = $conn->prepare("INSERT INTO affiliate_referrals (affiliate_id, referred_user_id, referral_code, ip_address, user_agent, converted, purchase_made) VALUES (?, ?, ?, ?, ?, 0, 0)");
        $refStmt->bind_param("iisss", $affiliateId, $testUserId, $affiliateCode, $ipAddress, $userAgent);
        $refStmt->execute();
        
        echo "<div class='step'>";
        echo "<p class='success'>✓ Test user created successfully!</p>";
        echo "<table>";
        echo "<tr><th>User ID</th><td>$testUserId</td></tr>";
        echo "<tr><th>Name</th><td>$testName</td></tr>";
        echo "<tr><th>Email</th><td>$testEmail</td></tr>";
        echo "<tr><th>Password</th><td>password123</td></tr>";
        echo "<tr><th>Referred By</th><td>Affiliate #$affiliateId ($affiliateName)</td></tr>";
        echo "</table>";
        echo "</div>";
        
        // Store in session for next step
        $_SESSION['test_user_id'] = $testUserId;
        $_SESSION['test_user_name'] = $testName;
        $_SESSION['test_user_email'] = $testEmail;
    } else {
        echo "<p class='error'>✗ Failed to create test user</p>";
    }
} else {
    echo "<p>Click the button below to create a test user who will be referred by you:</p>";
    echo "<form method='POST'>";
    echo "<button type='submit' name='create_user' class='btn'>Create Test User</button>";
    echo "</form>";
}

echo "</div>";

// STEP 3: Create test order
if (isset($_SESSION['test_user_id'])) {
    echo "<div class='section'>";
    echo "<h2>Step 3: Create Test Order</h2>";
    
    if (isset($_POST['create_order'])) {
        $testUserId = $_SESSION['test_user_id'];
        
        // Get a random product
        $product = $conn->query("SELECT * FROM products WHERE status = 'active' LIMIT 1")->fetch_assoc();
        
        if ($product) {
            $orderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
            $amount = $product['price'];
            $tax = ($amount * 0) / 100; // 0% tax for test
            $total = $amount + $tax;
            
            // Create order
            $stmt = $conn->prepare("INSERT INTO orders (user_id, order_number, total_amount, discount_amount, tax_amount, final_amount, payment_method, payment_status) VALUES (?, ?, ?, 0, ?, ?, 'cod', 'completed')");
            $stmt->bind_param("isddd", $testUserId, $orderNumber, $amount, $tax, $total);
            
            if ($stmt->execute()) {
                $orderId = $conn->insert_id;
                
                // Add order item
                $expiryDate = date('Y-m-d H:i:s', strtotime('+365 days'));
                $itemStmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, price, download_expiry) VALUES (?, ?, ?, ?)");
                $itemStmt->bind_param("iids", $orderId, $product['id'], $product['price'], $expiryDate);
                $itemStmt->execute();
                
                // Calculate commission
                $commissionRate = 10; // Default 10%
                $affData = $conn->query("SELECT commission_type, commission_value FROM affiliates WHERE id = $affiliateId")->fetch_assoc();
                if ($affData) {
                    if ($affData['commission_type'] == 'percentage') {
                        $commissionAmount = ($total * $affData['commission_value']) / 100;
                    } else {
                        $commissionAmount = $affData['commission_value'];
                    }
                } else {
                    $commissionAmount = ($total * 10) / 100;
                }
                
                // Create commission
                $commStmt = $conn->prepare("INSERT INTO affiliate_commissions (affiliate_id, order_id, commission_amount, commission_type, commission_rate, order_amount, status, level) VALUES (?, ?, ?, 'percentage', 10, ?, 'pending', 1)");
                $commStmt->bind_param("iidd", $affiliateId, $orderId, $commissionAmount, $total);
                $commStmt->execute();
                
                // Update affiliate earnings
                $conn->query("UPDATE affiliates SET pending_earnings = pending_earnings + $commissionAmount, total_earnings = total_earnings + $commissionAmount, total_sales = total_sales + 1 WHERE id = $affiliateId");
                
                // Mark referral as converted
                $conn->query("UPDATE affiliate_referrals SET converted = 1, purchase_made = 1, conversion_date = NOW(), first_purchase_date = NOW() WHERE affiliate_id = $affiliateId AND referred_user_id = $testUserId");
                
                // Update referral count
                $conn->query("UPDATE affiliates SET total_referrals = (SELECT COUNT(*) FROM affiliate_referrals WHERE affiliate_id = $affiliateId AND purchase_made = 1) WHERE id = $affiliateId");
                
                echo "<div class='step'>";
                echo "<p class='success'>✓ Test order created successfully!</p>";
                echo "<table>";
                echo "<tr><th>Order ID</th><td>$orderId</td></tr>";
                echo "<tr><th>Order Number</th><td>$orderNumber</td></tr>";
                echo "<tr><th>Product</th><td>{$product['title']}</td></tr>";
                echo "<tr><th>Amount</th><td>₹" . number_format($total, 2) . "</td></tr>";
                echo "<tr><th>Commission</th><td style='color: green; font-weight: bold;'>₹" . number_format($commissionAmount, 2) . "</td></tr>";
                echo "<tr><th>Status</th><td>Completed</td></tr>";
                echo "</table>";
                echo "</div>";
                
                // Clear test user from session
                unset($_SESSION['test_user_id']);
                unset($_SESSION['test_user_name']);
                unset($_SESSION['test_user_email']);
                
            } else {
                echo "<p class='error'>✗ Failed to create order</p>";
            }
        } else {
            echo "<p class='error'>✗ No products found. Please add a product first.</p>";
        }
    } else {
        echo "<p>Click the button below to create a test order for the test user:</p>";
        echo "<form method='POST'>";
        echo "<button type='submit' name='create_order' class='btn'>Create Test Order</button>";
        echo "</form>";
    }
    
    echo "</div>";
}

// STEP 4: Show results
echo "<div class='section'>";
echo "<h2>Step 4: Verify Results</h2>";

// Get referrals
$referrals = $conn->query("
    SELECT 
        ar.*,
        u.name,
        u.email
    FROM affiliate_referrals ar
    JOIN users u ON ar.referred_user_id = u.id
    WHERE ar.affiliate_id = $affiliateId
    ORDER BY ar.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

echo "<h3>Your Referrals:</h3>";
if (count($referrals) > 0) {
    echo "<table>";
    echo "<tr><th>Name</th><th>Email</th><th>Purchased</th><th>Date</th></tr>";
    foreach ($referrals as $ref) {
        $purchased = $ref['purchase_made'] ? '✓ Yes' : '✗ No';
        $purchasedColor = $ref['purchase_made'] ? 'green' : 'red';
        echo "<tr>";
        echo "<td>{$ref['name']}</td>";
        echo "<td>{$ref['email']}</td>";
        echo "<td style='color: $purchasedColor; font-weight: bold;'>$purchased</td>";
        echo "<td>" . date('Y-m-d H:i', strtotime($ref['created_at'])) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p class='warning'>No referrals yet. Create a test user in Step 2.</p>";
}

// Get commissions
$commissions = $conn->query("
    SELECT 
        ac.*,
        o.order_number
    FROM affiliate_commissions ac
    JOIN orders o ON ac.order_id = o.id
    WHERE ac.affiliate_id = $affiliateId
    ORDER BY ac.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

echo "<h3>Your Commissions:</h3>";
if (count($commissions) > 0) {
    echo "<table>";
    echo "<tr><th>Order #</th><th>Amount</th><th>Commission</th><th>Status</th><th>Date</th></tr>";
    foreach ($commissions as $comm) {
        echo "<tr>";
        echo "<td>{$comm['order_number']}</td>";
        echo "<td>₹" . number_format($comm['order_amount'], 2) . "</td>";
        echo "<td style='color: green; font-weight: bold;'>₹" . number_format($comm['commission_amount'], 2) . "</td>";
        echo "<td>{$comm['status']}</td>";
        echo "<td>" . date('Y-m-d H:i', strtotime($comm['created_at'])) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p class='warning'>No commissions yet. Create a test order in Step 3.</p>";
}

// Get affiliate stats
$stats = $conn->query("SELECT * FROM affiliates WHERE id = $affiliateId")->fetch_assoc();

echo "<h3>Your Statistics:</h3>";
echo "<table>";
echo "<tr><th>Total Referrals</th><td>{$stats['total_referrals']}</td></tr>";
echo "<tr><th>Total Sales</th><td>{$stats['total_sales']}</td></tr>";
echo "<tr><th>Total Earnings</th><td style='color: green; font-weight: bold;'>₹" . number_format($stats['total_earnings'], 2) . "</td></tr>";
echo "<tr><th>Pending Earnings</th><td>₹" . number_format($stats['pending_earnings'], 2) . "</td></tr>";
echo "<tr><th>Paid Earnings</th><td>₹" . number_format($stats['paid_earnings'], 2) . "</td></tr>";
echo "</table>";

echo "</div>";

// Final actions
echo "<div class='section' style='background: #d4edda; border: 2px solid #28a745;'>";
echo "<h2>✅ Testing Complete!</h2>";
echo "<p>You can now:</p>";
echo "<ul>";
echo "<li>View your <a href='affiliate-dashboard.php'>Affiliate Dashboard</a></li>";
echo "<li>Run this test again to create more test data</li>";
echo "<li>Share your real referral link with others</li>";
echo "</ul>";
echo "<p>";
echo "<a href='affiliate-dashboard.php' class='btn' style='background: #007bff;'>Open Dashboard</a>";
echo "<a href='test-referral-flow-complete.php' class='btn' style='background: #28a745;'>Run Test Again</a>";
echo "</p>";
echo "</div>";

?>
