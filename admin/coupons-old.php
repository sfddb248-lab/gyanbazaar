<?php
require_once '../config/config.php';
$pageTitle = 'Manage Coupons - Admin';

$success = '';

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM coupons WHERE id = $id");
    $success = 'Coupon deleted successfully';
}

// Handle add/edit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $code = strtoupper(clean($_POST['code']));
    $type = clean($_POST['type']);
    $value = (float)$_POST['value'];
    $minPurchase = (float)$_POST['min_purchase'];
    $usageLimit = $_POST['usage_limit'] ? (int)$_POST['usage_limit'] : null;
    $expiryDate = $_POST['expiry_date'] ?: null;
    $status = clean($_POST['status']);
    
    if ($id > 0) {
        $stmt = $conn->prepare("UPDATE coupons SET code=?, type=?, value=?, min_purchase=?, usage_limit=?, expiry_date=?, status=? WHERE id=?");
        $stmt->bind_param("ssddissi", $code, $type, $value, $minPurchase, $usageLimit, $expiryDate, $status, $id);
        $stmt->execute();
        $success = 'Coupon updated successfully';
    } else {
        $stmt = $conn->prepare("INSERT INTO coupons (code, type, value, min_purchase, usage_limit, expiry_date, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssddiis", $code, $type, $value, $minPurchase, $usageLimit, $expiryDate, $status);
        $stmt->execute();
        $success = 'Coupon added successfully';
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
            <i class="fas fa-check-circle"></i> <?php echo $success; ?>
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
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="id" id="couponId">
                    
                    <div class="form-outline mb-3">
                        <input type="text" id="code" name="code" class="form-control" required>
                        <label class="form-label" for="code">Coupon Code</label>
                    </div>
                    
                    <select name="type" id="type" class="form-select mb-3" required>
                        <option value="flat">Flat Discount</option>
                        <option value="percentage">Percentage Discount</option>
                    </select>
                    
                    <div class="form-outline mb-3">
                        <input type="number" id="value" name="value" class="form-control" step="0.01" required>
                        <label class="form-label" for="value">Discount Value</label>
                    </div>
                    
                    <div class="form-outline mb-3">
                        <input type="number" id="min_purchase" name="min_purchase" class="form-control" step="0.01" value="0">
                        <label class="form-label" for="min_purchase">Minimum Purchase</label>
                    </div>
                    
                    <div class="form-outline mb-3">
                        <input type="number" id="usage_limit" name="usage_limit" class="form-control">
                        <label class="form-label" for="usage_limit">Usage Limit (Optional)</label>
                    </div>
                    
                    <div class="form-outline mb-3">
                        <input type="date" id="expiry_date" name="expiry_date" class="form-control">
                        <label class="form-label" for="expiry_date">Expiry Date (Optional)</label>
                    </div>
                    
                    <select name="status" class="form-select mb-3" required>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-mdb-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Coupon</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function resetForm() {
    document.getElementById('modalTitle').textContent = 'Add Coupon';
    document.getElementById('couponId').value = '';
    document.querySelector('form').reset();
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
    document.querySelector('[name="status"]').value = coupon.status;
    
    new mdb.Modal(document.getElementById('couponModal')).show();
}
</script>

<?php include 'includes/admin-footer.php'; ?>
