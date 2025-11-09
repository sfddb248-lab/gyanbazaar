<?php
require_once '../config/config.php';
$pageTitle = 'Manage Orders - Admin';

// Get orders
$orders = $conn->query("SELECT o.*, u.name as user_name, u.email as user_email FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC")->fetch_all(MYSQLI_ASSOC);

include 'includes/admin-header.php';
?>

<div class="container-fluid my-4">
    <h2 class="mb-4"><i class="fas fa-shopping-cart"></i> Manage Orders</h2>
    
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Customer</th>
                            <th>Email</th>
                            <th>Amount</th>
                            <th>Payment</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><?php echo $order['order_number']; ?></td>
                            <td><?php echo htmlspecialchars($order['user_name']); ?></td>
                            <td><?php echo htmlspecialchars($order['user_email']); ?></td>
                            <td><?php echo formatCurrency($order['final_amount']); ?></td>
                            <td><?php echo ucfirst($order['payment_method']); ?></td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo match($order['payment_status']) {
                                        'completed' => 'success',
                                        'pending' => 'warning',
                                        'failed' => 'danger',
                                        'refunded' => 'info',
                                        default => 'secondary'
                                    };
                                ?>">
                                    <?php echo ucfirst($order['payment_status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                            <td>
                                <button class="btn btn-sm btn-primary" onclick='viewOrder(<?php echo json_encode($order); ?>)'>
                                    <i class="fas fa-eye"></i>
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

<!-- Order Details Modal -->
<div class="modal fade" id="orderModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Order Details</h5>
                <button type="button" class="btn-close" data-mdb-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="orderDetails">
                <!-- Order details will be loaded here -->
            </div>
        </div>
    </div>
</div>

<script>
function viewOrder(order) {
    const details = `
        <div class="mb-3">
            <strong>Order Number:</strong> ${order.order_number}<br>
            <strong>Customer:</strong> ${order.user_name}<br>
            <strong>Email:</strong> ${order.user_email}<br>
            <strong>Payment Method:</strong> ${order.payment_method}<br>
            <strong>Transaction ID:</strong> ${order.transaction_id || 'N/A'}<br>
            <strong>Date:</strong> ${new Date(order.created_at).toLocaleString()}
        </div>
        <hr>
        <div>
            <strong>Subtotal:</strong> ${order.total_amount}<br>
            <strong>Discount:</strong> ${order.discount_amount}<br>
            <strong>Tax:</strong> ${order.tax_amount}<br>
            <strong>Total:</strong> <span class="text-primary">${order.final_amount}</span>
        </div>
    `;
    
    document.getElementById('orderDetails').innerHTML = details;
    new mdb.Modal(document.getElementById('orderModal')).show();
}
</script>

<?php include 'includes/admin-footer.php'; ?>
