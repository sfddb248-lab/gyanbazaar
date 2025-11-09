<?php
require_once 'config/config.php';
require_once 'includes/affiliate-functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$affiliate = getAffiliateByUserId($userId);

// If not an affiliate, show registration form
if (!$affiliate) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_affiliate'])) {
        if (createAffiliate($userId)) {
            header('Location: affiliate-dashboard.php');
            exit;
        }
        $error = "Failed to register as affiliate";
    }
}

if ($affiliate) {
    $stats = getAffiliateStats($affiliate['id']);
    $affiliateLink = getAffiliateLink($affiliate['referral_code']);
    
    // Get today's earnings
    $todayStart = date('Y-m-d 00:00:00');
    $todayEnd = date('Y-m-d 23:59:59');
    
    $stmt = $conn->prepare("SELECT 
        COALESCE(SUM(commission_amount), 0) as today_earnings,
        COUNT(*) as today_commissions
        FROM affiliate_commissions 
        WHERE affiliate_id = ? 
        AND created_at BETWEEN ? AND ?");
    $stmt->bind_param("iss", $affiliate['id'], $todayStart, $todayEnd);
    $stmt->execute();
    $todayStats = $stmt->get_result()->fetch_assoc();
    
    // Get total withdrawals (completed payouts)
    $stmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) as total_withdrawals 
                           FROM affiliate_payouts 
                           WHERE affiliate_id = ? AND status = 'completed'");
    $stmt->bind_param("i", $affiliate['id']);
    $stmt->execute();
    $withdrawalStats = $stmt->get_result()->fetch_assoc();
    
    // Get total commissions count
    $stmt = $conn->prepare("SELECT COUNT(*) as total_commissions 
                           FROM affiliate_commissions 
                           WHERE affiliate_id = ?");
    $stmt->bind_param("i", $affiliate['id']);
    $stmt->execute();
    $commissionStats = $stmt->get_result()->fetch_assoc();
    
    // Calculate available for withdrawal (pending earnings)
    $availableForWithdrawal = $affiliate['pending_earnings'];
    
    // Get recent commissions
    $stmt = $conn->prepare("SELECT c.*, o.order_number, o.created_at as order_date 
                           FROM affiliate_commissions c 
                           JOIN orders o ON c.order_id = o.id 
                           WHERE c.affiliate_id = ? 
                           ORDER BY c.created_at DESC LIMIT 10");
    $stmt->bind_param("i", $affiliate['id']);
    $stmt->execute();
    $recentCommissions = $stmt->get_result();
    
    // Get payout history
    $stmt = $conn->prepare("SELECT * FROM affiliate_payouts WHERE affiliate_id = ? ORDER BY requested_at DESC LIMIT 10");
    $stmt->bind_param("i", $affiliate['id']);
    $stmt->execute();
    $payouts = $stmt->get_result();
    
    // Get level-wise earnings and referrals
    $levelStats = [];
    $maxLevels = (int)getAffiliateSetting('mlm_levels', 10);
    
    for ($level = 1; $level <= $maxLevels; $level++) {
        // Get earnings by level
        $stmt = $conn->prepare("SELECT 
            COUNT(*) as commission_count,
            COALESCE(SUM(commission_amount), 0) as total_earnings,
            COALESCE(SUM(CASE WHEN status = 'pending' THEN commission_amount ELSE 0 END), 0) as pending_earnings,
            COALESCE(SUM(CASE WHEN status = 'paid' THEN commission_amount ELSE 0 END), 0) as paid_earnings
            FROM affiliate_commissions 
            WHERE affiliate_id = ? AND level = ?");
        $stmt->bind_param("ii", $affiliate['id'], $level);
        $stmt->execute();
        $levelEarnings = $stmt->get_result()->fetch_assoc();
        
        // Get referral count for this level
        if ($level == 1) {
            // Direct referrals - use users.referred_by for accurate count
            $stmt = $conn->prepare("SELECT COUNT(*) as referral_count 
                                   FROM users 
                                   WHERE referred_by = ? 
                                   AND role = 'user'");
            $stmt->bind_param("i", $affiliate['id']);
        } else {
            // Indirect referrals through MLM structure
            $stmt = $conn->prepare("SELECT COUNT(*) as referral_count 
                                   FROM affiliate_mlm_structure 
                                   WHERE parent_affiliate_id = ? AND level = ?");
            $stmt->bind_param("ii", $affiliate['id'], $level);
        }
        $stmt->execute();
        $referralCount = $stmt->get_result()->fetch_assoc();
        
        $levelStats[$level] = [
            'commission_count' => $levelEarnings['commission_count'],
            'total_earnings' => $levelEarnings['total_earnings'],
            'pending_earnings' => $levelEarnings['pending_earnings'],
            'paid_earnings' => $levelEarnings['paid_earnings'],
            'referral_count' => $referralCount['referral_count'],
            'commission_rate' => getAffiliateSetting("level_{$level}_commission", 0)
        ];
    }
}

include 'includes/header.php';
?>

<div class="container my-5">
    <?php if (!$affiliate): ?>
        <!-- Affiliate Registration -->
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-body p-5">
                        <h2 class="text-center mb-4">Join Our Affiliate Program</h2>
                        <p class="text-center text-muted mb-4">Earn commission by promoting our products</p>
                        
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <div class="row mb-4">
                            <div class="col-md-4 text-center">
                                <i class="fas fa-link fa-3x text-primary mb-3"></i>
                                <h5>Get Your Link</h5>
                                <p class="text-muted">Receive a unique referral link</p>
                            </div>
                            <div class="col-md-4 text-center">
                                <i class="fas fa-share-alt fa-3x text-success mb-3"></i>
                                <h5>Share & Promote</h5>
                                <p class="text-muted">Share with your audience</p>
                            </div>
                            <div class="col-md-4 text-center">
                                <i class="fas fa-money-bill-wave fa-3x text-warning mb-3"></i>
                                <h5>Earn Commission</h5>
                                <p class="text-muted">Get paid for every sale</p>
                            </div>
                        </div>
                        
                        <form method="POST" class="text-center">
                            <button type="submit" name="register_affiliate" class="btn btn-primary btn-lg">
                                <i class="fas fa-rocket"></i> Become an Affiliate
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- Affiliate Dashboard -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-1">Affiliate Dashboard</h2>
                <p class="text-muted mb-0">
                    <i class="fas fa-user-circle"></i> <?php echo $_SESSION['user_name']; ?> 
                    <span class="badge bg-success ms-2">Active</span>
                </p>
            </div>
            <div class="text-end">
                <p class="mb-0 text-muted small">Referral Code</p>
                <h4 class="mb-0 text-primary"><?php echo $affiliate['referral_code']; ?></h4>
            </div>
        </div>
        
        <!-- Quick Summary Banner -->
        <div class="alert alert-light border shadow-sm mb-4">
            <div class="row text-center">
                <div class="col-md-4 border-end">
                    <h6 class="text-muted mb-2">Commission Rate</h6>
                    <h3 class="mb-0 text-primary">
                        <?php echo $affiliate['commission_value']; ?><?php echo $affiliate['commission_type'] === 'percentage' ? '%' : ' ₹'; ?>
                    </h3>
                </div>
                <div class="col-md-4 border-end">
                    <h6 class="text-muted mb-2">Conversion Rate</h6>
                    <h3 class="mb-0 text-success"><?php echo $stats['conversion_rate']; ?>%</h3>
                </div>
                <div class="col-md-4">
                    <h6 class="text-muted mb-2">Member Since</h6>
                    <h3 class="mb-0 text-info"><?php echo date('M Y', strtotime($affiliate['created_at'])); ?></h3>
                </div>
            </div>
        </div>
        
        <!-- Stats Cards - Row 1 -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card text-white bg-gradient-primary shadow-lg border-0">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-0 text-white-50">Today's Earnings</h6>
                                <h2 class="mb-0 mt-2">₹<?php echo number_format($todayStats['today_earnings'], 2); ?></h2>
                                <small class="text-white-50">
                                    <i class="fas fa-calendar-day"></i> <?php echo date('M d, Y'); ?>
                                </small>
                            </div>
                            <div class="text-white-50">
                                <i class="fas fa-coins fa-3x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card text-white bg-gradient-success shadow-lg border-0">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-0 text-white-50">Total Earnings</h6>
                                <h2 class="mb-0 mt-2">₹<?php echo number_format($affiliate['total_earnings'], 2); ?></h2>
                                <small class="text-white-50">
                                    <i class="fas fa-chart-line"></i> All time
                                </small>
                            </div>
                            <div class="text-white-50">
                                <i class="fas fa-wallet fa-3x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card text-white bg-gradient-info shadow-lg border-0">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-0 text-white-50">Total Withdrawals</h6>
                                <h2 class="mb-0 mt-2">₹<?php echo number_format($withdrawalStats['total_withdrawals'], 2); ?></h2>
                                <small class="text-white-50">
                                    <i class="fas fa-check-circle"></i> Completed
                                </small>
                            </div>
                            <div class="text-white-50">
                                <i class="fas fa-money-bill-wave fa-3x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card text-white bg-gradient-warning shadow-lg border-0">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-0 text-dark">Available Balance</h6>
                                <h2 class="mb-0 mt-2 text-dark">₹<?php echo number_format($availableForWithdrawal, 2); ?></h2>
                                <small class="text-dark">
                                    <i class="fas fa-hand-holding-usd"></i> For withdrawal
                                </small>
                            </div>
                            <div class="text-dark opacity-50">
                                <i class="fas fa-piggy-bank fa-3x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Stats Cards - Row 2 -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card text-white bg-gradient-danger shadow-lg border-0">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-0 text-white-50">Total Commissions</h6>
                                <h2 class="mb-0 mt-2"><?php echo number_format($commissionStats['total_commissions']); ?></h2>
                                <small class="text-white-50">
                                    <i class="fas fa-list"></i> All transactions
                                </small>
                            </div>
                            <div class="text-white-50">
                                <i class="fas fa-receipt fa-3x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card text-white bg-gradient-secondary shadow-lg border-0">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-0 text-white-50">Today's Commissions</h6>
                                <h2 class="mb-0 mt-2"><?php echo number_format($todayStats['today_commissions']); ?></h2>
                                <small class="text-white-50">
                                    <i class="fas fa-calendar-check"></i> <?php echo date('M d'); ?>
                                </small>
                            </div>
                            <div class="text-white-50">
                                <i class="fas fa-file-invoice-dollar fa-3x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card border-primary shadow-lg">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-0 text-muted">Total Referrals</h6>
                                <h2 class="mb-0 mt-2 text-primary"><?php echo $affiliate['total_referrals']; ?></h2>
                                <small class="text-muted">
                                    <i class="fas fa-users"></i> Referred users
                                </small>
                            </div>
                            <div class="text-primary opacity-50">
                                <i class="fas fa-user-friends fa-3x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card border-success shadow-lg">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-0 text-muted">Total Sales</h6>
                                <h2 class="mb-0 mt-2 text-success"><?php echo $affiliate['total_sales']; ?></h2>
                                <small class="text-muted">
                                    <i class="fas fa-shopping-cart"></i> Completed
                                </small>
                            </div>
                            <div class="text-success opacity-50">
                                <i class="fas fa-chart-bar fa-3x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
        .bg-gradient-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .bg-gradient-success {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        }
        .bg-gradient-info {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
        .bg-gradient-warning {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        .bg-gradient-danger {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        }
        .bg-gradient-secondary {
            background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
        }
        .card {
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.2) !important;
        }
        </style>
        
        <!-- Level-wise Referral & Earnings Breakdown -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card shadow-lg border-0">
                    <div class="card-header bg-gradient-primary text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-layer-group"></i> Level-wise Referral & Earnings Breakdown
                        </h5>
                        <small>Multi-Level Marketing (MLM) Performance</small>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-center" style="width: 80px;">Level</th>
                                        <th>Commission Rate</th>
                                        <th class="text-center">Referrals</th>
                                        <th class="text-center">Commissions</th>
                                        <th class="text-end">Total Earnings</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $totalReferrals = 0;
                                    $totalCommissions = 0;
                                    $totalEarnings = 0;
                                    $totalPending = 0;
                                    $totalPaid = 0;
                                    
                                    foreach ($levelStats as $level => $data): 
                                        $totalReferrals += $data['referral_count'];
                                        $totalCommissions += $data['commission_count'];
                                        $totalEarnings += $data['total_earnings'];
                                        $totalPending += $data['pending_earnings'];
                                        $totalPaid += $data['paid_earnings'];
                                        
                                        $levelColors = [
                                            1 => 'primary',
                                            2 => 'success',
                                            3 => 'info',
                                            4 => 'warning',
                                            5 => 'danger'
                                        ];
                                        $color = $levelColors[$level] ?? 'secondary';
                                    ?>
                                        <tr>
                                            <td class="text-center">
                                                <span class="badge bg-<?php echo $color; ?> rounded-pill px-3 py-2">
                                                    <i class="fas fa-layer-group"></i> Level <?php echo $level; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <strong class="text-<?php echo $color; ?>">
                                                    <?php echo $data['commission_rate']; ?>%
                                                </strong>
                                                <?php if ($level == 1): ?>
                                                    <small class="text-muted d-block">Direct Referrals</small>
                                                <?php else: ?>
                                                    <small class="text-muted d-block">Indirect (Level <?php echo $level; ?>)</small>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <h5 class="mb-0">
                                                    <i class="fas fa-users text-<?php echo $color; ?>"></i>
                                                    <?php echo number_format($data['referral_count']); ?>
                                                </h5>
                                            </td>
                                            <td class="text-center">
                                                <h5 class="mb-0">
                                                    <i class="fas fa-receipt text-<?php echo $color; ?>"></i>
                                                    <?php echo number_format($data['commission_count']); ?>
                                                </h5>
                                            </td>
                                            <td class="text-end">
                                                <strong class="text-<?php echo $color; ?>">
                                                    ₹<?php echo number_format($data['total_earnings'], 2); ?>
                                                </strong>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    
                                    <!-- Total Row -->
                                    <tr class="table-active fw-bold">
                                        <td class="text-center">
                                            <i class="fas fa-calculator"></i>
                                        </td>
                                        <td>TOTAL</td>
                                        <td class="text-center">
                                            <h5 class="mb-0 text-primary">
                                                <?php echo number_format($totalReferrals); ?>
                                            </h5>
                                        </td>
                                        <td class="text-center">
                                            <h5 class="mb-0 text-primary">
                                                <?php echo number_format($totalCommissions); ?>
                                            </h5>
                                        </td>
                                        <td class="text-end">
                                            <h5 class="mb-0 text-primary">
                                                ₹<?php echo number_format($totalEarnings, 2); ?>
                                            </h5>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer bg-light">
                        <div class="row text-center">
                            <div class="col-md-4">
                                <small class="text-muted d-block">Average per Level</small>
                                <strong class="text-primary">₹<?php echo number_format($totalEarnings / count($levelStats), 2); ?></strong>
                            </div>
                            <div class="col-md-4">
                                <small class="text-muted d-block">Best Performing Level</small>
                                <strong class="text-success">
                                    Level <?php 
                                    $maxEarnings = 0;
                                    $bestLevel = 1;
                                    foreach ($levelStats as $lvl => $data) {
                                        if ($data['total_earnings'] > $maxEarnings) {
                                            $maxEarnings = $data['total_earnings'];
                                            $bestLevel = $lvl;
                                        }
                                    }
                                    echo $bestLevel;
                                    ?>
                                </strong>
                            </div>
                            <div class="col-md-4">
                                <small class="text-muted d-block">MLM Depth</small>
                                <strong class="text-info"><?php echo count($levelStats); ?> Levels</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Performance Stats -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Performance Metrics</h5>
                        <div class="row">
                            <div class="col-md-3">
                                <p class="mb-1 text-muted">Total Clicks</p>
                                <h4><?php echo $stats['total_clicks']; ?></h4>
                            </div>
                            <div class="col-md-3">
                                <p class="mb-1 text-muted">Conversions</p>
                                <h4><?php echo $stats['converted_referrals']; ?></h4>
                            </div>
                            <div class="col-md-3">
                                <p class="mb-1 text-muted">Conversion Rate</p>
                                <h4><?php echo $stats['conversion_rate']; ?>%</h4>
                            </div>
                            <div class="col-md-3">
                                <p class="mb-1 text-muted">Commission Rate</p>
                                <h4><?php echo $affiliate['commission_value']; ?><?php echo $affiliate['commission_type'] === 'percentage' ? '%' : ' ₹'; ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Referral Link -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card shadow-lg border-0">
                    <div class="card-header bg-gradient-success text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-user-plus"></i> Your Signup Referral Link
                        </h5>
                        <small>Share this link to invite new users and earn commissions</small>
                    </div>
                    <div class="card-body">
                        <!-- Signup Page Referral Link -->
                        <div class="mb-4">
                            <label class="form-label fw-bold text-success">
                                <i class="fas fa-link"></i> Referral Link
                            </label>
                            <div class="input-group input-group-lg mb-2">
                                <input type="text" class="form-control" id="signupLink" value="<?php echo SITE_URL; ?>/signup.php?ref=<?php echo $affiliate['referral_code']; ?>" readonly>
                                <button class="btn btn-success" onclick="copyLink('signupLink')">
                                    <i class="fas fa-copy"></i> Copy Link
                                </button>
                            </div>
                            <div class="alert alert-info mb-3">
                                <i class="fas fa-lightbulb"></i> <strong>How it works:</strong><br>
                                When someone clicks your link and signs up, they'll see your name on the signup page and you'll earn commission on their purchases!
                            </div>
                        </div>
                        
                        <!-- Referral Code -->
                        <div class="alert alert-light border">
                            <div class="row align-items-center">
                                <div class="col-md-6">
                                    <strong><i class="fas fa-tag"></i> Your Referral Code:</strong>
                                    <h3 class="mb-0 text-success"><?php echo $affiliate['referral_code']; ?></h3>
                                </div>
                                <div class="col-md-6 text-end">
                                    <button class="btn btn-outline-success" onclick="copyText('<?php echo $affiliate['referral_code']; ?>')">
                                        <i class="fas fa-copy"></i> Copy Code
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-3">
                            <a href="affiliate-materials.php" class="btn btn-outline-primary">
                                <i class="fas fa-download"></i> Download Promotional Materials
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Referral Details Section -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="fas fa-users"></i> My Referrals & Their Purchases
                        </h5>
                        <p class="text-muted">View all users you've referred and their purchase history</p>
                        
                        <?php
                        // Get all referrals with their purchase details
                        // Exclude the affiliate themselves and admin users
                        $stmt = $conn->prepare("
                            SELECT 
                                u.id as user_id,
                                u.name as user_name,
                                u.email as user_email,
                                u.created_at as joined_date,
                                COUNT(DISTINCT o.id) as total_orders,
                                COALESCE(SUM(o.final_amount), 0) as total_spent,
                                COALESCE(SUM(ac.commission_amount), 0) as total_commission_earned
                            FROM users u
                            LEFT JOIN orders o ON u.id = o.user_id AND o.payment_status = 'completed'
                            LEFT JOIN affiliate_commissions ac ON o.id = ac.order_id AND ac.affiliate_id = ?
                            WHERE u.referred_by = ? 
                            AND u.id != ? 
                            AND u.role = 'user'
                            GROUP BY u.id
                            ORDER BY u.created_at DESC
                        ");
                        $stmt->bind_param("iii", $affiliate['id'], $affiliate['id'], $affiliate['user_id']);
                        $stmt->execute();
                        $referrals = $stmt->get_result();
                        ?>
                        
                        <?php if ($referrals->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Referral Name</th>
                                            <th>Email</th>
                                            <th>Joined Date</th>
                                            <th>Total Orders</th>
                                            <th>Total Spent</th>
                                            <th>Your Commission</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($referral = $referrals->fetch_assoc()): ?>
                                            <tr>
                                                <td>
                                                    <i class="fas fa-user-circle text-primary"></i>
                                                    <strong><?php echo htmlspecialchars($referral['user_name']); ?></strong>
                                                </td>
                                                <td><?php echo htmlspecialchars($referral['user_email']); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($referral['joined_date'])); ?></td>
                                                <td>
                                                    <span class="badge bg-info"><?php echo $referral['total_orders']; ?> orders</span>
                                                </td>
                                                <td>
                                                    <strong class="text-success">₹<?php echo number_format($referral['total_spent'], 2); ?></strong>
                                                </td>
                                                <td>
                                                    <strong class="text-primary">₹<?php echo number_format($referral['total_commission_earned'], 2); ?></strong>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary" 
                                                            onclick="viewReferralDetails(<?php echo $referral['user_id']; ?>, '<?php echo htmlspecialchars($referral['user_name']); ?>')">
                                                        <i class="fas fa-eye"></i> View Purchases
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> You haven't referred anyone yet. Share your referral link to start earning!
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recent Commissions -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Recent Commissions</h5>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Order #</th>
                                        <th>Date</th>
                                        <th>Order Amount</th>
                                        <th>Commission</th>
                                        <th>Level</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($commission = $recentCommissions->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $commission['order_number']; ?></td>
                                            <td><?php echo date('M d, Y', strtotime($commission['order_date'])); ?></td>
                                            <td>₹<?php echo number_format($commission['order_amount'], 2); ?></td>
                                            <td>₹<?php echo number_format($commission['commission_amount'], 2); ?></td>
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
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Payout Section -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Request Payout</h5>
                        <form action="affiliate-payout.php" method="POST">
                            <div class="mb-3">
                                <label class="form-label">Available Balance</label>
                                <h4 class="text-success">₹<?php echo number_format($affiliate['pending_earnings'], 2); ?></h4>
                            </div>
                            <button type="submit" class="btn btn-success" <?php echo $affiliate['pending_earnings'] < getAffiliateSetting('min_payout_amount', 500) ? 'disabled' : ''; ?>>
                                <i class="fas fa-money-check-alt"></i> Request Payout
                            </button>
                            <small class="d-block mt-2 text-muted">
                                Minimum payout: ₹<?php echo getAffiliateSetting('min_payout_amount', 500); ?>
                            </small>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Payout History</h5>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($payout = $payouts->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo date('M d, Y', strtotime($payout['requested_at'])); ?></td>
                                            <td>₹<?php echo number_format($payout['amount'], 2); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $payout['status'] === 'completed' ? 'success' : 'warning'; ?>">
                                                    <?php echo ucfirst($payout['status']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
function copyLink(elementId) {
    const linkInput = document.getElementById(elementId);
    linkInput.select();
    linkInput.setSelectionRange(0, 99999); // For mobile devices
    
    // Try modern clipboard API first
    if (navigator.clipboard) {
        navigator.clipboard.writeText(linkInput.value).then(() => {
            alert('✅ Signup referral link copied to clipboard!');
        }).catch(() => {
            // Fallback to execCommand
            document.execCommand('copy');
            alert('✅ Signup referral link copied to clipboard!');
        });
    } else {
        // Fallback for older browsers
        document.execCommand('copy');
        alert('✅ Signup referral link copied to clipboard!');
    }
}

function copyText(text) {
    // Create temporary input
    const tempInput = document.createElement('input');
    tempInput.value = text;
    document.body.appendChild(tempInput);
    tempInput.select();
    
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(() => {
            document.body.removeChild(tempInput);
            alert('✅ Referral code copied: ' + text);
        });
    } else {
        document.execCommand('copy');
        document.body.removeChild(tempInput);
        alert('✅ Referral code copied: ' + text);
    }
}
</script>

<?php include 'includes/footer.php'; ?>


<script>
// View referral purchase details
function viewReferralDetails(userId, userName) {
    // Create modal
    const modal = document.createElement('div');
    modal.className = 'modal fade';
    modal.id = 'referralDetailsModal';
    modal.innerHTML = `
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-shopping-bag"></i> Purchases by ${userName}
                    </h5>
                    <button type="button" class="btn-close" data-mdb-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Loading purchase details...</p>
                    </div>
                </div>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
    
    // Show modal
    const modalInstance = new mdb.Modal(modal);
    modalInstance.show();
    
    // Fetch purchase details
    fetch('get-referral-purchases.php?user_id=' + userId)
        .then(response => response.json())
        .then(data => {
            let content = '';
            
            if (data.success && data.purchases.length > 0) {
                content = `
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Order #</th>
                                    <th>Date</th>
                                    <th>Products</th>
                                    <th>Amount</th>
                                    <th>Your Commission</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                `;
                
                data.purchases.forEach(purchase => {
                    content += `
                        <tr>
                            <td><strong>${purchase.order_number}</strong></td>
                            <td>${purchase.order_date}</td>
                            <td>
                                <ul class="list-unstyled mb-0">
                                    ${purchase.products.map(p => `<li><i class="fas fa-box text-primary"></i> ${p}</li>`).join('')}
                                </ul>
                            </td>
                            <td><strong class="text-success">₹${purchase.amount}</strong></td>
                            <td><strong class="text-primary">₹${purchase.commission}</strong></td>
                            <td><span class="badge bg-${purchase.status === 'completed' ? 'success' : 'warning'}">${purchase.status}</span></td>
                        </tr>
                    `;
                });
                
                content += `
                            </tbody>
                        </table>
                    </div>
                    <div class="alert alert-info mt-3">
                        <strong>Total Earned from ${userName}:</strong> ₹${data.total_commission}
                    </div>
                `;
            } else {
                content = `
                    <div class="alert alert-warning">
                        <i class="fas fa-info-circle"></i> ${userName} hasn't made any purchases yet.
                    </div>
                `;
            }
            
            modal.querySelector('.modal-body').innerHTML = content;
        })
        .catch(error => {
            modal.querySelector('.modal-body').innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i> Error loading purchase details. Please try again.
                </div>
            `;
        });
    
    // Remove modal when closed
    modal.addEventListener('hidden.mdb.modal', () => {
        modal.remove();
    });
}
</script>
