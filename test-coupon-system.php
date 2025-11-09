<?php
require_once 'config/config.php';

echo "<h2>Coupon System Test</h2>";

// Check if coupons table exists
echo "<h3>1. Checking Coupons Table...</h3>";
$tableCheck = $conn->query("SHOW TABLES LIKE 'coupons'");
if ($tableCheck->num_rows > 0) {
    echo "✅ Coupons table exists<br>";
    
    // Check table structure
    $structure = $conn->query("DESCRIBE coupons");
    echo "<h4>Table Structure:</h4>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    while ($row = $structure->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['Field']}</td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>{$row['Key']}</td>";
        echo "<td>{$row['Default']}</td>";
        echo "</tr>";
    }
    echo "</table><br>";
} else {
    echo "❌ Coupons table does NOT exist<br>";
    echo "<p>Creating coupons table...</p>";
    
    $createTable = "CREATE TABLE coupons (
        id INT PRIMARY KEY AUTO_INCREMENT,
        code VARCHAR(50) UNIQUE NOT NULL,
        type ENUM('flat', 'percentage') NOT NULL,
        value DECIMAL(10,2) NOT NULL,
        min_purchase DECIMAL(10,2) DEFAULT 0,
        usage_limit INT,
        used_count INT DEFAULT 0,
        expiry_date DATE,
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($createTable)) {
        echo "✅ Coupons table created successfully<br>";
    } else {
        echo "❌ Error creating table: " . $conn->error . "<br>";
    }
}

// Test inserting a coupon
echo "<h3>2. Testing Coupon Insert...</h3>";
$testCode = 'TEST' . rand(1000, 9999);
$stmt = $conn->prepare("INSERT INTO coupons (code, type, value, min_purchase, status) VALUES (?, 'percentage', 20, 100, 'active')");
$stmt->bind_param("s", $testCode);

if ($stmt->execute()) {
    echo "✅ Test coupon '$testCode' inserted successfully<br>";
    $insertedId = $stmt->insert_id;
    
    // Verify the insert
    $verify = $conn->query("SELECT * FROM coupons WHERE id = $insertedId");
    if ($verify && $verify->num_rows > 0) {
        $coupon = $verify->fetch_assoc();
        echo "<h4>Inserted Coupon Details:</h4>";
        echo "<pre>" . print_r($coupon, true) . "</pre>";
        
        // Clean up test coupon
        $conn->query("DELETE FROM coupons WHERE id = $insertedId");
        echo "✅ Test coupon deleted (cleanup)<br>";
    }
} else {
    echo "❌ Error inserting test coupon: " . $stmt->error . "<br>";
}

// List all existing coupons
echo "<h3>3. Existing Coupons:</h3>";
$coupons = $conn->query("SELECT * FROM coupons ORDER BY created_at DESC");
if ($coupons && $coupons->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Code</th><th>Type</th><th>Value</th><th>Min Purchase</th><th>Usage</th><th>Expiry</th><th>Status</th><th>Created</th></tr>";
    while ($coupon = $coupons->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$coupon['id']}</td>";
        echo "<td><strong>{$coupon['code']}</strong></td>";
        echo "<td>{$coupon['type']}</td>";
        echo "<td>{$coupon['value']}</td>";
        echo "<td>{$coupon['min_purchase']}</td>";
        echo "<td>{$coupon['used_count']} / " . ($coupon['usage_limit'] ?? '∞') . "</td>";
        echo "<td>" . ($coupon['expiry_date'] ?? 'No expiry') . "</td>";
        echo "<td>{$coupon['status']}</td>";
        echo "<td>{$coupon['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "No coupons found in database.<br>";
}

// Check admin access
echo "<h3>4. Admin Access Check:</h3>";
if (function_exists('isLoggedIn') && function_exists('isAdmin')) {
    echo "✅ Admin functions exist<br>";
    if (isLoggedIn()) {
        echo "✅ User is logged in<br>";
        if (isAdmin()) {
            echo "✅ User is admin<br>";
        } else {
            echo "⚠️ User is NOT admin<br>";
        }
    } else {
        echo "⚠️ User is NOT logged in<br>";
    }
} else {
    echo "❌ Admin functions not found<br>";
}

// Check if MDB is accessible
echo "<h3>5. Frontend Check:</h3>";
echo "<p>Testing if modal can be triggered...</p>";
echo '<button class="btn btn-primary" data-mdb-toggle="modal" data-mdb-target="#testModal">Test Modal</button>';

echo '<div class="modal fade" id="testModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Test Modal</h5>
                <button type="button" class="btn-close" data-mdb-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                If you can see this, the modal system is working!
            </div>
        </div>
    </div>
</div>';

echo '<link href="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/7.1.0/mdb.min.css" rel="stylesheet">';
echo '<script src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/7.1.0/mdb.umd.min.js"></script>';

echo "<hr>";
echo "<h3>Summary:</h3>";
echo "<p>✅ = Working | ❌ = Error | ⚠️ = Warning</p>";
echo "<p><a href='admin/coupons.php'>Go to Coupons Management</a></p>";
?>
