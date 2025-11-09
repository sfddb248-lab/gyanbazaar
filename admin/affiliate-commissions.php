<?php
require_once '../config/config.php';
require_once '../includes/affiliate-functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Handle actions
if (isset($_POST['action'])) {
    $action = $_POST['action'];
    $commissionId = intval($_POST['commission_id']);
    
    switch ($action) {
        case 'approve':
            approveCommission($commissionId);
            $message = "Commission approved successfully";
            break;
            
        case 'cancel':
            $stmt = $conn->prepare("UPDATE affiliate_commissions SET status = 'cancelled' WHERE id = ?");
            $stmt->bind_param("i", $commissionId);
            $stmt->execute();
            $message = "Commission cancelled successfully";
            break;
    }
}

// Get filter
$statusFilter = isset($_GET['status']) ? $_GET['status'] : 'all';
$whereClause = $statusFilter !== 'all' ? "WHERE c.status = ?" : "";

// Get commissions
$query = "SELECT c.*, a.referral_code, u.name as affiliate_name, u.email as affiliate_email, o.order_number 
          FROM affiliate_commissions c 
          JOIN affiliates a ON c.affiliate_id = a.id 
          JOIN users u ON a.user_id = u.id 
          JOIN orders o ON c.order_id = o.id 
          $whereClause
          ORDER BY c.created_at DESC";

if ($statusFilter !== 'all') {
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $statusFilter);
    $stmt->execute();
    $commissions = $stmt->get_result();
} else {
    $commissions = $conn->query($query);
}

include 'includes/admin-header.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-md-12">
            <h2>Affiliate Commissions</h2>
            <?php if (isset($message)): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Stats Cards -->
    <div class="row mb-4">
        <?php
        $statsQuery = $conn->query("SELECT 
            SUM(CASE WHEN status = 'pending' THEN commission_amount ELSE 0 END) as pending_amount,
            SUM(CASE WHEN status = 'approved' THEN commission_amount ELSE 0 END) as approved_amount,
            SUM(CASE WHEN status = 'paid' THEN commission_amount ELSE 0 END) as paid_amount,
            COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_count
            FROM affiliate_commissions");
        $stats = $statsQuery->fetch_assoc();
        ?>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h6>Pending Commissions</h6>
                    <h3>₹<?php echo number_format($stats['pending_amount'], 2); ?></h3>
                    <small><?php echo $stats['pending_count']; ?> transactions</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6>Approved Commissions</h6>
                    <h3>₹<?php echo number_format($stats['approved_amount'], 2); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h6>Paid Commissions</h6>
                    <h3>₹<?php echo number_format($stats['paid_amount'], 2); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h6>Total Commissions</h6>
                    <h3>₹<?php echo number_format($stats['pending_amount'] + $stats['approved_amount'] + $stats['paid_amount'], 2); ?></h3>
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
                        <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All</option>
                        <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="approved" <?php echo $statusFilter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="paid" <?php echo $statusFilter === 'paid' ? 'selected' : ''; ?>>Paid</option>
                        <option value="cancelled" <?php echo $statusFilter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Commissions Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Date</th>
                            <th>Affiliate</th>
                            <th>Order #</th>
                            <th>Order Amount</th>
                            <th>Commission</th>
                            <th>Rate</th>
                            <th>Level</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($commission = $commissions->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $commission['id']; ?></td>
                                <td><?php echo date('M d, Y', strtotime($commission['created_at'])); ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($commission['affiliate_name']); ?></strong><br>
                                    <small class="text-muted"><?php echo $commission['referral_code']; ?></small>
                                </td>
                                <td><?php echo $commission['order_number']; ?></td>
                                <td>₹<?php echo number_format($commission['order_amount'], 2); ?></td>
                                <td><strong>₹<?php echo number_format($commission['commission_amount'], 2); ?></strong></td>
                                <td>
                                    <?php echo $commission['commission_rate']; ?><?php echo $commission['commission_type'] === 'percentage' ? '%' : ' ₹'; ?>
                                </td>
                                <td>
                                    <?php if ($commission['level'] > 1): ?>
                                        <span class="badge bg-info">Level <?php echo $commission['level']; ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-primary">Direct</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $statusClass = [
                                        'pending' => 'warning',
                                        'approved' => 'success',
                                        'paid' => 'info',
                                        'cancelled' => 'danger'
                                    ];
                                    ?>
                                    <span class="badge bg-<?php echo $statusClass[$commission['status']]; ?>">
                                        <?php echo ucfirst($commission['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($commission['status'] === 'pending'): ?>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="action" value="approve">
                                            <input type="hidden" name="commission_id" value="<?php echo $commission['id']; ?>">
                                            <button type="submit" class="btn btn-success btn-sm">
                                                <i class="fas fa-check"></i> Approve
                                            </button>
                                        </form>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="action" value="cancel">
                                            <input type="hidden" name="commission_id" value="<?php echo $commission['id']; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm" 
                                                    onclick="return confirm('Are you sure?')">
                                                <i class="fas fa-times"></i> Cancel
                                            </button>
                                        </form>
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

<?php include 'includes/admin-footer.php'; ?>
