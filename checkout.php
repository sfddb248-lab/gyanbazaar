<?php
require_once 'config/config.php';
requireLogin();
$pageTitle = 'Checkout - ' . getSetting('site_name');

// Handle single product purchase
if (isset($_GET['product'])) {
    $productId = (int)$_GET['product'];
    $_SESSION['cart'] = [$productId => 1];
}

$cartItems = getCartItems();
if (empty($cartItems)) {
    header('Location: ' . SITE_URL . '/cart.php');
    exit;
}

$subtotal = getCartTotal();
$discount = 0;
$couponCode = '';

// Handle coupon
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['apply_coupon'])) {
    $couponCode = clean($_POST['coupon_code']);
    $discount = applyCoupon($couponCode, $subtotal);
    if ($discount > 0) {
        $couponSuccess = "Coupon applied! You saved " . formatCurrency($discount);
    } else {
        $couponError = "Invalid or expired coupon code";
    }
}

$afterDiscount = $subtotal - $discount;
$tax = calculateTax($afterDiscount);
$total = $afterDiscount + $tax;

// Check available payment gateways
$availableGateways = [];

// Check UPI
$upiId = getSetting('upi_id', '');
if (!empty($upiId) && $upiId != 'merchant@upi') {
    $availableGateways['upi'] = [
        'name' => 'UPI Payment',
        'icon' => 'fas fa-mobile-alt',
        'description' => 'Google Pay, PhonePe, Paytm, etc.',
        'color' => 'success'
    ];
}

// Check Razorpay
$razorpayKey = getSetting('razorpay_key', '');
if (!empty($razorpayKey)) {
    $availableGateways['razorpay'] = [
        'name' => 'Razorpay',
        'icon' => 'fas fa-credit-card',
        'description' => 'Credit/Debit Card, Net Banking',
        'color' => 'primary'
    ];
}

// Check Stripe
$stripeKey = getSetting('stripe_key', '');
if (!empty($stripeKey)) {
    $availableGateways['stripe'] = [
        'name' => 'Stripe',
        'icon' => 'fab fa-stripe',
        'description' => 'Credit/Debit Card',
        'color' => 'primary'
    ];
}

// Check PayPal
$paypalClientId = getSetting('paypal_client_id', '');
if (!empty($paypalClientId)) {
    $availableGateways['paypal'] = [
        'name' => 'PayPal',
        'icon' => 'fab fa-paypal',
        'description' => 'PayPal Account',
        'color' => 'primary'
    ];
}

// If no gateways configured, show error
if (empty($availableGateways)) {
    $paymentError = 'No payment gateways configured. Please contact administrator.';
}

// Handle payment
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['place_order'])) {
    $paymentMethod = clean($_POST['payment_method']);
    
    // Create order
    $orderNumber = generateOrderNumber();
    $userId = $_SESSION['user_id'];
    
    // Set payment status based on method
    $paymentStatus = ($paymentMethod == 'upi') ? 'pending' : 'completed';
    
    $stmt = $conn->prepare("INSERT INTO orders (user_id, order_number, total_amount, discount_amount, tax_amount, final_amount, payment_method, payment_status, coupon_code) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isddddsss", $userId, $orderNumber, $subtotal, $discount, $tax, $total, $paymentMethod, $paymentStatus, $couponCode);
    
    if ($stmt->execute()) {
        $orderId = $conn->insert_id;
        
        // Add order items
        foreach ($cartItems as $item) {
            $expiryDate = date('Y-m-d H:i:s', strtotime('+' . DOWNLOAD_EXPIRY_DAYS . ' days'));
            $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, price, download_expiry) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iids", $orderId, $item['id'], $item['price'], $expiryDate);
            $stmt->execute();
            
            // Update download count
            $conn->query("UPDATE products SET downloads = downloads + 1 WHERE id = " . $item['id']);
        }
        
        // Update coupon usage
        if ($couponCode) {
            $conn->query("UPDATE coupons SET used_count = used_count + 1 WHERE code = '$couponCode'");
        }
        
        // Process affiliate commission - Multi-level distribution
        $userStmt = $conn->prepare("SELECT referred_by FROM users WHERE id = ?");
        $userStmt->bind_param("i", $userId);
        $userStmt->execute();
        $userResult = $userStmt->get_result();
        $userData = $userResult->fetch_assoc();
        
        if ($userData && $userData['referred_by']) {
            // User was referred by an affiliate
            $currentAffiliateId = $userData['referred_by'];
            
            // Check if MLM is enabled
            $mlmEnabled = (int)getAffiliateSetting('mlm_enabled', 0);
            $maxLevels = $mlmEnabled ? (int)getAffiliateSetting('mlm_levels', 10) : 1;
            
            // Process commissions for each level
            for ($level = 1; $level <= $maxLevels; $level++) {
                // Get affiliate details
                $affStmt = $conn->prepare("SELECT * FROM affiliates WHERE id = ?");
                $affStmt->bind_param("i", $currentAffiliateId);
                $affStmt->execute();
                $affiliate = $affStmt->get_result()->fetch_assoc();
                
                if (!$affiliate || $affiliate['user_id'] == $userId) {
                    break; // Stop if no affiliate or self-referral
                }
                
                // Get commission rate for this level
                if ($level == 1) {
                    // Level 1: Use affiliate's own commission rate
                    $commissionRate = (float)$affiliate['commission_value'];
                } else {
                    // Level 2+: Use global MLM level settings
                    $commissionRate = (float)getAffiliateSetting("level_{$level}_commission", 0);
                }
                
                if ($commissionRate > 0) {
                    // Calculate commission
                    $commissionAmount = ($total * $commissionRate) / 100;
                    
                    // Insert commission
                    $commStmt = $conn->prepare("
                        INSERT INTO affiliate_commissions 
                        (affiliate_id, order_id, commission_amount, commission_type, commission_rate, order_amount, status, level) 
                        VALUES (?, ?, ?, 'percentage', ?, ?, 'pending', ?)
                    ");
                    $commStmt->bind_param("iidddi", $currentAffiliateId, $orderId, $commissionAmount, $commissionRate, $total, $level);
                    $commStmt->execute();
                    
                    // Update affiliate earnings
                    $conn->query("
                        UPDATE affiliates 
                        SET pending_earnings = pending_earnings + $commissionAmount,
                            total_earnings = total_earnings + $commissionAmount,
                            total_sales = total_sales + 1
                        WHERE id = $currentAffiliateId
                    ");
                    
                    // Mark referral as converted (only for level 1)
                    if ($level == 1) {
                        $conn->query("
                            UPDATE affiliate_referrals 
                            SET converted = 1, 
                                purchase_made = 1,
                                conversion_date = NOW(),
                                first_purchase_date = COALESCE(first_purchase_date, NOW())
                            WHERE affiliate_id = $currentAffiliateId 
                            AND referred_user_id = $userId
                            AND purchase_made = 0
                        ");
                        
                        // Update referral count
                        $conn->query("
                            UPDATE affiliates 
                            SET total_referrals = (
                                SELECT COUNT(*) FROM affiliate_referrals 
                                WHERE affiliate_id = $currentAffiliateId AND purchase_made = 1
                            )
                            WHERE id = $currentAffiliateId
                        ");
                    }
                }
                
                // Get parent affiliate for next level
                if ($level < $maxLevels) {
                    $parentStmt = $conn->prepare("SELECT referred_by FROM users WHERE id = ?");
                    $parentStmt->bind_param("i", $affiliate['user_id']);
                    $parentStmt->execute();
                    $parentResult = $parentStmt->get_result();
                    $parentData = $parentResult->fetch_assoc();
                    
                    if ($parentData && $parentData['referred_by']) {
                        $currentAffiliateId = $parentData['referred_by'];
                    } else {
                        break; // No more parent affiliates
                    }
                }
            }
        }
        
        // Clear cart
        unset($_SESSION['cart']);
        
        // Redirect based on payment method
        if ($paymentMethod == 'upi') {
            // Redirect to UPI payment page
            header('Location: ' . SITE_URL . '/upi-payment.php?order=' . $orderId);
            exit;
        } else {
            // Send email for other payment methods
            sendEmail($_SESSION['user_email'], 'Order Confirmation - ' . $orderNumber, 
                      "Thank you for your purchase! Your order number is: $orderNumber");
            
            header('Location: ' . SITE_URL . '/orders.php?success=1');
            exit;
        }
    }
}

include 'includes/header.php';
?>

<div class="container my-4">
    <h2 class="mb-4"><i class="fas fa-credit-card"></i> Checkout</h2>
    
    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title mb-4">Order Items</h5>
                    <?php foreach ($cartItems as $item): ?>
                    <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                        <div>
                            <h6 class="mb-1"><?php echo htmlspecialchars($item['title']); ?></h6>
                            <small class="text-muted">Digital Product</small>
                        </div>
                        <span class="text-primary"><?php echo formatCurrency($item['price']); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title mb-4">Apply Coupon</h5>
                    
                    <?php if (isset($couponSuccess)): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> <?php echo $couponSuccess; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($couponError)): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i> <?php echo $couponError; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="input-group">
                            <input type="text" name="coupon_code" class="form-control" 
                                   placeholder="Enter coupon code" value="<?php echo htmlspecialchars($couponCode); ?>">
                            <button type="submit" name="apply_coupon" class="btn btn-outline-primary">
                                Apply
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-4">Payment Method</h5>
                    
                    <?php if (isset($paymentError)): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i> <?php echo $paymentError; ?>
                        </div>
                        <p class="text-muted">Please contact support to complete your purchase.</p>
                    <?php elseif (!empty($availableGateways)): ?>
                        <form method="POST" action="">
                            <?php 
                            $first = true;
                            foreach ($availableGateways as $key => $gateway): 
                            ?>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="radio" name="payment_method" 
                                           id="<?php echo $key; ?>" value="<?php echo $key; ?>" 
                                           <?php echo $first ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="<?php echo $key; ?>">
                                        <i class="<?php echo $gateway['icon']; ?> text-<?php echo $gateway['color']; ?>"></i> 
                                        <?php echo $gateway['name']; ?>
                                        <?php if (!empty($gateway['description'])): ?>
                                            <small class="d-block text-muted"><?php echo $gateway['description']; ?></small>
                                        <?php endif; ?>
                                    </label>
                                </div>
                            <?php 
                            $first = false;
                            endforeach; 
                            ?>
                            
                            <button type="submit" name="place_order" class="btn btn-success btn-lg w-100">
                                <i class="fas fa-lock"></i> Place Order - <?php echo formatCurrency($total); ?>
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-4">Order Summary</h5>
                    
                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal:</span>
                        <span><?php echo formatCurrency($subtotal); ?></span>
                    </div>
                    
                    <?php if ($discount > 0): ?>
                    <div class="d-flex justify-content-between mb-2 text-success">
                        <span>Discount:</span>
                        <span>-<?php echo formatCurrency($discount); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <div class="d-flex justify-content-between mb-2">
                        <span>Tax (<?php echo getSetting('tax_percentage'); ?>%):</span>
                        <span><?php echo formatCurrency($tax); ?></span>
                    </div>
                    
                    <hr>
                    
                    <div class="d-flex justify-content-between mb-3">
                        <strong>Total:</strong>
                        <strong class="text-primary h4"><?php echo formatCurrency($total); ?></strong>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <small>You will receive instant access to download your products after payment.</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Initialize MDB form inputs
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.form-outline').forEach((formOutline) => {
        new mdb.Input(formOutline).init();
    });
});
</script>

<?php include 'includes/footer.php'; ?>
