<?php
require_once 'config/config.php';

echo "Checking database...\n\n";

// Check all products
$result = $conn->query("SELECT id, title, product_type FROM products ORDER BY id DESC LIMIT 5");
echo "Recent products:\n";
while ($row = $result->fetch_assoc()) {
    echo "  ID: {$row['id']} | Type: {$row['product_type']} | Title: {$row['title']}\n";
}

echo "\n";

// Check course_sections table
$result = $conn->query("SELECT * FROM course_sections");
echo "Course sections: " . $result->num_rows . " found\n";
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "  Section ID: {$row['id']} | Product: {$row['product_id']} | Title: {$row['title']}\n";
    }
}

echo "\n";

// Check course_videos table
$result = $conn->query("SELECT * FROM course_videos");
echo "Course videos: " . $result->num_rows . " found\n";

echo "\n";

// Check if tables exist
$tables = ['course_sections', 'course_videos', 'user_video_progress'];
echo "Table status:\n";
foreach ($tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    $status = $result->num_rows > 0 ? "✓ EXISTS" : "❌ MISSING";
    echo "  $table: $status\n";
}
?>
