<?php
require_once 'config/config.php';

// Security check - only allow if logged in as admin
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'editor'])) {
    die("Access denied. Admin login required.");
}

echo "<h1>🗑️ Delete All Users (Keep Admin)</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .warning { color: orange; font-weight: bold; }
    .section { margin: 20px 0; padding: 20px; background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    h2 { color: #333; border-bottom: 2px solid #dc3545; padding-bottom: 10px; }
    .danger-zone { background: #fff3cd; border: 2px solid #ffc107; padding: 20px; border-radius: 8px; margin: 20px 0; }
    .btn { padding: 12px 24px; margin: 10px 5px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; text-decoration: none; display: inline-block; }
    .btn-danger { background: #dc3545; color: white; }
    .btn-success { background: #28a745; color: white; }
    .btn:hover { opacity: 0.9; }
</style>";

// Check if confirmation is provided
if (!isset($_GET['confirm']) || $_GET['confirm'] !== 'yes') {
    echo "<div class='danger-zone'>";
    echo "<h2>⚠️ WARNING - DESTRUCTIVE ACTION</h2>";
    echo "<p><strong>This will permanently delete:</strong></p>";
    echo "<ul>";
    echo "<li>All regular user accounts (role = 'user')</li>";
    echo "<li>All affiliate accounts linked to those users</li>";
    echo "<li>All orders from those users</li>";
    echo "<li>All referrals and commissions</li>";
    echo "<li>All course progress data</li>";
    echo "</ul>";
    echo "<p><strong style='color: red;'>Admin and Editor accounts will be preserved.</strong></p>";
    
    // Show what will be deleted
    $userCount = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'user'")->fetch_assoc()['count'];
    $affiliateCount = $conn->query("SELECT COUNT(*) as count FROM affiliates a JOIN users u ON a.user_id = u.id WHERE u.role = 'user'")->fetch_assoc()['count'];
    $orderCount = $conn->query("SELECT COUNT(*) as count FROM orders o JOIN users u ON o.user_id = u.id WHERE u.role = 'user'")->fetch_assoc()['count'];
    
    echo "<div style='background: white; padding: 15px; border-radius: 5px; margin: 15px 0;'>";
    echo "<h3>Items to be deleted:</h3>";
    echo "<ul>";
    echo "<li><strong>$userCount</strong> user accounts</li>";
    echo "<li><strong>$affiliateCount</strong> affiliate accounts</li>";
    echo "<li><strong>$orderCount</strong> orders</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<p><strong>Are you absolutely sure you want to proceed?</strong></p>";
    echo "<a href='?confirm=yes' class='btn btn-danger' onclick='return confirm(\"This action cannot be undone! Are you sure?\")'>✓ YES, DELETE ALL USERS</a>";
    echo "<a href='index.php' class='btn btn-success'>✗ Cancel</a>";
    echo "</div>";
    exit;
}

// Proceed with deletion
echo "<div class='section'>";
echo "<h2>Deleting All User Data...</h2>";

// Disable foreign key checks temporarily
$conn->query("SET FOREIGN_KEY_CHECKS = 0");

// Get list of user IDs to delete (excluding admin/editor)
$userIds = [];
$result = $conn->query("SELECT id, name, email FROM users WHERE role = 'user'");
while ($row = $result->fetch_assoc()) {
    $userIds[] = $row['id'];
    echo "<p>Found user: {$row['name']} ({$row['email']})</p>";
}

if (empty($userIds)) {
    echo "<p class='warning'>⚠ No regular users found to delete.</p>";
} else {
    $userIdList = implode(',', $userIds);
    
    // Delete in order to respect dependencies
    
    // 1. Delete course progress (if table exists)
    echo "<h3>Step 1: Deleting course progress...</h3>";
    try {
        $result = $conn->query("DELETE FROM course_progress WHERE user_id IN ($userIdList)");
        echo "<p class='success'>✓ Deleted course progress records</p>";
    } catch (Exception $e) {
        echo "<p class='warning'>⚠ Table course_progress doesn't exist (skipped)</p>";
    }
    
    // 2. Delete order items first
    echo "<h3>Step 2: Deleting order items...</h3>";
    try {
        $result = $conn->query("DELETE oi FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE o.user_id IN ($userIdList)");
        echo "<p class='success'>✓ Deleted order items</p>";
    } catch (Exception $e) {
        echo "<p class='warning'>⚠ Could not delete order items: " . $e->getMessage() . "</p>";
    }
    
    // 3. Delete affiliate commissions
    echo "<h3>Step 3: Deleting commissions...</h3>";
    try {
        $result = $conn->query("DELETE FROM affiliate_commissions WHERE order_id IN (SELECT id FROM orders WHERE user_id IN ($userIdList))");
        echo "<p class='success'>✓ Deleted commissions</p>";
    } catch (Exception $e) {
        echo "<p class='warning'>⚠ Could not delete commissions: " . $e->getMessage() . "</p>";
    }
    
    // 4. Delete orders
    echo "<h3>Step 4: Deleting orders...</h3>";
    try {
        $result = $conn->query("DELETE FROM orders WHERE user_id IN ($userIdList)");
        echo "<p class='success'>✓ Deleted orders</p>";
    } catch (Exception $e) {
        echo "<p class='warning'>⚠ Could not delete orders: " . $e->getMessage() . "</p>";
    }
    
    // 5. Delete affiliate referrals
    echo "<h3>Step 5: Deleting referrals...</h3>";
    try {
        $result = $conn->query("DELETE FROM affiliate_referrals WHERE referred_user_id IN ($userIdList)");
        echo "<p class='success'>✓ Deleted referral records</p>";
    } catch (Exception $e) {
        echo "<p class='warning'>⚠ Could not delete referrals: " . $e->getMessage() . "</p>";
    }
    
    // 6. Delete affiliate clicks
    echo "<h3>Step 6: Deleting affiliate clicks...</h3>";
    try {
        $affiliateIds = [];
        $affResult = $conn->query("SELECT id FROM affiliates WHERE user_id IN ($userIdList)");
        while ($row = $affResult->fetch_assoc()) {
            $affiliateIds[] = $row['id'];
        }
        if (!empty($affiliateIds)) {
            $affiliateIdList = implode(',', $affiliateIds);
            $conn->query("DELETE FROM affiliate_clicks WHERE affiliate_id IN ($affiliateIdList)");
            echo "<p class='success'>✓ Deleted affiliate clicks</p>";
        } else {
            echo "<p class='success'>✓ No affiliate clicks to delete</p>";
        }
    } catch (Exception $e) {
        echo "<p class='warning'>⚠ Could not delete clicks: " . $e->getMessage() . "</p>";
    }
    
    // 7. Delete affiliate payouts
    echo "<h3>Step 7: Deleting payouts...</h3>";
    try {
        if (!empty($affiliateIds)) {
            $conn->query("DELETE FROM affiliate_payouts WHERE affiliate_id IN ($affiliateIdList)");
            echo "<p class='success'>✓ Deleted payout records</p>";
        } else {
            echo "<p class='success'>✓ No payouts to delete</p>";
        }
    } catch (Exception $e) {
        echo "<p class='warning'>⚠ Could not delete payouts: " . $e->getMessage() . "</p>";
    }
    
    // 8. Delete affiliates
    echo "<h3>Step 8: Deleting affiliate accounts...</h3>";
    try {
        $result = $conn->query("DELETE FROM affiliates WHERE user_id IN ($userIdList)");
        echo "<p class='success'>✓ Deleted affiliate accounts</p>";
    } catch (Exception $e) {
        echo "<p class='warning'>⚠ Could not delete affiliates: " . $e->getMessage() . "</p>";
    }
    
    // 9. Delete support tickets
    echo "<h3>Step 9: Deleting support tickets...</h3>";
    try {
        $result = $conn->query("DELETE FROM support_tickets WHERE user_id IN ($userIdList)");
        echo "<p class='success'>✓ Deleted support tickets</p>";
    } catch (Exception $e) {
        echo "<p class='warning'>⚠ Could not delete support tickets: " . $e->getMessage() . "</p>";
    }
    
    // 10. Finally, delete users
    echo "<h3>Step 10: Deleting user accounts...</h3>";
    try {
        $result = $conn->query("DELETE FROM users WHERE id IN ($userIdList)");
        $deletedCount = $conn->affected_rows;
        echo "<p class='success'>✓ Deleted $deletedCount user accounts</p>";
    } catch (Exception $e) {
        echo "<p class='error'>✗ Could not delete users: " . $e->getMessage() . "</p>";
    }
}

// Re-enable foreign key checks
$conn->query("SET FOREIGN_KEY_CHECKS = 1");

echo "</div>";

// Show remaining users
echo "<div class='section'>";
echo "<h2>Remaining Users (Admin/Editor)</h2>";

$remainingUsers = $conn->query("SELECT id, name, email, role FROM users ORDER BY role, id")->fetch_all(MYSQLI_ASSOC);

if (count($remainingUsers) > 0) {
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #007bff; color: white;'><th>ID</th><th>Name</th><th>Email</th><th>Role</th></tr>";
    foreach ($remainingUsers as $user) {
        $roleColor = $user['role'] === 'admin' ? '#dc3545' : '#ffc107';
        echo "<tr>";
        echo "<td>{$user['id']}</td>";
        echo "<td>{$user['name']}</td>";
        echo "<td>{$user['email']}</td>";
        echo "<td><span style='background: $roleColor; color: white; padding: 5px 10px; border-radius: 3px;'>{$user['role']}</span></td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p class='warning'>⚠ No users remaining!</p>";
}

echo "</div>";

// Final statistics
echo "<div class='section' style='background: #d4edda; border: 2px solid #28a745;'>";
echo "<h2>✅ Deletion Complete!</h2>";

$stats = [
    'Total Users' => $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'],
    'Admin/Editor Users' => $conn->query("SELECT COUNT(*) as count FROM users WHERE role IN ('admin', 'editor')")->fetch_assoc()['count'],
    'Regular Users' => $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'user'")->fetch_assoc()['count'],
    'Affiliates' => $conn->query("SELECT COUNT(*) as count FROM affiliates")->fetch_assoc()['count'],
    'Orders' => $conn->query("SELECT COUNT(*) as count FROM orders")->fetch_assoc()['count'],
    'Referrals' => $conn->query("SELECT COUNT(*) as count FROM affiliate_referrals")->fetch_assoc()['count'],
    'Commissions' => $conn->query("SELECT COUNT(*) as count FROM affiliate_commissions")->fetch_assoc()['count']
];

echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%; background: white;'>";
echo "<tr style='background: #f8f9fa;'><th>Metric</th><th>Count</th></tr>";
foreach ($stats as $metric => $count) {
    echo "<tr><td><strong>$metric</strong></td><td>$count</td></tr>";
}
echo "</table>";

echo "<h3>Next Steps:</h3>";
echo "<ol>";
echo "<li>Database is now clean with only admin accounts</li>";
echo "<li>Run: <a href='reset-and-setup-affiliate-system.php'>Reset & Setup Affiliate System</a></li>";
echo "<li>Create a fresh affiliate account</li>";
echo "<li>Test the referral flow with new users</li>";
echo "</ol>";

echo "<p>";
echo "<a href='reset-and-setup-affiliate-system.php' class='btn btn-success'>Continue to Setup</a>";
echo "<a href='admin/users.php' class='btn' style='background: #007bff; color: white;'>View Users</a>";
echo "</p>";

echo "</div>";

?>
