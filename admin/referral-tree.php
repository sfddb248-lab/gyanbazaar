<?php
require_once '../config/config.php';
requireAdmin();

$pageTitle = 'Referral Tree - Admin';
$currentPage = 'referral-tree';

// Get all users with their referral information
$users = $conn->query("
    SELECT 
        u.id,
        u.name,
        u.email,
        u.created_at,
        u.referred_by,
        senior.name as senior_name,
        senior.email as senior_email,
        a.id as affiliate_id,
        a.referral_code,
        a.total_referrals,
        a.total_sales,
        a.total_earnings,
        a.pending_earnings,
        (SELECT COUNT(*) FROM users WHERE referred_by = a.id) as direct_referrals,
        (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.id AND o.payment_status = 'completed') as total_orders,
        (SELECT COALESCE(SUM(o.final_amount), 0) FROM orders o WHERE o.user_id = u.id AND o.payment_status = 'completed') as total_spent
    FROM users u
    LEFT JOIN affiliates a ON u.id = a.user_id
    LEFT JOIN affiliates senior_aff ON u.referred_by = senior_aff.id
    LEFT JOIN users senior ON senior_aff.user_id = senior.id
    WHERE u.role = 'user'
    ORDER BY u.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

include 'includes/admin-header.php';
?>

<style>
.user-card {
    border: 2px solid #e0e0e0;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 15px;
    transition: all 0.3s;
    background: white;
}

.user-card:hover {
    border-color: #1266f1;
    box-shadow: 0 4px 12px rgba(18, 102, 241, 0.15);
    transform: translateY(-2px);
}

.user-avatar {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    font-weight: bold;
}

.badge-custom {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.stat-box {
    text-align: center;
    padding: 10px;
    border-radius: 8px;
    background: #f8f9fa;
}

.stat-box h6 {
    font-size: 20px;
    margin: 5px 0;
    color: #1266f1;
}

.stat-box small {
    color: #6c757d;
    font-size: 11px;
}

.senior-link {
    background: #e7f3ff;
    padding: 10px 15px;
    border-radius: 8px;
    border-left: 4px solid #1266f1;
}

.commission-badge {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 8px 16px;
    border-radius: 20px;
    font-weight: bold;
}
</style>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2><i class="fas fa-sitemap"></i> Referral Tree & User Details</h2>
            <p class="text-muted">View all users, their referrers, and commission details</p>
        </div>
        <div class="col-md-4 text-end">
            <input type="text" id="searchInput" class="form-control" placeholder="🔍 Search users...">
        </div>
    </div>
    
    <!-- Summary Stats -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h3><?php echo count($users); ?></h3>
                    <p class="mb-0">Total Users</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h3><?php echo count(array_filter($users, fn($u) => $u['affiliate_id'])); ?></h3>
                    <p class="mb-0">Affiliates</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <h3><?php echo count(array_filter($users, fn($u) => $u['referred_by'])); ?></h3>
                    <p class="mb-0">Referred Users</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body text-center">
                    <h3>₹<?php echo number_format(array_sum(array_column($users, 'total_earnings')), 0); ?></h3>
                    <p class="mb-0">Total Commissions</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Users List -->
    <div class="row" id="usersList">
        <?php foreach ($users as $user): ?>
        <div class="col-12 user-item" data-search="<?php echo strtolower($user['name'] . ' ' . $user['email'] . ' ' . ($user['senior_name'] ?? '')); ?>">
            <div class="user-card">
                <div class="row align-items-center">
                    <!-- User Avatar & Info -->
                    <div class="col-md-3">
                        <div class="d-flex align-items-center">
                            <div class="user-avatar me-3">
                                <?php echo strtoupper(substr($user['name'], 0, 2)); ?>
                            </div>
                            <div>
                                <h6 class="mb-1">
                                    <strong><?php echo htmlspecialchars($user['name']); ?></strong>
                                    <?php if ($user['affiliate_id']): ?>
                                        <span class="badge bg-success badge-custom">Affiliate</span>
                                    <?php endif; ?>
                                </h6>
                                <small class="text-muted"><?php echo htmlspecialchars($user['email']); ?></small><br>
                                <small class="text-muted">
                                    <i class="fas fa-calendar"></i> Joined: <?php echo date('M d, Y', strtotime($user['created_at'])); ?>
                                </small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Senior/Referrer Info -->
                    <div class="col-md-3">
                        <?php if ($user['referred_by']): ?>
                        <div class="senior-link">
                            <small class="text-muted d-block mb-1">
                                <i class="fas fa-user-tie"></i> Referred By:
                            </small>
                            <strong><?php echo htmlspecialchars($user['senior_name']); ?></strong><br>
                            <small class="text-muted"><?php echo htmlspecialchars($user['senior_email']); ?></small>
                        </div>
                        <?php else: ?>
                        <div class="text-center text-muted">
                            <i class="fas fa-user-slash"></i><br>
                            <small>No Referrer</small>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Stats -->
                    <div class="col-md-4">
                        <div class="row g-2">
                            <div class="col-6">
                                <div class="stat-box">
                                    <h6><?php echo $user['direct_referrals']; ?></h6>
                                    <small>Referrals</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="stat-box">
                                    <h6><?php echo $user['total_orders']; ?></h6>
                                    <small>Orders</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="stat-box">
                                    <h6>₹<?php echo number_format($user['total_spent'], 0); ?></h6>
                                    <small>Spent</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="stat-box">
                                    <h6>₹<?php echo number_format($user['total_earnings'], 0); ?></h6>
                                    <small>Earned</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Actions -->
                    <div class="col-md-2 text-center">
                        <?php if ($user['affiliate_id']): ?>
                        <div class="mb-2">
                            <span class="commission-badge">
                                <?php echo $user['referral_code']; ?>
                            </span>
                        </div>
                        <?php endif; ?>
                        <button class="btn btn-sm btn-primary" onclick="viewDetails(<?php echo $user['id']; ?>)">
                            <i class="fas fa-eye"></i> View Details
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- User Details Modal -->
<div class="modal fade" id="userDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">User Details & Commissions</h5>
                <button type="button" class="btn-close" data-mdb-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modalContent">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Search functionality
document.getElementById('searchInput').addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const items = document.querySelectorAll('.user-item');
    
    items.forEach(item => {
        const searchData = item.getAttribute('data-search');
        if (searchData.includes(searchTerm)) {
            item.style.display = '';
        } else {
            item.style.display = 'none';
        }
    });
});

// View user details
function viewDetails(userId) {
    const modal = new mdb.Modal(document.getElementById('userDetailsModal'));
    modal.show();
    
    // Load user details via AJAX
    fetch('ajax-user-details.php?user_id=' + userId)
        .then(response => response.text())
        .then(html => {
            document.getElementById('modalContent').innerHTML = html;
        })
        .catch(error => {
            document.getElementById('modalContent').innerHTML = '<div class="alert alert-danger">Error loading details</div>';
        });
}
</script>

<?php include 'includes/admin-footer.php'; ?>
