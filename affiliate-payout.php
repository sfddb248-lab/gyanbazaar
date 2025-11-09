<?php
require_once 'config/config.php';
require_once 'includes/affiliate-functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$affiliate = getAffiliateByUserId($userId);

if (!$affiliate) {
    header('Location: affiliate-dashboard.php');
    exit;
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = floatval($_POST['amount']);
    $paymentMethod = $_POST['payment_method'];
    $paymentDetails = $_POST['payment_details'];
    
    $result = requestPayout($affiliate['id'], $amount, $paymentMethod, $paymentDetails);
    
    if ($result['success']) {
        $message = $result['message'];
    } else {
        $error = $result['message'];
    }
}

include 'includes/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-body">
                    <h3 class="card-title mb-4">Request Payout</h3>
                    
                    <?php if ($message): ?>
                        <div class="alert alert-success"><?php echo $message; ?></div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <div class="alert alert-info">
                        <strong>Available Balance:</strong> ₹<?php echo number_format($affiliate['pending_earnings'], 2); ?>
                    </div>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Payout Amount</label>
                            <input type="number" name="amount" class="form-control" 
                                   min="<?php echo getAffiliateSetting('min_payout_amount', 500); ?>" 
                                   max="<?php echo $affiliate['pending_earnings']; ?>" 
                                   step="0.01" required>
                            <small class="text-muted">
                                Minimum: ₹<?php echo getAffiliateSetting('min_payout_amount', 500); ?>
                            </small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Payment Method</label>
                            <select name="payment_method" class="form-select" required>
                                <option value="">Select Method</option>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="upi">UPI</option>
                                <option value="paypal">PayPal</option>
                                <option value="paytm">Paytm</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Payment Details</label>
                            <textarea name="payment_details" class="form-control" rows="4" 
                                      placeholder="Enter your bank account details, UPI ID, or PayPal email" required></textarea>
                            <small class="text-muted">
                                Provide complete payment information for processing
                            </small>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i> Submit Payout Request
                            </button>
                            <a href="affiliate-dashboard.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left"></i> Back to Dashboard
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
