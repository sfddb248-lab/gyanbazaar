<?php
require_once '../config/config.php';
$pageTitle = 'Manage Coupons - Admin';

$success = '';
$error = '';

// Check for success message from redirect
if (isset($_GET['success'])) {
    $success = $_GET['success'];
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM coupons WHERE id = $id");
    $success = 'Coupon deleted successfully';
}

// Handle add/edit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $code = strtoupper(trim($_POST['code']));
        $type = trim($_POST['type']);
        $value = (float)$_POST['value'];
        $minPurchase = isset($_POST['min_purchase']) ? (float)$_POST['min_purchase'] : 0;
        $usageLimit = !empty($_POST['usage_limit']) ? (int)$_POST['usage_limit'] : null;
        $expiryDate = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;
        $status = trim($_POST['status']);
        
        // Validation
        if (empty($code)) {
            throw new Exception('Coupon code is required');
        }
        if (!in_array($type, ['flat', 'percentage'])) {
            throw new Exception('Invalid discount type');
        }
        if ($value <= 0) {
            throw new Exception('Discount value must be greater than 0');
        }
        if ($type === 'percentage' && $value > 100) {
            throw new Exception('Percentage discount cannot exceed 100%');
        }
        
        if ($id > 0) {
            // Update existing coupon
            $stmt = $conn->prepare("UPDATE coupons SET code=?, type=?, value=?, min_purchase=?, usage_limit=?, expiry_date=?, status=? WHERE id=?");
            $stmt->bind_param("ssddissi", $code, $type, $value, $minPurchase, $usageLimit, $expiryDate, $status, $id);
            
            if ($stmt->execute()) {
                $success = 'Coupon updated successfully!';
            } else {
                throw new Exception('Failed to update coupon: ' . $stmt->error);
            }
        } else {
            // Check if code already exists
            $checkStmt = $conn->prepare("SELECT id FROM coupons WHERE code = ?");
            $checkStmt->bind_param("s", $code);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            
            if ($checkResult->num_rows > 0) {
                throw new Exception('Coupon code already exists');
            }
            
            // Insert new coupon
            $stmt = $conn->prepare("INSERT INTO coupons (code, type, value, min_purchase, usage_limit, expiry_date, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssddiis", $code, $type, $value, $minPurchase, $usageLimit, $expiryDate, $status);
            
            if ($stmt->execute()) {
                $success = 'Coupon added successfully!';
            } else {
                throw new Exception('Failed to add coupon: ' . $stmt->error);
            }
        }
        
        // Redirect to avoid form resubmission
        header('Location: coupons.php?success=' . urlencode($success));
        exit;
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get coupons
$coupons = $conn->query("SELECT * FROM coupons ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);

include 'includes/admin-header.php';
?>

<div class="container-fluid my-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-tags"></i> Manage Coupons</h2>
        <button class="btn btn-primary" data-mdb-toggle="modal" data-mdb-target="#couponModal" onclick="resetForm()">
            <i class="fas fa-plus"></i> Add Coupon
        </button>
    </div>
    
    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
            <button type="button" class="btn-close" data-mdb-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-mdb-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Type</th>
                            <th>Value</th>
                            <th>Min Purchase</th>
                            <th>Usage</th>
                            <th>Expiry</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($coupons as $coupon): ?>
                        <tr>
                            <td><strong><?php echo $coupon['code']; ?></strong></td>
                            <td><?php echo ucfirst($coupon['type']); ?></td>
                            <td><?php echo $coupon['type'] == 'percentage' ? $coupon['value'] . '%' : formatCurrency($coupon['value']); ?></td>
                            <td><?php echo formatCurrency($coupon['min_purchase']); ?></td>
                            <td><?php echo $coupon['used_count']; ?> / <?php echo $coupon['usage_limit'] ?? '∞'; ?></td>
                            <td><?php echo $coupon['expiry_date'] ? date('M d, Y', strtotime($coupon['expiry_date'])) : 'No expiry'; ?></td>
                            <td>
                                <span class="badge bg-<?php echo $coupon['status'] == 'active' ? 'success' : 'secondary'; ?>">
                                    <?php echo ucfirst($coupon['status']); ?>
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-primary" onclick='editCoupon(<?php echo json_encode($coupon); ?>)'>
                                    <i class="fas fa-edit"></i>
                                </button>
                                <a href="?delete=<?php echo $coupon['id']; ?>" 
                                   class="btn btn-sm btn-danger" 
                                   onclick="return confirm('Delete this coupon?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Coupon Modal -->
<div class="modal fade" id="couponModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Add Coupon</h5>
                <button type="button" class="btn-close" data-mdb-dismiss="modal"></button>
            </div>
            <form method="POST" action="" id="couponForm">
                <div class="modal-body">
                    <input type="hidden" name="id" id="couponId">
                    
                    <div class="mb-3">
                        <label for="code" class="form-label">Coupon Code *</label>
                        <input type="text" id="code" name="code" class="form-control" required placeholder="e.g., SAVE20">
                    </div>
                    
                    <div class="mb-3">
                        <label for="type" class="form-label">Discount Type *</label>
                        <select name="type" id="type" class="form-select" required>
                            <option value="flat">Flat Discount (₹)</option>
                            <option value="percentage">Percentage Discount (%)</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="value" class="form-label">Discount Value *</label>
                        <input type="number" id="value" name="value" class="form-control" step="0.01" min="0" required placeholder="e.g., 100 or 20">
                    </div>
                    
                    <div class="mb-3">
                        <label for="min_purchase" class="form-label">Minimum Purchase Amount</label>
                        <input type="number" id="min_purchase" name="min_purchase" class="form-control" step="0.01" min="0" value="0" placeholder="0 for no minimum">
                    </div>
                    
                    <div class="mb-3">
                        <label for="usage_limit" class="form-label">Usage Limit</label>
                        <input type="number" id="usage_limit" name="usage_limit" class="form-control" min="1" placeholder="Leave empty for unlimited">
                    </div>
                    
                    <div class="mb-3">
                        <label for="expiry_date" class="form-label">Expiry Date</label>
                        <input type="date" id="expiry_date" name="expiry_date" class="form-control" min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="status" class="form-label">Status *</label>
                        <select name="status" id="status" class="form-select" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-mdb-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Coupon
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Initialize modal
let couponModal;
document.addEventListener('DOMContentLoaded', function() {
    couponModal = new mdb.Modal(document.getElementById('couponModal'));
});

function resetForm() {
    document.getElementById('modalTitle').textContent = 'Add Coupon';
    document.getElementById('couponForm').reset();
    document.getElementById('couponId').value = '';
    document.getElementById('code').value = '';
    document.getElementById('type').value = 'flat';
    document.getElementById('value').value = '';
    document.getElementById('min_purchase').value = '0';
    document.getElementById('usage_limit').value = '';
    document.getElementById('expiry_date').value = '';
    document.getElementById('status').value = 'active';
}

function editCoupon(coupon) {
    document.getElementById('modalTitle').textContent = 'Edit Coupon';
    document.getElementById('couponId').value = coupon.id;
    document.getElementById('code').value = coupon.code;
    document.getElementById('type').value = coupon.type;
    document.getElementById('value').value = coupon.value;
    document.getElementById('min_purchase').value = coupon.min_purchase;
    document.getElementById('usage_limit').value = coupon.usage_limit || '';
    document.getElementById('expiry_date').value = coupon.expiry_date || '';
    document.getElementById('status').value = coupon.status;
    
    if (couponModal) {
        couponModal.show();
    } else {
        new mdb.Modal(document.getElementById('couponModal')).show();
    }
}

// Form validation
document.getElementById('couponForm').addEventListener('submit', function(e) {
    const value = parseFloat(document.getElementById('value').value);
    const type = document.getElementById('type').value;
    
    if (type === 'percentage' && value > 100) {
        e.preventDefault();
        alert('Percentage discount cannot be more than 100%');
        return false;
    }
    
    if (value <= 0) {
        e.preventDefault();
        alert('Discount value must be greater than 0');
        return false;
    }
    
    return true;
});
</script>

<?php include 'includes/admin-footer.php'; ?>
