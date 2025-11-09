<?php
require_once 'config/config.php';
require_once 'includes/functions.php';
require_once 'includes/whatsapp-functions.php';

// Check if user is in verification process
if (!isset($_SESSION['verify_user_id']) || !isset($_SESSION['verify_phone'])) {
    header('Location: signup-whatsapp.php');
    exit;
}

$userId = $_SESSION['verify_user_id'];
$phone = $_SESSION['verify_phone'];
$error = '';
$success = '';

// Handle OTP verification
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['verify_otp'])) {
        $otp = clean($_POST['otp']);
        
        if (empty($otp)) {
            $error = 'Please enter OTP';
        } else {
            $result = verifyWhatsAppOTP($phone, $otp);
            
            if ($result['success']) {
                // Clear verification session
                unset($_SESSION['verify_user_id']);
                unset($_SESSION['verify_phone']);
                
                // Set user session
                $_SESSION['user_id'] = $result['user_id'];
                
                $success = 'Account verified successfully! Redirecting...';
                header('refresh:2;url=index.php');
            } else {
                $error = $result['message'];
            }
        }
    } elseif (isset($_POST['resend_otp'])) {
        $result = resendWhatsAppOTP($phone);
        
        if ($result['success']) {
            $success = 'New OTP sent to your WhatsApp!';
        } else {
            $error = $result['message'];
        }
    }
}

// Get user info
$stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

include 'includes/header.php';
?>

<style>
    .verify-container {
        max-width: 500px;
        margin: 50px auto;
        padding: 40px;
        background: white;
        border-radius: 15px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    }
    .verify-header {
        text-align: center;
        margin-bottom: 30px;
    }
    .verify-header h2 {
        color: #1266f1;
        margin-bottom: 10px;
    }
    .whatsapp-icon {
        font-size: 60px;
        margin: 20px 0;
    }
    .phone-display {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        text-align: center;
        margin: 20px 0;
        font-size: 18px;
        font-weight: 600;
        color: #333;
    }
    .otp-input-group {
        display: flex;
        gap: 10px;
        justify-content: center;
        margin: 30px 0;
    }
    .otp-input {
        width: 50px;
        height: 60px;
        text-align: center;
        font-size: 24px;
        font-weight: bold;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        transition: border-color 0.3s;
    }
    .otp-input:focus {
        outline: none;
        border-color: #1266f1;
    }
    .otp-input-single {
        width: 100%;
        padding: 15px;
        text-align: center;
        font-size: 24px;
        font-weight: bold;
        letter-spacing: 10px;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        margin: 20px 0;
    }
    .otp-input-single:focus {
        outline: none;
        border-color: #1266f1;
    }
    .btn-verify {
        width: 100%;
        padding: 14px;
        background: #25D366;
        color: white;
        border: none;
        border-radius: 8px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.3s;
        margin: 10px 0;
    }
    .btn-verify:hover {
        background: #1ea952;
    }
    .btn-resend {
        width: 100%;
        padding: 14px;
        background: #f5f5f5;
        color: #333;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
        margin: 10px 0;
    }
    .btn-resend:hover {
        background: #e0e0e0;
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
    .info-box {
        background: #e3f2fd;
        padding: 15px;
        border-radius: 8px;
        margin: 20px 0;
        border-left: 4px solid #1266f1;
    }
    .info-box ul {
        margin: 10px 0 0 20px;
        color: #666;
    }
    .timer {
        text-align: center;
        color: #666;
        margin: 15px 0;
        font-size: 14px;
    }
    .timer.expired {
        color: #dc3545;
    }
</style>

<div class="verify-container">
    <div class="verify-header">
        <div class="whatsapp-icon">📱</div>
        <h2>Verify WhatsApp OTP</h2>
        <p>Hi <?php echo htmlspecialchars($user['name']); ?>!</p>
        <p>We've sent a 6-digit code to your WhatsApp</p>
    </div>
    
    <div class="phone-display">
        <?php echo htmlspecialchars($phone); ?>
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
        <input type="text" 
               name="otp" 
               class="otp-input-single" 
               placeholder="000000" 
               maxlength="6" 
               pattern="[0-9]{6}" 
               required 
               autofocus>
        
        <button type="submit" name="verify_otp" class="btn-verify">
            ✅ Verify OTP
        </button>
    </form>
    
    <form method="POST" action="">
        <button type="submit" name="resend_otp" class="btn-resend">
            🔄 Resend OTP
        </button>
    </form>
    
    <div class="info-box">
        <strong>📌 Important:</strong>
        <ul>
            <li>Check your WhatsApp messages</li>
            <li>OTP is valid for 10 minutes</li>
            <li>Don't share OTP with anyone</li>
            <li>If not received, click "Resend OTP"</li>
        </ul>
    </div>
    
    <div class="timer" id="timer">
        ⏰ OTP expires in: <span id="countdown">10:00</span>
    </div>
</div>

<script>
// OTP Input Auto-focus
document.querySelector('.otp-input-single').addEventListener('input', function(e) {
    this.value = this.value.replace(/[^0-9]/g, '');
});

// Countdown Timer
let timeLeft = 600; // 10 minutes in seconds

function updateTimer() {
    const minutes = Math.floor(timeLeft / 60);
    const seconds = timeLeft % 60;
    document.getElementById('countdown').textContent = 
        `${minutes}:${seconds.toString().padStart(2, '0')}`;
    
    if (timeLeft <= 0) {
        document.getElementById('timer').classList.add('expired');
        document.getElementById('countdown').textContent = 'EXPIRED';
        return;
    }
    
    if (timeLeft <= 60) {
        document.getElementById('timer').style.color = '#dc3545';
    }
    
    timeLeft--;
    setTimeout(updateTimer, 1000);
}

updateTimer();
</script>

<?php include 'includes/footer.php'; ?>
