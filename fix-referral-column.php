<?php
require_once 'config/config.php';

echo "<h2>Fixing Referral System Database</h2>";

// Check if referred_by column exists
$result = $conn->query("SHOW COLUMNS FROM users LIKE 'referred_by'");

if ($result->num_rows == 0) {
    echo "<p>❌ Column 'referred_by' does not exist. Adding it now...</p>";
    
    // Add referred_by column to users table
    $sql = "ALTER TABLE users ADD COLUMN referred_by INT NULL AFTER role";
    
    if ($conn->query($sql)) {
        echo "<p>✅ Successfully added 'referred_by' column to users table</p>";
    } else {
        echo "<p>❌ Error adding column: " . $conn->error . "</p>";
    }
    
    // Add foreign key constraint
    $sql = "ALTER TABLE users ADD CONSTRAINT fk_referred_by 
            FOREIGN KEY (referred_by) REFERENCES affiliates(id) 
            ON DELETE SET NULL";
    
    if ($conn->query($sql)) {
        echo "<p>✅ Successfully added foreign key constraint</p>";
    } else {
        echo "<p>⚠️ Note: Foreign key constraint not added (may already exist or affiliates table structure issue)</p>";
        echo "<p>Error: " . $conn->error . "</p>";
    }
} else {
    echo "<p>✅ Column 'referred_by' already exists</p>";
}

// Check current structure
echo "<h3>Current Users Table Structure:</h3>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";

$result = $conn->query("DESCRIBE users");
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $row['Field'] . "</td>";
    echo "<td>" . $row['Type'] . "</td>";
    echo "<td>" . $row['Null'] . "</td>";
    echo "<td>" . $row['Key'] . "</td>";
    echo "<td>" . ($row['Default'] ?? 'NULL') . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h3>✅ Database Fix Complete!</h3>";
echo "<p><a href='affiliate-dashboard.php'>Go to Affiliate Dashboard</a></p>";
?>
