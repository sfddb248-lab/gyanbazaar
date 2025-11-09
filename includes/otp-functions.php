<?php
// OTP Management Functions

/**
 * Generate and store OTP for user
 */
function generateOTP($userId, $email, $purpose = 'signup') {
    global $conn;
    
    // Generate 6-digit OTP
    $otp = sprintf("%06d", mt_rand(100000, 999999));
    $expiresAt = date('Y-m-d H:i:s', strtotime('+15 minutes'));
    
    // Store in users table
    $stmt = $conn->prepare("UPDATE users SET otp_code = ?, otp_expiry = ? WHERE id = ?");
    $stmt->bind_param("ssi", $otp, $expiresAt, $userId);
    $stmt->execute();
    
    // Log in otp_emails table
    $stmt = $conn->prepare("INSERT INTO otp_emails (user_id, email, otp_code, purpose, expires_at) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $userId, $email, $otp, $purpose, $expiresAt);
    $stmt->execute();
    $otpLogId = $conn->insert_id;
    
    return ['otp' => $otp, 'log_id' => $otpLogId, 'expires_at' => $expiresAt];
}

/**
 * Send OTP email
 */
function sendOTPEmail($email, $otp, $purpose = 'signup') {
    global $conn;
    
    $siteName = getSetting('site_name', 'GyanBazaar');
    
    $subjects = [
        'signup' => 'Verify Your Email - OTP Code',
        'login' => 'Login Verification Code',
        'reset' => 'Password Reset Code'
    ];
    
    $messages = [
        'signup' => "Welcome to $siteName!\n\nYour email verification code is:\n\n<h1 style='color:#1266f1; text-align:center; font-size:48px; letter-spacing:10px;'>$otp</h1>\n\nThis code will expire in 15 minutes.\n\nIf you didn't create an account, please ignore this email.",
        'login' => "Your login verification code is:\n\n<h1 style='color:#1266f1; text-align:center; font-size:48px; letter-spacing:10px;'>$otp</h1>\n\nThis code will expire in 15 minutes.",
        'reset' => "Your password reset code is:\n\n<h1 style='color:#1266f1; text-align:center; font-size:48px; letter-spacing:10px;'>$otp</h1>\n\nThis code will expire in 15 minutes."
    ];
    
    $subject = $subjects[$purpose] ?? $subjects['signup'];
    $message = $messages[$purpose] ?? $messages['signup'];
    
    // Try to send email
    $sent = sendEmail($email, $subject, $message);
    
    // Update log status
    $status = $sent ? 'sent' : 'failed';
    $stmt = $conn->prepare("UPDATE otp_emails SET status = ?, sent_at = NOW(), attempts = attempts + 1 WHERE email = ? AND otp_code = ? ORDER BY id DESC LIMIT 1");
    $stmt->bind_param("sss", $status, $email, $otp);
    $stmt->execute();
    
    return $sent;
}

/**
 * Verify OTP code
 */
function verifyOTP($userId, $otpCode) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT otp_code, otp_expiry FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    
    if (!$user) {
        return ['success' => false, 'message' => 'User not found'];
    }
    
    if (empty($user['otp_code'])) {
        return ['success' => false, 'message' => 'No OTP found. Please request a new one.'];
    }
    
    if (strtotime($user['otp_expiry']) < time()) {
        return ['success' => false, 'message' => 'OTP has expired. Please request a new one.'];
    }
    
    if ($user['otp_code'] !== $otpCode) {
        return ['success' => false, 'message' => 'Invalid OTP code'];
    }
    
    // OTP is valid - activate user
    $stmt = $conn->prepare("UPDATE users SET email_verified = TRUE, status = 'active', otp_code = NULL, otp_expiry = NULL WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    
    return ['success' => true, 'message' => 'Email verified successfully'];
}

/**
 * Check if OTP emails table exists
 */
function otpTableExists() {
    global $conn;
    $result = $conn->query("SHOW TABLES LIKE 'otp_emails'");
    return $result->num_rows > 0;
}

/**
 * Get OTP statistics for admin
 */
function getOTPStats() {
    global $conn;
    
    if (!otpTableExists()) {
        return null;
    }
    
    $stats = [];
    
    // Total OTPs sent today
    $result = $conn->query("SELECT COUNT(*) as count FROM otp_emails WHERE DATE(created_at) = CURDATE()");
    $stats['today'] = $result->fetch_assoc()['count'];
    
    // Pending OTPs
    $result = $conn->query("SELECT COUNT(*) as count FROM otp_emails WHERE status = 'pending'");
    $stats['pending'] = $result->fetch_assoc()['count'];
    
    // Failed OTPs
    $result = $conn->query("SELECT COUNT(*) as count FROM otp_emails WHERE status = 'failed'");
    $stats['failed'] = $result->fetch_assoc()['count'];
    
    return $stats;
}
?>
