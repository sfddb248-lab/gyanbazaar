<?php
require_once 'config/config.php';

// Check if user came from signup
if (!isset($_SESSION['otp_user_id']) || !isset($_SESSION['otp_email'])) {
    header('Location: ' . SITE_URL . '/signup.php');
    exit;
}

$userId = $_SESSION['otp_user_id'];
$email = $_SESSION['otp_email'];
$error = '';
$success = '';

// Handle OTP verification
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['verify_otp'])) {
    $enteredOtp = clean($_POST['otp']);
    
    // Verify OTP using new function
    $result = verifyOTP($userId, $enteredOtp);
    
    if ($result['success']) {
        // Clear OTP session
        unset($_SESSION['otp_user_id']);
        unset($_SESSION['otp_email']);
        
        $success = $result['message'] . ' You can now login.';
        
        // Redirect to login after 2 seconds
        header("refresh:2;url=" . SITE_URL . "/login.php");
    } else {
        $error = $result['message'];
    }
}

// Handle resend OTP
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['resend_otp'])) {
    // Generate and send new OTP
    $otpData = generateOTP($userId, $email, 'signup');
    $emailSent = sendOTPEmail($email, $otpData['otp'], 'signup');
    
    if ($emailSent) {
        $success = 'New OTP sent to your email!';
    } else {
        $success = 'New OTP generated. Check your email or contact support if not received.';
    }
}

$pageTitle = 'Verify OTP - ' . getSetting('site_name');
include 'includes/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-lg">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <i class="fas fa-envelope-open-text fa-3x text-primary mb-3"></i>
                        <h3>Verify Your Email</h3>
                        <p class="text-muted">We've sent a 6-digit OTP to</p>
                        <p class="fw-bold"><?php echo htmlspecialchars($email); ?></p>
                    </div>
                    

                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!$success): ?>
                    <form method="POST" action="">
                        <div class="mb-4">
                            <label class="form-label text-center d-block mb-3">Enter 6-Digit OTP</label>
                            <input type="text" 
                                   name="otp" 
                                   class="form-control form-control-lg text-center" 
                                   placeholder="000000"
                                   maxlength="6"
                                   pattern="[0-9]{6}"
                                   style="font-size: 2rem; letter-spacing: 10px; font-weight: bold;"
                                   required
                                   autofocus>
                            <small class="form-text text-muted d-block text-center mt-2">
                                <i class="fas fa-clock"></i> OTP expires in 15 minutes
                            </small>
                        </div>
                        
                        <button type="submit" name="verify_otp" class="btn btn-primary btn-lg w-100 mb-3">
                            <i class="fas fa-check-circle"></i> Verify OTP
                        </button>
                    </form>
                    
                    <div class="text-center">
                        <p class="text-muted mb-2">Didn't receive the code?</p>
                        <form method="POST" action="" class="d-inline">
                            <button type="submit" name="resend_otp" class="btn btn-link">
                                <i class="fas fa-redo"></i> Resend OTP
                            </button>
                        </form>
                    </div>
                    <?php endif; ?>
                    
                    <hr class="my-4">
                    
                    <div class="text-center">
                        <a href="<?php echo SITE_URL; ?>/signup.php" class="text-muted">
                            <i class="fas fa-arrow-left"></i> Back to Signup
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Auto-format OTP input
document.querySelector('input[name="otp"]').addEventListener('input', function(e) {
    this.value = this.value.replace(/[^0-9]/g, '');
});

// Auto-submit when 6 digits entered
document.querySelector('input[name="otp"]').addEventListener('input', function(e) {
    if (this.value.length === 6) {
        // Optional: auto-submit
        // this.form.submit();
    }
});
</script>

<?php include 'includes/footer.php'; ?>
