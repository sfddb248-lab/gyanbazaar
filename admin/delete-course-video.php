<?php
require_once '../config/config.php';
requireAdmin();

$videoId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$productId = isset($_GET['product']) ? (int)$_GET['product'] : 0;

// Get video details
$stmt = $conn->prepare("SELECT * FROM course_videos WHERE id = ?");
$stmt->bind_param("i", $videoId);
$stmt->execute();
$video = $stmt->get_result()->fetch_assoc();

if ($video) {
    // Delete video file
    if ($video['video_path'] && file_exists(UPLOAD_PATH . $video['video_path'])) {
        unlink(UPLOAD_PATH . $video['video_path']);
    }
    
    // Delete notes file
    if ($video['notes_path'] && file_exists(UPLOAD_PATH . $video['notes_path'])) {
        unlink(UPLOAD_PATH . $video['notes_path']);
    }
    
    // Delete from database
    $stmt = $conn->prepare("DELETE FROM course_videos WHERE id = ?");
    $stmt->bind_param("i", $videoId);
    $stmt->execute();
}

header('Location: ' . SITE_URL . '/admin/course-videos.php?product=' . $productId);
exit;
?>
