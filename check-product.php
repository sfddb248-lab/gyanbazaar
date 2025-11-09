<?php
require_once 'config/config.php';

$productId = 2;

echo "<h2>Product ID: $productId Details</h2>";

$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param("i", $productId);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if ($product) {
    echo "<table border='1' cellpadding='10'>";
    foreach ($product as $key => $value) {
        echo "<tr>";
        echo "<td><strong>$key</strong></td>";
        echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<hr>";
    echo "<h3>Checks:</h3>";
    echo "<ul>";
    echo "<li>Has file_path: " . (!empty($product['file_path']) ? '✓ YES' : '✗ NO') . "</li>";
    echo "<li>File exists: " . (file_exists($product['file_path'] ?? '') ? '✓ YES' : '✗ NO') . "</li>";
    echo "<li>Preview pages: " . ($product['preview_pages'] ?? 0) . "</li>";
    echo "<li>Total pages: " . ($product['total_pages'] ?? 0) . "</li>";
    echo "<li>Product type: " . ($product['product_type'] ?? 'not set') . "</li>";
    echo "</ul>";
    
    if (!empty($product['file_path'])) {
        echo "<hr>";
        echo "<h3>File Path:</h3>";
        echo "<p>" . htmlspecialchars($product['file_path']) . "</p>";
        echo "<p>Full path: " . realpath($product['file_path']) . "</p>";
    }
    
    echo "<hr>";
    echo "<h3>Test Links:</h3>";
    echo "<ul>";
    echo "<li><a href='product-detail.php?id=$productId'>Product Detail Page</a></li>";
    echo "<li><a href='ebook-viewer.php?id=$productId'>eBook Viewer</a></li>";
    echo "</ul>";
} else {
    echo "<p style='color:red;'>Product not found!</p>";
}
?>
