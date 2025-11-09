<?php
require_once 'config/config.php';

echo "<h2>Fixing Self-Referral Commissions</h2>";
echo "<p>Removing commissions where affiliates purchased their own products...</p>";

// Find and remove self-referral commissions
$query = "
    SELECT 
        ac.id as commission_id,
        ac.commission_amount,
        ac.order_id,
        o.order_number,
        o.user_id as buyer_id,
        a.user_id as affiliate_user_id,
        u.name as buyer_name,
        au.name as affiliate_name
    FROM affiliate_commissions ac
    JOIN orders o ON ac.order_id = o.id
    JOIN affiliates a ON ac.affiliate_id = a.id
    JOIN users u ON o.user_id = u.id
    JOIN users au ON a.user_id = au.id
    WHERE o.user_id = a.user_id
";

$result = $conn->query($query);

if ($result->num_rows > 0) {
    echo "<h3>Found " . $result->num_rows . " Self-Referral Commissions:</h3>";
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>Commission ID</th><th>Order #</th><th>User</th><th>Amount</th><th>Action</th></tr>";
    
    $totalRemoved = 0;
    $totalAmount = 0;
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['commission_id'] . "</td>";
        echo "<td>" . $row['order_number'] . "</td>";
        echo "<td>" . htmlspecialchars($row['buyer_name']) . " (bought own product)</td>";
        echo "<td>₹" . number_format($row['commission_amount'], 2) . "</td>";
        
        // Delete the commission
        $deleteStmt = $conn->prepare("DELETE FROM affiliate_commissions WHERE id = ?");
        $deleteStmt->bind_param("i", $row['commission_id']);
        
        if ($deleteStmt->execute()) {
            echo "<td><span style='color: green;'>✅ Removed</span></td>";
            $totalRemoved++;
            $totalAmount += $row['commission_amount'];
            
            // Update affiliate earnings
            $updateStmt = $conn->prepare("
                UPDATE affiliates 
                SET pending_earnings = pending_earnings - ?,
                    total_earnings = total_earnings - ?,
                    total_sales = total_sales - 1
                WHERE user_id = ?
            ");
            $updateStmt->bind_param("ddi", $row['commission_amount'], $row['commission_amount'], $row['affiliate_user_id']);
            $updateStmt->execute();
        } else {
            echo "<td><span style='color: red;'>❌ Error</span></td>";
        }
        
        echo "</tr>";
    }
    
    echo "</table>";
    
    echo "<h3>Summary:</h3>";
    echo "<ul>";
    echo "<li>✅ Removed <strong>$totalRemoved</strong> self-referral commissions</li>";
    echo "<li>💰 Total amount removed: <strong>₹" . number_format($totalAmount, 2) . "</strong></li>";
    echo "</ul>";
    
} else {
    echo "<p>✅ No self-referral commissions found. System is clean!</p>";
}

// Verify fix
echo "<h3>Verification:</h3>";
$verifyQuery = "
    SELECT COUNT(*) as count
    FROM affiliate_commissions ac
    JOIN orders o ON ac.order_id = o.id
    JOIN affiliates a ON ac.affiliate_id = a.id
    WHERE o.user_id = a.user_id
";
$verifyResult = $conn->query($verifyQuery);
$verifyCount = $verifyResult->fetch_assoc()['count'];

if ($verifyCount == 0) {
    echo "<p style='color: green; font-weight: bold;'>✅ All self-referral commissions have been removed!</p>";
} else {
    echo "<p style='color: red; font-weight: bold;'>⚠️ Warning: Still found $verifyCount self-referral commissions. Please run this script again.</p>";
}

echo "<h3>Prevention Measures Applied:</h3>";
echo "<ul>";
echo "<li>✅ Updated <code>checkout.php</code> to check if buyer is the affiliate</li>";
echo "<li>✅ Updated <code>createAffiliateCommission()</code> function with validation</li>";
echo "<li>✅ Affiliate cookie is cleared when affiliate buys their own products</li>";
echo "</ul>";

echo "<h3>How It Works Now:</h3>";
echo "<ol>";
echo "<li><strong>User A</strong> (Affiliate) shares referral link</li>";
echo "<li><strong>User B</strong> clicks link and signs up</li>";
echo "<li><strong>User B</strong> makes a purchase → <span style='color: green;'>✅ Commission given to User A</span></li>";
echo "<li><strong>User A</strong> makes a purchase → <span style='color: red;'>❌ NO commission (self-purchase)</span></li>";
echo "</ol>";

echo "<h3>✅ Fix Complete!</h3>";
echo "<p><a href='admin/affiliates.php'>Go to Admin Affiliates Page</a></p>";
echo "<p><a href='affiliate-dashboard.php'>Go to Affiliate Dashboard</a></p>";
?>
