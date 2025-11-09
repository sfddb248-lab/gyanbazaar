<?php
require_once 'config/config.php';

if (!isset($_SESSION['user_id'])) {
    die("Please login first");
}

$userId = $_SESSION['user_id'];
$affiliate = getAffiliateByUserId($userId);

if (!$affiliate) {
    die("You are not an affiliate");
}

echo "<h2>Referral Data Diagnostic</h2>";

// Check 1: Affiliate Info
echo "<h3>1. Your Affiliate Info</h3>";
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>Field</th><th>Value</th></tr>";
echo "<tr><td>Affiliate ID</td><td>" . $affiliate['id'] . "</td></tr>";
echo "<tr><td>User ID</td><td>" . $affiliate['user_id'] . "</td></tr>";
echo "<tr><td>Referral Code</td><td>" . $affiliate['referral_code'] . "</td></tr>";
echo "<tr><td>Total Referrals (from affiliates table)</td><td>" . $affiliate['total_referrals'] . "</td></tr>";
echo "</table>";

// Check 2: Users with referred_by set to this affiliate
echo "<h3>2. Users with referred_by = " . $affiliate['id'] . "</h3>";
$stmt = $conn->prepare("SELECT id, name, email, created_at, referred_by FROM users WHERE referred_by = ?");
$stmt->bind_param("i", $affiliate['id']);
$stmt->execute();
$usersWithReferredBy = $stmt->get_result();

if ($usersWithReferredBy->num_rows > 0) {
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>User ID</th><th>Name</th><th>Email</th><th>Joined Date</th><th>referred_by</th></tr>";
    while ($user = $usersWithReferredBy->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $user['id'] . "</td>";
        echo "<td>" . htmlspecialchars($user['name']) . "</td>";
        echo "<td>" . htmlspecialchars($user['email']) . "</td>";
        echo "<td>" . $user['created_at'] . "</td>";
        echo "<td>" . $user['referred_by'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>❌ No users found with referred_by = " . $affiliate['id'] . "</p>";
}

// Check 3: Affiliate Referrals Table
echo "<h3>3. Affiliate Referrals Table</h3>";
$stmt = $conn->prepare("SELECT * FROM affiliate_referrals WHERE affiliate_id = ?");
$stmt->bind_param("i", $affiliate['id']);
$stmt->execute();
$referrals = $stmt->get_result();

if ($referrals->num_rows > 0) {
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>ID</th><th>Referred User ID</th><th>Status</th><th>Created At</th></tr>";
    while ($ref = $referrals->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $ref['id'] . "</td>";
        echo "<td>" . $ref['referred_user_id'] . "</td>";
        echo "<td>" . $ref['status'] . "</td>";
        echo "<td>" . $ref['created_at'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check if these users have referred_by set
    echo "<h4>Checking if these users have referred_by set:</h4>";
    $referrals->data_seek(0); // Reset pointer
    while ($ref = $referrals->fetch_assoc()) {
        $userCheck = $conn->query("SELECT id, name, referred_by FROM users WHERE id = " . $ref['referred_user_id']);
        if ($userCheck && $user = $userCheck->fetch_assoc()) {
            echo "<p>User ID " . $user['id'] . " (" . htmlspecialchars($user['name']) . "): ";
            if ($user['referred_by'] == $affiliate['id']) {
                echo "<span style='color: green;'>✅ referred_by is set correctly</span>";
            } else if ($user['referred_by']) {
                echo "<span style='color: orange;'>⚠️ referred_by = " . $user['referred_by'] . " (should be " . $affiliate['id'] . ")</span>";
            } else {
                echo "<span style='color: red;'>❌ referred_by is NULL (should be " . $affiliate['id'] . ")</span>";
            }
            echo "</p>";
        }
    }
} else {
    echo "<p style='color: orange;'>⚠️ No entries in affiliate_referrals table</p>";
}

// Check 4: Check referred_by column exists
echo "<h3>4. Database Structure Check</h3>";
$columns = $conn->query("SHOW COLUMNS FROM users LIKE 'referred_by'");
if ($columns->num_rows > 0) {
    echo "<p style='color: green;'>✅ Column 'referred_by' exists in users table</p>";
    $col = $columns->fetch_assoc();
    echo "<pre>";
    print_r($col);
    echo "</pre>";
} else {
    echo "<p style='color: red;'>❌ Column 'referred_by' does NOT exist in users table</p>";
    echo "<p><strong>Action Required:</strong> Run <a href='fix-referral-column.php'>fix-referral-column.php</a></p>";
}

// Check 5: Suggest Fix
echo "<h3>5. Suggested Fix</h3>";

$stmt = $conn->prepare("SELECT * FROM affiliate_referrals WHERE affiliate_id = ?");
$stmt->bind_param("i", $affiliate['id']);
$stmt->execute();
$referralsToFix = $stmt->get_result();

if ($referralsToFix->num_rows > 0) {
    echo "<p>Found " . $referralsToFix->num_rows . " referrals in affiliate_referrals table that need to be synced to users.referred_by</p>";
    echo "<form method='POST'>";
    echo "<button type='submit' name='fix_referrals' class='btn btn-primary'>Fix Referral Links</button>";
    echo "</form>";
    
    if (isset($_POST['fix_referrals'])) {
        echo "<h4>Fixing Referrals...</h4>";
        $referralsToFix->data_seek(0);
        $fixed = 0;
        while ($ref = $referralsToFix->fetch_assoc()) {
            $updateStmt = $conn->prepare("UPDATE users SET referred_by = ? WHERE id = ?");
            $updateStmt->bind_param("ii", $affiliate['id'], $ref['referred_user_id']);
            if ($updateStmt->execute()) {
                echo "<p>✅ Updated user ID " . $ref['referred_user_id'] . "</p>";
                $fixed++;
            }
        }
        echo "<p style='color: green; font-weight: bold;'>✅ Fixed $fixed referrals!</p>";
        echo "<p><a href='affiliate-dashboard.php'>Go to Affiliate Dashboard</a></p>";
    }
}

echo "<hr>";
echo "<p><a href='affiliate-dashboard.php'>← Back to Affiliate Dashboard</a></p>";
?>

<style>
body { font-family: Arial, sans-serif; padding: 20px; }
table { border-collapse: collapse; margin: 20px 0; }
th { background: #4CAF50; color: white; }
td, th { padding: 10px; text-align: left; }
.btn { padding: 10px 20px; background: #4CAF50; color: white; border: none; cursor: pointer; }
.btn:hover { background: #45a049; }
</style>
