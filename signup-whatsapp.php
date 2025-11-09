<?php
require_once 'config/config.php';
require_once 'includes/functions.php';
require_once 'includes/whatsapp-functions.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = clean($_POST['name']);
    $email = clean($_POST['email']);
    $password = $_POST['password'];
    $phone = clean($_POST['phone']);
    
    // Validate inputs
    if (empty($name) || empty($email) || empty($password) || empty($phone)) {
        $error = 'All fields are required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    } else {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $error = 'Email already registered';
        } else {
            // Format phone number
            $formattedPhone = formatPhoneForWhatsApp($phone);
            
            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert user
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, phone, is_verified) VALUES (?, ?, ?, ?, 0)");
            $stmt->bind_param("ssss", $name, $email, $hashedPassword, $formattedPhone);
            
            if ($stmt->execute()) {
                $userId = $conn->insert_id;
                
                // Generate and send WhatsApp OTP
                $result = generateAndSendWhatsAppOTP($userId, $formattedPhone, $name);
                
                if ($result['success']) {
                    $_SESSION['verify_user_id'] = $userId;
                    $_SESSION['verify_phone'] = $formattedPhone;
                    header('Location: verify-whatsapp-otp.php');
                    exit;
                } else {
                    $error = $result['message'];
                }
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}

include 'includes/header.php';
?>

<style>
    .signup-container {
        max-width: 500px;
        margin: 50px auto;
        padding: 40px;
        background: white;
        border-radius: 15px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    }
    .signup-header {
        text-align: center;
        margin-bottom: 30px;
    }
    .signup-header h2 {
        color: #1266f1;
        margin-bottom: 10px;
    }
    .signup-header p {
        color: #666;
        font-size: 14px;
    }
    .whatsapp-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: #25D366;
        color: white;
        padding: 8px 16px;
        border-radius: 20px;
        font-size: 14px;
        margin: 10px 0;
    }
    .form-group {
        margin-bottom: 20px;
    }
    .form-group label {
        display: block;
        margin-bottom: 8px;
        color: #333;
        font-weight: 500;
    }
    .form-group input {
        width: 100%;
        padding: 12px 15px;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        font-size: 16px;
        transition: border-color 0.3s;
    }
    .form-group input:focus {
        outline: none;
        border-color: #1266f1;
    }
    .phone-input-group {
        display: flex;
        gap: 10px;
    }
    .country-code {
        width: 80px;
        background: #f5f5f5;
        text-align: center;
        font-weight: bold;
    }
    .btn-signup {
        width: 100%;
        padding: 14px;
        background: #1266f1;
        color: white;
        border: none;
        border-radius: 8px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.3s;
    }
    .btn-signup:hover {
        background: #0d47a1;
    }
    .alert {
        padding: 12px 15px;
        border-radius: 8px;
        margin-bottom: 20px;
    }
    .alert-error {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
    .alert-success {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    .login-link {
        text-align: center;
        margin-top: 20px;
        color: #666;
    }
    .login-link a {
        color: #1266f1;
        text-decoration: none;
        font-weight: 600;
    }
    .feature-list {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 8px;
        margin: 20px 0;
    }
    .feature-list h4 {
        color: #333;
        margin-bottom: 15px;
    }
    .feature-list ul {
        list-style: none;
        padding: 0;
    }
    .feature-list li {
        padding: 8px 0;
        color: #666;
    }
    .feature-list li:before {
        content: "✓ ";
        color: #25D366;
        font-weight: bold;
        margin-right: 8px;
    }
</style>

<div class="signup-container">
    <div class="signup-header">
        <h2>📱 Create Account</h2>
        <div class="whatsapp-badge">
            <span>📱</span>
            <span>WhatsApp Verification</span>
        </div>
        <p>Get OTP instantly on WhatsApp</p>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-error">
            <strong>❌ Error:</strong> <?php echo $error; ?>
        </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success">
            <strong>✅ Success:</strong> <?php echo $success; ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <div class="form-group">
            <label for="name">Full Name</label>
            <input type="text" id="name" name="name" placeholder="Enter your full name" required>
        </div>
        
        <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" placeholder="your-email@example.com" required>
        </div>
        
        <div class="form-group">
            <label for="phone">WhatsApp Number</label>
            <div class="phone-input-group">
                <input type="text" class="country-code" value="+91" readonly>
                <input type="tel" id="phone" name="phone" placeholder="9876543210" pattern="[0-9]{10}" required>
            </div>
            <small style="color: #666; display: block; margin-top: 5px;">
                📱 OTP will be sent to this WhatsApp number
            </small>
        </div>
        
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" placeholder="Minimum 6 characters" required>
        </div>
        
        <button type="submit" class="btn-signup">
            📱 Sign Up with WhatsApp OTP
        </button>
    </form>
    
    <div class="feature-list">
        <h4>Why WhatsApp Verification?</h4>
        <ul>
            <li>Instant OTP delivery (2-3 seconds)</li>
            <li>No spam folder issues</li>
            <li>98% delivery success rate</li>
            <li>More secure than email</li>
            <li>Easy to use</li>
        </ul>
    </div>
    
    <div class="login-link">
        Already have an account? <a href="login.php">Login here</a>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
