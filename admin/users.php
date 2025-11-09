<?php
require_once '../config/config.php';
$pageTitle = 'Manage Users - Admin';

$success = '';

// Handle block/unblock
if (isset($_GET['toggle_status'])) {
    $id = (int)$_GET['toggle_status'];
    $conn->query("UPDATE users SET status = IF(status='active', 'blocked', 'active') WHERE id = $id");
    $success = 'User status updated';
}

// Get users
$users = $conn->query("SELECT u.*, COUNT(o.id) as total_orders, SUM(o.final_amount) as total_spent FROM users u LEFT JOIN orders o ON u.id = o.user_id AND o.payment_status = 'completed' WHERE u.role = 'user' GROUP BY u.id ORDER BY u.created_at DESC")->fetch_all(MYSQLI_ASSOC);

include 'includes/admin-header.php';
?>

<div class="container-fluid my-4">
    <h2 class="mb-4"><i class="fas fa-users"></i> Manage Users</h2>
    
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
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Orders</th>
                            <th>Total Spent</th>
                            <th>Status</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td><?php echo htmlspecialchars($user['name']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo $user['total_orders'] ?? 0; ?></td>
                            <td><?php echo formatCurrency($user['total_spent'] ?? 0); ?></td>
                            <td>
                                <span class="badge bg-<?php echo $user['status'] == 'active' ? 'success' : 'danger'; ?>">
                                    <?php echo ucfirst($user['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                            <td>
                                <a href="?toggle_status=<?php echo $user['id']; ?>" 
                                   class="btn btn-sm btn-<?php echo $user['status'] == 'active' ? 'warning' : 'success'; ?>">
                                    <i class="fas fa-<?php echo $user['status'] == 'active' ? 'ban' : 'check'; ?>"></i>
                                    <?php echo $user['status'] == 'active' ? 'Block' : 'Unblock'; ?>
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

<?php include 'includes/admin-footer.php'; ?>
