<?php
require_once 'config/config.php';
requireLogin();

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$videoId = isset($data['video_id']) ? (int)$data['video_id'] : 0;
$watchedDuration = isset($data['watched_duration']) ? (int)$data['watched_duration'] : 0;
$totalDuration = isset($data['total_duration']) ? (int)$data['total_duration'] : 0;

if (!$videoId) {
    echo json_encode(['success' => false, 'error' => 'Invalid video ID']);
    exit;
}

// Get product ID from video
$stmt = $conn->prepare("SELECT product_id FROM course_videos WHERE id = ?");
$stmt->bind_param("i", $videoId);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$productId = $result['product_id'];

// Insert or update progress
$stmt = $conn->prepare("
    INSERT INTO user_video_progress (user_id, video_id, product_id, watched_duration, total_duration, completed)
    VALUES (?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE 
        watched_duration = VALUES(watched_duration),
        total_duration = VALUES(total_duration),
        completed = VALUES(completed)
");

$completed = ($watchedDuration >= $totalDuration * 0.9) ? 1 : 0; // 90% watched = completed
$stmt->bind_param("iiiiii", $_SESSION['user_id'], $videoId, $productId, $watchedDuration, $totalDuration, $completed);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to save progress']);
}
?>
