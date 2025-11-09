<?php
require_once 'config/config.php';
$pageTitle = 'Home - ' . getSetting('site_name');

// Track affiliate referral clicks
if (isset($_GET['ref'])) {
    $referralCode = $_GET['ref'];
    
    // Set cookie for tracking (30 days default)
    $cookieDuration = (int)getAffiliateSetting('cookie_duration_days', 30);
    setcookie('affiliate_ref', $referralCode, time() + ($cookieDuration * 86400), '/');
    
    // Track click
    trackAffiliateClick($referralCode);
    
    // Track referral if user is logged in
    if (isset($_SESSION['user_id'])) {
        trackAffiliateReferral($referralCode, $_SESSION['user_id']);
    }
    
    // Redirect to clean URL
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}

// Get featured products
$featuredProducts = $conn->query("SELECT * FROM products WHERE status = 'active' ORDER BY downloads DESC LIMIT 6")->fetch_all(MYSQLI_ASSOC);

include 'includes/header.php';
?>

<!-- Hero Section with Advanced Animation -->
<section class="position-relative overflow-hidden" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 100px 0;">
    <div class="container position-relative" style="z-index: 2;">
        <div class="row align-items-center">
            <div class="col-lg-6 text-white animate-fade-in-left">
                <h1 class="display-3 fw-bold mb-4 gradient-text" style="color: white !important;">
                    Welcome to<br><?php echo getSetting('site_name'); ?>
                </h1>
                <p class="lead mb-4 animate-fade-in-left delay-200" style="font-size: 1.25rem;">
                    Discover premium digital products, online courses, and ebooks for your creative journey
                </p>
                <div class="animate-fade-in-up delay-300">
                    <a href="<?php echo SITE_URL; ?>/products.php" class="btn-gradient me-3 mb-2">
                        <i class="fas fa-rocket"></i> Explore Products
                    </a>
                    <a href="<?php echo SITE_URL; ?>/products.php?type=course" class="btn-modern glass-button mb-2">
                        <i class="fas fa-video"></i> Browse Courses
                    </a>
                </div>
            </div>
            <div class="col-lg-6 d-none d-lg-block">
                <div class="animate-float">
                    <img src="https://via.placeholder.com/500x400/667eea/ffffff?text=Digital+Products" 
                         alt="Hero" class="img-fluid rounded-4 shadow-lg" style="border-radius: 2rem;">
                </div>
            </div>
        </div>
    </div>
    
    <!-- Animated Background Elements -->
    <div class="position-absolute" style="top: 10%; left: 5%; width: 100px; height: 100px; background: rgba(255,255,255,0.1); border-radius: 50%; animation: float 6s ease-in-out infinite;"></div>
    <div class="position-absolute" style="bottom: 10%; right: 5%; width: 150px; height: 150px; background: rgba(255,255,255,0.1); border-radius: 50%; animation: float 8s ease-in-out infinite;"></div>
</section>

<!-- Stats Section -->
<section class="py-5" style="background: #f7fafc;">
    <div class="container">
        <div class="row text-center">
            <div class="col-md-3 col-6 mb-4 animate-fade-in-up">
                <div class="stats-card glass-card p-4">
                    <div class="stats-card-icon primary mx-auto mb-3">
                        <i class="fas fa-box-open"></i>
                    </div>
                    <h3 class="fw-bold mb-0">500+</h3>
                    <p class="text-muted mb-0">Products</p>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-4 animate-fade-in-up delay-100">
                <div class="stats-card glass-card p-4">
                    <div class="stats-card-icon success mx-auto mb-3">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3 class="fw-bold mb-0">10K+</h3>
                    <p class="text-muted mb-0">Happy Users</p>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-4 animate-fade-in-up delay-200">
                <div class="stats-card glass-card p-4">
                    <div class="stats-card-icon danger mx-auto mb-3">
                        <i class="fas fa-download"></i>
                    </div>
                    <h3 class="fw-bold mb-0">50K+</h3>
                    <p class="text-muted mb-0">Downloads</p>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-4 animate-fade-in-up delay-300">
                <div class="stats-card glass-card p-4">
                    <div class="stats-card-icon warning mx-auto mb-3">
                        <i class="fas fa-star"></i>
                    </div>
                    <h3 class="fw-bold mb-0">4.9/5</h3>
                    <p class="text-muted mb-0">Rating</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Featured Products -->
<section class="container my-5 py-5">
    <div class="text-center mb-5 animate-fade-in-down">
        <h2 class="display-5 fw-bold mb-3">
            <i class="fas fa-star text-warning animate-pulse"></i> Featured Products
        </h2>
        <p class="lead text-muted">Handpicked premium products just for you</p>
    </div>
    
    <div class="row">
        <?php 
        $delay = 0;
        foreach ($featuredProducts as $product): 
        ?>
        <div class="col-lg-4 col-md-6 mb-4 animate-fade-in-up delay-<?php echo $delay; ?>00">
            <div class="modern-card modern-card-hover-lift">
                <div class="position-relative overflow-hidden" style="height: 250px;">
                    <img src="<?php echo UPLOAD_URL . ($product['screenshots'] ? explode(',', $product['screenshots'])[0] : 'placeholder.jpg'); ?>" 
                         class="w-100 h-100" style="object-fit: cover; transition: transform 0.5s ease;" 
                         onmouseover="this.style.transform='scale(1.1)'" 
                         onmouseout="this.style.transform='scale(1)'"
                         alt="<?php echo htmlspecialchars($product['title']); ?>">
                    
                    <!-- Product Type Badge -->
                    <div class="position-absolute top-0 end-0 m-3">
                        <?php
                        $productType = $product['product_type'] ?? 'digital';
                        $badges = [
                            'course' => ['icon' => 'fa-video', 'gradient' => 'gradient-success', 'text' => 'Course'],
                            'ebook' => ['icon' => 'fa-book', 'gradient' => 'gradient-primary', 'text' => 'eBook'],
                            'digital' => ['icon' => 'fa-file-download', 'gradient' => 'gradient-secondary', 'text' => 'Digital']
                        ];
                        $badge = $badges[$productType] ?? $badges['digital'];
                        ?>
                        <span class="badge-modern <?php echo $badge['gradient']; ?> animate-glow">
                            <i class="fas <?php echo $badge['icon']; ?>"></i> <?php echo $badge['text']; ?>
                        </span>
                    </div>
                    
                    <!-- Overlay on Hover -->
                    <div class="position-absolute bottom-0 start-0 end-0 p-3" 
                         style="background: linear-gradient(to top, rgba(0,0,0,0.7), transparent);">
                        <div class="text-white">
                            <small><i class="fas fa-download"></i> <?php echo $product['downloads']; ?> downloads</small>
                        </div>
                    </div>
                </div>
                
                <div class="p-4">
                    <h5 class="fw-bold mb-2"><?php echo htmlspecialchars($product['title']); ?></h5>
                    <p class="text-muted mb-3" style="font-size: 0.9rem; height: 40px; overflow: hidden;">
                        <?php echo htmlspecialchars(substr($product['description'], 0, 80)); ?>...
                    </p>
                    
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="h4 mb-0 fw-bold gradient-text"><?php echo formatCurrency($product['price']); ?></span>
                        </div>
                        <a href="<?php echo SITE_URL; ?>/product-detail.php?id=<?php echo $product['id']; ?>" 
                           class="btn-modern gradient-primary">
                            View Details <i class="fas fa-arrow-right ms-2"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php 
        $delay = ($delay + 1) % 5;
        endforeach; 
        ?>
    </div>
    
    <div class="text-center mt-5 animate-fade-in-up">
        <a href="<?php echo SITE_URL; ?>/products.php" class="btn-gradient btn-lg">
            View All Products <i class="fas fa-arrow-right ms-2"></i>
        </a>
    </div>
</section>

<!-- Features Section -->
<section class="py-5" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
    <div class="container">
        <div class="text-center text-white mb-5 animate-fade-in-down">
            <h2 class="display-5 fw-bold mb-3">Why Choose Us?</h2>
            <p class="lead">Premium quality products with exceptional support</p>
        </div>
        
        <div class="row">
            <div class="col-md-4 mb-4 animate-fade-in-left">
                <div class="glass-card p-4 text-center text-white h-100">
                    <div class="mb-3">
                        <i class="fas fa-shield-alt fa-3x animate-pulse"></i>
                    </div>
                    <h4 class="fw-bold mb-3">Secure & Safe</h4>
                    <p class="mb-0">All transactions are encrypted and secure. Your data is protected.</p>
                </div>
            </div>
            <div class="col-md-4 mb-4 animate-fade-in-up delay-200">
                <div class="glass-card p-4 text-center text-white h-100">
                    <div class="mb-3">
                        <i class="fas fa-bolt fa-3x animate-pulse"></i>
                    </div>
                    <h4 class="fw-bold mb-3">Instant Access</h4>
                    <p class="mb-0">Get immediate access to your purchases. Download anytime, anywhere.</p>
                </div>
            </div>
            <div class="col-md-4 mb-4 animate-fade-in-right">
                <div class="glass-card p-4 text-center text-white h-100">
                    <div class="mb-3">
                        <i class="fas fa-headset fa-3x animate-pulse"></i>
                    </div>
                    <h4 class="fw-bold mb-3">24/7 Support</h4>
                    <p class="mb-0">Our dedicated support team is always here to help you.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="py-5 my-5">
    <div class="container">
        <div class="modern-card p-5 text-center" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
            <div class="text-white animate-scale-in">
                <h2 class="display-5 fw-bold mb-3">Ready to Get Started?</h2>
                <p class="lead mb-4">Join thousands of satisfied customers today!</p>
                <?php if (!isLoggedIn()): ?>
                <a href="<?php echo SITE_URL; ?>/signup.php" class="btn-modern glass-button btn-lg me-3">
                    <i class="fas fa-user-plus"></i> Sign Up Now
                </a>
                <?php endif; ?>
                <a href="<?php echo SITE_URL; ?>/products.php" class="btn-modern glass-button btn-lg">
                    <i class="fas fa-shopping-bag"></i> Start Shopping
                </a>
            </div>
        </div>
    </div>
</section>

<style>
/* Additional inline styles for this page */
.stats-card {
    transition: all 0.3s ease;
}

.stats-card:hover {
    transform: translateY(-10px) scale(1.05);
}

.stats-card-icon {
    width: 70px;
    height: 70px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
}

.stats-card-icon.primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.stats-card-icon.success {
    background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
    color: white;
}

.stats-card-icon.danger {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: white;
}

.stats-card-icon.warning {
    background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
    color: white;
}
</style>

<?php include 'includes/footer.php'; ?>
