<?php
require_once 'config/config.php';

echo "<h2>Testing OTP Verification System</h2>";
echo "<hr>";

// Test 1: Check if OTP functions are loaded
echo "<h3>Test 1: OTP Functions</h3>";
if (function_exists('generateOTP')) {
    echo "<p style='color:green;'>✓ generateOTP() function exists</p>";
} else {
    echo "<p style='color:red;'>✗ generateOTP() function NOT found</p>";
}

if (function_exists('sendOTPEmail')) {
    echo "<p style='color:green;'>✓ sendOTPEmail() function exists</p>";
} else {
    echo "<p style='color:red;'>✗ sendOTPEmail() function NOT found</p>";
}

if (function_exists('verifyOTP')) {
    echo "<p style='color:green;'>✓ verifyOTP() function exists</p>";
} else {
    echo "<p style='color:red;'>✗ verifyOTP() function NOT found</p>";
}

// Test 2: Check if otp_emails table exists
echo "<hr><h3>Test 2: Database Tables</h3>";
$result = $conn->query("SHOW TABLES LIKE 'otp_emails'");
if ($result->num_rows > 0) {
    echo "<p style='color:green;'>✓ otp_emails table exists</p>";
    
    // Show table structure
    $result = $conn->query("DESCRIBE otp_emails");
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['Field']}</td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>{$row['Key']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color:red;'>✗ otp_emails table NOT found</p>";
}

// Test 3: Check users table for OTP fields
echo "<hr><h3>Test 3: Users Table OTP Fields</h3>";
$result = $conn->query("DESCRIBE users");
$fields = [];
while ($row = $result->fetch_assoc()) {
    $fields[] = $row['Field'];
}

$requiredFields = ['email_verified', 'otp_code', 'otp_expiry'];
foreach ($requiredFields as $field) {
    if (in_array($field, $fields)) {
        echo "<p style='color:green;'>✓ users.$field exists</p>";
    } else {
        echo "<p style='color:red;'>✗ users.$field NOT found</p>";
    }
}

// Test 4: Check if sendEmail function works
echo "<hr><h3>Test 4: Email Function</h3>";
if (function_exists('sendEmail')) {
    echo "<p style='color:green;'>✓ sendEmail() function exists</p>";
    
    // Try to send a test email (won't actually send if SMTP not configured)
    $testEmail = 'test@example.com';
    $result = @sendEmail($testEmail, 'Test Email', 'This is a test email');
    
    if ($result) {
        echo "<p style='color:green;'>✓ sendEmail() executed successfully</p>";
    } else {
        echo "<p style='color:orange;'>⚠ sendEmail() executed but may not have sent (SMTP not configured)</p>";
    }
} else {
    echo "<p style='color:red;'>✗ sendEmail() function NOT found</p>";
}

// Test 5: Check recent OTP records
echo "<hr><h3>Test 5: Recent OTP Records</h3>";
if ($conn->query("SHOW TABLES LIKE 'otp_emails'")->num_rows > 0) {
    $result = $conn->query("SELECT * FROM otp_emails ORDER BY created_at DESC LIMIT 5");
    
    if ($result->num_rows > 0) {
        echo "<p>Recent OTP records:</p>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Email</th><th>OTP</th><th>Status</th><th>Created</th><th>Expires</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>{$row['id']}</td>";
            echo "<td>{$row['email']}</td>";
            echo "<td><strong>{$row['otp_code']}</strong></td>";
            echo "<td>{$row['status']}</td>";
            echo "<td>{$row['created_at']}</td>";
            echo "<td>{$row['expires_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color:orange;'>⚠ No OTP records found (no signups yet)</p>";
    }
}

// Test 6: Check pending users
echo "<hr><h3>Test 6: Pending Users (Unverified)</h3>";
$result = $conn->query("SELECT id, name, email, status, email_verified, otp_code, created_at FROM users WHERE status = 'pending' OR email_verified = FALSE ORDER BY created_at DESC LIMIT 5");

if ($result->num_rows > 0) {
    echo "<p>Pending users waiting for verification:</p>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Status</th><th>Verified</th><th>OTP</th><th>Created</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['name']}</td>";
        echo "<td>{$row['email']}</td>";
        echo "<td>{$row['status']}</td>";
        echo "<td>" . ($row['email_verified'] ? 'Yes' : 'No') . "</td>";
        echo "<td><strong>" . ($row['otp_code'] ?? 'None') . "</strong></td>";
        echo "<td>{$row['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color:green;'>✓ No pending users (all verified or no signups yet)</p>";
}

// Summary
echo "<hr><h3>Summary</h3>";
$allGood = function_exists('generateOTP') && 
           function_exists('sendOTPEmail') && 
           function_exists('verifyOTP') &&
           $conn->query("SHOW TABLES LIKE 'otp_emails'")->num_rows > 0;

if ($allGood) {
    echo "<p style='color:green; font-size:18px; font-weight:bold;'>✓ OTP Verification System is READY!</p>";
    echo "<p>You can now test by creating a new account at: <a href='signup.php'>signup.php</a></p>";
} else {
    echo "<p style='color:red; font-size:18px; font-weight:bold;'>✗ OTP Verification System has issues</p>";
    echo "<p>Please check the errors above and fix them.</p>";
}

echo "<hr>";
echo "<p><a href='signup.php'>Test Signup</a> | <a href='login.php'>Test Login</a> | <a href='admin/view-otps.php'>View All OTPs (Admin)</a></p>";

$conn->close();
?>
