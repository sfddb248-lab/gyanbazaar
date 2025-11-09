<?php
require_once 'config/config.php';

$productId = 2;
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param("i", $productId);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

$pdfPath = $product['file_path'];
?>
<!DOCTYPE html>
<html>
<head>
    <title>PDF Test</title>
</head>
<body>
    <h2>PDF Test for Product ID: <?php echo $productId; ?></h2>
    
    <h3>Product Info:</h3>
    <ul>
        <li>Title: <?php echo htmlspecialchars($product['title']); ?></li>
        <li>File Path: <?php echo htmlspecialchars($pdfPath); ?></li>
        <li>File Exists: <?php echo file_exists($pdfPath) ? 'YES' : 'NO'; ?></li>
        <li>Preview Pages: <?php echo $product['preview_pages']; ?></li>
        <li>Total Pages: <?php echo $product['total_pages']; ?></li>
    </ul>
    
    <h3>Test 1: Direct Link</h3>
    <p><a href="<?php echo $pdfPath; ?>" target="_blank">Open PDF Directly</a></p>
    
    <h3>Test 2: Embed with iframe</h3>
    <iframe src="<?php echo $pdfPath; ?>" width="100%" height="600px" style="border:1px solid #ccc;"></iframe>
    
    <h3>Test 3: PDF.js Viewer</h3>
    <p><a href="pdf-viewer.php?id=<?php echo $productId; ?>" target="_blank">Open in PDF.js Viewer</a></p>
    
    <h3>Test 4: eBook Viewer</h3>
    <p><a href="ebook-viewer.php?id=<?php echo $productId; ?>" target="_blank">Open in eBook Viewer</a></p>
</body>
</html>
