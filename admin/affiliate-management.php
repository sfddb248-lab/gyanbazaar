<?php
require_once '../config/config.php';
requireAdmin();

$pageTitle = 'Affiliate Management - Admin';

// Handle actions
$message = '';
$messageType = '';

// Update affiliate commission
if (isset($_POST['update_affiliate_commission'])) {
    $affiliateId = (int)$_POST['affiliate_id'];
    $commissionType = clean($_POST['commission_type']);
    $commissionValue = (float)$_POST['commission_value'];
    
    $stmt = $conn->prepare("UPDATE affiliates SET commission_type = ?, commission_value = ? WHERE id = ?");
    $stmt->bind_param("sdi", $commissionType, $commissionValue, $affiliateId);
    
    if ($stmt->execute()) {
        $message = "Commission updated successfully!";
        $messageType = "success";
    } else {
        $message = "Failed to update commission.";
        $messageType = "danger";
    }
}

// Update affiliate status
if (isset($_POST['update_status'])) {
    $affiliateId = (int)$_POST['affiliate_id'];
    $status = clean($_POST['status']);
    
    $stmt = $conn->prepare("UPDATE affiliates SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $affiliateId);
    
    if ($stmt->execute()) {
        $message = "Status updated successfully!";
        $messageType = "success";
    }
}

// Update global MLM settings
if (isset($_POST['update_mlm_settings'])) {
    $mlmEnabled = isset($_POST['mlm_enabled']) ? 1 : 0;
    $mlmLevels = (int)$_POST['mlm_levels'];
    
    // Update or insert settings
    $conn->query("INSERT INTO affiliate_settings (setting_key, setting_value) VALUES ('mlm_enabled', '$mlmEnabled') ON DUPLICATE KEY UPDATE setting_value = '$mlmEnabled'");
    $conn->query("INSERT INTO affiliate_settings (setting_key, setting_value) VALUES ('mlm_levels', '$mlmLevels') ON DUPLICATE KEY UPDATE setting_value = '$mlmLevels'");
    
    // Update level commissions
    for ($level = 1; $level <= 10; $level++) {
        if (isset($_POST["level_{$level}_commission"])) {
            $levelCommission = (float)$_POST["level_{$level}_commission"];
            $conn->query("INSERT INTO affiliate_settings (setting_key, setting_value) VALUES ('level_{$level}_commission', '$levelCommission') ON DUPLICATE KEY UPDATE setting_value = '$levelCommission'");
        }
    }
    
    $message = "MLM settings updated successfully!";
    $messageType = "success";
}

// Get all affiliates
$affiliates = $conn->query("
    SELECT a.*, u.name, u.email, u.created_at as user_joined
    FROM affiliates a
    JOIN users u ON a.user_id = u.id
    ORDER BY a.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

// Get MLM settings
$mlmEnabled = (int)getAffiliateSetting('mlm_enabled', 0);
$mlmLevels = (int)getAffiliateSetting('mlm_levels', 10);

$currentPage = 'affiliate-management';
include 'includes/admin-header.php';
?>

<div class="container-fluid py-4">
    <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-mdb-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="fas fa-users-cog"></i> Affiliate Management</h2>
        </div>
    </div>
    
    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h6>Total Affiliates</h6>
                    <h3><?php echo count($affiliates); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6>Active Affiliates</h6>
                    <h3><?php echo count(array_filter($affiliates, fn($a) => $a['status'] == 'active')); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h6>Total Referrals</h6>
                    <h3><?php echo array_sum(array_column($affiliates, 'total_referrals')); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h6>Total Earnings</h6>
                    <h3>₹<?php echo number_format(array_sum(array_column($affiliates, 'total_earnings')), 2); ?></h3>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Tabs -->
    <ul class="nav nav-tabs mb-4" role="tablist">
        <li class="nav-item">
            <a class="nav-link active" data-mdb-toggle="tab" href="#affiliates">
                <i class="fas fa-users"></i> Affiliates
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-mdb-toggle="tab" href="#mlm-settings">
                <i class="fas fa-sitemap"></i> MLM Settings
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-mdb-toggle="tab" href="#commissions">
                <i class="fas fa-money-bill-wave"></i> Commissions
            </a>
        </li>
    </ul>
    
    <div class="tab-content">
        <!-- Affiliates Tab -->
        <div class="tab-pane fade show active" id="affiliates">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">All Affiliates</h5>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Referral Code</th>
                                    <th>Commission</th>
                                    <th>Referrals</th>
                                    <th>Sales</th>
                                    <th>Earnings</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($affiliates as $affiliate): ?>
                                <tr>
                                    <td><?php echo $affiliate['id']; ?></td>
                                    <td><?php echo htmlspecialchars($affiliate['name']); ?></td>
                                    <td><?php echo htmlspecialchars($affiliate['email']); ?></td>
                                    <td><code><?php echo $affiliate['referral_code']; ?></code></td>
                                    <td><?php echo $affiliate['commission_value']; ?>%</td>
                                    <td><?php echo $affiliate['total_referrals']; ?></td>
                                    <td><?php echo $affiliate['total_sales']; ?></td>
                                    <td>₹<?php echo number_format($affiliate['total_earnings'], 2); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $affiliate['status'] == 'active' ? 'success' : 'danger'; ?>">
                                            <?php echo ucfirst($affiliate['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-primary" onclick="editAffiliate(<?php echo htmlspecialchars(json_encode($affiliate)); ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- MLM Settings Tab -->
        <div class="tab-pane fade" id="mlm-settings">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Multi-Level Marketing Settings</h5>
                    <form method="POST">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="mlm_enabled" id="mlm_enabled" <?php echo $mlmEnabled ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="mlm_enabled">
                                        Enable Multi-Level Marketing
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label>Number of Levels</label>
                                <input type="number" class="form-control" name="mlm_levels" value="<?php echo $mlmLevels; ?>" min="1" max="10">
                            </div>
                        </div>
                        
                        <h6 class="mt-4">Commission Rates by Level</h6>
                        <div class="row">
                            <?php for ($level = 1; $level <= 10; $level++): ?>
                                <?php $levelCommission = (float)getAffiliateSetting("level_{$level}_commission", $level == 1 ? 10 : 0); ?>
                                <div class="col-md-6 mb-3">
                                    <label>Level <?php echo $level; ?> Commission (%)</label>
                                    <input type="number" class="form-control" name="level_<?php echo $level; ?>_commission" 
                                           value="<?php echo $levelCommission; ?>" min="0" max="100" step="0.01">
                                    <small class="text-muted">
                                        <?php if ($level == 1): ?>
                                            Direct referrals
                                        <?php else: ?>
                                            Referrals of level <?php echo $level - 1; ?> affiliates
                                        <?php endif; ?>
                                    </small>
                                </div>
                            <?php endfor; ?>
                        </div>
                        
                        <button type="submit" name="update_mlm_settings" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save MLM Settings
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Commissions Tab -->
        <div class="tab-pane fade" id="commissions">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Recent Commissions</h5>
                    <?php
                    $recentCommissions = $conn->query("
                        SELECT ac.*, o.order_number, u.name as affiliate_name, u2.name as buyer_name
                        FROM affiliate_commissions ac
                        JOIN orders o ON ac.order_id = o.id
                        JOIN affiliates a ON ac.affiliate_id = a.id
                        JOIN users u ON a.user_id = u.id
                        JOIN users u2 ON o.user_id = u2.id
                        ORDER BY ac.created_at DESC
                        LIMIT 50
                    ")->fetch_all(MYSQLI_ASSOC);
                    ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Affiliate</th>
                                    <th>Buyer</th>
                                    <th>Order #</th>
                                    <th>Order Amount</th>
                                    <th>Commission</th>
                                    <th>Level</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentCommissions as $comm): ?>
                                <tr>
                                    <td><?php echo $comm['id']; ?></td>
                                    <td><?php echo htmlspecialchars($comm['affiliate_name']); ?></td>
                                    <td><?php echo htmlspecialchars($comm['buyer_name']); ?></td>
                                    <td><?php echo $comm['order_number']; ?></td>
                                    <td>₹<?php echo number_format($comm['order_amount'], 2); ?></td>
                                    <td><strong class="text-success">₹<?php echo number_format($comm['commission_amount'], 2); ?></strong></td>
                                    <td>
                                        <span class="badge bg-<?php echo $comm['level'] == 1 ? 'primary' : 'info'; ?>">
                                            Level <?php echo $comm['level']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $comm['status'] == 'paid' ? 'success' : 'warning'; ?>">
                                            <?php echo ucfirst($comm['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($comm['created_at'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Affiliate Modal -->
<div class="modal fade" id="editAffiliateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Affiliate</h5>
                    <button type="button" class="btn-close" data-mdb-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="affiliate_id" id="edit_affiliate_id">
                    
                    <div class="mb-3">
                        <label>Affiliate Name</label>
                        <input type="text" class="form-control" id="edit_name" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label>Commission Type</label>
                        <select class="form-control" name="commission_type" id="edit_commission_type">
                            <option value="percentage">Percentage</option>
                            <option value="flat">Flat Amount</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label>Commission Value</label>
                        <input type="number" class="form-control" name="commission_value" id="edit_commission_value" 
                               min="0" max="100" step="0.01" required>
                        <small class="text-muted">For percentage: 0-100. For flat: amount in ₹</small>
                    </div>
                    
                    <div class="mb-3">
                        <label>Status</label>
                        <select class="form-control" name="status" id="edit_status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="suspended">Suspended</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-mdb-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_affiliate_commission" class="btn btn-primary">Save Changes</button>
                    <button type="submit" name="update_status" class="btn btn-warning">Update Status</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editAffiliate(affiliate) {
    document.getElementById('edit_affiliate_id').value = affiliate.id;
    document.getElementById('edit_name').value = affiliate.name;
    document.getElementById('edit_commission_type').value = affiliate.commission_type;
    document.getElementById('edit_commission_value').value = affiliate.commission_value;
    document.getElementById('edit_status').value = affiliate.status;
    
    const modal = new mdb.Modal(document.getElementById('editAffiliateModal'));
    modal.show();
}
</script>

<?php include 'includes/admin-footer.php'; ?>
