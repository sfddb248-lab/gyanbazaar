<?php
require_once 'config/config.php';

echo "<h1>💰 Update Commission Rate & Recalculate</h1>";
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
    input[type="number"] { padding: 8px; font-size: 16px; width: 100px; }
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

// Show current settings
echo "<div class='section'>";
echo "<h2>Current Commission Settings</h2>";
echo "<table>";
echo "<tr><th>Commission Type</th><td>{$affiliate['commission_type']}</td></tr>";
echo "<tr><th>Commission Value</th><td><strong style='color: #007bff; font-size: 20px;'>{$affiliate['commission_value']}%</strong></td></tr>";
echo "</table>";
echo "</div>";

// Update form
if (!isset($_POST['update_rate'])) {
    echo "<div class='section'>";
    echo "<h2>Update Commission Rate</h2>";
    echo "<form method='POST'>";
    echo "<p><label>New Commission Rate (%):</label><br>";
    echo "<input type='number' name='new_rate' value='{$affiliate['commission_value']}' min='0' max='100' step='0.01' required></p>";
    echo "<p><label><input type='checkbox' name='recalculate' checked> Recalculate all existing commissions</label></p>";
    echo "<p><button type='submit' name='update_rate' class='btn'>Update Commission Rate</button></p>";
    echo "</form>";
    echo "</div>";
} else {
    // Process update
    $newRate = (float)$_POST['new_rate'];
    $recalculate = isset($_POST['recalculate']);
    
    echo "<div class='section'>";
    echo "<h2>Updating Commission Rate...</h2>";
    
    // Update affiliate commission rate
    $stmt = $conn->prepare("UPDATE affiliates SET commission_value = ? WHERE id = ?");
    $stmt->bind_param("di", $newRate, $affiliateId);
    $stmt->execute();
    
    echo "<p class='success'>✓ Updated commission rate to {$newRate}%</p>";
    
    if ($recalculate) {
        echo "<h3>Recalculating Existing Commissions...</h3>";
        
        // Get all commissions for this affiliate
        $commissions = $conn->query("
            SELECT ac.*, o.final_amount as order_amount
            FROM affiliate_commissions ac
            JOIN orders o ON ac.order_id = o.id
            WHERE ac.affiliate_id = $affiliateId
            AND ac.level = 1
        ")->fetch_all(MYSQLI_ASSOC);
        
        if (count($commissions) > 0) {
            echo "<table>";
            echo "<tr><th>Commission ID</th><th>Order Amount</th><th>Old Commission</th><th>New Commission</th><th>Difference</th></tr>";
            
            $totalOldCommission = 0;
            $totalNewCommission = 0;
            
            foreach ($commissions as $comm) {
                $oldCommission = $comm['commission_amount'];
                $newCommission = ($comm['order_amount'] * $newRate) / 100;
                $difference = $newCommission - $oldCommission;
                
                $totalOldCommission += $oldCommission;
                $totalNewCommission += $newCommission;
                
                // Update commission
                $conn->query("UPDATE affiliate_commissions SET commission_amount = $newCommission, commission_rate = $newRate WHERE id = {$comm['id']}");
                
                $diffColor = $difference >= 0 ? 'green' : 'red';
                $diffSign = $difference >= 0 ? '+' : '';
                
                echo "<tr>";
                echo "<td>{$comm['id']}</td>";
                echo "<td>₹" . number_format($comm['order_amount'], 2) . "</td>";
                echo "<td>₹" . number_format($oldCommission, 2) . "</td>";
                echo "<td style='color: green; font-weight: bold;'>₹" . number_format($newCommission, 2) . "</td>";
                echo "<td style='color: $diffColor;'>$diffSign₹" . number_format($difference, 2) . "</td>";
                echo "</tr>";
            }
            
            $totalDifference = $totalNewCommission - $totalOldCommission;
            $diffColor = $totalDifference >= 0 ? 'green' : 'red';
            $diffSign = $totalDifference >= 0 ? '+' : '';
            
            echo "<tr style='background: #f8f9fa; font-weight: bold;'>";
            echo "<td>TOTAL</td>";
            echo "<td></td>";
            echo "<td>₹" . number_format($totalOldCommission, 2) . "</td>";
            echo "<td style='color: green;'>₹" . number_format($totalNewCommission, 2) . "</td>";
            echo "<td style='color: $diffColor;'>$diffSign₹" . number_format($totalDifference, 2) . "</td>";
            echo "</tr>";
            echo "</table>";
            
            // Recalculate affiliate earnings
            $earnings = $conn->query("
                SELECT 
                    COALESCE(SUM(commission_amount), 0) as total,
                    COALESCE(SUM(CASE WHEN status = 'pending' THEN commission_amount ELSE 0 END), 0) as pending,
                    COALESCE(SUM(CASE WHEN status = 'paid' THEN commission_amount ELSE 0 END), 0) as paid
                FROM affiliate_commissions
                WHERE affiliate_id = $affiliateId
            ")->fetch_assoc();
            
            $conn->query("
                UPDATE affiliates 
                SET total_earnings = {$earnings['total']},
                    pending_earnings = {$earnings['pending']},
                    paid_earnings = {$earnings['paid']}
                WHERE id = $affiliateId
            ");
            
            echo "<p class='success'>✓ Recalculated " . count($commissions) . " commission(s)</p>";
            echo "<p class='success'>✓ Updated affiliate earnings</p>";
        } else {
            echo "<p class='warning'>No commissions found to recalculate</p>";
        }
    }
    
    echo "</div>";
    
    // Show updated stats
    $affiliate = $conn->query("SELECT * FROM affiliates WHERE id = $affiliateId")->fetch_assoc();
    
    echo "<div class='section' style='background: #d4edda; border: 2px solid #28a745;'>";
    echo "<h2>✅ Updated Statistics</h2>";
    echo "<table>";
    echo "<tr><th>Commission Rate</th><td><strong style='color: #007bff; font-size: 20px;'>{$affiliate['commission_value']}%</strong></td></tr>";
    echo "<tr><th>Total Referrals</th><td>{$affiliate['total_referrals']}</td></tr>";
    echo "<tr><th>Total Sales</th><td>{$affiliate['total_sales']}</td></tr>";
    echo "<tr><th>Total Earnings</th><td style='color: green; font-weight: bold;'>₹" . number_format($affiliate['total_earnings'], 2) . "</td></tr>";
    echo "<tr><th>Pending Earnings</th><td>₹" . number_format($affiliate['pending_earnings'], 2) . "</td></tr>";
    echo "<tr><th>Paid Earnings</th><td>₹" . number_format($affiliate['paid_earnings'], 2) . "</td></tr>";
    echo "</table>";
    
    echo "<p><a href='affiliate-dashboard.php' class='btn' style='background: #007bff;'>📊 View Affiliate Dashboard</a></p>";
    echo "<p><a href='update-commission-rate.php' class='btn' style='background: #6c757d;'>🔄 Update Again</a></p>";
    echo "</div>";
}

?>
