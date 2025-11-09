<?php
require_once 'config/config.php';

echo "<h2>Syncing Referral Data</h2>";
echo "<p>This script will sync affiliate_referrals table with users.referred_by column</p>";

// Step 1: Check if referred_by column exists
echo "<h3>Step 1: Checking Database Structure</h3>";
$columns = $conn->query("SHOW COLUMNS FROM users LIKE 'referred_by'");
if ($columns->num_rows == 0) {
    echo "<p style='color: red;'>❌ Column 'referred_by' does not exist!</p>";
    echo "<p><strong>Please run:</strong> <a href='fix-referral-column.php'>fix-referral-column.php</a> first</p>";
    exit;
} else {
    echo "<p style='color: green;'>✅ Column 'referred_by' exists</p>";
}

// Step 2: Sync affiliate_referrals to users.referred_by
echo "<h3>Step 2: Syncing Referral Data</h3>";

$query = "
    SELECT 
        ar.id as referral_id,
        ar.affiliate_id,
        ar.referred_user_id,
        u.name as user_name,
        u.referred_by as current_referred_by,
        a.referral_code
    FROM affiliate_referrals ar
    JOIN users u ON ar.referred_user_id = u.id
    JOIN affiliates a ON ar.affiliate_id = a.id
    WHERE u.referred_by IS NULL OR u.referred_by != ar.affiliate_id
";

$result = $conn->query($query);

if ($result->num_rows > 0) {
    echo "<p>Found " . $result->num_rows . " users that need to be synced</p>";
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>User ID</th><th>User Name</th><th>Current referred_by</th><th>Should be</th><th>Action</th></tr>";
    
    $synced = 0;
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['referred_user_id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['user_name']) . "</td>";
        echo "<td>" . ($row['current_referred_by'] ?? 'NULL') . "</td>";
        echo "<td>" . $row['affiliate_id'] . "</td>";
        
        // Update the user
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
    echo "<p style='color: green; font-weight: bold;'>✅ Synced $synced users!</p>";
} else {
    echo "<p style='color: green;'>✅ All referral data is already synced!</p>";
}

// Step 3: Verify sync
echo "<h3>Step 3: Verification</h3>";

$verifyQuery = "
    SELECT 
        ar.affiliate_id,
        COUNT(*) as referrals_in_table,
        (SELECT COUNT(*) FROM users WHERE referred_by = ar.affiliate_id) as referrals_in_users
    FROM affiliate_referrals ar
    GROUP BY ar.affiliate_id
";

$verifyResult = $conn->query($verifyQuery);

if ($verifyResult->num_rows > 0) {
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>Affiliate ID</th><th>Referrals in affiliate_referrals</th><th>Referrals in users.referred_by</th><th>Status</th></tr>";
    
    while ($row = $verifyResult->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['affiliate_id'] . "</td>";
        echo "<td>" . $row['referrals_in_table'] . "</td>";
        echo "<td>" . $row['referrals_in_users'] . "</td>";
        
        if ($row['referrals_in_table'] == $row['referrals_in_users']) {
            echo "<td style='color: green;'>✅ Synced</td>";
        } else {
            echo "<td style='color: orange;'>⚠️ Mismatch</td>";
        }
        
        echo "</tr>";
    }
    
    echo "</table>";
}

// Step 4: Recalculate affiliate stats
echo "<h3>Step 4: Recalculating Affiliate Statistics</h3>";

$affiliates = $conn->query("SELECT id FROM affiliates");
$updated = 0;

while ($affiliate = $affiliates->fetch_assoc()) {
    $affiliateId = $affiliate['id'];
    
    // Count referrals from users table
    $countResult = $conn->query("SELECT COUNT(*) as count FROM users WHERE referred_by = $affiliateId");
    $count = $countResult->fetch_assoc()['count'];
    
    // Update affiliate
    $updateStmt = $conn->prepare("UPDATE affiliates SET total_referrals = ? WHERE id = ?");
    $updateStmt->bind_param("ii", $count, $affiliateId);
    $updateStmt->execute();
    
    echo "<p>Affiliate ID $affiliateId: Updated total_referrals to $count</p>";
    $updated++;
}

echo "<p style='color: green; font-weight: bold;'>✅ Updated $updated affiliates!</p>";

// Summary
echo "<h3>✅ Sync Complete!</h3>";
echo "<ul>";
echo "<li>✅ Synced affiliate_referrals to users.referred_by</li>";
echo "<li>✅ Recalculated affiliate statistics</li>";
echo "<li>✅ Verified data integrity</li>";
echo "</ul>";

echo "<h3>Next Steps:</h3>";
echo "<ol>";
echo "<li><a href='affiliate-dashboard.php'>Go to Affiliate Dashboard</a> - Check your referrals list</li>";
echo "<li><a href='admin/affiliates.php'>Go to Admin Panel</a> - Verify affiliate stats</li>";
echo "<li><a href='check-referral-data.php'>Run Diagnostic</a> - Detailed check</li>";
echo "</ol>";

?>

<style>
body { font-family: Arial, sans-serif; padding: 20px; max-width: 1200px; margin: 0 auto; }
table { border-collapse: collapse; margin: 20px 0; width: 100%; }
th { background: #4CAF50; color: white; padding: 12px; }
td { padding: 10px; border: 1px solid #ddd; }
h2 { color: #333; border-bottom: 3px solid #4CAF50; padding-bottom: 10px; }
h3 { color: #555; margin-top: 30px; }
a { color: #4CAF50; text-decoration: none; font-weight: bold; }
a:hover { text-decoration: underline; }
</style>
