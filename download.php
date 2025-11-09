<?php
require_once 'config/config.php';
requireLogin();

$itemId = isset($_GET['item']) ? (int)$_GET['item'] : 0;
$userId = $_SESSION['user_id'];

// Verify ownership and download limits
$stmt = $conn->prepare("
    SELECT oi.*, p.file_path, p.title, o.user_id 
    FROM order_items oi 
    JOIN products p ON oi.product_id = p.id 
    JOIN orders o ON oi.order_id = o.id 
    WHERE oi.id = ? AND o.user_id = ? AND o.payment_status = 'completed'
");
$stmt->bind_param("ii", $itemId, $userId);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();

if (!$item) {
    die('Invalid download request');
}

// Check download limits
if ($item['download_count'] >= MAX_DOWNLOAD_COUNT) {
    die('Download limit exceeded');
}

// Check expiry
if ($item['download_expiry'] && strtotime($item['download_expiry']) < time()) {
    die('Download link has expired');
}

// Update download count
$conn->query("UPDATE order_items SET download_count = download_count + 1 WHERE id = $itemId");

// Serve file
// Check if file_path is already a full path or relative
if (file_exists($item['file_path'])) {
    $filePath = $item['file_path'];
} elseif (file_exists(UPLOAD_PATH . $item['file_path'])) {
    $filePath = UPLOAD_PATH . $item['file_path'];
} else {
    // Try relative to root
    $filePath = __DIR__ . '/' . $item['file_path'];
}

if (file_exists($filePath)) {
    // Get file extension to set proper content type
    $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    $contentType = 'application/octet-stream';
    
    if ($extension == 'pdf') {
        $contentType = 'application/pdf';
    } elseif ($extension == 'zip') {
        $contentType = 'application/zip';
    }
    
    // Set headers for download
    header('Content-Description: File Transfer');
    header('Content-Type: ' . $contentType);
    header('Content-Disposition: attachment; filename="' . $item['title'] . '.' . $extension . '"');
    header('Content-Length: ' . filesize($filePath));
    header('Pragma: public');
    header('Cache-Control: must-revalidate');
    header('Expires: 0');
    
    // Clear output buffer
    ob_clean();
    flush();
    
    // Read and output file
    readfile($filePath);
    exit;
} else {
    die('File not found. Path checked: ' . htmlspecialchars($item['file_path']));
}
?>
