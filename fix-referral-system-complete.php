<?php
require_once 'config/config.php';

echo "<h1>Complete Referral System Fix</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; padding: 20px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .warning { color: orange; font-weight: bold; }
    .section { margin: 20px 0; padding: 15px; background: #f5f5f5; border-radius: 5px; }
</style>";

$totalFixed = 0;

// STEP 1: Ensure referred_by column exists
echo "<div class='section'>";
echo "<h2>Step 1: Database Structure</h2>";
$columns = $conn->query("SHOW COLUMNS FROM users LIKE 'referred_by'");
if ($columns->num_rows == 0) {
    echo "<p>Adding referred_by column...</p>";
    $conn->query("ALTER TABLE users ADD COLUMN referred_by INT NULL AFTER role");
    echo "<p class='success'>✓ Added referred_by column</p>";
} else {
    echo "<p class='success'>✓ referred_by column exists</p>";
}
echo "</div>";

// STEP 2: Find and sync OLD referrals from orders
echo "<div class='section'>";
echo "<h2>Step 2: Find Old Referrals from Orders</h2>";

// Find users who made purchases but don't have referred_by set
// Look for orders with affiliate_id or referral_code
$oldReferrals = $conn->query("
    SELECT DISTINCT
        o.user_id,
        o.affiliate_id,
        o.referral_code,
        u.name,
        u.email,
        u.referred_by,
        COUNT(o.id) as order_count
    FROM orders o
    JOIN users u ON o.user_id = u.id
    WHERE (o.affiliate_id IS NOT NULL OR o.referral_code IS NOT NULL)
    AND u.referred_by IS NULL
    AND u.role = 'user'
    GROUP BY o.user_id
");

if ($oldReferrals->num_rows > 0) {
    echo "<p class='warning'>Found {$oldReferrals->num_rows} old referrals from orders...</p>";
    while ($row = $oldReferrals->fetch_assoc()) {
        $affiliateId = $row['affiliate_id'];
        
        // If no affiliate_id, try to get it from referral_code
        if (!$affiliateId && $row['referral_code']) {
            $affResult = $conn->query("SELECT id FROM affiliates WHERE referral_code = '{$row['referral_code']}'");
            if ($affResult->num_rows > 0) {
                $affiliateId = $affResult->fetch_assoc()['id'];
            }
        }
        
        if ($affiliateId) {
            // Update user's referred_by
            $conn->query("UPDATE users SET referred_by = $affiliateId WHERE id = {$row['user_id']}");
            echo "<p>✓ Set referred_by for old user: {$row['name']} ({$row['order_count']} orders)</p>";
            $totalFixed++;
        }
    }
    echo "<p class='success'>✓ Processed {$oldReferrals->num_rows} old referrals</p>";
} else {
    echo "<p class='success'>✓ No old referrals found in orders</p>";
}
echo "</div>";

// STEP 3: Sync existing affiliate_referrals to users.referred_by
echo "<div class='section'>";
echo "<h2>Step 3: Sync Referral Data</h2>";

$syncQuery = "
    SELECT 
        ar.referred_user_id,
        ar.affiliate_id,
        u.name,
        u.referred_by as current_value
    FROM affiliate_referrals ar
    JOIN users u ON ar.referred_user_id = u.id
    WHERE u.referred_by IS NULL OR u.referred_by != ar.affiliate_id
";

$syncResult = $conn->query($syncQuery);
if ($syncResult->num_rows > 0) {
    echo "<p>Found {$syncResult->num_rows} users to sync...</p>";
    while ($row = $syncResult->fetch_assoc()) {
        $conn->query("UPDATE users SET referred_by = {$row['affiliate_id']} WHERE id = {$row['referred_user_id']}");
        echo "<p>✓ Synced user: {$row['name']}</p>";
        $totalFixed++;
    }
    echo "<p class='success'>✓ Synced {$syncResult->num_rows} users</p>";
} else {
    echo "<p class='success'>✓ All users already synced</p>";
}
echo "</div>";

// STEP 4: Create missing referral records for users with referred_by
echo "<div class='section'>";
echo "<h2>Step 4: Create Missing Referral Records</h2>";

$missingReferrals = $conn->query("
    SELECT 
        u.id as user_id,
        u.name,
        u.email,
        u.referred_by,
        a.referral_code
    FROM users u
    JOIN affiliates a ON u.referred_by = a.id
    LEFT JOIN affiliate_referrals ar ON ar.referred_user_id = u.id AND ar.affiliate_id = a.id
    WHERE u.referred_by IS NOT NULL
    AND ar.id IS NULL
");

if ($missingReferrals->num_rows > 0) {
    echo "<p>Creating {$missingReferrals->num_rows} missing referral records...</p>";
    while ($row = $missingReferrals->fetch_assoc()) {
        $stmt = $conn->prepare("
            INSERT INTO affiliate_referrals 
            (affiliate_id, referred_user_id, referral_code, ip_address, user_agent, converted, purchase_made) 
            VALUES (?, ?, ?, '127.0.0.1', 'System', 0, 0)
        ");
        $stmt->bind_param("iis", $row['referred_by'], $row['user_id'], $row['referral_code']);
        $stmt->execute();
        echo "<p>✓ Created referral record for: {$row['name']}</p>";
        $totalFixed++;
    }
    echo "<p class='success'>✓ Created {$missingReferrals->num_rows} referral records</p>";
} else {
    echo "<p class='success'>✓ All referral records exist</p>";
}
echo "</div>";

// STEP 5: Mark referrals as converted if they have purchases
echo "<div class='section'>";
echo "<h2>Step 5: Mark Converted Referrals</h2>";

$unconvertedWithPurchases = $conn->query("
    SELECT 
        ar.id as referral_id,
        ar.referred_user_id,
        u.name,
        COUNT(o.id) as order_count
    FROM affiliate_referrals ar
    JOIN users u ON ar.referred_user_id = u.id
    JOIN orders o ON u.id = o.user_id AND o.payment_status = 'completed'
    WHERE ar.converted = 0 OR ar.purchase_made = 0
    GROUP BY ar.id
");

if ($unconvertedWithPurchases->num_rows > 0) {
    echo "<p>Marking {$unconvertedWithPurchases->num_rows} referrals as converted...</p>";
    while ($row = $unconvertedWithPurchases->fetch_assoc()) {
        $conn->query("
            UPDATE affiliate_referrals 
            SET converted = 1, 
                purchase_made = 1,
                conversion_date = NOW(),
                first_purchase_date = NOW()
            WHERE id = {$row['referral_id']}
        ");
        echo "<p>✓ Marked as converted: {$row['name']} ({$row['order_count']} orders)</p>";
        $totalFixed++;
    }
    echo "<p class='success'>✓ Marked {$unconvertedWithPurchases->num_rows} referrals as converted</p>";
} else {
    echo "<p class='success'>✓ All referrals with purchases are marked as converted</p>";
}
echo "</div>";

// STEP 6: Create missing commissions for ALL orders (including old ones)
echo "<div class='section'>";
echo "<h2>Step 6: Create Missing Commissions (Including Old Orders)</h2>";

$missingCommissions = $conn->query("
    SELECT 
        o.id as order_id,
        o.order_number,
        o.final_amount,
        o.created_at as order_date,
        u.name as user_name,
        u.referred_by as affiliate_id,
        a.commission_type,
        a.commission_value,
        a.referral_code
    FROM orders o
    JOIN users u ON o.user_id = u.id
    JOIN affiliates a ON u.referred_by = a.id
    LEFT JOIN affiliate_commissions ac ON o.id = ac.order_id AND ac.affiliate_id = a.id
    WHERE o.payment_status = 'completed'
    AND u.referred_by IS NOT NULL
    AND u.role = 'user'
    AND ac.id IS NULL
    ORDER BY o.created_at ASC
");

if ($missingCommissions->num_rows > 0) {
    echo "<p class='warning'>Creating {$missingCommissions->num_rows} missing commissions (including old orders)...</p>";
    echo "<table border='1' cellpadding='8' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
    echo "<tr style='background: #f0f0f0;'><th>Order #</th><th>User</th><th>Affiliate Code</th><th>Order Date</th><th>Amount</th><th>Commission</th><th>Status</th></tr>";
    
    $totalCommissionAmount = 0;
    
    while ($row = $missingCommissions->fetch_assoc()) {
        // Calculate commission
        if ($row['commission_type'] == 'percentage') {
            $commissionAmount = ($row['final_amount'] * $row['commission_value']) / 100;
        } else {
            $commissionAmount = $row['commission_value'];
        }
        
        $totalCommissionAmount += $commissionAmount;
        
        // Insert commission
        $stmt = $conn->prepare("
            INSERT INTO affiliate_commissions 
            (affiliate_id, order_id, commission_amount, commission_type, commission_rate, order_amount, status, level, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, 'pending', 1, ?)
        ");
        $stmt->bind_param("iidsdds", 
            $row['affiliate_id'], 
            $row['order_id'], 
            $commissionAmount, 
            $row['commission_type'], 
            $row['commission_value'], 
            $row['final_amount'],
            $row['order_date']
        );
        
        if ($stmt->execute()) {
            // Update affiliate earnings
            $conn->query("
                UPDATE affiliates 
                SET pending_earnings = pending_earnings + $commissionAmount,
                    total_earnings = total_earnings + $commissionAmount,
                    total_sales = total_sales + 1
                WHERE id = {$row['affiliate_id']}
            ");
            
            echo "<tr>";
            echo "<td>{$row['order_number']}</td>";
            echo "<td>{$row['user_name']}</td>";
            echo "<td>{$row['referral_code']}</td>";
            echo "<td>" . date('Y-m-d', strtotime($row['order_date'])) . "</td>";
            echo "<td>₹" . number_format($row['final_amount'], 2) . "</td>";
            echo "<td style='color: green; font-weight: bold;'>₹" . number_format($commissionAmount, 2) . "</td>";
            echo "<td style='color: green;'>✓ Created</td>";
            echo "</tr>";
            
            $totalFixed++;
        } else {
            echo "<tr>";
            echo "<td>{$row['order_number']}</td>";
            echo "<td>{$row['user_name']}</td>";
            echo "<td>{$row['referral_code']}</td>";
            echo "<td>" . date('Y-m-d', strtotime($row['order_date'])) . "</td>";
            echo "<td>₹" . number_format($row['final_amount'], 2) . "</td>";
            echo "<td>-</td>";
            echo "<td style='color: red;'>✗ Failed</td>";
            echo "</tr>";
        }
    }
    
    echo "</table>";
    echo "<p class='success'>✓ Created {$missingCommissions->num_rows} commissions</p>";
    echo "<p class='success'>✓ Total commission amount: ₹" . number_format($totalCommissionAmount, 2) . "</p>";
} else {
    echo "<p class='success'>✓ All orders have commissions</p>";
}
echo "</div>";

// STEP 7: Update affiliate statistics
echo "<div class='section'>";
echo "<h2>Step 7: Recalculate Affiliate Statistics</h2>";

$affiliates = $conn->query("SELECT id FROM affiliates")->fetch_all(MYSQLI_ASSOC);
foreach ($affiliates as $affiliate) {
    $affiliateId = $affiliate['id'];
    
    // Count referrals who made purchases
    $referralCount = $conn->query("
        SELECT COUNT(*) as count 
        FROM affiliate_referrals 
        WHERE affiliate_id = $affiliateId AND purchase_made = 1
    ")->fetch_assoc()['count'];
    
    // Count total sales
    $salesCount = $conn->query("
        SELECT COUNT(*) as count 
        FROM orders o
        JOIN users u ON o.user_id = u.id
        WHERE u.referred_by = $affiliateId AND o.payment_status = 'completed'
    ")->fetch_assoc()['count'];
    
    // Calculate earnings
    $earnings = $conn->query("
        SELECT 
            COALESCE(SUM(commission_amount), 0) as total,
            COALESCE(SUM(CASE WHEN status = 'pending' THEN commission_amount ELSE 0 END), 0) as pending,
            COALESCE(SUM(CASE WHEN status = 'paid' THEN commission_amount ELSE 0 END), 0) as paid
        FROM affiliate_commissions
        WHERE affiliate_id = $affiliateId
    ")->fetch_assoc();
    
    // Update affiliate
    $conn->query("
        UPDATE affiliates 
        SET total_referrals = $referralCount,
            total_sales = $salesCount,
            total_earnings = {$earnings['total']},
            pending_earnings = {$earnings['pending']},
            paid_earnings = {$earnings['paid']}
        WHERE id = $affiliateId
    ");
    
    echo "<p>✓ Updated affiliate #$affiliateId: $referralCount referrals, $salesCount sales, ₹" . number_format($earnings['total'], 2) . " earnings</p>";
}
echo "<p class='success'>✓ Updated all affiliate statistics</p>";
echo "</div>";

// STEP 8: Clear admin/editor referred_by
echo "<div class='section'>";
echo "<h2>Step 8: Clean Admin Data</h2>";
$adminCleared = $conn->query("UPDATE users SET referred_by = NULL WHERE role IN ('admin', 'editor') AND referred_by IS NOT NULL");
if ($conn->affected_rows > 0) {
    echo "<p class='success'>✓ Cleared referred_by for {$conn->affected_rows} admin users</p>";
} else {
    echo "<p class='success'>✓ No admin users with referrals</p>";
}
echo "</div>";

// STEP 9: Remove self-referrals
echo "<div class='section'>";
echo "<h2>Step 9: Remove Self-Referrals</h2>";
$selfReferrals = $conn->query("
    SELECT u.id, u.name 
    FROM users u
    JOIN affiliates a ON u.id = a.user_id
    WHERE u.referred_by = a.id
");

if ($selfReferrals->num_rows > 0) {
    echo "<p>Removing {$selfReferrals->num_rows} self-referrals...</p>";
    while ($row = $selfReferrals->fetch_assoc()) {
        echo "<p>✓ Removing self-referral for: {$row['name']}</p>";
    }
    $conn->query("
        UPDATE users u
        JOIN affiliates a ON u.id = a.user_id
        SET u.referred_by = NULL
        WHERE u.referred_by = a.id
    ");
    echo "<p class='success'>✓ Removed {$selfReferrals->num_rows} self-referrals</p>";
} else {
    echo "<p class='success'>✓ No self-referrals found</p>";
}
echo "</div>";

// FINAL SUMMARY
echo "<div class='section' style='background: #d4edda; border: 2px solid #28a745;'>";
echo "<h2>✅ COMPLETE!</h2>";
echo "<p class='success'>Total fixes applied: $totalFixed</p>";
echo "<h3>Summary:</h3>";

// Get final counts
$totalAffiliates = $conn->query("SELECT COUNT(*) as count FROM affiliates")->fetch_assoc()['count'];
$totalReferrals = $conn->query("SELECT COUNT(*) as count FROM affiliate_referrals WHERE purchase_made = 1")->fetch_assoc()['count'];
$totalCommissions = $conn->query("SELECT COUNT(*) as count FROM affiliate_commissions")->fetch_assoc()['count'];
$totalEarnings = $conn->query("SELECT COALESCE(SUM(total_earnings), 0) as total FROM affiliates")->fetch_assoc()['total'];

echo "<ul>";
echo "<li><strong>Total Affiliates:</strong> $totalAffiliates</li>";
echo "<li><strong>Total Referrals (with purchases):</strong> $totalReferrals</li>";
echo "<li><strong>Total Commissions:</strong> $totalCommissions</li>";
echo "<li><strong>Total Earnings:</strong> ₹" . number_format($totalEarnings, 2) . "</li>";
echo "</ul>";

echo "<h3>Next Steps:</h3>";
echo "<ol>";
echo "<li>Test the referral flow with a new user</li>";
echo "<li>Share a referral link: <code>http://localhost/GyanBazaar/?ref=YOUR_CODE</code></li>";
echo "<li>Sign up a new user through that link</li>";
echo "<li>Make a purchase with that user</li>";
echo "<li>Check the affiliate dashboard to see the referral and commission</li>";
echo "</ol>";

echo "<p><a href='affiliate-dashboard.php' style='padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; display: inline-block; margin: 10px 5px;'>View Affiliate Dashboard</a>";
echo "<a href='diagnose-referral-system.php' style='padding: 10px 20px; background: #28a745; color: white; text-decoration: none; border-radius: 5px; display: inline-block; margin: 10px 5px;'>Run Diagnostic</a></p>";

echo "</div>";
?>
