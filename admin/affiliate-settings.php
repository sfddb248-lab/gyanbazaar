<?php
require_once '../config/config.php';
require_once '../includes/affiliate-functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Handle settings update
if (isset($_POST['update_settings'])) {
    $settings = [
        'affiliate_enabled' => isset($_POST['affiliate_enabled']) ? '1' : '0',
        'min_payout_amount' => $_POST['min_payout_amount'],
        'mlm_enabled' => isset($_POST['mlm_enabled']) ? '1' : '0',
        'mlm_levels' => $_POST['mlm_levels'],
        'auto_approve_affiliates' => isset($_POST['auto_approve_affiliates']) ? '1' : '0',
        'cookie_duration_days' => $_POST['cookie_duration_days']
    ];
    
    // Add all 10 level commissions
    for ($i = 1; $i <= 10; $i++) {
        $settings["level_{$i}_commission"] = $_POST["level_{$i}_commission"] ?? '0';
    }
    
    foreach ($settings as $key => $value) {
        $stmt = $conn->prepare("INSERT INTO affiliate_settings (setting_key, setting_value) VALUES (?, ?) 
                               ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->bind_param("sss", $key, $value, $value);
        $stmt->execute();
    }
    
    $message = "Settings updated successfully - All 10 levels configured!";
}

// Get current settings
$currentSettings = [];
$result = $conn->query("SELECT setting_key, setting_value FROM affiliate_settings");
while ($row = $result->fetch_assoc()) {
    $currentSettings[$row['setting_key']] = $row['setting_value'];
}

include 'includes/admin-header.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-md-12">
            <h2>Affiliate Settings</h2>
            <?php if (isset($message)): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
        </div>
    </div>
    
    <form method="POST">
        <div class="row">
            <!-- General Settings -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5>General Settings</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="affiliate_enabled" 
                                       <?php echo ($currentSettings['affiliate_enabled'] ?? '1') === '1' ? 'checked' : ''; ?>>
                                <label class="form-check-label">Enable Affiliate Program</label>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="auto_approve_affiliates" 
                                       <?php echo ($currentSettings['auto_approve_affiliates'] ?? '0') === '1' ? 'checked' : ''; ?>>
                                <label class="form-check-label">Auto-Approve New Affiliates</label>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Cookie Duration (Days)</label>
                            <input type="number" name="cookie_duration_days" class="form-control" 
                                   value="<?php echo $currentSettings['cookie_duration_days'] ?? '30'; ?>" required>
                            <small class="text-muted">How long to track referrals</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Payout Settings -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5>Payout Settings</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Minimum Payout Amount (₹)</label>
                            <input type="number" name="min_payout_amount" class="form-control" 
                                   value="<?php echo $currentSettings['min_payout_amount'] ?? '500'; ?>" 
                                   step="0.01" required>
                            <small class="text-muted">Minimum amount for payout requests</small>
                        </div>
                        
                        <div class="alert alert-info mb-0">
                            <i class="fas fa-info-circle"></i> <strong>Note:</strong><br>
                            Commission rates are set individually for each affiliate in the 
                            <a href="affiliates.php" class="alert-link">Affiliates Management</a> page.
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- MLM Settings -->
            <div class="col-md-12 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5>Multi-Level Marketing (MLM) Settings</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="mlm_enabled" 
                                       id="mlm_enabled"
                                       <?php echo ($currentSettings['mlm_enabled'] ?? '1') === '1' ? 'checked' : ''; ?>>
                                <label class="form-check-label">Enable Multi-Level Commissions</label>
                            </div>
                            <small class="text-muted">Allow affiliates to earn from their referrals' sales</small>
                        </div>
                        
                        <div id="mlm_settings">
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">Number of Levels</label>
                                    <select name="mlm_levels" class="form-select">
                                        <?php for ($i = 1; $i <= 10; $i++): ?>
                                            <option value="<?php echo $i; ?>" <?php echo ($currentSettings['mlm_levels'] ?? '10') == $i ? 'selected' : ''; ?>>
                                                <?php echo $i; ?> Level<?php echo $i > 1 ? 's' : ''; ?>
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                    <small class="text-muted">Maximum MLM depth (1-10 levels)</small>
                                </div>
                            </div>
                            
                            <div class="alert alert-info">
                                <strong><i class="fas fa-info-circle"></i> Commission Rates per Level</strong><br>
                                Set the commission percentage for each level. Level 1 is for direct referrals.
                            </div>
                            
                            <div class="row">
                                <?php for ($level = 1; $level <= 10; $level++): 
                                    $defaultValues = [10, 5, 2, 1.5, 1, 0.75, 0.5, 0.25, 0.15, 0.1];
                                    $defaultValue = $defaultValues[$level - 1];
                                ?>
                                    <div class="col-md-6 col-lg-4 col-xl-3 mb-3">
                                        <label class="form-label">
                                            <span class="badge bg-primary">Level <?php echo $level; ?></span>
                                            <?php if ($level == 1): ?>
                                                <small class="text-muted">(Direct)</small>
                                            <?php endif; ?>
                                        </label>
                                        <div class="input-group">
                                            <input type="number" 
                                                   name="level_<?php echo $level; ?>_commission" 
                                                   class="form-control" 
                                                   value="<?php echo $currentSettings["level_{$level}_commission"] ?? $defaultValue; ?>" 
                                                   step="0.01" 
                                                   min="0"
                                                   max="100"
                                                   <?php echo $level == 1 ? 'required' : ''; ?>>
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                <?php endfor; ?>
                                </div>
                            </div>
                            
                            <div class="alert alert-info">
                                <strong>How MLM Works:</strong><br>
                                - Level 1: Direct sales from affiliates they referred<br>
                                - Level 2: Sales from affiliates referred by their Level 1 affiliates<br>
                                - Level 3: Sales from affiliates referred by their Level 2 affiliates
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="text-center">
            <button type="submit" name="update_settings" class="btn btn-primary btn-lg">
                <i class="fas fa-save"></i> Save Settings
            </button>
        </div>
    </form>
</div>

<script>
document.getElementById('mlm_enabled').addEventListener('change', function() {
    document.getElementById('mlm_settings').style.display = this.checked ? 'block' : 'none';
});

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    const mlmEnabled = document.getElementById('mlm_enabled');
    document.getElementById('mlm_settings').style.display = mlmEnabled.checked ? 'block' : 'none';
});
</script>

<?php include 'includes/admin-footer.php'; ?>
