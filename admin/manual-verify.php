<?php
require_once '../config/config.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['user_id'])) {
    $userId = (int)$_POST['user_id'];
    
    // Manually verify user
    $stmt = $conn->prepare("UPDATE users SET email_verified = TRUE, status = 'active', otp_code = NULL, otp_expiry = NULL WHERE id = ?");
    $stmt->bind_param("i", $userId);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = 'User verified successfully!';
    } else {
        $_SESSION['error'] = 'Failed to verify user.';
    }
}

header('Location: view-otps.php');
exit;
?>
