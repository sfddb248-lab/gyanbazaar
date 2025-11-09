<?php
require_once '../config/config.php';
requireAdmin();

$pageTitle = 'Approve Commissions - Admin';
$currentPage = 'approve-commissions';

// Handle actions
$message = '';
$messageType = '';

// Approve selected commissions
if (isset($_POST['approve_commissions'])) {
    $commissionIds = $_POST['commission_ids'] ?? [];
    
    if (count($commissionIds) > 0) {
        $ids = implode(',', array_map('intval', $commissionIds));
        $conn->query("UPDATE affiliate_commissions SET status = 'approved', approved_at = NOW() WHERE id IN ($ids)");
        
        $message = "Approved " . count($commissionIds) . " commission(s) successfully!";
        $messageType = "success";
    }
}

// Approve all pending commissions
if (isset($_POST['approve_all'])) {
    $result = $conn->query("UPDATE affiliate_commissions SET status = 'approved', approved_at = NOW() WHERE status = 'pending'");
    $affected = $conn->affected_rows;
    
    $message = "Approved all $affected pending commission(s)!";
    $messageType = "success";
}

// Enable auto-approval
if (isset($_POST['enable_auto_approval'])) {
    $autoApprove = isset($_POST['auto_approve']) ? 1 : 0;
    $autoApproveDays = (int)$_POST['auto_approve_days'];
    
    $conn->query("INSERT INTO affiliate_settings (setting_key, setting_value) VALUES ('auto_approve_commissions', '$autoApprove') ON DUPLICATE KEY UPDATE setting_value = '$autoApprove'");
    $conn->query("INSERT INTO affiliate_settings (setting_key, setting_value) VALUES ('auto_approve_days', '$autoApproveDays') ON DUPLICATE KEY UPDATE setting_value = '$autoApproveDays'");
    
    $message = "Auto-approval settings updated!";
    $messageType = "success";
}

// Run auto-approval now
if (isset($_POST['run_auto_approval'])) {
    $autoApproveDays = (int)getAffiliateSetting('auto_approve_days', 7);
    $cutoffDate = date('Y-m-d H:i:s', strtotime("-$autoApproveDays days"));
    
    $result = $conn->query("
        UPDATE affiliate_commissions 
        SET status = 'approved', approved_at = NOW() 
        WHERE status = 'pending' 
        AND created_at <= '$cutoffDate'
    ");
    
    $affected = $conn->affected_rows;
    
    if ($affected > 0) {
        $message = "Auto-approved $affected commission(s) older than $autoApproveDays days!";
        $messageType = "success";
    } else {
        $message = "No commissions found older than $autoApproveDays days to auto-approve.";
        $messageType = "info";
    }
}

// Get pending commissions
$pendingCommissions = $conn->query("
    SELECT 
        ac.*,
        o.order_number,
        o.created_at as order_date,
        a.referral_code,
        u.name as affiliate_name,
        u.email as affiliate_email,
        buyer.name as buyer_name
    FROM affiliate_commissions ac
    JOIN orders o ON ac.order_id = o.id
    JOIN affiliates a ON ac.affiliate_id = a.id
    JOIN users u ON a.user_id = u.id
    JOIN users buyer ON o.user_id = buyer.id
    WHERE ac.status = 'pending'
    ORDER BY ac.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

// Get approved commissions
$approvedCommissions = $conn->query("
    SELECT 
        ac.*,
        o.order_number,
        a.referral_code,
        u.name as affiliate_name
    FROM affiliate_commissions ac
    JOIN orders o ON ac.order_id = o.id
    JOIN affiliates a ON ac.affiliate_id = a.id
    JOIN users u ON a.user_id = u.id
    WHERE ac.status = 'approved'
    ORDER BY ac.approved_at DESC
    LIMIT 50
")->fetch_all(MYSQLI_ASSOC);

// Get settings
$autoApprove = (int)getAffiliateSetting('auto_approve_commissions', 0);
$autoApproveDays = (int)getAffiliateSetting('auto_approve_days', 7);

// Calculate totals
$totalPending = array_sum(array_column($pendingCommissions, 'commission_amount'));
$totalApproved = array_sum(array_column($approvedCommissions, 'commission_amount'));

include 'includes/admin-header.php';
?>

<style>
.commission-card {
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 10px;
    transition: all 0.3s;
}

.commission-card:hover {
    border-color: #1266f1;
    box-shadow: 0 2px 8px rgba(18, 102, 241, 0.15);
}

.commission-card.selected {
    border-color: #28a745;
    background: #f0fff4;
}
</style>

<div class="container-fluid py-4">
    <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-mdb-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="fas fa-check-circle"></i> Commission Approval</h2>
            <p class="text-muted">Review and approve pending commissions</p>
        </div>
    </div>
    
    <!-- Statistics -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-warning text-white">
                <div class="card-body text-center">
                    <h6>Pending Commissions</h6>
                    <h3><?php echo count($pendingCommissions); ?></h3>
                    <p class="mb-0">₹<?php echo number_format($totalPending, 2); ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h6>Approved Commissions</h6>
                    <h3><?php echo count($approvedCommissions); ?></h3>
                    <p class="mb-0">₹<?php echo number_format($totalApproved, 2); ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <h6>Auto-Approval</h6>
                    <h3><?php echo $autoApprove ? 'ON' : 'OFF'; ?></h3>
                    <p class="mb-0"><?php echo $autoApproveDays; ?> days delay</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Tabs -->
    <ul class="nav nav-tabs mb-4" role="tablist">
        <li class="nav-item">
            <a class="nav-link active" data-mdb-toggle="tab" href="#pending-tab">
                <i class="fas fa-clock"></i> Pending (<?php echo count($pendingCommissions); ?>)
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-mdb-toggle="tab" href="#approved-tab">
                <i class="fas fa-check"></i> Approved (<?php echo count($approvedCommissions); ?>)
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-mdb-toggle="tab" href="#settings-tab">
                <i class="fas fa-cog"></i> Auto-Approval Settings
            </a>
        </li>
    </ul>
    
    <div class="tab-content">
        <!-- Pending Tab -->
        <div class="tab-pane fade show active" id="pending-tab">
            <?php if (count($pendingCommissions) > 0): ?>
                <form method="POST">
                    <div class="mb-3">
                        <button type="button" class="btn btn-primary" onclick="selectAll()">
                            <i class="fas fa-check-square"></i> Select All
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="deselectAll()">
                            <i class="fas fa-square"></i> Deselect All
                        </button>
                        <button type="submit" name="approve_commissions" class="btn btn-success">
                            <i class="fas fa-check"></i> Approve Selected
                        </button>
                        <button type="submit" name="approve_all" class="btn btn-warning" 
                                onclick="return confirm('Approve ALL pending commissions?')">
                            <i class="fas fa-check-double"></i> Approve All
                        </button>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th width="50">
                                        <input type="checkbox" id="select_all" onchange="toggleAll(this)">
                                    </th>
                                    <th>Order #</th>
                                    <th>Affiliate</th>
                                    <th>Buyer</th>
                                    <th>Order Amount</th>
                                    <th>Commission</th>
                                    <th>Level</th>
                                    <th>Date</th>
                                    <th>Age</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pendingCommissions as $comm): ?>
                                <?php
                                $age = floor((time() - strtotime($comm['created_at'])) / 86400);
                                $ageColor = $age >= $autoApproveDays ? 'success' : 'warning';
                                ?>
                                <tr>
                                    <td>
                                        <input type="checkbox" name="commission_ids[]" value="<?php echo $comm['id']; ?>" 
                                               class="commission-checkbox">
                                    </td>
                                    <td><?php echo $comm['order_number']; ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($comm['affiliate_name']); ?><br>
                                        <small class="text-muted"><?php echo $comm['referral_code']; ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($comm['buyer_name']); ?></td>
                                    <td>₹<?php echo number_format($comm['order_amount'], 2); ?></td>
                                    <td><strong class="text-success">₹<?php echo number_format($comm['commission_amount'], 2); ?></strong></td>
                                    <td><span class="badge bg-primary">L<?php echo $comm['level']; ?></span></td>
                                    <td><?php echo date('M d, Y', strtotime($comm['created_at'])); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $ageColor; ?>">
                                            <?php echo $age; ?> days
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </form>
            <?php else: ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> All commissions are approved! No pending commissions.
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Approved Tab -->
        <div class="tab-pane fade" id="approved-tab">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Affiliate</th>
                            <th>Commission</th>
                            <th>Level</th>
                            <th>Approved Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($approvedCommissions as $comm): ?>
                        <tr>
                            <td><?php echo $comm['order_number']; ?></td>
                            <td>
                                <?php echo htmlspecialchars($comm['affiliate_name']); ?><br>
                                <small class="text-muted"><?php echo $comm['referral_code']; ?></small>
                            </td>
                            <td><strong class="text-success">₹<?php echo number_format($comm['commission_amount'], 2); ?></strong></td>
                            <td><span class="badge bg-primary">L<?php echo $comm['level']; ?></span></td>
                            <td><?php echo date('M d, Y H:i', strtotime($comm['approved_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Settings Tab -->
        <div class="tab-pane fade" id="settings-tab">
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h5><i class="fas fa-robot"></i> Auto-Approval Settings</h5>
                            <form method="POST">
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="auto_approve" 
                                               id="auto_approve" <?php echo $autoApprove ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="auto_approve">
                                            <strong>Enable Auto-Approval</strong>
                                        </label>
                                    </div>
                                    <small class="text-muted">Automatically approve commissions after specified days</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Auto-Approve After (Days)</label>
                                    <input type="number" class="form-control" name="auto_approve_days" 
                                           value="<?php echo $autoApproveDays; ?>" min="0" max="365" required>
                                    <small class="text-muted">Commissions will be automatically approved after this many days</small>
                                </div>
                                
                                <button type="submit" name="enable_auto_approval" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Save Settings
                                </button>
                            </form>
                            
                            <hr>
                            
                            <form method="POST">
                                <h6 class="mt-3"><i class="fas fa-play-circle"></i> Manual Trigger</h6>
                                <p class="text-muted small">Run auto-approval process now (approves commissions older than <?php echo $autoApproveDays; ?> days)</p>
                                <button type="submit" name="run_auto_approval" class="btn btn-success">
                                    <i class="fas fa-bolt"></i> Run Auto-Approval Now
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h5><i class="fas fa-info-circle"></i> How It Works</h5>
                            <div class="alert alert-info">
                                <h6>Commission Approval Process:</h6>
                                <ol class="mb-0">
                                    <li><strong>Pending:</strong> New commissions start as "pending"</li>
                                    <li><strong>Review:</strong> Admin reviews and approves commissions</li>
                                    <li><strong>Approved:</strong> Commissions become available for withdrawal</li>
                                    <li><strong>Paid:</strong> Marked as paid when withdrawal is processed</li>
                                </ol>
                            </div>
                            
                            <div class="alert alert-warning">
                                <h6>Auto-Approval:</h6>
                                <p class="mb-0">
                                    When enabled, commissions older than the specified days will be 
                                    automatically approved. This reduces manual work but ensure you're 
                                    comfortable with automatic approvals.
                                </p>
                            </div>
                            
                            <div class="alert alert-success">
                                <h6>Best Practice:</h6>
                                <p class="mb-0">
                                    Set auto-approval to 7-30 days to allow time for order verification 
                                    and potential refunds before approving commissions.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Initialize tabs on page load
document.addEventListener('DOMContentLoaded', function() {
    // Initialize all tab triggers
    const triggerTabList = document.querySelectorAll('[data-mdb-toggle="tab"]');
    triggerTabList.forEach(triggerEl => {
        const tabTrigger = new mdb.Tab(triggerEl);
        
        triggerEl.addEventListener('click', event => {
            event.preventDefault();
            tabTrigger.show();
        });
    });
    
    // Handle hash navigation
    if (window.location.hash) {
        const hash = window.location.hash;
        const tabTrigger = document.querySelector(`[data-mdb-toggle="tab"][href="${hash}"]`);
        if (tabTrigger) {
            const tab = new mdb.Tab(tabTrigger);
            tab.show();
        }
    }
});

function toggleAll(checkbox) {
    const checkboxes = document.querySelectorAll('.commission-checkbox');
    checkboxes.forEach(cb => cb.checked = checkbox.checked);
}

function selectAll() {
    const checkboxes = document.querySelectorAll('.commission-checkbox');
    checkboxes.forEach(cb => cb.checked = true);
    document.getElementById('select_all').checked = true;
}

function deselectAll() {
    const checkboxes = document.querySelectorAll('.commission-checkbox');
    checkboxes.forEach(cb => cb.checked = false);
    document.getElementById('select_all').checked = false;
}
</script>

<?php include 'includes/admin-footer.php'; ?>
