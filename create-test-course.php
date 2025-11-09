<?php
require_once 'config/config.php';

echo "Creating test course...\n\n";

// Check if test course already exists
$result = $conn->query("SELECT id FROM products WHERE title = 'Test Course - Video Playlist'");
if ($result->num_rows > 0) {
    $course = $result->fetch_assoc();
    echo "✓ Test course already exists (ID: {$course['id']})\n";
    echo "\nManage videos at:\n";
    echo "http://localhost/DigitalKhazana/admin/course-videos.php?product={$course['id']}\n\n";
    exit;
}

// Get a category (or use NULL)
$categoryId = null;
$result = $conn->query("SELECT id FROM categories LIMIT 1");
if ($result->num_rows > 0) {
    $categoryId = $result->fetch_assoc()['id'];
}

// Create test course
$title = "Test Course - Video Playlist";
$slug = "test-course-video-playlist";
$description = "This is a test course to demonstrate the video playlist feature. You can upload multiple videos organized in sections.";
$price = 99.99;
$productType = "course";
$status = "active";

$stmt = $conn->prepare("
    INSERT INTO products (title, slug, description, price, category_id, product_type, status, created_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
");

$stmt->bind_param("sssdiis", $title, $slug, $description, $price, $categoryId, $productType, $status);

if ($stmt->execute()) {
    $courseId = $stmt->insert_id;
    echo "✅ Test course created successfully!\n\n";
    echo "Course Details:\n";
    echo "  ID: $courseId\n";
    echo "  Title: $title\n";
    echo "  Type: course\n";
    echo "  Price: $" . number_format($price, 2) . "\n";
    echo "  Status: active\n\n";
    
    echo "Next Steps:\n";
    echo "1. Go to admin products: http://localhost/DigitalKhazana/admin/products.php\n";
    echo "2. You'll see a video icon (🎥) next to the test course\n";
    echo "3. Click it to manage videos\n\n";
    
    echo "Or go directly to:\n";
    echo "http://localhost/DigitalKhazana/admin/course-videos.php?product=$courseId\n\n";
    
    // Create a sample section
    echo "Creating sample section...\n";
    $sectionTitle = "Introduction";
    $sectionDesc = "Welcome to the course";
    $orderIndex = 1;
    
    $stmt = $conn->prepare("INSERT INTO course_sections (product_id, title, description, order_index) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("issi", $courseId, $sectionTitle, $sectionDesc, $orderIndex);
    
    if ($stmt->execute()) {
        $sectionId = $stmt->insert_id;
        echo "✓ Sample section created (ID: $sectionId)\n\n";
        echo "You can now upload videos to this section!\n";
        echo "Upload URL: http://localhost/DigitalKhazana/admin/upload-course-video.php?section=$sectionId&product=$courseId\n";
    }
    
} else {
    echo "❌ Error creating course: " . $conn->error . "\n";
}
?>
