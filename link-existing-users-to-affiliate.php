<?php
require_once 'config/config.php';

echo "<h1>🔗 Link Existing Users to Affiliate</h1>";
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
    .btn { padding: 12px 24px; background: #28a745; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; text-decoration: none; display: inline-block; margin: 10px 5px; }
</style>";

// Must be logged in
if (!isset($_SESSION['user_id'])) {
    die("<p class='error'>Please login first!</p>");
}

$userId = $_SESSION['user_id'];

// Get affiliate account
$affiliate = $conn->query("SELECT * FROM affiliates WHERE user_id = $userId")->fetch_assoc();

if (!$affiliate) {
    die("<p class='error'>You don't have an affiliate account. Go to <a href='affiliate-dashboard.php'>Affiliate Dashboard</a> first.</p>");
}

$affiliateId = $affiliate['id'];
$referralCode = $affiliate['referral_code'];

// Show current state
echo "<div class='section'>";
echo "<h2>Your Affiliate Account</h2>";
echo "<table>";
echo "<tr><th>Affiliate ID</th><td>$affiliateId</td></tr>";
echo "<tr><th>Referral Code</th><td><strong style='color: #007bff;'>$referralCode</strong></td></tr>";
echo "<tr><th>Current Referrals</th><td>{$affiliate['total_referrals']}</td></tr>";
echo "</table>";
echo "</div>";

// Find users without referred_by
echo "<div class='section'>";
echo "<h2>Users Without Referral Link</h2>";

$unlinkedUsers = $conn->query("
    SELECT id, name, email, created_at 
    FROM users 
    WHERE referred_by IS NULL 
    AND role = 'user'
    AND id != $userId
    ORDER BY created_at DESC
")->fetch_all(MYSQLI_ASSOC);

if (count($unlinkedUsers) > 0) {
    echo "<p class='warning'>Found " . count($unlinkedUsers) . " user(s) without referral link:</p>";
    
    echo "<form method='POST'>";
    echo "<table>";
    echo "<tr><th>Select</th><th>ID</th><th>Name</th><th>Email</th><th>Joined</th></tr>";
    
    foreach ($unlinkedUsers as $user) {
        echo "<tr>";
        echo "<td><input type='checkbox' name='users[]' value='{$user['id']}' checked></td>";
        echo "<td>{$user['id']}</td>";
        echo "<td>{$user['name']}</td>";
        echo "<td>{$user['email']}</td>";
        echo "<td>" . date('Y-m-d H:i', strtotime($user['created_at'])) . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    echo "<p><button type='submit' name='link_users' class='btn'>Link Selected Users to My Affiliate Account</button></p>";
    echo "</form>";
} else {
    echo "<p class='success'>✓ All users are already linked to an affiliate</p>";
}

echo "</div>";

// Process linking
if (isset($_POST['link_users']) && isset($_POST['users'])) {
    echo "<div class='section'>";
    echo "<h2>Linking Users...</h2>";
    
    $selectedUsers = $_POST['users'];
    $linkedCount = 0;
    
    foreach ($selectedUsers as $selectedUserId) {
        $selectedUserId = (int)$selectedUserId;
        
        // Get user info
        $user = $conn->query("SELECT * FROM users WHERE id = $selectedUserId")->fetch_assoc();
        
        if ($user) {
            // Update referred_by
            $conn->query("UPDATE users SET referred_by = $affiliateId WHERE id = $selectedUserId");
            
            // Create referral record
            $ipAddress = $_SERVER['REMOTE_ADDR'];
            $userAgent = $_SERVER['HTTP_USER_AGENT'];
            
            $stmt = $conn->prepare("INSERT INTO affiliate_referrals (affiliate_id, referred_user_id, referral_code, ip_address, user_agent, converted, purchase_made) VALUES (?, ?, ?, ?, ?, 0, 0) ON DUPLICATE KEY UPDATE affiliate_id = affiliate_id");
            $stmt->bind_param("iisss", $affiliateId, $selectedUserId, $referralCode, $ipAddress, $userAgent);
            $stmt->execute();
            
            // Check if user has orders
            $orders = $conn->query("SELECT * FROM orders WHERE user_id = $selectedUserId AND payment_status = 'completed'")->fetch_all(MYSQLI_ASSOC);
            
            if (count($orders) > 0) {
                echo "<p class='success'>✓ Linked: {$user['name']} - Found " . count($orders) . " order(s)</p>";
                
                // Get affiliate commission settings
                $affData = $conn->query("SELECT commission_type, commission_value FROM affiliates WHERE id = $affiliateId")->fetch_assoc();
                
                // Create commissions for existing orders
                foreach ($orders as $order) {
                    $orderId = $order['id'];
                    $orderAmount = $order['final_amount'];
                    
                    // Check if commission already exists
                    $existingComm = $conn->query("SELECT id FROM affiliate_commissions WHERE order_id = $orderId AND affiliate_id = $affiliateId")->fetch_assoc();
                    
                    if (!$existingComm) {
                        // Calculate commission based on affiliate settings
                        if ($affData['commission_type'] == 'percentage') {
                            $commissionAmount = ($orderAmount * $affData['commission_value']) / 100;
                            $commissionRate = $affData['commission_value'];
                        } else {
                            $commissionAmount = $affData['commission_value'];
                            $commissionRate = 0;
                        }
                        
                        $stmt = $conn->prepare("INSERT INTO affiliate_commissions (affiliate_id, order_id, commission_amount, commission_type, commission_rate, order_amount, status, level, created_at) VALUES (?, ?, ?, ?, ?, ?, 'pending', 1, ?)");
                        $stmt->bind_param("iidsdds", $affiliateId, $orderId, $commissionAmount, $affData['commission_type'], $commissionRate, $orderAmount, $order['created_at']);
                        $stmt->execute();
                        
                        // Update affiliate earnings
                        $conn->query("UPDATE affiliates SET pending_earnings = pending_earnings + $commissionAmount, total_earnings = total_earnings + $commissionAmount, total_sales = total_sales + 1 WHERE id = $affiliateId");
                        
                        echo "<p style='margin-left: 30px;'>→ Created commission: ₹" . number_format($commissionAmount, 2) . " for order {$order['order_number']}</p>";
                    }
                }
                
                // Mark referral as converted
                $conn->query("UPDATE affiliate_referrals SET converted = 1, purchase_made = 1, conversion_date = NOW(), first_purchase_date = NOW() WHERE affiliate_id = $affiliateId AND referred_user_id = $selectedUserId");
                
            } else {
                echo "<p class='success'>✓ Linked: {$user['name']} - No orders yet</p>";
            }
            
            $linkedCount++;
        }
    }
    
    // Update referral count
    $conn->query("UPDATE affiliates SET total_referrals = (SELECT COUNT(*) FROM affiliate_referrals WHERE affiliate_id = $affiliateId AND purchase_made = 1) WHERE id = $affiliateId");
    
    echo "<p class='success'><strong>✓ Successfully linked $linkedCount user(s) to your affiliate account!</strong></p>";
    
    echo "</div>";
    
    // Show updated stats
    $affiliate = $conn->query("SELECT * FROM affiliates WHERE id = $affiliateId")->fetch_assoc();
    
    echo "<div class='section' style='background: #d4edda; border: 2px solid #28a745;'>";
    echo "<h2>✅ Updated Statistics</h2>";
    echo "<table>";
    echo "<tr><th>Total Referrals</th><td>{$affiliate['total_referrals']}</td></tr>";
    echo "<tr><th>Total Sales</th><td>{$affiliate['total_sales']}</td></tr>";
    echo "<tr><th>Total Earnings</th><td style='color: green; font-weight: bold;'>₹" . number_format($affiliate['total_earnings'], 2) . "</td></tr>";
    echo "<tr><th>Pending Earnings</th><td>₹" . number_format($affiliate['pending_earnings'], 2) . "</td></tr>";
    echo "</table>";
    
    echo "<p><a href='affiliate-dashboard.php' class='btn' style='background: #007bff;'>📊 View Affiliate Dashboard</a></p>";
    echo "</div>";
}

?>
