<?php
require_once '../config/config.php';
$pageTitle = 'Settings - Admin';

$success = '';

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    foreach ($_POST as $key => $value) {
        if ($key != 'submit') {
            $value = clean($value);
            $stmt = $conn->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
            $stmt->bind_param("ss", $value, $key);
            $stmt->execute();
        }
    }
    $success = 'Settings updated successfully';
}

// Get all settings
$settings = [];
$result = $conn->query("SELECT * FROM settings");
while ($row = $result->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

include 'includes/admin-header.php';
?>

<div class="container-fluid my-4">
    <h2 class="mb-4"><i class="fas fa-cog"></i> Settings</h2>
    
    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            <button type="button" class="btn-close" data-mdb-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <div class="row">
            <!-- General Settings -->
            <div class="col-lg-6 mb-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-info-circle"></i> General Settings</h5>
                    </div>
                    <div class="card-body">
                        <div class="form-outline mb-3">
                            <input type="text" name="site_name" class="form-control" 
                                   value="<?php echo htmlspecialchars($settings['site_name'] ?? ''); ?>" required>
                            <label class="form-label">Site Name</label>
                        </div>
                        
                        <div class="form-outline mb-3">
                            <select name="currency" class="form-select" required>
                                <option value="USD" <?php echo ($settings['currency'] ?? '') == 'USD' ? 'selected' : ''; ?>>USD ($)</option>
                                <option value="EUR" <?php echo ($settings['currency'] ?? '') == 'EUR' ? 'selected' : ''; ?>>EUR (€)</option>
                                <option value="GBP" <?php echo ($settings['currency'] ?? '') == 'GBP' ? 'selected' : ''; ?>>GBP (£)</option>
                                <option value="INR" <?php echo ($settings['currency'] ?? '') == 'INR' ? 'selected' : ''; ?>>INR (₹)</option>
                            </select>
                            <label class="form-label">Currency</label>
                        </div>
                        
                        <div class="form-outline mb-3">
                            <input type="number" name="tax_percentage" class="form-control" 
                                   value="<?php echo htmlspecialchars($settings['tax_percentage'] ?? '0'); ?>" 
                                   step="0.01" required>
                            <label class="form-label">Tax Percentage (%)</label>
                        </div>
                        
                        <div class="form-outline mb-3">
                            <input type="email" name="admin_email" class="form-control" 
                                   value="<?php echo htmlspecialchars($settings['admin_email'] ?? 'admin@gyanbazaar.com'); ?>" 
                                   required>
                            <label class="form-label">Admin Email</label>
                            <small class="text-muted">Email address for receiving notifications</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Payment Gateway Settings -->
            <div class="col-lg-6 mb-4">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-credit-card"></i> Payment Gateway</h5>
                    </div>
                    <div class="card-body">
                        <div class="form-outline mb-3">
                            <select name="payment_gateway" class="form-select" required>
                                <option value="upi" <?php echo ($settings['payment_gateway'] ?? '') == 'upi' ? 'selected' : ''; ?>>UPI</option>
                                <option value="razorpay" <?php echo ($settings['payment_gateway'] ?? '') == 'razorpay' ? 'selected' : ''; ?>>Razorpay</option>
                                <option value="stripe" <?php echo ($settings['payment_gateway'] ?? '') == 'stripe' ? 'selected' : ''; ?>>Stripe</option>
                                <option value="paypal" <?php echo ($settings['payment_gateway'] ?? '') == 'paypal' ? 'selected' : ''; ?>>PayPal</option>
                            </select>
                            <label class="form-label">Default Payment Gateway</label>
                        </div>
                        
                        <h6 class="mt-4 mb-3 text-success">UPI Payment</h6>
                        <div class="form-outline mb-3">
                            <input type="text" name="upi_id" class="form-control" 
                                   value="<?php echo htmlspecialchars($settings['upi_id'] ?? ''); ?>" 
                                   placeholder="yourname@upi">
                            <label class="form-label">UPI ID</label>
                            <small class="text-muted">Your UPI ID for receiving payments (e.g., merchant@paytm)</small>
                        </div>
                        
                        <h6 class="mt-4 mb-3">Razorpay</h6>
                        <div class="form-outline mb-3">
                            <input type="text" name="razorpay_key" class="form-control" 
                                   value="<?php echo htmlspecialchars($settings['razorpay_key'] ?? ''); ?>">
                            <label class="form-label">Razorpay Key</label>
                        </div>
                        <div class="form-outline mb-3">
                            <input type="text" name="razorpay_secret" class="form-control" 
                                   value="<?php echo htmlspecialchars($settings['razorpay_secret'] ?? ''); ?>">
                            <label class="form-label">Razorpay Secret</label>
                        </div>
                        
                        <h6 class="mt-4 mb-3">Stripe</h6>
                        <div class="form-outline mb-3">
                            <input type="text" name="stripe_key" class="form-control" 
                                   value="<?php echo htmlspecialchars($settings['stripe_key'] ?? ''); ?>">
                            <label class="form-label">Stripe Publishable Key</label>
                        </div>
                        <div class="form-outline mb-3">
                            <input type="text" name="stripe_secret" class="form-control" 
                                   value="<?php echo htmlspecialchars($settings['stripe_secret'] ?? ''); ?>">
                            <label class="form-label">Stripe Secret Key</label>
                        </div>
                        
                        <h6 class="mt-4 mb-3">PayPal</h6>
                        <div class="form-outline mb-3">
                            <input type="text" name="paypal_client_id" class="form-control" 
                                   value="<?php echo htmlspecialchars($settings['paypal_client_id'] ?? ''); ?>">
                            <label class="form-label">PayPal Client ID</label>
                        </div>
                        <div class="form-outline mb-3">
                            <input type="text" name="paypal_secret" class="form-control" 
                                   value="<?php echo htmlspecialchars($settings['paypal_secret'] ?? ''); ?>">
                            <label class="form-label">PayPal Secret</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="text-center">
            <button type="submit" name="submit" class="btn btn-primary btn-lg">
                <i class="fas fa-save"></i> Save Settings
            </button>
        </div>
    </form>
</div>

<?php include 'includes/admin-footer.php'; ?>
