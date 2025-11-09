<?php
require_once '../config/config.php';
requireAdmin();
$pageTitle = 'Verify Payments - Admin';

$success = '';
$error = '';

// Handle approve/reject
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $orderId = (int)$_POST['order_id'];
    $action = clean($_POST['action']);
    
    // Get order details
    $stmt = $conn->prepare("SELECT o.*, u.email as user_email, u.name as user_name FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = ?");
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    
    if ($order) {
        if ($action == 'approve') {
            // Approve payment
            $stmt = $conn->prepare("UPDATE orders SET payment_status = 'completed' WHERE id = ?");
            $stmt->bind_param("i", $orderId);
            
            if ($stmt->execute()) {
                // Send confirmation email to customer
                sendEmail($order['user_email'], 'Payment Approved - Order ' . $order['order_number'], 
                          "Great news! Your payment has been verified and approved.\n\nOrder Number: " . $order['order_number'] . "\nTransaction ID: " . $order['transaction_id'] . "\nAmount: " . formatCurrency($order['final_amount']) . "\n\nYou can now access your purchased products.");
                
                $success = 'Payment approved successfully! Customer has been notified.';
            } else {
                $error = 'Failed to approve payment.';
            }
        } elseif ($action == 'reject') {
            $reason = clean($_POST['reason']);
            
            // Reject payment
            $stmt = $conn->prepare("UPDATE orders SET payment_status = 'failed' WHERE id = ?");
            $stmt->bind_param("i", $orderId);
            
            if ($stmt->execute()) {
                // Send rejection email to customer
                sendEmail($order['user_email'], 'Payment Verification Failed - Order ' . $order['order_number'], 
                          "We were unable to verify your payment.\n\nOrder Number: " . $order['order_number'] . "\nTransaction ID: " . $order['transaction_id'] . "\nReason: " . $reason . "\n\nPlease contact support or try placing a new order.");
                
                $success = 'Payment rejected. Customer has been notified.';
            } else {
                $error = 'Failed to reject payment.';
            }
        }
    }
}

// Get pending payments
$pendingPayments = $conn->query("
    SELECT o.*, u.name as user_name, u.email as user_email 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    WHERE o.payment_status = 'pending' AND o.payment_method = 'upi' AND o.transaction_id IS NOT NULL
    ORDER BY o.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

// Get recent verified payments
$recentPayments = $conn->query("
    SELECT o.*, u.name as user_name 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    WHERE o.payment_method = 'upi' AND o.payment_status IN ('completed', 'failed')
    ORDER BY o.created_at DESC 
    LIMIT 10
")->fetch_all(MYSQLI_ASSOC);

include 'includes/admin-header.php';
?>

<div class="container-fluid my-4">
    <h2 class="mb-4"><i class="fas fa-check-circle"></i> Verify UPI Payments</h2>
    
    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            <button type="button" class="btn-close" data-mdb-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            <button type="button" class="btn-close" data-mdb-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <!-- Pending Payments -->
    <div class="card mb-4">
        <div class="card-header bg-warning text-dark">
            <h5 class="mb-0">
                <i class="fas fa-clock"></i> Pending Verification 
                <span class="badge bg-dark"><?php echo count($pendingPayments); ?></span>
            </h5>
        </div>
        <div class="card-body">
            <?php if (empty($pendingPayments)): ?>
                <div class="text-center text-muted py-4">
                    <i class="fas fa-check-circle fa-3x mb-3"></i>
                    <p>No pending payments to verify</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Transaction ID</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendingPayments as $payment): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($payment['order_number']); ?></strong>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($payment['user_name']); ?><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($payment['user_email']); ?></small>
                                </td>
                                <td>
                                    <strong class="text-success"><?php echo formatCurrency($payment['final_amount']); ?></strong>
                                </td>
                                <td>
                                    <code><?php echo htmlspecialchars($payment['transaction_id']); ?></code>
                                </td>
                                <td>
                                    <?php echo date('M d, Y H:i', strtotime($payment['created_at'])); ?>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-success" 
                                            onclick="approvePayment(<?php echo $payment['id']; ?>, '<?php echo htmlspecialchars($payment['order_number']); ?>')">
                                        <i class="fas fa-check"></i> Approve
                                    </button>
                                    <button class="btn btn-sm btn-danger" 
                                            onclick="rejectPayment(<?php echo $payment['id']; ?>, '<?php echo htmlspecialchars($payment['order_number']); ?>')">
                                        <i class="fas fa-times"></i> Reject
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Recent Verified Payments -->
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-history"></i> Recent Verified Payments</h5>
        </div>
        <div class="card-body">
            <?php if (empty($recentPayments)): ?>
                <p class="text-muted text-center">No verified payments yet</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Transaction ID</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentPayments as $payment): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($payment['order_number']); ?></td>
                                <td><?php echo htmlspecialchars($payment['user_name']); ?></td>
                                <td><?php echo formatCurrency($payment['final_amount']); ?></td>
                                <td><code><?php echo htmlspecialchars($payment['transaction_id']); ?></code></td>
                                <td>
                                    <span class="badge bg-<?php echo $payment['payment_status'] == 'completed' ? 'success' : 'danger'; ?>">
                                        <?php echo ucfirst($payment['payment_status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y H:i', strtotime($payment['created_at'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Approve Modal -->
<div class="modal fade" id="approveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">Approve Payment</h5>
                <button type="button" class="btn-close" data-mdb-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="order_id" id="approveOrderId">
                    <input type="hidden" name="action" value="approve">
                    <p>Are you sure you want to approve payment for order <strong id="approveOrderNumber"></strong>?</p>
                    <p class="text-muted">Customer will be notified and can access their products.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-mdb-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check"></i> Approve Payment
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
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Reject Payment</h5>
                <button type="button" class="btn-close" data-mdb-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="order_id" id="rejectOrderId">
                    <input type="hidden" name="action" value="reject">
                    <p>Reject payment for order <strong id="rejectOrderNumber"></strong>?</p>
                    <div class="form-outline mb-3">
                        <textarea name="reason" class="form-control" rows="3" required></textarea>
                        <label class="form-label">Reason for rejection</label>
                    </div>
                    <p class="text-muted">Customer will be notified with this reason.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-mdb-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-times"></i> Reject Payment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let approveModal, rejectModal;

document.addEventListener('DOMContentLoaded', function() {
    approveModal = new mdb.Modal(document.getElementById('approveModal'));
    rejectModal = new mdb.Modal(document.getElementById('rejectModal'));
});

function approvePayment(orderId, orderNumber) {
    document.getElementById('approveOrderId').value = orderId;
    document.getElementById('approveOrderNumber').textContent = orderNumber;
    approveModal.show();
}

function rejectPayment(orderId, orderNumber) {
    document.getElementById('rejectOrderId').value = orderId;
    document.getElementById('rejectOrderNumber').textContent = orderNumber;
    rejectModal.show();
}
</script>

<?php include 'includes/admin-footer.php'; ?>
