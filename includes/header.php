<?php
if (!isset($pageTitle)) $pageTitle = getSetting('site_name', 'GyaanBazaar');
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
    <!-- Ultra Modern Theme -->
    <link href="<?php echo SITE_URL; ?>/assets/css/ultra-modern-theme.css" rel="stylesheet">
    <link href="<?php echo SITE_URL; ?>/assets/css/advanced-theme.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #1266f1;
            --secondary-color: #b23cfd;
        }
        
        body {
            padding-bottom: 0;
        }
        
        /* Desktop Navbar */
        .desktop-nav {
            display: none;
        }
        
        /* Mobile Bottom Navigation */
        .mobile-bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: var(--mdb-surface-bg);
            box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
            z-index: 1000;
            display: flex;
            justify-content: space-around;
            padding: 6px 0;
            overflow-x: auto;
        }
        
        .mobile-bottom-nav a {
            flex: 1;
            text-align: center;
            padding: 6px 4px;
            color: var(--mdb-body-color);
            text-decoration: none;
            transition: all 0.3s;
            min-width: 60px;
        }
        
        .mobile-bottom-nav a.active {
            color: var(--primary-color);
        }
        
        .mobile-bottom-nav i {
            font-size: 20px;
            display: block;
            margin-bottom: 3px;
        }
        
        .mobile-bottom-nav span {
            font-size: 10px;
            display: block;
            white-space: nowrap;
        }
        
        /* Mobile AppBar */
        .mobile-appbar {
            position: sticky;
            top: 0;
            z-index: 999;
            background: var(--mdb-surface-bg);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 12px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .mobile-appbar .logo {
            font-size: 20px;
            font-weight: bold;
            color: var(--primary-color);
        }
        
        /* Hamburger Menu */
        .hamburger-menu {
            display: none;
            flex-direction: column;
            cursor: pointer;
            padding: 5px;
        }
        
        .hamburger-menu span {
            width: 25px;
            height: 3px;
            background: var(--mdb-body-color);
            margin: 3px 0;
            transition: 0.3s;
            border-radius: 3px;
        }
        
        .hamburger-menu.active span:nth-child(1) {
            transform: rotate(-45deg) translate(-5px, 6px);
        }
        
        .hamburger-menu.active span:nth-child(2) {
            opacity: 0;
        }
        
        .hamburger-menu.active span:nth-child(3) {
            transform: rotate(45deg) translate(-5px, -6px);
        }
        
        /* Mobile Menu Overlay */
        .mobile-menu-overlay {
            position: fixed;
            top: 0;
            left: -100%;
            width: 80%;
            max-width: 300px;
            height: 100vh;
            background: var(--mdb-surface-bg);
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            z-index: 9998;
            transition: left 0.3s;
            overflow-y: auto;
            padding: 20px;
        }
        
        .mobile-menu-overlay.active {
            left: 0;
        }
        
        .mobile-menu-backdrop {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100vh;
            background: rgba(0,0,0,0.5);
            z-index: 9997;
            display: none;
        }
        
        .mobile-menu-backdrop.active {
            display: block;
        }
        
        .mobile-menu-overlay .menu-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid rgba(0,0,0,0.1);
        }
        
        .mobile-menu-overlay .menu-item {
            padding: 15px 10px;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            color: var(--mdb-body-color);
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .mobile-menu-overlay .menu-item:hover {
            background: rgba(18, 102, 241, 0.1);
            padding-left: 20px;
        }
        
        .mobile-menu-overlay .menu-item i {
            width: 30px;
            font-size: 18px;
            margin-right: 15px;
            color: var(--primary-color);
        }
        
        @media (max-width: 768px) {
            .hamburger-menu {
                display: flex;
            }
        }
        
        /* Dropdown Menu Fix */
        .navbar {
            position: relative !important;
            overflow: visible !important;
            z-index: 10000 !important;
        }
        
        .navbar-nav {
            position: relative !important;
            overflow: visible !important;
            z-index: 10001 !important;
        }
        
        .nav-item.dropdown {
            position: relative !important;
            z-index: 10002 !important;
        }
        
        .dropdown-menu {
            position: absolute !important;
            z-index: 99999 !important;
            min-width: 220px !important;
            box-shadow: 0 8px 24px rgba(0,0,0,0.25) !important;
            border: 1px solid rgba(0,0,0,0.1) !important;
            margin-top: 0.5rem !important;
            background: var(--mdb-surface-bg) !important;
        }
        
        .dropdown-menu.show {
            display: block !important;
            opacity: 1 !important;
            visibility: visible !important;
        }
        
        .dropdown-toggle::after {
            margin-left: 0.5em !important;
        }
        
        .dropdown-item {
            padding: 10px 20px !important;
            transition: all 0.3s !important;
            display: flex !important;
            align-items: center !important;
        }
        
        .dropdown-item:hover {
            background-color: rgba(18, 102, 241, 0.1) !important;
            padding-left: 25px !important;
        }
        
        .dropdown-item i {
            width: 20px !important;
            margin-right: 10px !important;
        }
        
        .dropdown-divider {
            margin: 0.5rem 0 !important;
        }
        
        /* Product Cards */
        .product-card {
            transition: transform 0.3s, box-shadow 0.3s;
            height: 100%;
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }
        
        .product-card img {
            height: 200px;
            object-fit: cover;
        }
        
        /* Mobile Product Card */
        .mobile-product-card {
            margin-bottom: 16px;
            border-radius: 12px;
            overflow: hidden;
        }
        
        .mobile-product-card img {
            height: 180px;
            object-fit: cover;
            width: 100%;
        }
        
        /* Cart Badge */
        .cart-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #dc4c64;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        /* Theme Toggle */
        .theme-toggle {
            cursor: pointer;
            font-size: 20px;
        }
        
        /* Desktop Styles */
        @media (min-width: 768px) {
            body {
                padding-bottom: 0;
            }
            
            .mobile-bottom-nav,
            .mobile-appbar {
                display: none;
            }
            
            .desktop-nav {
                display: block;
            }
            
            .product-card img {
                height: 250px;
            }
            
            .mobile-product-card {
                display: none;
            }
        }
        
        /* Hero Section */
        .hero-section {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 80px 0;
            text-align: center;
        }
        
        .hero-section h1 {
            font-size: 3rem;
            font-weight: bold;
            margin-bottom: 20px;
        }
        
        @media (max-width: 767px) {
            .hero-section {
                padding: 40px 20px;
            }
            
            .hero-section h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    
    <!-- Mobile AppBar -->
    <div class="mobile-appbar">
        <div class="hamburger-menu" onclick="toggleMobileMenu()">
            <span></span>
            <span></span>
            <span></span>
        </div>
        <div class="logo">
            <i class="fas fa-gem"></i> <?php echo getSetting('site_name', 'GyaanBazaar'); ?>
        </div>
        <div>
            <span class="theme-toggle me-3" onclick="toggleTheme()">
                <i class="fas fa-moon"></i>
            </span>
            <?php if (isLoggedIn()): ?>
                <a href="<?php echo SITE_URL; ?>/cart.php" class="position-relative">
                    <i class="fas fa-shopping-cart"></i>
                    <?php if (isset($_SESSION['cart']) && count($_SESSION['cart']) > 0): ?>
                        <span class="cart-badge"><?php echo count($_SESSION['cart']); ?></span>
                    <?php endif; ?>
                </a>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Mobile Menu Overlay -->
    <div class="mobile-menu-backdrop" onclick="toggleMobileMenu()"></div>
    <div class="mobile-menu-overlay">
        <div class="menu-header">
            <h5><i class="fas fa-bars"></i> Menu</h5>
            <button class="btn btn-sm btn-light" onclick="toggleMobileMenu()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <?php if (isLoggedIn()): ?>
            <div class="mb-3 pb-3 border-bottom">
                <div class="d-flex align-items-center">
                    <i class="fas fa-user-circle fa-3x text-primary me-3"></i>
                    <div>
                        <strong><?php echo $_SESSION['user_name']; ?></strong><br>
                        <small class="text-muted"><?php echo $_SESSION['user_email']; ?></small>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <a href="<?php echo SITE_URL; ?>" class="menu-item">
            <i class="fas fa-home"></i> Home
        </a>
        <a href="<?php echo SITE_URL; ?>/products.php" class="menu-item">
            <i class="fas fa-th-large"></i> Products
        </a>
        
        <?php if (isLoggedIn()): ?>
            <a href="<?php echo SITE_URL; ?>/orders.php" class="menu-item">
                <i class="fas fa-box"></i> My Orders
            </a>
            <a href="<?php echo SITE_URL; ?>/affiliate-dashboard.php" class="menu-item">
                <i class="fas fa-handshake"></i> Affiliate
            </a>
            <a href="<?php echo SITE_URL; ?>/cart.php" class="menu-item">
                <i class="fas fa-shopping-cart"></i> Cart
                <?php if (isset($_SESSION['cart']) && count($_SESSION['cart']) > 0): ?>
                    <span class="badge bg-primary ms-2"><?php echo count($_SESSION['cart']); ?></span>
                <?php endif; ?>
            </a>
            <a href="<?php echo SITE_URL; ?>/profile.php" class="menu-item">
                <i class="fas fa-user"></i> Profile
            </a>
            
            <?php if (isAdmin()): ?>
                <a href="<?php echo SITE_URL; ?>/admin" class="menu-item">
                    <i class="fas fa-cog"></i> Admin Dashboard
                </a>
            <?php endif; ?>
            
            <a href="<?php echo SITE_URL; ?>/logout.php" class="menu-item text-danger">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        <?php else: ?>
            <a href="<?php echo SITE_URL; ?>/login.php" class="menu-item">
                <i class="fas fa-sign-in-alt"></i> Login
            </a>
            <a href="<?php echo SITE_URL; ?>/signup.php" class="menu-item">
                <i class="fas fa-user-plus"></i> Sign Up
            </a>
        <?php endif; ?>
    </div>
    
    <!-- Desktop Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light desktop-nav">
        <div class="container">
            <a class="navbar-brand" href="<?php echo SITE_URL; ?>">
                <i class="fas fa-gem text-primary"></i>
                <strong><?php echo getSetting('site_name', 'GyaanBazaar'); ?></strong>
            </a>
            
            <button class="navbar-toggler" type="button" data-mdb-toggle="collapse" data-mdb-target="#navbarNav">
                <i class="fas fa-bars"></i>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage == 'index' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage == 'products' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/products.php">Products</a>
                    </li>
                    
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $currentPage == 'orders' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/orders.php">My Orders</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $currentPage == 'affiliate-dashboard' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/affiliate-dashboard.php">
                                <i class="fas fa-handshake"></i> Affiliate
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link position-relative <?php echo $currentPage == 'cart' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/cart.php">
                                <i class="fas fa-shopping-cart"></i> Cart
                                <?php if (isset($_SESSION['cart']) && count($_SESSION['cart']) > 0): ?>
                                    <span class="cart-badge"><?php echo count($_SESSION['cart']); ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" 
                               data-mdb-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user"></i> <?php echo $_SESSION['user_name']; ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/profile.php">
                                    <i class="fas fa-user-circle"></i> Profile
                                </a></li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/orders.php">
                                    <i class="fas fa-shopping-bag"></i> My Orders
                                </a></li>
                                <?php if (isAdmin()): ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/admin">
                                        <i class="fas fa-cog"></i> Admin Dashboard
                                    </a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="<?php echo SITE_URL; ?>/logout.php">
                                    <i class="fas fa-sign-out-alt"></i> Logout
                                </a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo SITE_URL; ?>/login.php">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="btn btn-primary btn-sm" href="<?php echo SITE_URL; ?>/signup.php">Sign Up</a>
                        </li>
                    <?php endif; ?>
                    
                    <li class="nav-item ms-2">
                        <span class="theme-toggle" onclick="toggleTheme()">
                            <i class="fas fa-moon"></i>
                        </span>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
