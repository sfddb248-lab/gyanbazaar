<?php
require_once 'config/config.php';

echo "<h1>🔧 Complete Referral System Fix</h1>";
echo "<p>This script will fix all referral-related issues in one go.</p>";
echo "<hr>";

$totalFixed = 0;

// ============================================
// STEP 1: Check if referred_by column exists
// ============================================
echo "<h2>Step 1: Database Structure Check</h2>";
$columns = $conn->query("SHOW COLUMNS FROM users LIKE 'referred_by'");

if ($columns->num_rows == 0) {
    echo "<p style='color: red;'>❌ Column 'referred_by' does not exist!</p>";
    echo "<p><strong>Adding column...</strong></p>";
    
    $sql = "ALTER TABLE users ADD COLUMN referred_by INT NULL AFTER role";
    if ($conn->query($sql)) {
        echo "<p style='color: green;'>✅ Successfully added 'referred_by' column</p>";
    } else {
        echo "<p style='color: red;'>❌ Error: " . $conn->error . "</p>";
        exit;
    }
} else {
    echo "<p style='color: green;'>✅ Column 'referred_by' exists</p>";
}

// ============================================
// STEP 2: Clear admin users' referred_by
// ============================================
echo "<h2>Step 2: Clearing Admin Users</h2>";
$adminQuery = "SELECT id, name, email FROM users WHERE role IN ('admin', 'editor') AND referred_by IS NOT NULL";
$adminResult = $conn->query($adminQuery);

if ($adminResult->num_rows > 0) {
    echo "<p>Found " . $adminResult->num_rows . " admin user(s) with referred_by set</p>";
    while ($admin = $adminResult->fetch_assoc()) {
        echo "<p>Clearing: " . htmlspecialchars($admin['name']) . " (" . $admin['email'] . ")</p>";
    }
    
    $conn->query("UPDATE users SET referred_by = NULL WHERE role IN ('admin', 'editor')");
    echo "<p style='color: green;'>✅ Cleared referred_by for admin users</p>";
    $totalFixed += $adminResult->num_rows;
} else {
    echo "<p style='color: green;'>✅ No admin users with referred_by found</p>";
}

// ============================================
// STEP 3: Remove self-referrals
// ============================================
echo "<h2>Step 3: Removing Self-Referrals</h2>";
$selfQuery = "
    SELECT u.id, u.name 
    FROM users u
    JOIN affiliates a ON u.id = a.user_id
    WHERE u.referred_by = a.id
";
$selfResult = $conn->query($selfQuery);

if ($selfResult->num_rows > 0) {
    echo "<p>Found " . $selfResult->num_rows . " self-referral(s)</p>";
    while ($self = $selfResult->fetch_assoc()) {
        echo "<p>Clearing: " . htmlspecialchars($self['name']) . "</p>";
    }
    
    $conn->query("
        UPDATE users u
        JOIN affiliates a ON u.id = a.user_id
        SET u.referred_by = NULL
        WHERE u.referred_by = a.id
    ");
    echo "<p style='color: green;'>✅ Removed self-referrals</p>";
    $totalFixed += $selfResult->num_rows;
} else {
    echo "<p style='color: green;'>✅ No self-referrals found</p>";
}

// ============================================
// STEP 4: Sync affiliate_referrals to users.referred_by
// ============================================
echo "<h2>Step 4: Syncing Referral Data</h2>";
$syncQuery = "
    SELECT 
        ar.affiliate_id,
        ar.referred_user_id,
        u.name,
        u.referred_by as current_value
    FROM affiliate_referrals ar
    JOIN users u ON ar.referred_user_id = u.id
    WHERE (u.referred_by IS NULL OR u.referred_by != ar.affiliate_id)
    AND u.role = 'user'
";
$syncResult = $conn->query($syncQuery);

if ($syncResult->num_rows > 0) {
    echo "<p>Found " . $syncResult->num_rows . " user(s) to sync</p>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>User ID</th><th>Name</th><th>Current</th><th>New</th><th>Status</th></tr>";
    
    $synced = 0;
    while ($row = $syncResult->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['referred_user_id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['name']) . "</td>";
        echo "<td>" . ($row['current_value'] ?? 'NULL') . "</td>";
        echo "<td>" . $row['affiliate_id'] . "</td>";
        
        $updateStmt = $conn->prepare("UPDATE users SET referred_by = ? WHERE id = ?");
        $updateStmt->bind_param("ii", $row['affiliate_id'], $row['referred_user_id']);
        
        if ($updateStmt->execute()) {
            echo "<td style='color: green;'>✅ Synced</td>";
            $synced++;
        } else {
            echo "<td style='color: red;'>❌ Error</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
    echo "<p style='color: green;'>✅ Synced $synced user(s)</p>";
    $totalFixed += $synced;
} else {
    echo "<p style='color: green;'>✅ All referral data is already synced</p>";
}

// ============================================
// STEP 5: Remove invalid referrals
// ============================================
echo "<h2>Step 5: Cleaning Invalid Referrals</h2>";
$invalidQuery = "
    SELECT u.id, u.name, u.referred_by
    FROM users u
    LEFT JOIN affiliates a ON u.referred_by = a.id
    WHERE u.referred_by IS NOT NULL AND a.id IS NULL
";
$invalidResult = $conn->query($invalidQuery);

if ($invalidResult->num_rows > 0) {
    echo "<p>Found " . $invalidResult->num_rows . " user(s) with invalid referred_by</p>";
    while ($invalid = $invalidResult->fetch_assoc()) {
        echo "<p>Clearing: " . htmlspecialchars($invalid['name']) . " (invalid affiliate ID: " . $invalid['referred_by'] . ")</p>";
    }
    
    $conn->query("
        UPDATE users u
        LEFT JOIN affiliates a ON u.referred_by = a.id
        SET u.referred_by = NULL
        WHERE u.referred_by IS NOT NULL AND a.id IS NULL
    ");
    echo "<p style='color: green;'>✅ Cleared invalid referrals</p>";
    $totalFixed += $invalidResult->num_rows;
} else {
    echo "<p style='color: green;'>✅ No invalid referrals found</p>";
}

// ============================================
// STEP 6: Recalculate affiliate statistics
// ============================================
echo "<h2>Step 6: Recalculating Affiliate Statistics</h2>";
$affiliates = $conn->query("SELECT id, referral_code FROM affiliates");
$recalculated = 0;

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Affiliate ID</th><th>Code</th><th>Referrals</th><th>Sales</th><th>Earnings</th></tr>";

while ($affiliate = $affiliates->fetch_assoc()) {
    $affiliateId = $affiliate['id'];
    
    // Count referrals (only regular users)
    $refCount = $conn->query("
        SELECT COUNT(*) as count 
        FROM users 
        WHERE referred_by = $affiliateId 
        AND role = 'user'
    ")->fetch_assoc()['count'];
    
    // Count sales
    $salesCount = $conn->query("
        SELECT COUNT(DISTINCT o.id) as count
        FROM orders o
        JOIN users u ON o.user_id = u.id
        WHERE u.referred_by = $affiliateId
        AND o.payment_status = 'completed'
    ")->fetch_assoc()['count'];
    
    // Calculate earnings
    $earnings = $conn->query("
        SELECT 
            COALESCE(SUM(CASE WHEN status IN ('approved', 'paid') THEN commission_amount ELSE 0 END), 0) as total,
            COALESCE(SUM(CASE WHEN status = 'pending' THEN commission_amount ELSE 0 END), 0) as pending
        FROM affiliate_commissions
        WHERE affiliate_id = $affiliateId
    ")->fetch_assoc();
    
    // Update affiliate
    $updateStmt = $conn->prepare("
        UPDATE affiliates 
        SET total_referrals = ?,
            total_sales = ?,
            total_earnings = ?,
            pending_earnings = ?
        WHERE id = ?
    ");
    $updateStmt->bind_param("iiddi", $refCount, $salesCount, $earnings['total'], $earnings['pending'], $affiliateId);
    $updateStmt->execute();
    
    echo "<tr>";
    echo "<td>" . $affiliateId . "</td>";
    echo "<td>" . $affiliate['referral_code'] . "</td>";
    echo "<td>" . $refCount . "</td>";
    echo "<td>" . $salesCount . "</td>";
    echo "<td>₹" . number_format($earnings['total'], 2) . "</td>";
    echo "</tr>";
    
    $recalculated++;
}

echo "</table>";
echo "<p style='color: green;'>✅ Recalculated statistics for $recalculated affiliate(s)</p>";

// ============================================
// STEP 7: Verification
// ============================================
echo "<h2>Step 7: Final Verification</h2>";

// Check for any remaining issues
$issues = [];

// Check 1: Admin users with referred_by
$adminCheck = $conn->query("SELECT COUNT(*) as count FROM users WHERE role IN ('admin', 'editor') AND referred_by IS NOT NULL")->fetch_assoc()['count'];
if ($adminCheck > 0) {
    $issues[] = "⚠️ $adminCheck admin user(s) still have referred_by set";
} else {
    echo "<p style='color: green;'>✅ No admin users with referred_by</p>";
}

// Check 2: Self-referrals
$selfCheck = $conn->query("
    SELECT COUNT(*) as count
    FROM users u
    JOIN affiliates a ON u.id = a.user_id
    WHERE u.referred_by = a.id
")->fetch_assoc()['count'];
if ($selfCheck > 0) {
    $issues[] = "⚠️ $selfCheck self-referral(s) still exist";
} else {
    echo "<p style='color: green;'>✅ No self-referrals</p>";
}

// Check 3: Invalid referrals
$invalidCheck = $conn->query("
    SELECT COUNT(*) as count
    FROM users u
    LEFT JOIN affiliates a ON u.referred_by = a.id
    WHERE u.referred_by IS NOT NULL AND a.id IS NULL
")->fetch_assoc()['count'];
if ($invalidCheck > 0) {
    $issues[] = "⚠️ $invalidCheck invalid referral(s) still exist";
} else {
    echo "<p style='color: green;'>✅ No invalid referrals</p>";
}

// ============================================
// SUMMARY
// ============================================
echo "<hr>";
echo "<h2>✅ Fix Complete!</h2>";

if (empty($issues)) {
    echo "<div style='background: #d4edda; border: 2px solid #28a745; padding: 20px; border-radius: 10px;'>";
    echo "<h3 style='color: #155724; margin-top: 0;'>🎉 All Issues Fixed!</h3>";
    echo "<p><strong>Total items fixed:</strong> $totalFixed</p>";
    echo "<ul>";
    echo "<li>✅ Database structure verified</li>";
    echo "<li>✅ Admin users cleaned</li>";
    echo "<li>✅ Self-referrals removed</li>";
    echo "<li>✅ Referral data synced</li>";
    echo "<li>✅ Invalid referrals cleared</li>";
    echo "<li>✅ Statistics recalculated</li>";
    echo "</ul>";
    echo "</div>";
} else {
    echo "<div style='background: #fff3cd; border: 2px solid #ffc107; padding: 20px; border-radius: 10px;'>";
    echo "<h3 style='color: #856404; margin-top: 0;'>⚠️ Some Issues Remain</h3>";
    echo "<ul>";
    foreach ($issues as $issue) {
        echo "<li>$issue</li>";
    }
    echo "</ul>";
    echo "<p><strong>Recommendation:</strong> Run this script again or contact support.</p>";
    echo "</div>";
}

echo "<h3>Next Steps:</h3>";
echo "<ol>";
echo "<li><a href='affiliate-dashboard.php' style='color: #007bff; font-weight: bold;'>Go to Affiliate Dashboard</a> - Check your referrals list</li>";
echo "<li><a href='admin/affiliates.php' style='color: #007bff; font-weight: bold;'>Go to Admin Panel</a> - Verify affiliate stats</li>";
echo "<li>Test new signup with referral link</li>";
echo "<li>Verify commission tracking works</li>";
echo "</ol>";

echo "<p style='margin-top: 30px;'><small>Script completed at: " . date('Y-m-d H:i:s') . "</small></p>";
?>

<style>
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    padding: 20px;
    max-width: 1200px;
    margin: 0 auto;
    background: #f5f5f5;
}
h1 {
    color: #333;
    border-bottom: 4px solid #007bff;
    padding-bottom: 15px;
}
h2 {
    color: #555;
    margin-top: 40px;
    background: #fff;
    padding: 15px;
    border-left: 5px solid #007bff;
}
table {
    border-collapse: collapse;
    margin: 20px 0;
    width: 100%;
    background: white;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
th {
    background: #007bff;
    color: white;
    padding: 12px;
    text-align: left;
}
td {
    padding: 10px;
    border: 1px solid #ddd;
}
tr:nth-child(even) {
    background: #f9f9f9;
}
a {
    color: #007bff;
    text-decoration: none;
    font-weight: bold;
}
a:hover {
    text-decoration: underline;
}
p {
    line-height: 1.6;
}
</style>
