<?php
require_once 'config/config.php';

echo "Fixing test course...\n\n";

// Update product type
$conn->query("UPDATE products SET product_type = 'course' WHERE id = 3");
echo "✓ Updated product type to 'course' for ID 3\n";

// Verify
$result = $conn->query("SELECT id, title, product_type FROM products WHERE id = 3");
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo "\nVerification:\n";
    echo "  ID: {$row['id']}\n";
    echo "  Title: {$row['title']}\n";
    echo "  Type: {$row['product_type']}\n\n";
    
    if ($row['product_type'] == 'course') {
        echo "✅ Test course is now properly configured!\n\n";
        echo "Access it at:\n";
        echo "http://localhost/DigitalKhazana/admin/products.php\n";
        echo "Look for the video icon (🎥) next to 'Test Course - Video Playlist'\n\n";
        echo "Or go directly to:\n";
        echo "http://localhost/DigitalKhazana/admin/course-videos.php?product=3\n";
    }
}
?>
