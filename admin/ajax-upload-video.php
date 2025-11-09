<?php
require_once '../config/config.php';
requireAdmin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['video'])) {
    echo json_encode(['success' => false, 'error' => 'No video file uploaded']);
    exit;
}

$file = $_FILES['video'];
$sectionId = isset($_POST['section_id']) ? (int)$_POST['section_id'] : 0;
$productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;

// Validate file
if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'error' => 'Upload error: ' . $file['error']]);
    exit;
}

// Check file size (500MB)
if ($file['size'] > 524288000) {
    echo json_encode(['success' => false, 'error' => 'File size exceeds 500MB limit']);
    exit;
}

// Validate video type
$allowedTypes = ['video/mp4', 'video/webm', 'video/ogg', 'video/quicktime', 'video/x-msvideo'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mimeType, $allowedTypes)) {
    echo json_encode(['success' => false, 'error' => 'Invalid video format']);
    exit;
}

// Create upload directory
$uploadDir = UPLOAD_PATH . 'courses/videos/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Generate unique filename
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = 'video_' . $productId . '_' . $sectionId . '_' . time() . '_' . uniqid() . '.' . $extension;
$targetPath = $uploadDir . $filename;

// Move uploaded file
if (move_uploaded_file($file['tmp_name'], $targetPath)) {
    $relativePath = 'courses/videos/' . $filename;
    echo json_encode([
        'success' => true,
        'path' => $relativePath,
        'size' => $file['size'],
        'filename' => $filename
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to save video file']);
}
?>
