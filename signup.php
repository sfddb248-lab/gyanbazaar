<?php
require_once 'config/config.php';
$pageTitle = 'Sign Up - ' . getSetting('site_name');

$error = '';
$success = '';
$referralCode = '';
$referrerName = '';

// Check for referral code in URL or cookie
if (isset($_GET['ref'])) {
    $referralCode = clean($_GET['ref']);
    // Get referrer info
    $stmt = $conn->prepare("SELECT a.referral_code, u.name FROM affiliates a JOIN users u ON a.user_id = u.id WHERE a.referral_code = ? AND a.status = 'active'");
    $stmt->bind_param("s", $referralCode);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $referrer = $result->fetch_assoc();
        $referrerName = $referrer['name'];
    }
} elseif (isset($_COOKIE['affiliate_ref'])) {
    $referralCode = $_COOKIE['affiliate_ref'];
    // Get referrer info
    $stmt = $conn->prepare("SELECT a.referral_code, u.name FROM affiliates a JOIN users u ON a.user_id = u.id WHERE a.referral_code = ? AND a.status = 'active'");
    $stmt->bind_param("s", $referralCode);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $referrer = $result->fetch_assoc();
        $referrerName = $referrer['name'];
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = clean($_POST['name']);
    $email = clean($_POST['email']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    
    if (empty($name) || empty($email) || empty($password)) {
        $error = 'All fields are required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match';
    } else {
        // Check if email exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $error = 'Email already registered';
        } else {
            // Create user account (active immediately)
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Check if there's an affiliate referral
            $affiliateId = null;
            if (isset($_COOKIE['affiliate_ref'])) {
                $referralCode = $_COOKIE['affiliate_ref'];
                $stmt = $conn->prepare("SELECT id FROM affiliates WHERE referral_code = ? AND status = 'active'");
                $stmt->bind_param("s", $referralCode);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($row = $result->fetch_assoc()) {
                    $affiliateId = $row['id'];
                }
            }
            
            // Insert user with referred_by field
            if ($affiliateId) {
                $stmt = $conn->prepare("INSERT INTO users (name, email, password, status, email_verified, referred_by) VALUES (?, ?, ?, 'active', TRUE, ?)");
                $stmt->bind_param("sssi", $name, $email, $hashedPassword, $affiliateId);
            } else {
                $stmt = $conn->prepare("INSERT INTO users (name, email, password, status, email_verified) VALUES (?, ?, ?, 'active', TRUE)");
                $stmt->bind_param("sss", $name, $email, $hashedPassword);
            }
            
            if ($stmt->execute()) {
                // Auto-login the user after successful registration
                $userId = $stmt->insert_id;
                $_SESSION['user_id'] = $userId;
                $_SESSION['user_name'] = $name;
                $_SESSION['user_email'] = $email;
                $_SESSION['role'] = 'user';
                
                // Track affiliate referral on signup
                if ($affiliateId) {
                    $referralCode = $_COOKIE['affiliate_ref'];
                    
                    // Create referral record
                    $ipAddress = $_SERVER['REMOTE_ADDR'];
                    $userAgent = $_SERVER['HTTP_USER_AGENT'];
                    $refStmt = $conn->prepare("INSERT INTO affiliate_referrals (affiliate_id, referred_user_id, referral_code, ip_address, user_agent, converted, purchase_made) VALUES (?, ?, ?, ?, ?, 0, 0)");
                    $refStmt->bind_param("iisss", $affiliateId, $userId, $referralCode, $ipAddress, $userAgent);
                    $refStmt->execute();
                    
                    // Also call the function if it exists
                    if (function_exists('trackAffiliateReferral')) {
                        trackAffiliateReferral($referralCode, $userId);
                    }
                }
                
                // Redirect to home page
                header('Location: ' . SITE_URL . '/index.php');
                exit;
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    
    <!-- MDBootstrap CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/7.1.0/mdb.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <!-- Animate.css -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            position: relative;
            overflow-x: hidden;
            overflow-y: auto;
            padding: 20px 0;
        }
        
        /* Animated Background */
        .bg-animation {
            position: absolute;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 0;
        }
        
        .bg-animation span {
            position: absolute;
            display: block;
            width: 20px;
            height: 20px;
            background: rgba(255, 255, 255, 0.1);
            animation: float 25s infinite;
            bottom: -150px;
        }
        
        .bg-animation span:nth-child(1) {
            left: 25%;
            width: 80px;
            height: 80px;
            animation-delay: 0s;
        }
        
        .bg-animation span:nth-child(2) {
            left: 10%;
            width: 20px;
            height: 20px;
            animation-delay: 2s;
            animation-duration: 12s;
        }
        
        .bg-animation span:nth-child(3) {
            left: 70%;
            width: 20px;
            height: 20px;
            animation-delay: 4s;
        }
        
        .bg-animation span:nth-child(4) {
            left: 40%;
            width: 60px;
            height: 60px;
            animation-delay: 0s;
            animation-duration: 18s;
        }
        
        .bg-animation span:nth-child(5) {
            left: 65%;
            width: 20px;
            height: 20px;
            animation-delay: 0s;
        }
        
        .bg-animation span:nth-child(6) {
            left: 75%;
            width: 110px;
            height: 110px;
            animation-delay: 3s;
        }
        
        .bg-animation span:nth-child(7) {
            left: 35%;
            width: 150px;
            height: 150px;
            animation-delay: 7s;
        }
        
        .bg-animation span:nth-child(8) {
            left: 50%;
            width: 25px;
            height: 25px;
            animation-delay: 15s;
            animation-duration: 45s;
        }
        
        .bg-animation span:nth-child(9) {
            left: 20%;
            width: 15px;
            height: 15px;
            animation-delay: 2s;
            animation-duration: 35s;
        }
        
        .bg-animation span:nth-child(10) {
            left: 85%;
            width: 150px;
            height: 150px;
            animation-delay: 0s;
            animation-duration: 11s;
        }
        
        @keyframes float {
            0% {
                transform: translateY(0) rotate(0deg);
                opacity: 1;
                border-radius: 0;
            }
            100% {
                transform: translateY(-1000px) rotate(720deg);
                opacity: 0;
                border-radius: 50%;
            }
        }
        
        .signup-container {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 500px;
            padding: 20px;
        }
        
        .signup-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px 30px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: slideInUp 0.6s ease-out;
            margin: 20px 0;
        }
        
        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .logo-container {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .logo-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 35px;
            color: white;
            margin-bottom: 15px;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
                box-shadow: 0 0 0 0 rgba(240, 147, 251, 0.7);
            }
            50% {
                transform: scale(1.05);
                box-shadow: 0 0 0 20px rgba(240, 147, 251, 0);
            }
        }
        
        .signup-title {
            font-size: 24px;
            font-weight: 700;
            color: #333;
            margin-bottom: 8px;
        }
        
        .signup-subtitle {
            color: #666;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 15px;
            position: relative;
        }
        
        .form-control {
            width: 100%;
            padding: 15px 20px 15px 50px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s;
            background: white;
        }
        
        .form-control:focus {
            border-color: #f093fb;
            box-shadow: 0 0 0 3px rgba(240, 147, 251, 0.1);
            outline: none;
        }
        
        .input-icon {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            font-size: 18px;
            transition: all 0.3s;
        }
        
        .form-control:focus + .input-icon {
            color: #f093fb;
        }
        
        .btn-signup {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            border: none;
            border-radius: 10px;
            color: white;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
        }
        
        .btn-signup:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(240, 147, 251, 0.4);
        }
        
        .btn-signup:active {
            transform: translateY(0);
        }
        
        .divider {
            text-align: center;
            margin: 25px 0;
            position: relative;
        }
        
        .divider::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            width: 100%;
            height: 1px;
            background: #e0e0e0;
        }
        
        .divider span {
            background: white;
            padding: 0 15px;
            position: relative;
            color: #999;
            font-size: 14px;
        }
        
        .login-link {
            text-align: center;
            margin-top: 25px;
            color: #666;
        }
        
        .login-link a {
            color: #f093fb;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .login-link a:hover {
            color: #f5576c;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            animation: shake 0.5s;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
            20%, 40%, 60%, 80% { transform: translateX(5px); }
        }
        
        .alert-danger {
            background: #fee;
            color: #c33;
            border-left: 4px solid #c33;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }
        
        .referral-banner {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
            animation: slideInDown 0.6s ease-out;
        }
        
        @keyframes slideInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .password-strength {
            height: 4px;
            background: #e0e0e0;
            border-radius: 2px;
            margin-top: 8px;
            overflow: hidden;
        }
        
        .password-strength-bar {
            height: 100%;
            width: 0;
            transition: all 0.3s;
            border-radius: 2px;
        }
        
        @media (max-width: 576px) {
            body {
                padding: 10px 0;
            }
            
            .signup-container {
                padding: 10px;
            }
            
            .signup-card {
                padding: 30px 20px;
                margin: 10px 0;
            }
            
            .signup-title {
                font-size: 22px;
            }
            
            .logo-icon {
                width: 60px;
                height: 60px;
                font-size: 30px;
                margin-bottom: 10px;
            }
            
            .form-group {
                margin-bottom: 12px;
            }
            
            .form-control {
                padding: 12px 15px 12px 45px;
                font-size: 15px;
            }
            
            .input-icon {
                left: 15px;
                font-size: 16px;
            }
        }
    </style>
</head>
<body>
    <!-- Animated Background -->
    <div class="bg-animation">
        <span></span>
        <span></span>
        <span></span>
        <span></span>
        <span></span>
        <span></span>
        <span></span>
        <span></span>
        <span></span>
        <span></span>
    </div>
    
    <div class="signup-container">
        <div class="signup-card">
            <div class="logo-container">
                <div class="logo-icon">
                    <i class="fas fa-user-plus"></i>
                </div>
                <h1 class="signup-title">Create Account</h1>
                <p class="signup-subtitle">Join <?php echo getSetting('site_name'); ?> today!</p>
            </div>
            
            <?php if ($referrerName): ?>
                <div class="referral-banner">
                    <i class="fas fa-gift"></i> 
                    <strong>Special Invitation!</strong><br>
                    You've been referred by <strong><?php echo htmlspecialchars($referrerName); ?></strong>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" id="signupForm">
                <div class="form-group">
                    <input type="text" name="name" class="form-control" placeholder="Full Name" required>
                    <i class="fas fa-user input-icon"></i>
                </div>
                
                <div class="form-group">
                    <input type="email" name="email" class="form-control" placeholder="Email Address" required>
                    <i class="fas fa-envelope input-icon"></i>
                </div>
                
                <div class="form-group">
                    <input type="password" name="password" id="password" class="form-control" placeholder="Password" required>
                    <i class="fas fa-lock input-icon"></i>
                    <div class="password-strength">
                        <div class="password-strength-bar" id="strengthBar"></div>
                    </div>
                </div>
                
                <div class="form-group">
                    <input type="password" name="confirm_password" class="form-control" placeholder="Confirm Password" required>
                    <i class="fas fa-lock input-icon"></i>
                </div>
                
                <button type="submit" class="btn-signup">
                    <i class="fas fa-user-plus"></i> Create Account
                </button>
            </form>
            
            <div class="divider">
                <span>OR</span>
            </div>
            
            <div class="login-link">
                Already have an account? <a href="<?php echo SITE_URL; ?>/login.php">Login</a>
            </div>
        </div>
    </div>
    
    <script>
        // Password strength indicator
        const password = document.getElementById('password');
        const strengthBar = document.getElementById('strengthBar');
        
        password.addEventListener('input', function() {
            const val = this.value;
            let strength = 0;
            
            if (val.length >= 6) strength += 25;
            if (val.length >= 10) strength += 25;
            if (/[a-z]/.test(val) && /[A-Z]/.test(val)) strength += 25;
            if (/\d/.test(val)) strength += 25;
            
            strengthBar.style.width = strength + '%';
            
            if (strength <= 25) {
                strengthBar.style.background = '#f44336';
            } else if (strength <= 50) {
                strengthBar.style.background = '#ff9800';
            } else if (strength <= 75) {
                strengthBar.style.background = '#2196f3';
            } else {
                strengthBar.style.background = '#4caf50';
            }
        });
    </script>
    
    <!-- MDBootstrap JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/7.1.0/mdb.umd.min.js"></script>
</body>
</html>
