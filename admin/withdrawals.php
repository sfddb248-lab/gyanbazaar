<?php
require_once '../config/config.php';
requireAdmin();

$pageTitle = 'Withdrawal Management - Admin';
$currentPage = 'withdrawals';

// Handle actions
$message = '';
$messageType = '';

// Approve withdrawal
if (isset($_POST['approve_withdrawal'])) {
    $payoutId = (int)$_POST['payout_id'];
    $transactionId = clean($_POST['transaction_id']);
    $taxAmount = (float)$_POST['tax_amount'];
    $taxPercentage = (float)$_POST['tax_percentage'];
    
    // Get payout details
    $payout = $conn->query("SELECT * FROM affiliate_payouts WHERE id = $payoutId")->fetch_assoc();
    
    if ($payout) {
        $finalAmount = $payout['amount'] - $taxAmount;
        
        // Update payout
        $stmt = $conn->prepare("UPDATE affiliate_payouts SET status = 'completed', transaction_id = ?, tax_amount = ?, tax_percentage = ?, final_amount = ?, processed_at = NOW(), completed_at = NOW() WHERE id = ?");
        $stmt->bind_param("sdddi", $transactionId, $taxAmount, $taxPercentage, $finalAmount, $payoutId);
        $stmt->execute();
        
        // Update affiliate earnings
        $conn->query("UPDATE affiliates SET pending_earnings = pending_earnings - {$payout['amount']}, paid_earnings = paid_earnings + {$payout['amount']} WHERE id = {$payout['affiliate_id']}");
        
        $message = "Withdrawal approved successfully! ₹" . number_format($finalAmount, 2) . " paid after tax.";
        $messageType = "success";
    }
}

// Reject withdrawal
if (isset($_POST['reject_withdrawal'])) {
    $payoutId = (int)$_POST['payout_id'];
    $rejectReason = clean($_POST['reject_reason']);
    
    $stmt = $conn->prepare("UPDATE affiliate_payouts SET status = 'rejected', reject_reason = ?, processed_at = NOW() WHERE id = ?");
    $stmt->bind_param("si", $rejectReason, $payoutId);
    $stmt->execute();
    
    $message = "Withdrawal rejected.";
    $messageType = "warning";
}

// Update tax settings
if (isset($_POST['update_tax_settings'])) {
    $tdsPercentage = (float)$_POST['tds_percentage'];
    $minWithdrawal = (float)$_POST['min_withdrawal'];
    $maxWithdrawal = (float)$_POST['max_withdrawal'];
    
    $conn->query("INSERT INTO affiliate_settings (setting_key, setting_value) VALUES ('tds_percentage', '$tdsPercentage') ON DUPLICATE KEY UPDATE setting_value = '$tdsPercentage'");
    $conn->query("INSERT INTO affiliate_settings (setting_key, setting_value) VALUES ('min_payout_amount', '$minWithdrawal') ON DUPLICATE KEY UPDATE setting_value = '$minWithdrawal'");
    $conn->query("INSERT INTO affiliate_settings (setting_key, setting_value) VALUES ('max_payout_amount', '$maxWithdrawal') ON DUPLICATE KEY UPDATE setting_value = '$maxWithdrawal'");
    
    $message = "Tax settings updated successfully!";
    $messageType = "success";
}

// Get withdrawal requests
$withdrawals = $conn->query("
    SELECT 
        ap.*,
        a.referral_code,
        u.name as affiliate_name,
        u.email as affiliate_email,
        a.pending_earnings,
        a.total_earnings
    FROM affiliate_payouts ap
    JOIN affiliates a ON ap.affiliate_id = a.id
    JOIN users u ON a.user_id = u.id
    ORDER BY 
        CASE ap.status 
            WHEN 'pending' THEN 1 
            WHEN 'processing' THEN 2 
            WHEN 'completed' THEN 3 
            WHEN 'rejected' THEN 4 
        END,
        ap.requested_at DESC
")->fetch_all(MYSQLI_ASSOC);

// Get tax settings
$tdsPercentage = (float)getAffiliateSetting('tds_percentage', 10);
$minWithdrawal = (float)getAffiliateSetting('min_payout_amount', 500);
$maxWithdrawal = (float)getAffiliateSetting('max_payout_amount', 50000);

// Calculate statistics
$pendingCount = count(array_filter($withdrawals, fn($w) => $w['status'] == 'pending'));
$pendingAmount = array_sum(array_map(fn($w) => $w['status'] == 'pending' ? $w['amount'] : 0, $withdrawals));
$completedAmount = array_sum(array_map(fn($w) => $w['status'] == 'completed' ? $w['amount'] : 0, $withdrawals));
$totalTax = array_sum(array_map(fn($w) => $w['status'] == 'completed' ? $w['tax_amount'] : 0, $withdrawals));

include 'includes/admin-header.php';
?>

<style>
.withdrawal-card {
    border: 2px solid #e0e0e0;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 15px;
    transition: all 0.3s;
    background: white;
}

.withdrawal-card.pending {
    border-left: 5px solid #ffc107;
    background: #fffbf0;
}

.withdrawal-card.completed {
    border-left: 5px solid #28a745;
}

.withdrawal-card.rejected {
    border-left: 5px solid #dc3545;
    opacity: 0.7;
}

.amount-display {
    font-size: 32px;
    font-weight: bold;
    color: #1266f1;
}

.tax-calculator {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    border-left: 4px solid #1266f1;
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
            <h2><i class="fas fa-money-check-alt"></i> Withdrawal Management</h2>
            <p class="text-muted">Manage affiliate withdrawal requests and tax settings</p>
        </div>
    </div>
    
    <!-- Statistics -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body text-center">
                    <h6>Pending Requests</h6>
                    <h3><?php echo $pendingCount; ?></h3>
                    <p class="mb-0">₹<?php echo number_format($pendingAmount, 2); ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h6>Total Paid</h6>
                    <h3>₹<?php echo number_format($completedAmount, 0); ?></h3>
                    <p class="mb-0"><?php echo count(array_filter($withdrawals, fn($w) => $w['status'] == 'completed')); ?> withdrawals</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <h6>Total Tax Collected</h6>
                    <h3>₹<?php echo number_format($totalTax, 0); ?></h3>
                    <p class="mb-0">TDS deducted</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h6>Current TDS Rate</h6>
                    <h3><?php echo $tdsPercentage; ?>%</h3>
                    <p class="mb-0">Tax deduction</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Tabs -->
    <ul class="nav nav-tabs mb-4" role="tablist">
        <li class="nav-item">
            <a class="nav-link active" data-mdb-toggle="tab" href="#pending-tab">
                <i class="fas fa-clock"></i> Pending (<?php echo $pendingCount; ?>)
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-mdb-toggle="tab" href="#all-tab">
                <i class="fas fa-list"></i> All Withdrawals
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-mdb-toggle="tab" href="#settings-tab">
                <i class="fas fa-cog"></i> Tax Settings
            </a>
        </li>
    </ul>
    
    <div class="tab-content">
        <!-- Pending Tab -->
        <div class="tab-pane fade show active" id="pending-tab">
            <?php 
            $pendingWithdrawals = array_filter($withdrawals, fn($w) => $w['status'] == 'pending');
            if (count($pendingWithdrawals) > 0): 
            ?>
                <?php foreach ($pendingWithdrawals as $withdrawal): ?>
                <div class="withdrawal-card pending">
                    <div class="row align-items-center">
                        <div class="col-md-3">
                            <h6 class="mb-1"><strong><?php echo htmlspecialchars($withdrawal['affiliate_name']); ?></strong></h6>
                            <small class="text-muted"><?php echo htmlspecialchars($withdrawal['affiliate_email']); ?></small><br>
                            <span class="badge bg-info"><?php echo $withdrawal['referral_code']; ?></span>
                        </div>
                        <div class="col-md-2 text-center">
                            <div class="amount-display">₹<?php echo number_format($withdrawal['amount'], 0); ?></div>
                            <small class="text-muted">Requested Amount</small>
                        </div>
                        <div class="col-md-2">
                            <p class="mb-1"><strong>Payment Method:</strong></p>
                            <p class="mb-1"><?php echo ucfirst($withdrawal['payment_method']); ?></p>
                            <small class="text-muted"><?php echo date('M d, Y H:i', strtotime($withdrawal['requested_at'])); ?></small>
                        </div>
                        <div class="col-md-2">
                            <p class="mb-1"><strong>Available Balance:</strong></p>
                            <p class="mb-0 text-success">₹<?php echo number_format($withdrawal['pending_earnings'], 2); ?></p>
                        </div>
                        <div class="col-md-3 text-end">
                            <button class="btn btn-success btn-sm mb-2" onclick="approveWithdrawal(<?php echo htmlspecialchars(json_encode($withdrawal)); ?>)">
                                <i class="fas fa-check"></i> Approve
                            </button>
                            <button class="btn btn-danger btn-sm mb-2" onclick="rejectWithdrawal(<?php echo $withdrawal['id']; ?>)">
                                <i class="fas fa-times"></i> Reject
                            </button>
                        </div>
                    </div>
                    <?php if ($withdrawal['payment_details']): ?>
                    <div class="mt-3 p-3 bg-light rounded">
                        <strong>Payment Details:</strong><br>
                        <?php echo nl2br(htmlspecialchars($withdrawal['payment_details'])); ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> No pending withdrawal requests
                </div>
            <?php endif; ?>
        </div>
        
        <!-- All Withdrawals Tab -->
        <div class="tab-pane fade" id="all-tab">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Affiliate</th>
                            <th>Amount</th>
                            <th>Tax</th>
                            <th>Final Amount</th>
                            <th>Method</th>
                            <th>Status</th>
                            <th>Transaction ID</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($withdrawals as $withdrawal): ?>
                        <tr>
                            <td><?php echo $withdrawal['id']; ?></td>
                            <td>
                                <?php echo htmlspecialchars($withdrawal['affiliate_name']); ?><br>
                                <small class="text-muted"><?php echo $withdrawal['referral_code']; ?></small>
                            </td>
                            <td>₹<?php echo number_format($withdrawal['amount'], 2); ?></td>
                            <td>
                                <?php if ($withdrawal['tax_amount']): ?>
                                    ₹<?php echo number_format($withdrawal['tax_amount'], 2); ?>
                                    <small class="text-muted">(<?php echo $withdrawal['tax_percentage']; ?>%)</small>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($withdrawal['final_amount']): ?>
                                    <strong class="text-success">₹<?php echo number_format($withdrawal['final_amount'], 2); ?></strong>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td><?php echo ucfirst($withdrawal['payment_method']); ?></td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo $withdrawal['status'] == 'completed' ? 'success' : 
                                        ($withdrawal['status'] == 'pending' ? 'warning' : 
                                        ($withdrawal['status'] == 'rejected' ? 'danger' : 'info')); 
                                ?>">
                                    <?php echo ucfirst($withdrawal['status']); ?>
                                </span>
                            </td>
                            <td><?php echo $withdrawal['transaction_id'] ?? '-'; ?></td>
                            <td><?php echo date('M d, Y', strtotime($withdrawal['requested_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Tax Settings Tab -->
        <div class="tab-pane fade" id="settings-tab">
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h5><i class="fas fa-percentage"></i> Tax & Withdrawal Settings</h5>
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label">TDS Percentage (%)</label>
                                    <input type="number" class="form-control" name="tds_percentage" 
                                           value="<?php echo $tdsPercentage; ?>" min="0" max="100" step="0.01" required>
                                    <small class="text-muted">Tax Deducted at Source on withdrawals</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Minimum Withdrawal Amount (₹)</label>
                                    <input type="number" class="form-control" name="min_withdrawal" 
                                           value="<?php echo $minWithdrawal; ?>" min="0" step="0.01" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Maximum Withdrawal Amount (₹)</label>
                                    <input type="number" class="form-control" name="max_withdrawal" 
                                           value="<?php echo $maxWithdrawal; ?>" min="0" step="0.01" required>
                                </div>
                                
                                <button type="submit" name="update_tax_settings" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Save Settings
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h5><i class="fas fa-calculator"></i> Tax Calculator</h5>
                            <div class="tax-calculator">
                                <label>Withdrawal Amount (₹)</label>
                                <input type="number" class="form-control mb-3" id="calc_amount" value="1000" oninput="calculateTax()">
                                
                                <table class="table table-sm">
                                    <tr>
                                        <td>Requested Amount:</td>
                                        <td class="text-end"><strong id="calc_requested">₹1,000.00</strong></td>
                                    </tr>
                                    <tr>
                                        <td>TDS (<?php echo $tdsPercentage; ?>%):</td>
                                        <td class="text-end text-danger"><strong id="calc_tax">-₹<?php echo number_format(1000 * $tdsPercentage / 100, 2); ?></strong></td>
                                    </tr>
                                    <tr class="table-active">
                                        <td><strong>Final Amount:</strong></td>
                                        <td class="text-end"><strong class="text-success" id="calc_final">₹<?php echo number_format(1000 - (1000 * $tdsPercentage / 100), 2); ?></strong></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Approve Modal -->
<div class="modal fade" id="approveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">Approve Withdrawal</h5>
                    <button type="button" class="btn-close btn-close-white" data-mdb-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="payout_id" id="approve_payout_id">
                    
                    <div class="mb-3">
                        <label>Affiliate</label>
                        <input type="text" class="form-control" id="approve_affiliate" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label>Requested Amount</label>
                        <input type="text" class="form-control" id="approve_amount" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label>TDS Percentage (%)</label>
                        <input type="number" class="form-control" name="tax_percentage" id="approve_tax_percentage" 
                               value="<?php echo $tdsPercentage; ?>" min="0" max="100" step="0.01" required 
                               oninput="updateApprovalCalculation()">
                    </div>
                    
                    <div class="mb-3">
                        <label>Tax Amount (₹)</label>
                        <input type="number" class="form-control" name="tax_amount" id="approve_tax_amount" 
                               step="0.01" required readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label>Final Amount to Pay (₹)</label>
                        <input type="text" class="form-control text-success fw-bold" id="approve_final_amount" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label>Transaction ID / Reference Number</label>
                        <input type="text" class="form-control" name="transaction_id" required 
                               placeholder="Enter transaction ID or reference number">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-mdb-dismiss="modal">Cancel</button>
                    <button type="submit" name="approve_withdrawal" class="btn btn-success">
                        <i class="fas fa-check"></i> Approve & Pay
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Reject Withdrawal</h5>
                    <button type="button" class="btn-close btn-close-white" data-mdb-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="payout_id" id="reject_payout_id">
                    
                    <div class="mb-3">
                        <label>Reason for Rejection</label>
                        <textarea class="form-control" name="reject_reason" rows="3" required 
                                  placeholder="Enter reason for rejecting this withdrawal request"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-mdb-dismiss="modal">Cancel</button>
                    <button type="submit" name="reject_withdrawal" class="btn btn-danger">
                        <i class="fas fa-times"></i> Reject Request
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const tdsPercentage = <?php echo $tdsPercentage; ?>;

function calculateTax() {
    const amount = parseFloat(document.getElementById('calc_amount').value) || 0;
    const tax = amount * tdsPercentage / 100;
    const final = amount - tax;
    
    document.getElementById('calc_requested').textContent = '₹' + amount.toLocaleString('en-IN', {minimumFractionDigits: 2});
    document.getElementById('calc_tax').textContent = '-₹' + tax.toLocaleString('en-IN', {minimumFractionDigits: 2});
    document.getElementById('calc_final').textContent = '₹' + final.toLocaleString('en-IN', {minimumFractionDigits: 2});
}

function approveWithdrawal(withdrawal) {
    document.getElementById('approve_payout_id').value = withdrawal.id;
    document.getElementById('approve_affiliate').value = withdrawal.affiliate_name + ' (' + withdrawal.referral_code + ')';
    document.getElementById('approve_amount').value = '₹' + parseFloat(withdrawal.amount).toLocaleString('en-IN', {minimumFractionDigits: 2});
    
    updateApprovalCalculation();
    
    const modal = new mdb.Modal(document.getElementById('approveModal'));
    modal.show();
}

function updateApprovalCalculation() {
    const amountStr = document.getElementById('approve_amount').value.replace('₹', '').replace(/,/g, '');
    const amount = parseFloat(amountStr) || 0;
    const taxPercentage = parseFloat(document.getElementById('approve_tax_percentage').value) || 0;
    const tax = amount * taxPercentage / 100;
    const final = amount - tax;
    
    document.getElementById('approve_tax_amount').value = tax.toFixed(2);
    document.getElementById('approve_final_amount').value = '₹' + final.toLocaleString('en-IN', {minimumFractionDigits: 2});
}

function rejectWithdrawal(payoutId) {
    document.getElementById('reject_payout_id').value = payoutId;
    const modal = new mdb.Modal(document.getElementById('rejectModal'));
    modal.show();
}
</script>

<?php include 'includes/admin-footer.php'; ?>
