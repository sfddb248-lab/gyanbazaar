<?php
/**
 * WhatsApp OTP Functions
 * Send OTP via WhatsApp using Twilio API
 */

// Twilio Configuration
define('TWILIO_ACCOUNT_SID', 'YOUR_ACCOUNT_SID'); // Get from Twilio Console
define('TWILIO_AUTH_TOKEN', 'YOUR_AUTH_TOKEN');   // Get from Twilio Console
define('TWILIO_WHATSAPP_NUMBER', 'whatsapp:+14155238886'); // Twilio WhatsApp number

/**
 * Send WhatsApp message using Twilio
 */
function sendWhatsAppMessage($to, $message) {
    $accountSid = TWILIO_ACCOUNT_SID;
    $authToken = TWILIO_AUTH_TOKEN;
    $from = TWILIO_WHATSAPP_NUMBER;
    
    // Format phone number for WhatsApp
    if (!str_starts_with($to, 'whatsapp:')) {
        $to = 'whatsapp:' . $to;
    }
    
    // Twilio API endpoint
    $url = "https://api.twilio.com/2010-04-01/Accounts/$accountSid/Messages.json";
    
    // Prepare data
    $data = [
        'From' => $from,
        'To' => $to,
        'Body' => $message
    ];
    
    // Initialize cURL
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_USERPWD, "$accountSid:$authToken");
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    
    // Execute request
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // Log the response
    error_log("WhatsApp API Response: $response");
    
    return $httpCode == 201; // 201 = Created (success)
}

/**
 * Send OTP via WhatsApp
 */
function sendWhatsAppOTP($phone, $otp, $userName = '') {
    $siteName = getSetting('site_name', 'GyanBazaar');
    
    // Format message
    $message = "🔐 *$siteName - OTP Verification*\n\n";
    
    if ($userName) {
        $message .= "Hello *$userName*,\n\n";
    }
    
    $message .= "Your OTP code is:\n\n";
    $message .= "*$otp*\n\n";
    $message .= "⏰ Valid for 10 minutes\n\n";
    $message .= "⚠️ Do not share this code with anyone.\n\n";
    $message .= "If you didn't request this, please ignore this message.";
    
    return sendWhatsAppMessage($phone, $message);
}

/**
 * Generate and send WhatsApp OTP
 */
function generateAndSendWhatsAppOTP($userId, $phone, $userName = '') {
    global $conn;
    
    // Generate 6-digit OTP
    $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    
    // Set expiry time (10 minutes)
    $expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));
    
    // Store OTP in database
    $stmt = $conn->prepare("INSERT INTO otp_verifications (user_id, phone, otp, expires_at, verification_type) VALUES (?, ?, ?, ?, 'whatsapp')");
    $stmt->bind_param("isss", $userId, $phone, $otp, $expiresAt);
    
    if ($stmt->execute()) {
        // Send OTP via WhatsApp
        if (sendWhatsAppOTP($phone, $otp, $userName)) {
            return [
                'success' => true,
                'message' => 'OTP sent to your WhatsApp',
                'otp' => $otp // Remove in production
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to send WhatsApp message. Please check your phone number.'
            ];
        }
    }
    
    return [
        'success' => false,
        'message' => 'Failed to generate OTP'
    ];
}

/**
 * Verify WhatsApp OTP
 */
function verifyWhatsAppOTP($phone, $otp) {
    global $conn;
    
    // Check if OTP exists and is valid
    $stmt = $conn->prepare("
        SELECT id, user_id 
        FROM otp_verifications 
        WHERE phone = ? 
        AND otp = ? 
        AND expires_at > NOW() 
        AND is_used = 0
        AND verification_type = 'whatsapp'
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->bind_param("ss", $phone, $otp);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        // Mark OTP as used
        $updateStmt = $conn->prepare("UPDATE otp_verifications SET is_used = 1 WHERE id = ?");
        $updateStmt->bind_param("i", $row['id']);
        $updateStmt->execute();
        
        // Mark user as verified
        $userStmt = $conn->prepare("UPDATE users SET is_verified = 1 WHERE id = ?");
        $userStmt->bind_param("i", $row['user_id']);
        $userStmt->execute();
        
        return [
            'success' => true,
            'message' => 'Phone number verified successfully',
            'user_id' => $row['user_id']
        ];
    }
    
    return [
        'success' => false,
        'message' => 'Invalid or expired OTP'
    ];
}

/**
 * Format phone number for WhatsApp
 * Converts various formats to international format
 */
function formatPhoneForWhatsApp($phone) {
    // Remove all non-numeric characters
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    // If doesn't start with country code, assume India (+91)
    if (!str_starts_with($phone, '91') && strlen($phone) == 10) {
        $phone = '91' . $phone;
    }
    
    // Add + prefix
    return '+' . $phone;
}

/**
 * Resend WhatsApp OTP
 */
function resendWhatsAppOTP($phone) {
    global $conn;
    
    // Get user by phone
    $stmt = $conn->prepare("SELECT id, name FROM users WHERE phone = ?");
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($user = $result->fetch_assoc()) {
        // Mark old OTPs as used
        $updateStmt = $conn->prepare("UPDATE otp_verifications SET is_used = 1 WHERE phone = ? AND is_used = 0");
        $updateStmt->bind_param("s", $phone);
        $updateStmt->execute();
        
        // Generate and send new OTP
        return generateAndSendWhatsAppOTP($user['id'], $phone, $user['name']);
    }
    
    return [
        'success' => false,
        'message' => 'Phone number not found'
    ];
}
?>
