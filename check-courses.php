<?php
require_once 'config/config.php';

echo "Checking for courses...\n\n";

// Check if any courses exist
$result = $conn->query("SELECT id, title, product_type FROM products WHERE product_type = 'course'");

if ($result->num_rows > 0) {
    echo "✓ Courses found:\n";
    while ($row = $result->fetch_assoc()) {
        echo "  - {$row['title']} (ID: {$row['id']})\n";
        echo "    Video management: http://localhost/DigitalKhazana/admin/course-videos.php?product={$row['id']}\n\n";
    }
} else {
    echo "⚠ No courses found!\n\n";
    echo "To create a course:\n";
    echo "1. Go to: http://localhost/DigitalKhazana/admin/products.php\n";
    echo "2. Click 'Add Product'\n";
    echo "3. Set 'Product Type' to 'Course'\n";
    echo "4. Fill in details and save\n";
    echo "5. Then click the video icon (🎥) next to the course\n\n";
}

// Check if product_type has 'course' option
$result = $conn->query("SHOW COLUMNS FROM products LIKE 'product_type'");
if ($result->num_rows > 0) {
    $column = $result->fetch_assoc();
    echo "Product types available: {$column['Type']}\n";
    
    if (strpos($column['Type'], 'course') === false) {
        echo "\n❌ ERROR: 'course' type is NOT in the product_type ENUM!\n";
        echo "Need to add it to the database.\n";
    } else {
        echo "✓ 'course' type is available\n";
    }
}
?>
