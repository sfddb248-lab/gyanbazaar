<?php
require_once '../config/config.php';
requireAdmin();

$userId = (int)$_GET['user_id'];

// Get user details
$user = $conn->query("
    SELECT 
        u.*,
        a.id as affiliate_id,
        a.referral_code,
        a.commission_type,
        a.commission_value,
        a.total_referrals,
        a.total_sales,
        a.total_earnings,
        a.pending_earnings,
        a.paid_earnings,
        senior.name as senior_name,
        senior.email as senior_email,
        senior_aff.referral_code as senior_code
    FROM users u
    LEFT JOIN affiliates a ON u.id = a.user_id
    LEFT JOIN affiliates senior_aff ON u.referred_by = senior_aff.id
    LEFT JOIN users senior ON senior_aff.user_id = senior.id
    WHERE u.id = $userId
")->fetch_assoc();

if (!$user) {
    echo '<div class="alert alert-danger">User not found</div>';
    exit;
}

// Get user's referrals
$referrals = $conn->query("
    SELECT 
        u.id,
        u.name,
        u.email,
        u.created_at,
        COUNT(DISTINCT o.id) as orders,
        COALESCE(SUM(o.final_amount), 0) as spent
    FROM users u
    LEFT JOIN orders o ON u.id = o.user_id AND o.payment_status = 'completed'
    WHERE u.referred_by = {$user['affiliate_id']}
    GROUP BY u.id
    ORDER BY u.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

// Get commissions earned BY this user (as affiliate)
$commissionsEarned = $conn->query("
    SELECT 
        ac.*,
        o.order_number,
        o.final_amount as order_amount,
        buyer.name as buyer_name,
        buyer.email as buyer_email
    FROM affiliate_commissions ac
    JOIN orders o ON ac.order_id = o.id
    JOIN users buyer ON o.user_id = buyer.id
    WHERE ac.affiliate_id = {$user['affiliate_id']}
    ORDER BY ac.created_at DESC
    LIMIT 50
")->fetch_all(MYSQLI_ASSOC);

// Get commissions generated FOR others (when this user buys)
$commissionsGenerated = $conn->query("
    SELECT 
        ac.*,
        o.order_number,
        o.final_amount as order_amount,
        aff.referral_code,
        u.name as affiliate_name,
        u.email as affiliate_email
    FROM orders o
    JOIN affiliate_commissions ac ON o.id = ac.order_id
    JOIN affiliates aff ON ac.affiliate_id = aff.id
    JOIN users u ON aff.user_id = u.id
    WHERE o.user_id = $userId
    ORDER BY ac.level ASC, ac.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

// Get user's orders
$orders = $conn->query("
    SELECT *
    FROM orders
    WHERE user_id = $userId
    ORDER BY created_at DESC
    LIMIT 20
")->fetch_all(MYSQLI_ASSOC);

?>

<div class="row">
    <!-- Left Column: User Info -->
    <div class="col-md-4">
        <div class="card mb-3">
            <div class="card-body text-center">
                <div style="width: 100px; height: 100px; border-radius: 50%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; display: flex; align-items: center; justify-content: center; font-size: 40px; font-weight: bold; margin: 0 auto 15px;">
                    <?php echo strtoupper(substr($user['name'], 0, 2)); ?>
                </div>
                <h5><?php echo htmlspecialchars($user['name']); ?></h5>
                <p class="text-muted mb-2"><?php echo htmlspecialchars($user['email']); ?></p>
                <p class="mb-2">
                    <span class="badge bg-<?php echo $user['status'] == 'active' ? 'success' : 'danger'; ?>">
                        <?php echo ucfirst($user['status']); ?>
                    </span>
                    <?php if ($user['affiliate_id']): ?>
                        <span class="badge bg-primary">Affiliate</span>
                    <?php endif; ?>
                </p>
                <small class="text-muted">
                    <i class="fas fa-calendar"></i> Joined: <?php echo date('M d, Y', strtotime($user['created_at'])); ?>
                </small>
            </div>
        </div>
        
        <?php if ($user['referred_by']): ?>
        <div class="card mb-3">
            <div class="card-body">
                <h6><i class="fas fa-user-tie"></i> Senior/Referrer</h6>
                <hr>
                <p class="mb-1"><strong><?php echo htmlspecialchars($user['senior_name']); ?></strong></p>
                <p class="mb-1"><small><?php echo htmlspecialchars($user['senior_email']); ?></small></p>
                <p class="mb-0">
                    <span class="badge bg-info"><?php echo $user['senior_code']; ?></span>
                </p>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ($user['affiliate_id']): ?>
        <div class="card">
            <div class="card-body">
                <h6><i class="fas fa-chart-line"></i> Affiliate Stats</h6>
                <hr>
                <table class="table table-sm">
                    <tr>
                        <td>Referral Code:</td>
                        <td><strong><?php echo $user['referral_code']; ?></strong></td>
                    </tr>
                    <tr>
                        <td>Commission:</td>
                        <td><strong><?php echo $user['commission_value']; ?>%</strong></td>
                    </tr>
                    <tr>
                        <td>Total Referrals:</td>
                        <td><strong><?php echo $user['total_referrals']; ?></strong></td>
                    </tr>
                    <tr>
                        <td>Total Sales:</td>
                        <td><strong><?php echo $user['total_sales']; ?></strong></td>
                    </tr>
                    <tr>
                        <td>Total Earnings:</td>
                        <td><strong class="text-success">₹<?php echo number_format($user['total_earnings'], 2); ?></strong></td>
                    </tr>
                    <tr>
                        <td>Pending:</td>
                        <td><strong class="text-warning">₹<?php echo number_format($user['pending_earnings'], 2); ?></strong></td>
                    </tr>
                    <tr>
                        <td>Paid:</td>
                        <td><strong class="text-primary">₹<?php echo number_format($user['paid_earnings'], 2); ?></strong></td>
                    </tr>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Right Column: Details -->
    <div class="col-md-8">
        <!-- Tabs -->
        <ul class="nav nav-tabs mb-3" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" data-mdb-toggle="tab" href="#referrals-tab">
                    <i class="fas fa-users"></i> Referrals (<?php echo count($referrals); ?>)
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-mdb-toggle="tab" href="#commissions-earned-tab">
                    <i class="fas fa-money-bill-wave"></i> Commissions Earned (<?php echo count($commissionsEarned); ?>)
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-mdb-toggle="tab" href="#commissions-generated-tab">
                    <i class="fas fa-hand-holding-usd"></i> Commissions Generated (<?php echo count($commissionsGenerated); ?>)
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-mdb-toggle="tab" href="#orders-tab">
                    <i class="fas fa-shopping-cart"></i> Orders (<?php echo count($orders); ?>)
                </a>
            </li>
        </ul>
        
        <div class="tab-content">
            <!-- Referrals Tab -->
            <div class="tab-pane fade show active" id="referrals-tab">
                <?php if (count($referrals) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Joined</th>
                                <th>Orders</th>
                                <th>Spent</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($referrals as $ref): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($ref['name']); ?></td>
                                <td><?php echo htmlspecialchars($ref['email']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($ref['created_at'])); ?></td>
                                <td><?php echo $ref['orders']; ?></td>
                                <td>₹<?php echo number_format($ref['spent'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="alert alert-info">No referrals yet</div>
                <?php endif; ?>
            </div>
            
            <!-- Commissions Earned Tab -->
            <div class="tab-pane fade" id="commissions-earned-tab">
                <?php if (count($commissionsEarned) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Buyer</th>
                                <th>Order Amount</th>
                                <th>Commission</th>
                                <th>Level</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($commissionsEarned as $comm): ?>
                            <tr>
                                <td><?php echo $comm['order_number']; ?></td>
                                <td><?php echo htmlspecialchars($comm['buyer_name']); ?></td>
                                <td>₹<?php echo number_format($comm['order_amount'], 2); ?></td>
                                <td><strong class="text-success">₹<?php echo number_format($comm['commission_amount'], 2); ?></strong></td>
                                <td><span class="badge bg-primary">L<?php echo $comm['level']; ?></span></td>
                                <td><span class="badge bg-<?php echo $comm['status'] == 'paid' ? 'success' : 'warning'; ?>"><?php echo ucfirst($comm['status']); ?></span></td>
                                <td><?php echo date('M d, Y', strtotime($comm['created_at'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="alert alert-info">No commissions earned yet</div>
                <?php endif; ?>
            </div>
            
            <!-- Commissions Generated Tab -->
            <div class="tab-pane fade" id="commissions-generated-tab">
                <?php if (count($commissionsGenerated) > 0): ?>
                <div class="alert alert-info mb-3">
                    <i class="fas fa-info-circle"></i> These are commissions generated for other affiliates when this user makes purchases
                </div>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Affiliate</th>
                                <th>Code</th>
                                <th>Order Amount</th>
                                <th>Commission</th>
                                <th>Level</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($commissionsGenerated as $comm): ?>
                            <tr>
                                <td><?php echo $comm['order_number']; ?></td>
                                <td><?php echo htmlspecialchars($comm['affiliate_name']); ?></td>
                                <td><span class="badge bg-info"><?php echo $comm['referral_code']; ?></span></td>
                                <td>₹<?php echo number_format($comm['order_amount'], 2); ?></td>
                                <td><strong class="text-success">₹<?php echo number_format($comm['commission_amount'], 2); ?></strong></td>
                                <td><span class="badge bg-primary">L<?php echo $comm['level']; ?></span></td>
                                <td><?php echo date('M d, Y', strtotime($comm['created_at'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="alert alert-info">No commissions generated yet</div>
                <?php endif; ?>
            </div>
            
            <!-- Orders Tab -->
            <div class="tab-pane fade" id="orders-tab">
                <?php if (count($orders) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Amount</th>
                                <th>Payment Method</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><?php echo $order['order_number']; ?></td>
                                <td>₹<?php echo number_format($order['final_amount'], 2); ?></td>
                                <td><?php echo ucfirst($order['payment_method']); ?></td>
                                <td><span class="badge bg-<?php echo $order['payment_status'] == 'completed' ? 'success' : 'warning'; ?>"><?php echo ucfirst($order['payment_status']); ?></span></td>
                                <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="alert alert-info">No orders yet</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
