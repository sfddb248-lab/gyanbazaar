<?php
require_once 'config/config.php';

// Security check - only run if explicitly confirmed
if (!isset($_GET['confirm']) || $_GET['confirm'] !== 'yes') {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Clean Database</title>
        <style>
            body { font-family: Arial; padding: 50px; background: #f5f5f5; }
            .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            h2 { color: #d32f2f; }
            .warning { background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 20px 0; }
            .btn { display: inline-block; padding: 12px 24px; margin: 10px 5px; text-decoration: none; border-radius: 5px; font-weight: bold; }
            .btn-danger { background: #d32f2f; color: white; }
            .btn-secondary { background: #6c757d; color: white; }
            ul { line-height: 2; }
        </style>
    </head>
    <body>
        <div class="container">
            <h2>⚠️ Clean Database - Confirmation Required</h2>
            
            <div class="warning">
                <strong>WARNING:</strong> This will permanently delete the following data:
            </div>
            
            <ul>
                <li>All test users (except admin)</li>
                <li>All orders and order items</li>
                <li>All OTP records</li>
                <li>All support tickets</li>
                <li>All user sessions</li>
            </ul>
            
            <p><strong>What will be kept:</strong></p>
            <ul>
                <li>✓ Admin account (admin@gyanbazaar.com)</li>
                <li>✓ Products and categories</li>
                <li>✓ Coupons</li>
                <li>✓ Settings</li>
            </ul>
            
            <p style="color: #d32f2f; font-weight: bold;">This action CANNOT be undone!</p>
            
            <a href="?confirm=yes" class="btn btn-danger" onclick="return confirm('Are you absolutely sure? This will delete all test users and their data!')">
                🗑️ Yes, Clean Database
            </a>
            <a href="index.php" class="btn btn-secondary">
                ← Cancel
            </a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Proceed with cleaning
echo "<h2>🧹 Cleaning Database...</h2>";
echo "<hr>";

// Get admin user ID
$adminEmail = 'admin@gyanbazaar.com';
$result = $conn->query("SELECT id FROM users WHERE email = '$adminEmail' OR role = 'admin' LIMIT 1");
$adminId = $result->fetch_assoc()['id'] ?? 1;

echo "<p>Admin ID: $adminId (will be preserved)</p>";
echo "<hr>";

// 1. Delete OTP emails
echo "<h3>1. Cleaning OTP Records...</h3>";
$result = $conn->query("DELETE FROM otp_emails WHERE user_id != $adminId");
echo "<p style='color:green;'>✓ Deleted " . $conn->affected_rows . " OTP records</p>";

// 2. Delete support tickets
echo "<h3>2. Cleaning Support Tickets...</h3>";
if ($conn->query("SHOW TABLES LIKE 'support_tickets'")->num_rows > 0) {
    $result = $conn->query("DELETE FROM support_tickets WHERE user_id != $adminId");
    echo "<p style='color:green;'>✓ Deleted " . $conn->affected_rows . " support tickets</p>";
} else {
    echo "<p style='color:orange;'>⚠ Support tickets table not found</p>";
}

// 3. Delete order items
echo "<h3>3. Cleaning Order Items...</h3>";
$result = $conn->query("DELETE FROM order_items WHERE order_id IN (SELECT id FROM orders WHERE user_id != $adminId)");
echo "<p style='color:green;'>✓ Deleted " . $conn->affected_rows . " order items</p>";

// 4. Delete orders
echo "<h3>4. Cleaning Orders...</h3>";
$result = $conn->query("DELETE FROM orders WHERE user_id != $adminId");
echo "<p style='color:green;'>✓ Deleted " . $conn->affected_rows . " orders</p>";

// 5. Delete users (except admin)
echo "<h3>5. Cleaning Users...</h3>";
$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE id != $adminId");
$userCount = $result->fetch_assoc()['count'];
$result = $conn->query("DELETE FROM users WHERE id != $adminId");
echo "<p style='color:green;'>✓ Deleted $userCount test users</p>";

// 6. Reset auto increment
echo "<h3>6. Resetting Auto Increments...</h3>";
$conn->query("ALTER TABLE users AUTO_INCREMENT = " . ($adminId + 1));
echo "<p style='color:green;'>✓ Reset users auto increment</p>";

// 7. Show remaining data
echo "<hr>";
echo "<h3>✅ Database Cleaned Successfully!</h3>";

echo "<h4>Remaining Data:</h4>";
echo "<ul>";

// Count users
$result = $conn->query("SELECT COUNT(*) as count FROM users");
$count = $result->fetch_assoc()['count'];
echo "<li>Users: <strong>$count</strong> (admin only)</li>";

// Count products
$result = $conn->query("SELECT COUNT(*) as count FROM products");
$count = $result->fetch_assoc()['count'];
echo "<li>Products: <strong>$count</strong></li>";

// Count categories
$result = $conn->query("SELECT COUNT(*) as count FROM categories");
$count = $result->fetch_assoc()['count'];
echo "<li>Categories: <strong>$count</strong></li>";

// Count coupons
$result = $conn->query("SELECT COUNT(*) as count FROM coupons");
$count = $result->fetch_assoc()['count'];
echo "<li>Coupons: <strong>$count</strong></li>";

// Count orders
$result = $conn->query("SELECT COUNT(*) as count FROM orders");
$count = $result->fetch_assoc()['count'];
echo "<li>Orders: <strong>$count</strong></li>";

// Count OTP records
$result = $conn->query("SELECT COUNT(*) as count FROM otp_emails");
$count = $result->fetch_assoc()['count'];
echo "<li>OTP Records: <strong>$count</strong></li>";

echo "</ul>";

echo "<hr>";
echo "<p style='color:green; font-size:18px; font-weight:bold;'>✓ Database is now clean and ready for fresh start!</p>";
echo "<p><a href='index.php'>← Back to Home</a> | <a href='signup.php'>Create New Account</a></p>";

// Delete this script for security
echo "<hr>";
echo "<p style='color:orange;'><strong>Security Note:</strong> Please delete this file (clean-database.php) after use.</p>";

$conn->close();
?>
