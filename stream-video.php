<?php
// Secure Video Streaming Endpoint
// Prevents direct video URL access and download
require_once 'config/config.php';
requireLogin();

$videoId = isset($_GET['video']) ? (int)$_GET['video'] : 0;
$productId = isset($_GET['product']) ? (int)$_GET['product'] : 0;
$isPreview = isset($_GET['preview']) && $_GET['preview'] == 1;

if (!$videoId || !$productId) {
    http_response_code(403);
    die('Access denied');
}

// Check if this is a preview request
if ($isPreview) {
    // Verify the video is marked as preview
    $stmt = $conn->prepare("SELECT is_preview FROM course_videos WHERE id = ? AND product_id = ?");
    $stmt->bind_param("ii", $videoId, $productId);
    $stmt->execute();
    $videoCheck = $stmt->get_result()->fetch_assoc();
    
    if (!$videoCheck || !$videoCheck['is_preview']) {
        http_response_code(403);
        die('This video is not available for preview');
    }
    // Preview access granted, skip purchase check
} else {
    // Regular access - verify user has purchased this course
    if (!isLoggedIn()) {
        http_response_code(403);
        die('Please login to watch videos');
    }
    
    $stmt = $conn->prepare("
        SELECT o.id FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        WHERE o.user_id = ? AND oi.product_id = ? AND o.payment_status = 'completed'
        LIMIT 1
    ");
    $stmt->bind_param("ii", $_SESSION['user_id'], $productId);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();

    if (!$order) {
        http_response_code(403);
        die('You must purchase this course to watch videos');
    }
}

// Get video details
$stmt = $conn->prepare("SELECT video_path FROM course_videos WHERE id = ? AND product_id = ?");
$stmt->bind_param("ii", $videoId, $productId);
$stmt->execute();
$video = $stmt->get_result()->fetch_assoc();

if (!$video) {
    http_response_code(404);
    die('Video not found');
}

$videoPath = UPLOAD_PATH . $video['video_path'];

if (!file_exists($videoPath)) {
    http_response_code(404);
    die('Video file not found');
}

// Get file info
$fileSize = filesize($videoPath);
$fileInfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($fileInfo, $videoPath);
finfo_close($fileInfo);

// Set headers for streaming
header('Content-Type: ' . $mimeType);
header('Content-Length: ' . $fileSize);
header('Accept-Ranges: bytes');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
header('X-Content-Type-Options: nosniff');

// Prevent download
header('Content-Disposition: inline; filename="video.mp4"');

// Handle range requests for seeking
if (isset($_SERVER['HTTP_RANGE'])) {
    list($param, $range) = explode('=', $_SERVER['HTTP_RANGE']);
    
    if (strtolower(trim($param)) != 'bytes') {
        header("HTTP/1.1 400 Invalid Request");
        exit;
    }
    
    $range = explode(',', $range);
    $range = explode('-', $range[0]);
    
    $start = intval($range[0]);
    $end = (isset($range[1]) && is_numeric($range[1])) ? intval($range[1]) : $fileSize - 1;
    
    if ($start > $end || $start > $fileSize - 1 || $end >= $fileSize) {
        header('HTTP/1.1 416 Requested Range Not Satisfiable');
        header("Content-Range: bytes */$fileSize");
        exit;
    }
    
    $length = $end - $start + 1;
    
    header('HTTP/1.1 206 Partial Content');
    header("Content-Range: bytes $start-$end/$fileSize");
    header("Content-Length: $length");
    
    $file = fopen($videoPath, 'rb');
    fseek($file, $start);
    
    $buffer = 8192;
    while (!feof($file) && ($pos = ftell($file)) <= $end) {
        if ($pos + $buffer > $end) {
            $buffer = $end - $pos + 1;
        }
        echo fread($file, $buffer);
        flush();
    }
    
    fclose($file);
} else {
    // Stream entire file
    readfile($videoPath);
}

exit;
?>
