<?php
require_once 'config/config.php';

echo "<h1>Add Profile Photo Column</h1>";
echo "<style>body { font-family: Arial, sans-serif; padding: 20px; } .success { color: green; font-weight: bold; }</style>";

try {
    // Check if column exists
    $check = $conn->query("SHOW COLUMNS FROM users LIKE 'profile_photo'");
    
    if ($check->num_rows == 0) {
        // Add profile_photo column
        $conn->query("ALTER TABLE users ADD COLUMN profile_photo VARCHAR(255) NULL AFTER email");
        echo "<p class='success'>✓ Added profile_photo column to users table</p>";
    } else {
        echo "<p class='success'>✓ profile_photo column already exists</p>";
    }
    
    // Create uploads directory if it doesn't exist
    $uploadDir = __DIR__ . '/assets/uploads/profiles';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
        echo "<p class='success'>✓ Created profiles upload directory</p>";
    } else {
        echo "<p class='success'>✓ Profiles upload directory exists</p>";
    }
    
    echo "<h2 class='success'>✅ Setup Complete!</h2>";
    echo "<p><a href='profile.php' style='padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px;'>Go to Profile</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
