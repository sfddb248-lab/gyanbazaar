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
    $affiliateId = intval($_POST['affiliate_id']);
    
    switch ($action) {
        case 'approve':
            $stmt = $conn->prepare("UPDATE affiliates SET status = 'active' WHERE id = ?");
            $stmt->bind_param("i", $affiliateId);
            $stmt->execute();
            $message = "Affiliate approved successfully";
            break;
            
        case 'suspend':
            $stmt = $conn->prepare("UPDATE affiliates SET status = 'suspended' WHERE id = ?");
            $stmt->bind_param("i", $affiliateId);
            $stmt->execute();
            $message = "Affiliate suspended successfully";
            break;
            
        case 'update_commission':
            $commissionType = $_POST['commission_type'];
            $commissionValue = floatval($_POST['commission_value']);
            $stmt = $conn->prepare("UPDATE affiliates SET commission_type = ?, commission_value = ? WHERE id = ?");
            $stmt->bind_param("sdi", $commissionType, $commissionValue, $affiliateId);
            $stmt->execute();
            $message = "Commission updated successfully";
            break;
    }
}

// Get all affiliates
$stmt = $conn->prepare("SELECT a.*, u.name, u.email FROM affiliates a JOIN users u ON a.user_id = u.id ORDER BY a.created_at DESC");
$stmt->execute();
$affiliates = $stmt->get_result();

include 'includes/admin-header.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-md-12">
            <h2>Affiliate Management</h2>
            <?php if (isset($message)): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Stats Cards -->
    <div class="row mb-4">
        <?php
        $statsQuery = $conn->query("SELECT 
            COUNT(*) as total_affiliates,
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_affiliates,
            SUM(total_earnings) as total_earnings,
            SUM(pending_earnings) as pending_earnings
            FROM affiliates");
        $stats = $statsQuery->fetch_assoc();
        ?>
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h6>Total Affiliates</h6>
                    <h3><?php echo $stats['total_affiliates']; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6>Active Affiliates</h6>
                    <h3><?php echo $stats['active_affiliates']; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h6>Total Earnings Paid</h6>
                    <h3>₹<?php echo number_format($stats['total_earnings'], 2); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h6>Pending Earnings</h6>
                    <h3>₹<?php echo number_format($stats['pending_earnings'], 2); ?></h3>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Affiliates Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Referral Code</th>
                            <th>Commission</th>
                            <th>Earnings</th>
                            <th>Referrals</th>
                            <th>Sales</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($affiliate = $affiliates->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $affiliate['id']; ?></td>
                                <td><?php echo htmlspecialchars($affiliate['name']); ?></td>
                                <td><?php echo htmlspecialchars($affiliate['email']); ?></td>
                                <td>
                                    <code><?php echo $affiliate['referral_code']; ?></code>
                                </td>
                                <td>
                                    <?php echo $affiliate['commission_value']; ?><?php echo $affiliate['commission_type'] === 'percentage' ? '%' : ' ₹'; ?>
                                </td>
                                <td>
                                    <?php if ($affiliate['total_earnings'] > 0 || $affiliate['pending_earnings'] > 0): ?>
                                        <strong class="text-success">₹<?php echo number_format($affiliate['total_earnings'], 2); ?></strong><br>
                                        <small class="text-warning">Pending: ₹<?php echo number_format($affiliate['pending_earnings'], 2); ?></small>
                                    <?php else: ?>
                                        <span class="text-muted">₹0.00</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($affiliate['total_referrals'] > 0): ?>
                                        <span class="badge bg-info"><?php echo $affiliate['total_referrals']; ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">0</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($affiliate['total_sales'] > 0): ?>
                                        <span class="badge bg-success"><?php echo $affiliate['total_sales']; ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">0</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $statusClass = [
                                        'active' => 'success',
                                        'inactive' => 'secondary',
                                        'suspended' => 'danger'
                                    ];
                                    ?>
                                    <span class="badge bg-<?php echo $statusClass[$affiliate['status']]; ?>">
                                        <?php echo ucfirst($affiliate['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-info" onclick="viewAffiliate(<?php echo $affiliate['id']; ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-primary" onclick="editCommission(<?php echo $affiliate['id']; ?>, '<?php echo $affiliate['commission_type']; ?>', <?php echo $affiliate['commission_value']; ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <?php if ($affiliate['status'] !== 'active'): ?>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="action" value="approve">
                                                <input type="hidden" name="affiliate_id" value="<?php echo $affiliate['id']; ?>">
                                                <button type="submit" class="btn btn-success">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="action" value="suspend">
                                                <input type="hidden" name="affiliate_id" value="<?php echo $affiliate['id']; ?>">
                                                <button type="submit" class="btn btn-warning">
                                                    <i class="fas fa-ban"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Edit Commission Modal -->
<div class="modal fade" id="editCommissionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Commission</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_commission">
                    <input type="hidden" name="affiliate_id" id="edit_affiliate_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Commission Type</label>
                        <select name="commission_type" id="edit_commission_type" class="form-select">
                            <option value="percentage">Percentage</option>
                            <option value="flat">Flat Amount</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Commission Value</label>
                        <input type="number" name="commission_value" id="edit_commission_value" 
                               class="form-control" step="0.01" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Commission</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editCommission(id, type, value) {
    document.getElementById('edit_affiliate_id').value = id;
    document.getElementById('edit_commission_type').value = type;
    document.getElementById('edit_commission_value').value = value;
    new bootstrap.Modal(document.getElementById('editCommissionModal')).show();
}

function viewAffiliate(id) {
    window.location.href = 'affiliate-details.php?id=' + id;
}
</script>

<?php include 'includes/admin-footer.php'; ?>
