<?php
require_once 'config/config.php';

echo "<h2>Fixing Admin User Referral Data</h2>";
echo "<p>Admin users should not have 'referred_by' set, as they cannot be referred by affiliates.</p>";

// Find admin users with referred_by set
$query = "SELECT id, name, email, role, referred_by FROM users WHERE role IN ('admin', 'editor') AND referred_by IS NOT NULL";
$result = $conn->query($query);

if ($result->num_rows > 0) {
    echo "<h3>Found " . $result->num_rows . " admin user(s) with referred_by set:</h3>";
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>User ID</th><th>Name</th><th>Email</th><th>Role</th><th>Current referred_by</th><th>Action</th></tr>";
    
    $fixed = 0;
    while ($user = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $user['id'] . "</td>";
        echo "<td>" . htmlspecialchars($user['name']) . "</td>";
        echo "<td>" . htmlspecialchars($user['email']) . "</td>";
        echo "<td>" . $user['role'] . "</td>";
        echo "<td>" . $user['referred_by'] . "</td>";
        
        // Clear referred_by for admin users
        $updateStmt = $conn->prepare("UPDATE users SET referred_by = NULL WHERE id = ?");
        $updateStmt->bind_param("i", $user['id']);
        
        if ($updateStmt->execute()) {
            echo "<td style='color: green;'>✅ Cleared</td>";
            $fixed++;
        } else {
            echo "<td style='color: red;'>❌ Error</td>";
        }
        
        echo "</tr>";
    }
    
    echo "</table>";
    echo "<p style='color: green; font-weight: bold;'>✅ Fixed $fixed admin user(s)!</p>";
} else {
    echo "<p style='color: green;'>✅ No admin users with referred_by found. Data is clean!</p>";
}

// Also check for users referring themselves
echo "<h3>Checking for Self-Referrals</h3>";

$selfReferralQuery = "
    SELECT u.id, u.name, u.email, u.referred_by, a.id as affiliate_id
    FROM users u
    JOIN affiliates a ON u.id = a.user_id
    WHERE u.referred_by = a.id
";

$selfReferrals = $conn->query($selfReferralQuery);

if ($selfReferrals->num_rows > 0) {
    echo "<p style='color: orange;'>⚠️ Found " . $selfReferrals->num_rows . " user(s) referring themselves:</p>";
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>User ID</th><th>Name</th><th>Email</th><th>Action</th></tr>";
    
    $fixedSelf = 0;
    while ($user = $selfReferrals->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $user['id'] . "</td>";
        echo "<td>" . htmlspecialchars($user['name']) . "</td>";
        echo "<td>" . htmlspecialchars($user['email']) . "</td>";
        
        // Clear self-referral
        $updateStmt = $conn->prepare("UPDATE users SET referred_by = NULL WHERE id = ?");
        $updateStmt->bind_param("i", $user['id']);
        
        if ($updateStmt->execute()) {
            echo "<td style='color: green;'>✅ Cleared</td>";
            $fixedSelf++;
        } else {
            echo "<td style='color: red;'>❌ Error</td>";
        }
        
        echo "</tr>";
    }
    
    echo "</table>";
    echo "<p style='color: green; font-weight: bold;'>✅ Fixed $fixedSelf self-referral(s)!</p>";
} else {
    echo "<p style='color: green;'>✅ No self-referrals found!</p>";
}

// Recalculate affiliate stats
echo "<h3>Recalculating Affiliate Statistics</h3>";

$affiliates = $conn->query("SELECT id FROM affiliates");
$updated = 0;

while ($affiliate = $affiliates->fetch_assoc()) {
    $affiliateId = $affiliate['id'];
    
    // Count only regular users (not admins) who are referred
    $countResult = $conn->query("
        SELECT COUNT(*) as count 
        FROM users 
        WHERE referred_by = $affiliateId 
        AND role = 'user'
    ");
    $count = $countResult->fetch_assoc()['count'];
    
    // Update affiliate
    $updateStmt = $conn->prepare("UPDATE affiliates SET total_referrals = ? WHERE id = ?");
    $updateStmt->bind_param("ii", $count, $affiliateId);
    $updateStmt->execute();
    
    echo "<p>Affiliate ID $affiliateId: Updated total_referrals to $count</p>";
    $updated++;
}

echo "<p style='color: green; font-weight: bold;'>✅ Updated $updated affiliate(s)!</p>";

// Summary
echo "<h3>✅ Fix Complete!</h3>";
echo "<ul>";
echo "<li>✅ Cleared referred_by for admin users</li>";
echo "<li>✅ Removed self-referrals</li>";
echo "<li>✅ Recalculated affiliate statistics</li>";
echo "</ul>";

echo "<h3>Rules Applied:</h3>";
echo "<ol>";
echo "<li><strong>Admin users cannot be referred</strong> - referred_by must be NULL for admins</li>";
echo "<li><strong>Users cannot refer themselves</strong> - referred_by cannot equal own affiliate ID</li>";
echo "<li><strong>Only regular users count as referrals</strong> - role must be 'user'</li>";
echo "</ol>";

echo "<h3>Next Steps:</h3>";
echo "<p><a href='affiliate-dashboard.php'>Go to Affiliate Dashboard</a> - Check your referrals list (should not show admin users)</p>";
echo "<p><a href='admin/affiliates.php'>Go to Admin Panel</a> - Verify affiliate stats are correct</p>";

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
