<?php
require_once '../config/config.php';
require_once '../includes/affiliate-functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Handle payout processing
if (isset($_POST['process_payout'])) {
    $payoutId = intval($_POST['payout_id']);
    $transactionId = $_POST['transaction_id'];
    $status = $_POST['status'];
    
    if (processPayout($payoutId, $transactionId, $status)) {
        $message = "Payout processed successfully";
    } else {
        $error = "Failed to process payout";
    }
}

// Get payouts
$statusFilter = isset($_GET['status']) ? $_GET['status'] : 'pending';
$whereClause = $statusFilter !== 'all' ? "WHERE p.status = ?" : "";

$query = "SELECT p.*, a.referral_code, u.name as affiliate_name, u.email as affiliate_email 
          FROM affiliate_payouts p 
          JOIN affiliates a ON p.affiliate_id = a.id 
          JOIN users u ON a.user_id = u.id 
          $whereClause
          ORDER BY p.requested_at DESC";

if ($statusFilter !== 'all') {
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $statusFilter);
    $stmt->execute();
    $payouts = $stmt->get_result();
} else {
    $payouts = $conn->query($query);
}

include 'includes/admin-header.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-md-12">
            <h2>Affiliate Payouts</h2>
            <?php if (isset($message)): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Stats Cards -->
    <div class="row mb-4">
        <?php
        $statsQuery = $conn->query("SELECT 
            SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END) as pending_amount,
            SUM(CASE WHEN status = 'processing' THEN amount ELSE 0 END) as processing_amount,
            SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as completed_amount,
            COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_count
            FROM affiliate_payouts");
        $stats = $statsQuery->fetch_assoc();
        ?>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h6>Pending Payouts</h6>
                    <h3>₹<?php echo number_format($stats['pending_amount'], 2); ?></h3>
                    <small><?php echo $stats['pending_count']; ?> requests</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h6>Processing</h6>
                    <h3>₹<?php echo number_format($stats['processing_amount'], 2); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6>Completed</h6>
                    <h3>₹<?php echo number_format($stats['completed_amount'], 2); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h6>Total Payouts</h6>
                    <h3>₹<?php echo number_format($stats['pending_amount'] + $stats['processing_amount'] + $stats['completed_amount'], 2); ?></h3>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filter -->
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="processing" <?php echo $statusFilter === 'processing' ? 'selected' : ''; ?>>Processing</option>
                        <option value="completed" <?php echo $statusFilter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="failed" <?php echo $statusFilter === 'failed' ? 'selected' : ''; ?>>Failed</option>
                        <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All</option>
                    </select>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Payouts Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Requested Date</th>
                            <th>Affiliate</th>
                            <th>Amount</th>
                            <th>Payment Method</th>
                            <th>Payment Details</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($payout = $payouts->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $payout['id']; ?></td>
                                <td><?php echo date('M d, Y H:i', strtotime($payout['requested_at'])); ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($payout['affiliate_name']); ?></strong><br>
                                    <small class="text-muted"><?php echo $payout['affiliate_email']; ?></small><br>
                                    <small class="text-muted">Code: <?php echo $payout['referral_code']; ?></small>
                                </td>
                                <td><strong>₹<?php echo number_format($payout['amount'], 2); ?></strong></td>
                                <td><?php echo ucfirst(str_replace('_', ' ', $payout['payment_method'])); ?></td>
                                <td>
                                    <small><?php echo nl2br(htmlspecialchars($payout['payment_details'])); ?></small>
                                </td>
                                <td>
                                    <?php
                                    $statusClass = [
                                        'pending' => 'warning',
                                        'processing' => 'info',
                                        'completed' => 'success',
                                        'failed' => 'danger'
                                    ];
                                    ?>
                                    <span class="badge bg-<?php echo $statusClass[$payout['status']]; ?>">
                                        <?php echo ucfirst($payout['status']); ?>
                                    </span>
                                    <?php if ($payout['transaction_id']): ?>
                                        <br><small class="text-muted">TXN: <?php echo $payout['transaction_id']; ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($payout['status'] === 'pending' || $payout['status'] === 'processing'): ?>
                                        <button class="btn btn-primary btn-sm" 
                                                onclick="processPayout(<?php echo $payout['id']; ?>, '<?php echo htmlspecialchars($payout['affiliate_name']); ?>', <?php echo $payout['amount']; ?>)">
                                            <i class="fas fa-check"></i> Process
                                        </button>
                                    <?php endif; ?>
                                    <?php if ($payout['notes']): ?>
                                        <button class="btn btn-info btn-sm" 
                                                onclick="alert('<?php echo htmlspecialchars($payout['notes']); ?>')">
                                            <i class="fas fa-sticky-note"></i>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Process Payout Modal -->
<div class="modal fade" id="processPayoutModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Process Payout</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="payout_id" id="payout_id">
                    
                    <div class="alert alert-info">
                        <strong>Affiliate:</strong> <span id="payout_affiliate"></span><br>
                        <strong>Amount:</strong> ₹<span id="payout_amount"></span>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Transaction ID</label>
                        <input type="text" name="transaction_id" class="form-control" required>
                        <small class="text-muted">Enter the payment transaction ID</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select" required>
                            <option value="processing">Processing</option>
                            <option value="completed">Completed</option>
                            <option value="failed">Failed</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="process_payout" class="btn btn-primary">Process Payout</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function processPayout(id, affiliate, amount) {
    document.getElementById('payout_id').value = id;
    document.getElementById('payout_affiliate').textContent = affiliate;
    document.getElementById('payout_amount').textContent = amount.toFixed(2);
    new bootstrap.Modal(document.getElementById('processPayoutModal')).show();
}
</script>

<?php include 'includes/admin-footer.php'; ?>
