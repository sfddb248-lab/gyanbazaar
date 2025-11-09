<?php
require_once 'config/config.php';
requireLogin();

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$videoId = isset($data['video_id']) ? (int)$data['video_id'] : 0;

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

// Mark as completed
$stmt = $conn->prepare("
    INSERT INTO user_video_progress (user_id, video_id, product_id, completed)
    VALUES (?, ?, ?, 1)
    ON DUPLICATE KEY UPDATE completed = 1
");
$stmt->bind_param("iii", $_SESSION['user_id'], $videoId, $productId);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to mark as completed']);
}
?>
