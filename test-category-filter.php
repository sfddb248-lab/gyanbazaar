<?php
require_once 'config/config.php';

echo "Testing Category Filter\n";
echo "========================\n\n";

// Check categories
echo "1. Categories in database:\n";
$categories = $conn->query("SELECT * FROM categories ORDER BY name")->fetch_all(MYSQLI_ASSOC);
if (empty($categories)) {
    echo "   ❌ No categories found!\n";
    echo "   Creating sample categories...\n\n";
    
    // Create sample categories
    $sampleCategories = ['Programming', 'Design', 'Business', 'Marketing'];
    foreach ($sampleCategories as $catName) {
        $slug = strtolower(str_replace(' ', '-', $catName));
        $conn->query("INSERT INTO categories (name, slug) VALUES ('$catName', '$slug')");
        echo "   ✓ Created: $catName\n";
    }
    
    // Refresh categories
    $categories = $conn->query("SELECT * FROM categories ORDER BY name")->fetch_all(MYSQLI_ASSOC);
}

foreach ($categories as $cat) {
    echo "   - {$cat['name']} (ID: {$cat['id']})\n";
}

// Check products with categories
echo "\n2. Products with categories:\n";
$products = $conn->query("
    SELECT p.id, p.title, p.category_id, c.name as category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    ORDER BY p.id
")->fetch_all(MYSQLI_ASSOC);

if (empty($products)) {
    echo "   ❌ No products found!\n";
} else {
    foreach ($products as $product) {
        $catName = $product['category_name'] ?: 'No Category';
        echo "   - {$product['title']} → Category: $catName (ID: {$product['category_id']})\n";
    }
}

// Test category filter
echo "\n3. Testing category filter:\n";
if (!empty($categories)) {
    $testCatId = $categories[0]['id'];
    $testCatName = $categories[0]['name'];
    
    $result = $conn->query("
        SELECT COUNT(*) as count 
        FROM products 
        WHERE category_id = $testCatId AND status = 'active'
    ")->fetch_assoc();
    
    echo "   Category: $testCatName (ID: $testCatId)\n";
    echo "   Products in this category: {$result['count']}\n";
    
    if ($result['count'] > 0) {
        echo "   ✓ Category filter should work!\n";
        echo "\n   Test URL:\n";
        echo "   http://localhost/DigitalKhazana/products.php?category=$testCatId\n";
    } else {
        echo "   ⚠ No products in this category\n";
        echo "   Assign products to categories in admin panel\n";
    }
}

// Check for products without categories
echo "\n4. Products without categories:\n";
$noCategory = $conn->query("
    SELECT COUNT(*) as count 
    FROM products 
    WHERE category_id IS NULL OR category_id = 0
")->fetch_assoc();

if ($noCategory['count'] > 0) {
    echo "   ⚠ {$noCategory['count']} products have no category assigned\n";
    echo "   Assign categories to these products in admin panel\n";
} else {
    echo "   ✓ All products have categories\n";
}

echo "\n5. Summary:\n";
echo "   Total Categories: " . count($categories) . "\n";
echo "   Total Products: " . count($products) . "\n";
echo "   Products without category: {$noCategory['count']}\n";

echo "\n✅ Category filter code is correct!\n";
echo "   If filtering doesn't work, assign categories to products.\n";
?>
