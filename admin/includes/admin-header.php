<?php
// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    header('Location: ' . SITE_URL . '/admin/login.php');
    exit;
}

if (!isset($pageTitle)) $pageTitle = 'Admin - ' . getSetting('site_name');
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$isDarkMode = isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'dark';
?>
<!DOCTYPE html>
<html lang="en" data-mdb-theme="<?php echo $isDarkMode ? 'dark' : 'light'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    
    <!-- MDBootstrap CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/7.1.0/mdb.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <!-- Admin Animations -->
    <link href="<?php echo SITE_URL; ?>/assets/css/admin-animations.css" rel="stylesheet">
    <!-- Admin Ultra Modern Theme -->
    <style>
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 250px;
            background: var(--mdb-surface-bg);
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            overflow-y: auto;
            z-index: 1000;
        }
        
        .sidebar .logo {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(0,0,0,0.1);
        }
        
        .sidebar .nav-link {
            padding: 12px 20px;
            color: var(--mdb-body-color);
            border-left: 3px solid transparent;
        }
        
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: rgba(18, 102, 241, 0.1);
            border-left-color: #1266f1;
            color: #1266f1;
        }
        
        .main-content {
            margin-left: 250px;
            min-height: 100vh;
        }
        
        .top-navbar {
            background: var(--mdb-surface-bg);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 15px 30px;
            position: sticky;
            top: 0;
            z-index: 999;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s;
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="logo">
            <h4 class="text-primary mb-0">
                <i class="fas fa-gem"></i> Admin Panel
            </h4>
        </div>
        
        <nav class="nav flex-column">
            <a href="<?php echo SITE_URL; ?>/admin/index.php" 
               class="nav-link <?php echo $currentPage == 'index' ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            <a href="<?php echo SITE_URL; ?>/admin/products.php" 
               class="nav-link <?php echo $currentPage == 'products' ? 'active' : ''; ?>">
                <i class="fas fa-box"></i> Products
            </a>
            <?php
            // Show Courses menu if any courses exist
            $coursesExist = $conn->query("SELECT COUNT(*) as count FROM products WHERE product_type = 'course'")->fetch_assoc()['count'];
            if ($coursesExist > 0):
            ?>
            <a href="<?php echo SITE_URL; ?>/admin/products.php" 
               class="nav-link">
                <i class="fas fa-video"></i> Courses
                <span class="badge bg-info ms-2"><?php echo $coursesExist; ?></span>
            </a>
            <?php endif; ?>
            <a href="<?php echo SITE_URL; ?>/admin/orders.php" 
               class="nav-link <?php echo $currentPage == 'orders' ? 'active' : ''; ?>">
                <i class="fas fa-shopping-cart"></i> Orders
            </a>
            <a href="<?php echo SITE_URL; ?>/admin/verify-payments.php" 
               class="nav-link <?php echo $currentPage == 'verify-payments' ? 'active' : ''; ?>">
                <i class="fas fa-check-circle"></i> Verify Payments
                <?php
                // Show pending count badge
                $pendingCount = $conn->query("SELECT COUNT(*) as count FROM orders WHERE payment_status = 'pending' AND payment_method = 'upi' AND transaction_id IS NOT NULL")->fetch_assoc()['count'];
                if ($pendingCount > 0):
                ?>
                    <span class="badge bg-warning text-dark ms-2"><?php echo $pendingCount; ?></span>
                <?php endif; ?>
            </a>
            <a href="<?php echo SITE_URL; ?>/admin/view-otps.php" 
               class="nav-link <?php echo $currentPage == 'view-otps' ? 'active' : ''; ?>">
                <i class="fas fa-key"></i> View OTPs
                <?php
                // Show pending OTP count (check if column exists)
                $checkOtpColumn = $conn->query("SHOW COLUMNS FROM users LIKE 'otp_code'");
                if ($checkOtpColumn && $checkOtpColumn->num_rows > 0) {
                    $otpResult = $conn->query("SELECT COUNT(*) as count FROM users WHERE email_verified = FALSE AND status = 'pending' AND otp_code IS NOT NULL");
                    if ($otpResult) {
                        $otpCount = $otpResult->fetch_assoc()['count'];
                        if ($otpCount > 0):
                ?>
                    <span class="badge bg-info ms-2"><?php echo $otpCount; ?></span>
                <?php 
                        endif;
                    }
                }
                ?>
            </a>
            <a href="<?php echo SITE_URL; ?>/admin/users.php" 
               class="nav-link <?php echo $currentPage == 'users' ? 'active' : ''; ?>">
                <i class="fas fa-users"></i> Users
            </a>
            <a href="<?php echo SITE_URL; ?>/admin/coupons.php" 
               class="nav-link <?php echo $currentPage == 'coupons' ? 'active' : ''; ?>">
                <i class="fas fa-tags"></i> Coupons
            </a>
            
            <div class="nav-link text-muted" style="font-size: 0.85rem; padding: 8px 20px;">
                <strong>AFFILIATE SYSTEM</strong>
            </div>
            <a href="<?php echo SITE_URL; ?>/admin/commission-levels.php" 
               class="nav-link <?php echo $currentPage == 'commission-levels' ? 'active' : ''; ?>">
                <i class="fas fa-layer-group"></i> Commission Levels
            </a>
            <a href="<?php echo SITE_URL; ?>/admin/referral-tree.php" 
               class="nav-link <?php echo $currentPage == 'referral-tree' ? 'active' : ''; ?>">
                <i class="fas fa-sitemap"></i> Referral Tree
            </a>
            <a href="<?php echo SITE_URL; ?>/admin/affiliate-management.php" 
               class="nav-link <?php echo $currentPage == 'affiliate-management' ? 'active' : ''; ?>">
                <i class="fas fa-users-cog"></i> Affiliate Management
            </a>
            <a href="<?php echo SITE_URL; ?>/admin/affiliates.php" 
               class="nav-link <?php echo $currentPage == 'affiliates' ? 'active' : ''; ?>">
                <i class="fas fa-users"></i> Affiliates
            </a>
            <a href="<?php echo SITE_URL; ?>/admin/affiliate-commissions.php" 
               class="nav-link <?php echo $currentPage == 'affiliate-commissions' ? 'active' : ''; ?>">
                <i class="fas fa-dollar-sign"></i> Commissions
            </a>
            <a href="<?php echo SITE_URL; ?>/admin/approve-commissions.php" 
               class="nav-link <?php echo $currentPage == 'approve-commissions' ? 'active' : ''; ?>">
                <i class="fas fa-check-circle"></i> Approve Commissions
            </a>
            <a href="<?php echo SITE_URL; ?>/admin/withdrawals.php" 
               class="nav-link <?php echo $currentPage == 'withdrawals' ? 'active' : ''; ?>">
                <i class="fas fa-money-check-alt"></i> Withdrawals
            </a>
            <a href="<?php echo SITE_URL; ?>/admin/affiliate-payouts.php" 
               class="nav-link <?php echo $currentPage == 'affiliate-payouts' ? 'active' : ''; ?>">
                <i class="fas fa-money-check-alt"></i> Payouts
            </a>
            <a href="<?php echo SITE_URL; ?>/admin/affiliate-materials.php" 
               class="nav-link <?php echo $currentPage == 'affiliate-materials' ? 'active' : ''; ?>">
                <i class="fas fa-images"></i> Materials
            </a>
            <a href="<?php echo SITE_URL; ?>/admin/affiliate-settings.php" 
               class="nav-link <?php echo $currentPage == 'affiliate-settings' ? 'active' : ''; ?>">
                <i class="fas fa-sliders-h"></i> Affiliate Settings
            </a>
            <a href="<?php echo SITE_URL; ?>/admin/reports.php" 
               class="nav-link <?php echo $currentPage == 'reports' ? 'active' : ''; ?>">
                <i class="fas fa-chart-bar"></i> Reports
            </a>
            <a href="<?php echo SITE_URL; ?>/admin/settings.php" 
               class="nav-link <?php echo $currentPage == 'settings' ? 'active' : ''; ?>">
                <i class="fas fa-cog"></i> Settings
            </a>
            
            <hr>
            
            <a href="<?php echo SITE_URL; ?>" class="nav-link">
                <i class="fas fa-home"></i> Back to Site
            </a>
            <a href="<?php echo SITE_URL; ?>/admin/logout.php" class="nav-link text-danger">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </nav>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Navbar -->
        <div class="top-navbar d-flex justify-content-between align-items-center">
            <div>
                <button class="btn btn-link d-md-none" onclick="toggleSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
                <span class="ms-2">Welcome, <?php echo $_SESSION['user_name']; ?></span>
            </div>
            <div>
                <span class="theme-toggle me-3" onclick="toggleTheme()" style="cursor: pointer;">
                    <i class="fas fa-moon"></i>
                </span>
                <a href="<?php echo SITE_URL; ?>/profile.php" class="btn btn-sm btn-primary">
                    <i class="fas fa-user"></i> Profile
                </a>
            </div>
        </div>
