<?php
require_once 'config/config.php';
require_once 'includes/affiliate-functions.php';

echo "<h2>Creating Missing Commissions</h2>";
echo "<p>This script will create commissions for orders that should have generated commissions but didn't.</p>";
echo "<hr>";

// Find orders from referred users that don't have commissions
$query = "
    SELECT 
        o.id as order_id,
        o.order_number,
        o.user_id,
        o.final_amount,
        o.created_at,
        u.name as buyer_name,
        u.referred_by as affiliate_id,
        a.referral_code,
        a.commission_type,
        a.commission_value
    FROM orders o
    JOIN users u ON o.user_id = u.id
    JOIN affiliates a ON u.referred_by = a.id
    LEFT JOIN affiliate_commissions ac ON o.id = ac.order_id AND ac.affiliate_id = a.id
    WHERE o.payment_status = 'completed'
    AND u.referred_by IS NOT NULL
    AND u.role = 'user'
    AND ac.id IS NULL
    ORDER BY o.created_at DESC
";

$result = $conn->query($query);

if ($result->num_rows > 0) {
    echo "<h3>Found " . $result->num_rows . " order(s) without commissions</h3>";
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>Order #</th><th>Buyer</th><th>Amount</th><th>Affiliate</th><th>Commission</th><th>Action</th></tr>";
    
    $created = 0;
    $totalCommission = 0;
    
    while ($order = $result->fetch_assoc()) {
        // Calculate commission
        $commissionAmount = calculateCommission(
            $order['final_amount'], 
            $order['commission_type'], 
            $order['commission_value']
        );
        
        echo "<tr>";
        echo "<td>" . $order['order_number'] . "</td>";
        echo "<td>" . htmlspecialchars($order['buyer_name']) . "</td>";
        echo "<td>₹" . number_format($order['final_amount'], 2) . "</td>";
        echo "<td>" . $order['referral_code'] . "</td>";
        echo "<td>₹" . number_format($commissionAmount, 2) . "</td>";
        
        // Create commission
        $stmt = $conn->prepare("
            INSERT INTO affiliate_commissions 
            (affiliate_id, order_id, commission_amount, commission_type, commission_rate, order_amount, level, status) 
            VALUES (?, ?, ?, ?, ?, ?, 1, 'pending')
        ");
        $stmt->bind_param(
            "iidsdd", 
            $order['affiliate_id'], 
            $order['order_id'], 
            $commissionAmount, 
            $order['commission_type'], 
            $order['commission_value'], 
            $order['final_amount']
        );
        
        if ($stmt->execute()) {
            echo "<td style='color: green;'>✅ Created</td>";
            $created++;
            $totalCommission += $commissionAmount;
            
            // Update affiliate earnings
            $updateStmt = $conn->prepare("
                UPDATE affiliates 
                SET pending_earnings = pending_earnings + ?,
                    total_earnings = total_earnings + ?,
                    total_sales = total_sales + 1
                WHERE id = ?
            ");
            $updateStmt->bind_param("ddi", $commissionAmount, $commissionAmount, $order['affiliate_id']);
            $updateStmt->execute();
            
            // Update order with affiliate info
            $orderUpdateStmt = $conn->prepare("
                UPDATE orders 
                SET affiliate_id = ?, referral_code = ? 
                WHERE id = ?
            ");
            $orderUpdateStmt->bind_param("isi", $order['affiliate_id'], $order['referral_code'], $order['order_id']);
            $orderUpdateStmt->execute();
            
        } else {
            echo "<td style='color: red;'>❌ Error: " . $conn->error . "</td>";
        }
        
        echo "</tr>";
    }
    
    echo "</table>";
    
    echo "<h3>Summary:</h3>";
    echo "<ul>";
    echo "<li>✅ Created <strong>$created</strong> commission(s)</li>";
    echo "<li>💰 Total commission amount: <strong>₹" . number_format($totalCommission, 2) . "</strong></li>";
    echo "</ul>";
    
} else {
    echo "<p style='color: green;'>✅ No missing commissions found! All orders have proper commissions.</p>";
}

// Verification
echo "<h3>Verification:</h3>";

// Check if there are still orders without commissions
$verifyQuery = "
    SELECT COUNT(*) as count
    FROM orders o
    JOIN users u ON o.user_id = u.id
    LEFT JOIN affiliate_commissions ac ON o.id = ac.order_id
    WHERE o.payment_status = 'completed'
    AND u.referred_by IS NOT NULL
    AND u.role = 'user'
    AND ac.id IS NULL
";
$verifyResult = $conn->query($verifyQuery);
$remainingCount = $verifyResult->fetch_assoc()['count'];

if ($remainingCount == 0) {
    echo "<p style='color: green; font-weight: bold;'>✅ All orders now have commissions!</p>";
} else {
    echo "<p style='color: orange; font-weight: bold;'>⚠️ Warning: Still found $remainingCount order(s) without commissions. Please run this script again.</p>";
}

// Show affiliate stats
echo "<h3>Updated Affiliate Statistics:</h3>";

$affiliates = $conn->query("
    SELECT 
        a.id,
        a.referral_code,
        u.name,
        a.total_referrals,
        a.total_sales,
        a.total_earnings,
        a.pending_earnings
    FROM affiliates a
    JOIN users u ON a.user_id = u.id
    WHERE a.total_referrals > 0
");

if ($affiliates->num_rows > 0) {
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>Affiliate</th><th>Code</th><th>Referrals</th><th>Sales</th><th>Total Earnings</th><th>Pending</th></tr>";
    
    while ($aff = $affiliates->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($aff['name']) . "</td>";
        echo "<td>" . $aff['referral_code'] . "</td>";
        echo "<td>" . $aff['total_referrals'] . "</td>";
        echo "<td>" . $aff['total_sales'] . "</td>";
        echo "<td>₹" . number_format($aff['total_earnings'], 2) . "</td>";
        echo "<td>₹" . number_format($aff['pending_earnings'], 2) . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
}

echo "<h3>✅ Process Complete!</h3>";
echo "<p><a href='affiliate-dashboard.php'>Go to Affiliate Dashboard</a> - Check your updated earnings</p>";
echo "<p><a href='admin/affiliates.php'>Go to Admin Panel</a> - Verify affiliate stats</p>";

?>

<style>
body {
    font-family: Arial, sans-serif;
    padding: 20px;
    max-width: 1200px;
    margin: 0 auto;
}
table {
    border-collapse: collapse;
    margin: 20px 0;
    width: 100%;
}
th {
    background: #4CAF50;
    color: white;
    padding: 12px;
}
td {
    padding: 10px;
    border: 1px solid #ddd;
}
h2 {
    color: #333;
    border-bottom: 3px solid #4CAF50;
    padding-bottom: 10px;
}
h3 {
    color: #555;
    margin-top: 30px;
}
a {
    color: #4CAF50;
    text-decoration: none;
    font-weight: bold;
}
a:hover {
    text-decoration: underline;
}
</style>
