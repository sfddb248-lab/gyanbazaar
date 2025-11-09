<?php
require_once 'config/config.php';

echo "<h1>🔄 Recalculate Affiliate Earnings</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; padding: 20px; }
    .success { color: green; font-weight: bold; }
    .info { color: blue; }
    table { border-collapse: collapse; width: 100%; margin: 20px 0; }
    th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
    th { background: #007bff; color: white; }
</style>";

echo "<p>This script will recalculate all affiliate earnings based on commission statuses.</p>";

// Get all affiliates
$affiliates = $conn->query("SELECT * FROM affiliates ORDER BY id ASC")->fetch_all(MYSQLI_ASSOC);

echo "<h2>Processing " . count($affiliates) . " affiliate(s)...</h2>";

echo "<table>";
echo "<tr><th>Affiliate</th><th>Old Pending</th><th>New Pending</th><th>Old Paid</th><th>New Paid</th><th>Total</th></tr>";

foreach ($affiliates as $affiliate) {
    $affiliateId = $affiliate['id'];
    
    // Get user name
    $user = $conn->query("SELECT name FROM users WHERE id = {$affiliate['user_id']}")->fetch_assoc();
    $name = $user['name'] ?? 'Unknown';
    
    // Calculate earnings from commissions
    $earnings = $conn->query("
        SELECT 
            COALESCE(SUM(commission_amount), 0) as total,
            COALESCE(SUM(CASE WHEN status IN ('pending', 'approved') THEN commission_amount ELSE 0 END), 0) as pending,
            COALESCE(SUM(CASE WHEN status = 'paid' THEN commission_amount ELSE 0 END), 0) as paid
        FROM affiliate_commissions
        WHERE affiliate_id = $affiliateId
    ")->fetch_assoc();
    
    $oldPending = $affiliate['pending_earnings'];
    $oldPaid = $affiliate['paid_earnings'];
    
    $newPending = $earnings['pending'];
    $newPaid = $earnings['paid'];
    $newTotal = $earnings['total'];
    
    // Update affiliate
    $conn->query("
        UPDATE affiliates 
        SET total_earnings = $newTotal,
            pending_earnings = $newPending,
            paid_earnings = $newPaid
        WHERE id = $affiliateId
    ");
    
    echo "<tr>";
    echo "<td><strong>$name</strong></td>";
    echo "<td>₹" . number_format($oldPending, 2) . "</td>";
    echo "<td style='color: " . ($newPending != $oldPending ? 'green' : 'black') . "; font-weight: bold;'>₹" . number_format($newPending, 2) . "</td>";
    echo "<td>₹" . number_format($oldPaid, 2) . "</td>";
    echo "<td style='color: " . ($newPaid != $oldPaid ? 'green' : 'black') . "; font-weight: bold;'>₹" . number_format($newPaid, 2) . "</td>";
    echo "<td><strong>₹" . number_format($newTotal, 2) . "</strong></td>";
    echo "</tr>";
}

echo "</table>";

echo "<h2 class='success'>✅ Recalculation Complete!</h2>";

// Show summary
echo "<h2>Summary by Commission Status:</h2>";

$statusSummary = $conn->query("
    SELECT 
        status,
        COUNT(*) as count,
        COALESCE(SUM(commission_amount), 0) as amount
    FROM affiliate_commissions
    GROUP BY status
")->fetch_all(MYSQLI_ASSOC);

echo "<table>";
echo "<tr><th>Status</th><th>Count</th><th>Amount</th></tr>";
foreach ($statusSummary as $row) {
    echo "<tr>";
    echo "<td><strong>" . ucfirst($row['status']) . "</strong></td>";
    echo "<td>{$row['count']}</td>";
    echo "<td>₹" . number_format($row['amount'], 2) . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<div style='background: #d4edda; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<h3>✅ What was updated:</h3>";
echo "<ul>";
echo "<li><strong>Pending Earnings:</strong> Now includes both 'pending' AND 'approved' commissions</li>";
echo "<li><strong>Paid Earnings:</strong> Only includes 'paid' commissions</li>";
echo "<li><strong>Total Earnings:</strong> Sum of all commissions</li>";
echo "</ul>";
echo "<p><strong>Note:</strong> 'Approved' commissions are now available for withdrawal!</p>";
echo "</div>";

echo "<p><a href='affiliate-dashboard.php' style='padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px;'>View Affiliate Dashboard</a></p>";

?>
