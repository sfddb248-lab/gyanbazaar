<?php
require_once 'config/config.php';
requireLogin();

$orderId = isset($_GET['order']) ? (int)$_GET['order'] : 0;

if (!$orderId) {
    header('Location: ' . SITE_URL . '/orders.php');
    exit;
}

// Get order details
$userId = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $orderId, $userId);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order || $order['payment_method'] != 'upi') {
    header('Location: ' . SITE_URL . '/orders.php');
    exit;
}

// Get UPI ID from settings
$upiId = getSetting('upi_id', 'merchant@upi');
$merchantName = getSetting('site_name', 'GyanBazaar');

$pageTitle = 'UPI Payment - ' . getSetting('site_name');
include 'includes/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card shadow">
                <div class="card-header bg-success text-white text-center">
                    <h4 class="mb-0"><i class="fas fa-mobile-alt"></i> UPI Payment</h4>
                </div>
                <div class="card-body text-center p-4">
                    <h5 class="mb-3">Order #<?php echo htmlspecialchars($order['order_number']); ?></h5>
                    <h2 class="text-success mb-4"><?php echo formatCurrency($order['final_amount']); ?></h2>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Scan QR code or use UPI ID to complete payment
                    </div>
                    
                    <!-- QR Code -->
                    <div class="mb-4">
                        <div id="qrcode" class="d-inline-block p-3 bg-white border rounded"></div>
                    </div>
                    
                    <!-- UPI ID -->
                    <div class="mb-4">
                        <label class="form-label fw-bold">UPI ID:</label>
                        <div class="input-group">
                            <input type="text" class="form-control form-control-lg text-center" 
                                   id="upiId" value="<?php echo htmlspecialchars($upiId); ?>" readonly>
                            <button class="btn btn-outline-primary" onclick="copyUpiId()">
                                <i class="fas fa-copy"></i> Copy
                            </button>
                        </div>
                    </div>
                    
                    <!-- Payment Apps -->
                    <div class="mb-4">
                        <p class="fw-bold mb-3">Pay using:</p>
                        <div class="d-flex justify-content-center gap-3 flex-wrap">
                            <button class="btn btn-outline-primary" onclick="openUpiApp('gpay')">
                                <i class="fab fa-google-pay"></i> Google Pay
                            </button>
                            <button class="btn btn-outline-primary" onclick="openUpiApp('phonepe')">
                                <i class="fas fa-mobile-alt"></i> PhonePe
                            </button>
                            <button class="btn btn-outline-primary" onclick="openUpiApp('paytm')">
                                <i class="fas fa-wallet"></i> Paytm
                            </button>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <!-- Payment Confirmation -->
                    <div class="mb-3">
                        <div class="alert alert-warning">
                            <i class="fas fa-info-circle"></i> 
                            <strong>Important:</strong> After completing payment, enter your 12-digit UPI Transaction ID below
                        </div>
                        
                        <form method="POST" action="verify-upi-payment.php" id="verifyForm">
                            <input type="hidden" name="order_id" value="<?php echo $orderId; ?>">
                            
                            <label class="form-label fw-bold mb-2">
                                <i class="fas fa-receipt"></i> UPI Transaction ID (12 digits)
                            </label>
                            
                            <div class="input-group input-group-lg mb-2">
                                <span class="input-group-text">
                                    <i class="fas fa-hashtag"></i>
                                </span>
                                <input type="text" 
                                       name="transaction_id" 
                                       id="transactionId"
                                       class="form-control form-control-lg text-center" 
                                       placeholder="Enter 12-digit Transaction ID"
                                       maxlength="12"
                                       pattern="[0-9]{12}"
                                       style="font-size: 1.2rem; letter-spacing: 2px; font-weight: bold;"
                                       required>
                            </div>
                            
                            <div id="txnIdHelp" class="form-text mb-3">
                                <i class="fas fa-lightbulb"></i> 
                                Example: 123456789012 (12 digits only)
                            </div>
                            
                            <div id="txnIdError" class="alert alert-danger d-none">
                                <i class="fas fa-exclamation-triangle"></i> 
                                Please enter exactly 12 digits
                            </div>
                            
                            <div id="txnIdSuccess" class="alert alert-success d-none">
                                <i class="fas fa-check-circle"></i> 
                                Valid Transaction ID format
                            </div>
                            
                            <button type="submit" class="btn btn-success btn-lg w-100" id="verifyBtn">
                                <i class="fas fa-check-circle"></i> Verify Payment
                            </button>
                        </form>
                    </div>
                    
                    <div class="mt-3">
                        <a href="<?php echo SITE_URL; ?>/orders.php" class="btn btn-link">
                            <i class="fas fa-arrow-left"></i> Back to Orders
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Instructions -->
            <div class="card mt-3">
                <div class="card-body">
                    <h6 class="card-title"><i class="fas fa-question-circle"></i> How to Pay?</h6>
                    <ol class="mb-0">
                        <li>Scan the QR code with any UPI app</li>
                        <li>Or copy the UPI ID and paste in your UPI app</li>
                        <li>Or click on your preferred payment app button</li>
                        <li>Complete the payment</li>
                        <li><strong>Copy the 12-digit Transaction ID</strong> from payment confirmation</li>
                        <li>Enter the Transaction ID above and click "Verify Payment"</li>
                    </ol>
                    
                    <div class="alert alert-info mt-3 mb-0">
                        <i class="fas fa-info-circle"></i> 
                        <strong>Where to find Transaction ID?</strong><br>
                        After payment, your UPI app will show a confirmation with a 12-digit Transaction ID (also called UTR or Reference Number).
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- QR Code Library -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

<script>
// Generate UPI payment string
const upiId = '<?php echo $upiId; ?>';
const amount = '<?php echo $order['final_amount']; ?>';
const merchantName = '<?php echo $merchantName; ?>';
const orderNumber = '<?php echo $order['order_number']; ?>';

const upiString = `upi://pay?pa=${upiId}&pn=${encodeURIComponent(merchantName)}&am=${amount}&cu=INR&tn=${encodeURIComponent('Order ' + orderNumber)}`;

// Generate QR Code
new QRCode(document.getElementById("qrcode"), {
    text: upiString,
    width: 256,
    height: 256,
    colorDark: "#000000",
    colorLight: "#ffffff",
    correctLevel: QRCode.CorrectLevel.H
});

// Copy UPI ID
function copyUpiId() {
    const upiInput = document.getElementById('upiId');
    upiInput.select();
    document.execCommand('copy');
    alert('UPI ID copied to clipboard!');
}

// Transaction ID Validation
const txnIdInput = document.getElementById('transactionId');
const txnIdError = document.getElementById('txnIdError');
const txnIdSuccess = document.getElementById('txnIdSuccess');
const txnIdHelp = document.getElementById('txnIdHelp');
const verifyBtn = document.getElementById('verifyBtn');

// Real-time validation
txnIdInput.addEventListener('input', function(e) {
    // Remove non-numeric characters
    this.value = this.value.replace(/[^0-9]/g, '');
    
    const value = this.value;
    const length = value.length;
    
    // Hide all messages first
    txnIdError.classList.add('d-none');
    txnIdSuccess.classList.add('d-none');
    txnIdHelp.classList.remove('d-none');
    
    if (length > 0 && length < 12) {
        // Show remaining digits needed
        txnIdHelp.innerHTML = `<i class="fas fa-info-circle"></i> ${12 - length} more digit(s) needed`;
        txnIdHelp.classList.remove('text-muted');
        txnIdHelp.classList.add('text-warning');
        verifyBtn.disabled = true;
    } else if (length === 12) {
        // Valid format
        txnIdHelp.classList.add('d-none');
        txnIdSuccess.classList.remove('d-none');
        verifyBtn.disabled = false;
    } else if (length === 0) {
        // Empty
        txnIdHelp.innerHTML = `<i class="fas fa-lightbulb"></i> Example: 123456789012 (12 digits only)`;
        txnIdHelp.classList.remove('text-warning');
        txnIdHelp.classList.add('text-muted');
        verifyBtn.disabled = true;
    }
});

// Form validation on submit
document.getElementById('verifyForm').addEventListener('submit', function(e) {
    const value = txnIdInput.value;
    
    if (value.length !== 12 || !/^[0-9]{12}$/.test(value)) {
        e.preventDefault();
        txnIdError.classList.remove('d-none');
        txnIdSuccess.classList.add('d-none');
        txnIdInput.focus();
        return false;
    }
    
    // Show loading state
    verifyBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verifying...';
    verifyBtn.disabled = true;
    
    return true;
});

// Initial state
verifyBtn.disabled = true;

// Open UPI apps
function openUpiApp(app) {
    let url = upiString;
    
    // App-specific deep links
    switch(app) {
        case 'gpay':
            url = `gpay://upi/pay?pa=${upiId}&pn=${encodeURIComponent(merchantName)}&am=${amount}&cu=INR&tn=${encodeURIComponent('Order ' + orderNumber)}`;
            break;
        case 'phonepe':
            url = `phonepe://pay?pa=${upiId}&pn=${encodeURIComponent(merchantName)}&am=${amount}&cu=INR&tn=${encodeURIComponent('Order ' + orderNumber)}`;
            break;
        case 'paytm':
            url = `paytmmp://pay?pa=${upiId}&pn=${encodeURIComponent(merchantName)}&am=${amount}&cu=INR&tn=${encodeURIComponent('Order ' + orderNumber)}`;
            break;
    }
    
    // Try to open app, fallback to generic UPI
    window.location.href = url;
    
    // Fallback after 2 seconds
    setTimeout(function() {
        if (confirm('App not installed? Click OK to use generic UPI link')) {
            window.location.href = upiString;
        }
    }, 2000);
}
</script>

<?php include 'includes/footer.php'; ?>
