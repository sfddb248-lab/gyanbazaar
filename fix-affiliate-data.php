<?php
require_once 'config/config.php';

echo "<h2>Fixing Affiliate Data</h2>";

// Step 1: Recalculate all affiliate statistics
echo "<h3>Step 1: Recalculating Affiliate Statistics</h3>";

$affiliates = $conn->query("SELECT id FROM affiliates");

while ($affiliate = $affiliates->fetch_assoc()) {
    $affiliateId = $affiliate['id'];
    
    // Count total referrals (users with referred_by = this affiliate)
    $referralsResult = $conn->query("SELECT COUNT(*) as count FROM users WHERE referred_by = $affiliateId");
    $totalReferrals = $referralsResult->fetch_assoc()['count'];
    
    // Count total sales (completed orders from referred users)
    $salesResult = $conn->query("
        SELECT COUNT(DISTINCT o.id) as count 
        FROM orders o 
        JOIN users u ON o.user_id = u.id 
        WHERE u.referred_by = $affiliateId 
        AND o.payment_status = 'completed'
    ");
    $totalSales = $salesResult->fetch_assoc()['count'];
    
    // Calculate total earnings (sum of all approved/paid commissions)
    $earningsResult = $conn->query("
        SELECT COALESCE(SUM(commission_amount), 0) as total 
        FROM affiliate_commissions 
        WHERE affiliate_id = $affiliateId 
        AND status IN ('approved', 'paid')
    ");
    $totalEarnings = $earningsResult->fetch_assoc()['total'];
    
    // Calculate pending earnings (sum of pending commissions)
    $pendingResult = $conn->query("
        SELECT COALESCE(SUM(commission_amount), 0) as total 
        FROM affiliate_commissions 
        WHERE affiliate_id = $affiliateId 
        AND status = 'pending'
    ");
    $pendingEarnings = $pendingResult->fetch_assoc()['total'];
    
    // Update affiliate record
    $stmt = $conn->prepare("
        UPDATE affiliates 
        SET total_referrals = ?, 
            total_sales = ?, 
            total_earnings = ?, 
            pending_earnings = ? 
        WHERE id = ?
    ");
    $stmt->bind_param("iiddi", $totalReferrals, $totalSales, $totalEarnings, $pendingEarnings, $affiliateId);
    $stmt->execute();
    
    echo "<p>✅ Affiliate ID $affiliateId: Referrals=$totalReferrals, Sales=$totalSales, Earnings=₹$totalEarnings, Pending=₹$pendingEarnings</p>";
}

// Step 2: Remove orphaned affiliate records (affiliates with no user)
echo "<h3>Step 2: Checking for Orphaned Affiliate Records</h3>";

$orphaned = $conn->query("
    SELECT a.id, a.referral_code 
    FROM affiliates a 
    LEFT JOIN users u ON a.user_id = u.id 
    WHERE u.id IS NULL
");

if ($orphaned->num_rows > 0) {
    echo "<p>⚠️ Found " . $orphaned->num_rows . " orphaned affiliate records:</p>";
    while ($row = $orphaned->fetch_assoc()) {
        echo "<p>- Affiliate ID: " . $row['id'] . ", Code: " . $row['referral_code'] . "</p>";
    }
    echo "<p><strong>Note:</strong> These should be manually reviewed and deleted if necessary.</p>";
} else {
    echo "<p>✅ No orphaned affiliate records found.</p>";
}

// Step 3: Check for users with invalid referred_by
echo "<h3>Step 3: Checking for Invalid Referrals</h3>";

$invalid = $conn->query("
    SELECT u.id, u.name, u.email, u.referred_by 
    FROM users u 
    LEFT JOIN affiliates a ON u.referred_by = a.id 
    WHERE u.referred_by IS NOT NULL 
    AND a.id IS NULL
");

if ($invalid->num_rows > 0) {
    echo "<p>⚠️ Found " . $invalid->num_rows . " users with invalid referred_by:</p>";
    while ($row = $invalid->fetch_assoc()) {
        echo "<p>- User ID: " . $row['id'] . ", Name: " . $row['name'] . ", Invalid Affiliate ID: " . $row['referred_by'] . "</p>";
    }
    
    // Fix by setting referred_by to NULL
    $conn->query("
        UPDATE users u 
        LEFT JOIN affiliates a ON u.referred_by = a.id 
        SET u.referred_by = NULL 
        WHERE u.referred_by IS NOT NULL 
        AND a.id IS NULL
    ");
    echo "<p>✅ Fixed by setting referred_by to NULL for these users.</p>";
} else {
    echo "<p>✅ No invalid referrals found.</p>";
}

// Step 4: Summary
echo "<h3>Summary</h3>";

$summary = $conn->query("
    SELECT 
        COUNT(*) as total_affiliates,
        SUM(CASE WHEN total_referrals > 0 THEN 1 ELSE 0 END) as affiliates_with_referrals,
        SUM(CASE WHEN total_sales > 0 THEN 1 ELSE 0 END) as affiliates_with_sales,
        SUM(total_referrals) as total_referrals,
        SUM(total_sales) as total_sales,
        SUM(total_earnings) as total_earnings,
        SUM(pending_earnings) as pending_earnings
    FROM affiliates
")->fetch_assoc();

echo "<table border='1' cellpadding='10'>";
echo "<tr><th>Metric</th><th>Value</th></tr>";
echo "<tr><td>Total Affiliates</td><td>" . $summary['total_affiliates'] . "</td></tr>";
echo "<tr><td>Affiliates with Referrals</td><td>" . $summary['affiliates_with_referrals'] . "</td></tr>";
echo "<tr><td>Affiliates with Sales</td><td>" . $summary['affiliates_with_sales'] . "</td></tr>";
echo "<tr><td>Total Referrals</td><td>" . $summary['total_referrals'] . "</td></tr>";
echo "<tr><td>Total Sales</td><td>" . $summary['total_sales'] . "</td></tr>";
echo "<tr><td>Total Earnings</td><td>₹" . number_format($summary['total_earnings'], 2) . "</td></tr>";
echo "<tr><td>Pending Earnings</td><td>₹" . number_format($summary['pending_earnings'], 2) . "</td></tr>";
echo "</table>";

echo "<h3>✅ Affiliate Data Fix Complete!</h3>";
echo "<p><a href='admin/affiliates.php'>Go to Admin Affiliates Page</a></p>";
echo "<p><a href='affiliate-dashboard.php'>Go to Affiliate Dashboard</a></p>";
?>
