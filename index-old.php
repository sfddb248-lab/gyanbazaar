<?php
require_once 'config/config.php';
$pageTitle = 'Home - ' . getSetting('site_name');

// Get featured products
$featuredProducts = $conn->query("SELECT * FROM products WHERE status = 'active' ORDER BY downloads DESC LIMIT 6")->fetch_all(MYSQLI_ASSOC);

include 'includes/header.php';
?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <h1 class="animate__animated animate__fadeInDown">Welcome to <?php echo getSetting('site_name'); ?></h1>
        <p class="lead mb-4">Discover premium digital products for your creative projects</p>
        <a href="<?php echo SITE_URL; ?>/products.php" class="btn btn-light btn-lg">
            <i class="fas fa-rocket"></i> Explore Products
        </a>
    </div>
</section>

<!-- Featured Products -->
<section class="container my-5">
    <h2 class="text-center mb-4">
        <i class="fas fa-star text-warning"></i> Featured Products
    </h2>
    
    <!-- Desktop Grid -->
    <div class="row d-none d-md-flex">
        <?php foreach ($featuredProducts as $product): ?>
        <div class="col-md-4 mb-4">
            <div class="card product-card">
                <div class="position-relative">
                    <img src="<?php echo UPLOAD_URL . ($product['screenshots'] ? explode(',', $product['screenshots'])[0] : 'placeholder.jpg'); ?>" 
                         class="card-img-top" alt="<?php echo htmlspecialchars($product['title']); ?>">
                    <!-- Product Type Badge -->
                    <div class="position-absolute top-0 end-0 m-2">
                        <?php
                        $productType = $product['product_type'] ?? 'digital';
                        $badges = [
                            'course' => ['icon' => 'fa-video', 'color' => 'success', 'text' => 'Course'],
                            'ebook' => ['icon' => 'fa-book', 'color' => 'info', 'text' => 'eBook'],
                            'digital' => ['icon' => 'fa-file-download', 'color' => 'primary', 'text' => 'Digital']
                        ];
                        $badge = $badges[$productType] ?? $badges['digital'];
                        ?>
                        <span class="badge bg-<?php echo $badge['color']; ?> shadow">
                            <i class="fas <?php echo $badge['icon']; ?>"></i> <?php echo $badge['text']; ?>
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <h5 class="card-title"><?php echo htmlspecialchars($product['title']); ?></h5>
                    <p class="card-text text-truncate"><?php echo htmlspecialchars(substr($product['description'], 0, 100)); ?>...</p>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="h5 mb-0 text-primary"><?php echo formatCurrency($product['price']); ?></span>
                        <a href="<?php echo SITE_URL; ?>/product-detail.php?id=<?php echo $product['id']; ?>" 
                           class="btn btn-primary btn-sm">View Details</a>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Mobile List -->
    <div class="d-md-none">
        <?php foreach ($featuredProducts as $product): ?>
        <div class="card mobile-product-card">
            <div class="position-relative">
                <img src="<?php echo UPLOAD_URL . ($product['screenshots'] ? explode(',', $product['screenshots'])[0] : 'placeholder.jpg'); ?>" 
                     alt="<?php echo htmlspecialchars($product['title']); ?>">
                <!-- Product Type Badge -->
                <div class="position-absolute top-0 end-0 m-2">
                    <?php
                    $productType = $product['product_type'] ?? 'digital';
                    $badges = [
                        'course' => ['icon' => 'fa-video', 'color' => 'success', 'text' => 'Course'],
                        'ebook' => ['icon' => 'fa-book', 'color' => 'info', 'text' => 'eBook'],
                        'digital' => ['icon' => 'fa-file-download', 'color' => 'primary', 'text' => 'Digital']
                    ];
                    $badge = $badges[$productType] ?? $badges['digital'];
                    ?>
                    <span class="badge bg-<?php echo $badge['color']; ?> shadow">
                        <i class="fas <?php echo $badge['icon']; ?>"></i> <?php echo $badge['text']; ?>
                    </span>
                </div>
            </div>
            <div class="card-body">
                <h5 class="card-title"><?php echo htmlspecialchars($product['title']); ?></h5>
                <p class="card-text"><?php echo htmlspecialchars(substr($product['description'], 0, 80)); ?>...</p>
                <div class="d-flex justify-content-between align-items-center">
                    <span class="h5 mb-0 text-primary"><?php echo formatCurrency($product['price']); ?></span>
                    <a href="<?php echo SITE_URL; ?>/product-detail.php?id=<?php echo $product['id']; ?>" 
                       class="btn btn-primary">View</a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- Features Section -->
<section class="bg-light py-5">
    <div class="container">
        <div class="row text-center">
            <div class="col-md-4 mb-4">
                <i class="fas fa-shield-alt fa-3x text-primary mb-3"></i>
                <h4>Secure Downloads</h4>
                <p>All products are securely stored and delivered instantly</p>
            </div>
            <div class="col-md-4 mb-4">
                <i class="fas fa-headset fa-3x text-primary mb-3"></i>
                <h4>24/7 Support</h4>
                <p>Our team is always here to help you</p>
            </div>
            <div class="col-md-4 mb-4">
                <i class="fas fa-sync-alt fa-3x text-primary mb-3"></i>
                <h4>Lifetime Access</h4>
                <p>Download your purchases anytime, anywhere</p>
            </div>
        </div>
    </div>
</section>

<!-- Testimonials -->
<section class="container my-5">
    <h2 class="text-center mb-4">What Our Customers Say</h2>
    <div class="row">
        <div class="col-md-4 mb-3">
            <div class="card">
                <div class="card-body">
                    <div class="mb-3">
                        <i class="fas fa-star text-warning"></i>
                        <i class="fas fa-star text-warning"></i>
                        <i class="fas fa-star text-warning"></i>
                        <i class="fas fa-star text-warning"></i>
                        <i class="fas fa-star text-warning"></i>
                    </div>
                    <p class="card-text">"Amazing quality products! Highly recommended for anyone looking for digital assets."</p>
                    <p class="text-muted mb-0"><strong>- Sarah Johnson</strong></p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card">
                <div class="card-body">
                    <div class="mb-3">
                        <i class="fas fa-star text-warning"></i>
                        <i class="fas fa-star text-warning"></i>
                        <i class="fas fa-star text-warning"></i>
                        <i class="fas fa-star text-warning"></i>
                        <i class="fas fa-star text-warning"></i>
                    </div>
                    <p class="card-text">"Fast delivery and excellent customer service. Will definitely buy again!"</p>
                    <p class="text-muted mb-0"><strong>- Mike Chen</strong></p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card">
                <div class="card-body">
                    <div class="mb-3">
                        <i class="fas fa-star text-warning"></i>
                        <i class="fas fa-star text-warning"></i>
                        <i class="fas fa-star text-warning"></i>
                        <i class="fas fa-star text-warning"></i>
                        <i class="fas fa-star text-warning"></i>
                    </div>
                    <p class="card-text">"Great platform with a wide variety of products. Very user-friendly!"</p>
                    <p class="text-muted mb-0"><strong>- Emily Davis</strong></p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
