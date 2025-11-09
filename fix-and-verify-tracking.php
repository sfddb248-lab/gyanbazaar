<?php
require_once 'config/config.php';

echo "<h1>🔧 Fix & Verify Affiliate Tracking</h1>";
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
    pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; }
</style>";

// Check 1: Database structure
echo "<div class='section'>";
echo "<h2>Check 1: Database Structure</h2>";

$checks = [
    'users.referred_by' => "SHOW COLUMNS FROM users LIKE 'referred_by'",
    'affiliate_referrals.purchase_made' => "SHOW COLUMNS FROM affiliate_referrals LIKE 'purchase_made'",
    'affiliate_commissions table' => "SHOW TABLES LIKE 'affiliate_commissions'"
];

foreach ($checks as $name => $query) {
    $result = $conn->query($query);
    if ($result && $result->num_rows > 0) {
        echo "<p class='success'>✓ $name exists</p>";
    } else {
        echo "<p class='error'>✗ $name missing</p>";
    }
}

echo "</div>";

// Check 2: Current data state
echo "<div class='section'>";
echo "<h2>Check 2: Current Data State</h2>";

$queries = [
    'Total Users' => "SELECT COUNT(*) as count FROM users WHERE role='user'",
    'Users with referred_by' => "SELECT COUNT(*) as count FROM users WHERE referred_by IS NOT NULL AND role='user'",
    'Total Affiliates' => "SELECT COUNT(*) as count FROM affiliates WHERE status='active'",
    'Total Orders' => "SELECT COUNT(*) as count FROM orders WHERE payment_status='completed'",
    'Referral Records' => "SELECT COUNT(*) as count FROM affiliate_referrals",
    'Referrals with Purchases' => "SELECT COUNT(*) as count FROM affiliate_referrals WHERE purchase_made=1",
    'Total Commissions' => "SELECT COUNT(*) as count FROM affiliate_commissions"
];

echo "<table>";
echo "<tr><th>Metric</th><th>Count</th></tr>";
foreach ($queries as $name => $query) {
    $result = $conn->query($query);
    $count = $result ? $result->fetch_assoc()['count'] : 0;
    echo "<tr><td>$name</td><td><strong>$count</strong></td></tr>";
}
echo "</table>";

echo "</div>";

// Check 3: Show actual affiliate data
echo "<div class='section'>";
echo "<h2>Check 3: Your Affiliate Account</h2>";

if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    $affiliate = $conn->query("SELECT * FROM affiliates WHERE user_id = $userId")->fetch_assoc();
    
    if ($affiliate) {
        echo "<p class='success'>✓ You have an affiliate account</p>";
        echo "<table>";
        echo "<tr><th>Field</th><th>Value</th></tr>";
        echo "<tr><td>Affiliate ID</td><td>{$affiliate['id']}</td></tr>";
        echo "<tr><td>Referral Code</td><td><strong style='color: #007bff;'>{$affiliate['referral_code']}</strong></td></tr>";
        echo "<tr><td>Total Referrals</td><td>{$affiliate['total_referrals']}</td></tr>";
        echo "<tr><td>Total Sales</td><td>{$affiliate['total_sales']}</td></tr>";
        echo "<tr><td>Total Earnings</td><td>₹" . number_format($affiliate['total_earnings'], 2) . "</td></tr>";
        echo "<tr><td>Pending Earnings</td><td>₹" . number_format($affiliate['pending_earnings'], 2) . "</td></tr>";
        echo "</table>";
        
        $affiliateId = $affiliate['id'];
        
        // Check users referred by this affiliate
        echo "<h3>Users Referred by You (via users.referred_by):</h3>";
        $referredUsers = $conn->query("
            SELECT id, name, email, created_at 
            FROM users 
            WHERE referred_by = $affiliateId 
            AND role = 'user'
            ORDER BY created_at DESC
        ")->fetch_all(MYSQLI_ASSOC);
        
        if (count($referredUsers) > 0) {
            echo "<table>";
            echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Joined</th></tr>";
            foreach ($referredUsers as $user) {
                echo "<tr>";
                echo "<td>{$user['id']}</td>";
                echo "<td>{$user['name']}</td>";
                echo "<td>{$user['email']}</td>";
                echo "<td>" . date('Y-m-d H:i', strtotime($user['created_at'])) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p class='warning'>⚠ No users have referred_by = $affiliateId</p>";
        }
        
        // Check referral records
        echo "<h3>Referral Records (affiliate_referrals table):</h3>";
        $referralRecords = $conn->query("
            SELECT ar.*, u.name, u.email 
            FROM affiliate_referrals ar
            LEFT JOIN users u ON ar.referred_user_id = u.id
            WHERE ar.affiliate_id = $affiliateId
            ORDER BY ar.created_at DESC
        ")->fetch_all(MYSQLI_ASSOC);
        
        if (count($referralRecords) > 0) {
            echo "<table>";
            echo "<tr><th>ID</th><th>User</th><th>Email</th><th>Purchased</th><th>Date</th></tr>";
            foreach ($referralRecords as $ref) {
                $purchased = $ref['purchase_made'] ? '✓ Yes' : '✗ No';
                $color = $ref['purchase_made'] ? 'green' : 'red';
                echo "<tr>";
                echo "<td>{$ref['id']}</td>";
                echo "<td>" . ($ref['name'] ?? 'N/A') . "</td>";
                echo "<td>" . ($ref['email'] ?? 'N/A') . "</td>";
                echo "<td style='color: $color;'>$purchased</td>";
                echo "<td>" . date('Y-m-d H:i', strtotime($ref['created_at'])) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p class='warning'>⚠ No records in affiliate_referrals table</p>";
        }
        
        // Check orders from referred users
        echo "<h3>Orders from Referred Users:</h3>";
        $orders = $conn->query("
            SELECT o.*, u.name, u.email 
            FROM orders o
            JOIN users u ON o.user_id = u.id
            WHERE u.referred_by = $affiliateId
            AND o.payment_status = 'completed'
            ORDER BY o.created_at DESC
        ")->fetch_all(MYSQLI_ASSOC);
        
        if (count($orders) > 0) {
            echo "<table>";
            echo "<tr><th>Order #</th><th>User</th><th>Amount</th><th>Date</th></tr>";
            foreach ($orders as $order) {
                echo "<tr>";
                echo "<td>{$order['order_number']}</td>";
                echo "<td>{$order['name']}</td>";
                echo "<td>₹" . number_format($order['final_amount'], 2) . "</td>";
                echo "<td>" . date('Y-m-d H:i', strtotime($order['created_at'])) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p class='warning'>⚠ No orders from referred users</p>";
        }
        
        // Check commissions
        echo "<h3>Your Commissions:</h3>";
        $commissions = $conn->query("
            SELECT ac.*, o.order_number 
            FROM affiliate_commissions ac
            JOIN orders o ON ac.order_id = o.id
            WHERE ac.affiliate_id = $affiliateId
            ORDER BY ac.created_at DESC
        ")->fetch_all(MYSQLI_ASSOC);
        
        if (count($commissions) > 0) {
            echo "<table>";
            echo "<tr><th>Order #</th><th>Amount</th><th>Commission</th><th>Status</th><th>Date</th></tr>";
            foreach ($commissions as $comm) {
                echo "<tr>";
                echo "<td>{$comm['order_number']}</td>";
                echo "<td>₹" . number_format($comm['order_amount'], 2) . "</td>";
                echo "<td style='color: green;'>₹" . number_format($comm['commission_amount'], 2) . "</td>";
                echo "<td>{$comm['status']}</td>";
                echo "<td>" . date('Y-m-d H:i', strtotime($comm['created_at'])) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p class='warning'>⚠ No commissions found</p>";
        }
        
    } else {
        echo "<p class='error'>✗ You don't have an affiliate account</p>";
        echo "<p>Go to <a href='affiliate-dashboard.php'>Affiliate Dashboard</a> to create one.</p>";
    }
} else {
    echo "<p class='error'>✗ You are not logged in</p>";
    echo "<p>Please <a href='login.php'>login</a> first.</p>";
}

echo "</div>";

// Check 4: Identify the problem
echo "<div class='section'>";
echo "<h2>Check 4: Problem Diagnosis</h2>";

$problems = [];
$solutions = [];

// Check if there are users but no referred_by
$usersWithoutReferredBy = $conn->query("SELECT COUNT(*) as count FROM users WHERE referred_by IS NULL AND role='user'")->fetch_assoc()['count'];
$totalUsers = $conn->query("SELECT COUNT(*) as count FROM users WHERE role='user'")->fetch_assoc()['count'];

if ($totalUsers > 0 && $usersWithoutReferredBy == $totalUsers) {
    $problems[] = "All users have NULL referred_by field";
    $solutions[] = "Users are not being tracked when they sign up via referral link";
}

// Check if there are orders but no commissions
$totalOrders = $conn->query("SELECT COUNT(*) as count FROM orders WHERE payment_status='completed'")->fetch_assoc()['count'];
$totalCommissions = $conn->query("SELECT COUNT(*) as count FROM affiliate_commissions")->fetch_assoc()['count'];

if ($totalOrders > 0 && $totalCommissions == 0) {
    $problems[] = "Orders exist but no commissions created";
    $solutions[] = "Checkout process is not creating commissions";
}

// Check if affiliate functions are loaded
if (!function_exists('getAffiliateByCode')) {
    $problems[] = "Affiliate functions not loaded";
    $solutions[] = "Check if includes/affiliate-functions.php is included in config.php";
}

if (count($problems) > 0) {
    echo "<h3 style='color: red;'>Problems Found:</h3>";
    echo "<ul>";
    foreach ($problems as $problem) {
        echo "<li class='error'>$problem</li>";
    }
    echo "</ul>";
    
    echo "<h3 style='color: orange;'>Solutions:</h3>";
    echo "<ul>";
    foreach ($solutions as $solution) {
        echo "<li class='warning'>$solution</li>";
    }
    echo "</ul>";
} else {
    echo "<p class='success'>✓ No obvious problems detected</p>";
}

echo "</div>";

// Provide action buttons
echo "<div class='section' style='background: #e7f3ff;'>";
echo "<h2>Next Steps</h2>";
echo "<p><strong>Based on the diagnosis above, choose an action:</strong></p>";
echo "<p>";
echo "<a href='test-referral-flow-complete.php' class='btn' style='padding: 12px 24px; background: #28a745; color: white; text-decoration: none; border-radius: 5px; display: inline-block; margin: 5px;'>🧪 Run Complete Test</a>";
echo "<a href='reset-and-setup-affiliate-system.php' class='btn' style='padding: 12px 24px; background: #dc3545; color: white; text-decoration: none; border-radius: 5px; display: inline-block; margin: 5px;'>🔄 Reset System</a>";
echo "<a href='affiliate-dashboard.php' class='btn' style='padding: 12px 24px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; display: inline-block; margin: 5px;'>📊 View Dashboard</a>";
echo "</p>";
echo "</div>";

?>
