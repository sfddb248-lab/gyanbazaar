<?php
require_once 'config/config.php';

$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get product
$stmt = $conn->prepare("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.id = ? AND p.status = 'active'");
$stmt->bind_param("i", $productId);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    header('Location: ' . SITE_URL . '/products.php');
    exit;
}

$pageTitle = $product['title'] . ' - ' . getSetting('site_name');

// Handle Add to Cart
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_to_cart'])) {
    requireLogin();
    
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    if (!in_array($productId, array_keys($_SESSION['cart']))) {
        $_SESSION['cart'][$productId] = 1;
        $success = 'Product added to cart!';
    } else {
        $info = 'Product already in cart';
    }
}

// Get related products
$stmt = $conn->prepare("SELECT * FROM products WHERE category_id = ? AND id != ? AND status = 'active' LIMIT 4");
$stmt->bind_param("ii", $product['category_id'], $productId);
$stmt->execute();
$relatedProducts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$screenshots = $product['screenshots'] ? explode(',', $product['screenshots']) : [];

include 'includes/header.php';
?>

<div class="container my-4">
    <?php if (isset($success)): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            <a href="<?php echo SITE_URL; ?>/cart.php" class="alert-link">Go to Cart</a>
            <button type="button" class="btn-close" data-mdb-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if (isset($info)): ?>
        <div class="alert alert-info alert-dismissible fade show">
            <i class="fas fa-info-circle"></i> <?php echo $info; ?>
            <button type="button" class="btn-close" data-mdb-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <div class="row">
        <!-- Product Images -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <img src="<?php echo !empty($screenshots[0]) ? UPLOAD_URL . $screenshots[0] : getCourseImage($product); ?>" 
                     class="card-img-top" alt="<?php echo htmlspecialchars($product['title']); ?>" 
                     style="height: 400px; object-fit: cover;"
                     onerror="this.src='<?php echo getCourseImage($product); ?>'">
            </div>
            
            <?php if (count($screenshots) > 1): ?>
            <div class="row mt-3">
                <?php foreach (array_slice($screenshots, 1, 3) as $screenshot): ?>
                <div class="col-4">
                    <img src="<?php echo UPLOAD_URL . $screenshot; ?>" 
                         class="img-fluid rounded" alt="Screenshot" 
                         style="height: 100px; object-fit: cover; width: 100%;">
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Product Details -->
        <div class="col-md-6">
            <h1 class="mb-3"><?php echo htmlspecialchars($product['title']); ?></h1>
            
            <div class="mb-3">
                <span class="badge bg-primary"><?php echo htmlspecialchars($product['category_name']); ?></span>
                <?php if (($product['product_type'] ?? 'digital') == 'ebook'): ?>
                    <span class="badge bg-info"><i class="fas fa-book"></i> eBook</span>
                <?php elseif (($product['product_type'] ?? 'digital') == 'course'): ?>
                    <span class="badge bg-success"><i class="fas fa-video"></i> Video Course</span>
                <?php endif; ?>
                <span class="text-muted ms-2">
                    <i class="fas fa-download"></i> <?php echo $product['downloads']; ?> downloads
                </span>
            </div>
            
            <?php if (($product['product_type'] ?? 'digital') == 'ebook' && $product['total_pages'] > 0): ?>
            <div class="mb-3">
                <span class="text-muted">
                    <i class="fas fa-file-pdf"></i> <?php echo $product['total_pages']; ?> pages
                </span>
            </div>
            <?php endif; ?>
            
            <?php if (($product['product_type'] ?? 'digital') == 'course'): ?>
            <?php
            // Get course statistics
            $lectureCount = $conn->query("SELECT COUNT(*) as count FROM course_videos WHERE product_id = $productId")->fetch_assoc()['count'];
            $sectionCount = $conn->query("SELECT COUNT(*) as count FROM course_sections WHERE product_id = $productId")->fetch_assoc()['count'];
            $totalDuration = $conn->query("SELECT SUM(video_size) as total FROM course_videos WHERE product_id = $productId")->fetch_assoc()['total'];
            ?>
            <div class="alert alert-success mb-3">
                <div class="row text-center">
                    <div class="col-4">
                        <h4 class="mb-0"><i class="fas fa-video"></i> <?php echo $lectureCount; ?></h4>
                        <small>Lectures</small>
                    </div>
                    <div class="col-4">
                        <h4 class="mb-0"><i class="fas fa-folder"></i> <?php echo $sectionCount; ?></h4>
                        <small>Sections</small>
                    </div>
                    <div class="col-4">
                        <h4 class="mb-0"><i class="fas fa-infinity"></i></h4>
                        <small>Lifetime Access</small>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <h2 class="text-primary mb-4"><?php echo formatCurrency($product['price']); ?></h2>
            
            <div class="mb-4">
                <h5>Description</h5>
                <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
            </div>
            
            <?php if (($product['product_type'] ?? 'digital') == 'course'): ?>
            <!-- Course Curriculum -->
            <div class="mb-4">
                <h5><i class="fas fa-list"></i> Course Curriculum</h5>
                <?php
                // Get course sections with videos
                $sections = $conn->query("
                    SELECT s.*, COUNT(v.id) as video_count
                    FROM course_sections s
                    LEFT JOIN course_videos v ON s.id = v.section_id
                    WHERE s.product_id = $productId
                    GROUP BY s.id
                    ORDER BY s.order_index ASC
                ")->fetch_all(MYSQLI_ASSOC);
                ?>
                
                <?php if (!empty($sections)): ?>
                <div class="accordion" id="curriculumAccordion">
                    <?php foreach ($sections as $index => $section): ?>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="heading<?php echo $section['id']; ?>">
                            <button class="accordion-button <?php echo $index > 0 ? 'collapsed' : ''; ?>" 
                                    type="button" 
                                    data-mdb-toggle="collapse" 
                                    data-mdb-target="#collapse<?php echo $section['id']; ?>">
                                <i class="fas fa-folder me-2"></i>
                                <?php echo htmlspecialchars($section['title']); ?>
                                <span class="badge bg-primary ms-2"><?php echo $section['video_count']; ?> lectures</span>
                            </button>
                        </h2>
                        <div id="collapse<?php echo $section['id']; ?>" 
                             class="accordion-collapse collapse <?php echo $index == 0 ? 'show' : ''; ?>" 
                             data-mdb-parent="#curriculumAccordion">
                            <div class="accordion-body">
                                <?php
                                // Get videos for this section
                                $videos = $conn->query("
                                    SELECT title, video_duration, is_preview
                                    FROM course_videos
                                    WHERE section_id = {$section['id']}
                                    ORDER BY order_index ASC
                                ")->fetch_all(MYSQLI_ASSOC);
                                ?>
                                
                                <?php if (!empty($videos)): ?>
                                <ul class="list-unstyled mb-0">
                                    <?php foreach ($videos as $video): ?>
                                    <li class="mb-2">
                                        <i class="fas fa-play-circle text-primary"></i>
                                        <?php echo htmlspecialchars($video['title']); ?>
                                        <?php if ($video['video_duration']): ?>
                                            <span class="text-muted">(<?php echo $video['video_duration']; ?>)</span>
                                        <?php endif; ?>
                                        <?php if ($video['is_preview']): ?>
                                            <span class="badge bg-success">Free Preview</span>
                                        <?php endif; ?>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                                <?php else: ?>
                                <p class="text-muted mb-0">No lectures in this section yet.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p class="text-muted">Course curriculum will be available soon.</p>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($product['file_path']) && isset($product['preview_pages']) && $product['preview_pages'] > 0): ?>
            <div class="alert alert-info mb-4">
                <i class="fas fa-book-open"></i> 
                <strong>Free Preview Available!</strong> Read the first <?php echo $product['preview_pages']; ?> pages for free.
            </div>
            <div class="mb-4">
                <a href="<?php echo SITE_URL; ?>/ebook-viewer.php?id=<?php echo $productId; ?>" 
                   class="btn btn-outline-primary btn-lg w-100" target="_blank">
                    <i class="fas fa-book-reader"></i> Read Preview (<?php echo $product['preview_pages']; ?> pages)
                </a>
            </div>
            <?php endif; ?>
            
            <?php if ($product['demo_url']): ?>
            <div class="mb-4">
                <a href="<?php echo htmlspecialchars($product['demo_url']); ?>" 
                   target="_blank" class="btn btn-outline-primary">
                    <i class="fas fa-external-link-alt"></i> View Demo
                </a>
            </div>
            <?php endif; ?>
            
            <?php if (($product['product_type'] ?? 'digital') == 'course'): ?>
            <?php
            // Check if course has preview video
            $hasPreview = $conn->query("SELECT COUNT(*) as count FROM course_videos WHERE product_id = $productId AND is_preview = 1")->fetch_assoc()['count'];
            if ($hasPreview > 0):
            ?>
            <div class="alert alert-info mb-4">
                <i class="fas fa-play-circle"></i> 
                <strong>Free Preview Available!</strong> Watch 1 lecture for free before purchasing.
            </div>
            <div class="mb-4">
                <a href="<?php echo SITE_URL; ?>/course-preview.php?id=<?php echo $productId; ?>" 
                   class="btn btn-success btn-lg w-100" target="_blank">
                    <i class="fas fa-play-circle"></i> Watch Free Preview Lecture
                </a>
            </div>
            <?php endif; ?>
            <?php endif; ?>
            
            <?php if (isLoggedIn()): ?>
                <form method="POST" action="">
                    <button type="submit" name="add_to_cart" class="btn btn-primary btn-lg w-100 mb-2">
                        <i class="fas fa-shopping-cart"></i> Add to Cart
                    </button>
                </form>
                <a href="<?php echo SITE_URL; ?>/checkout.php?product=<?php echo $productId; ?>" 
                   class="btn btn-success btn-lg w-100">
                    <i class="fas fa-bolt"></i> Buy Now
                </a>
            <?php else: ?>
                <a href="<?php echo SITE_URL; ?>/login.php" class="btn btn-primary btn-lg w-100">
                    <i class="fas fa-sign-in-alt"></i> Login to Purchase
                </a>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Related Products -->
    <?php if (!empty($relatedProducts)): ?>
    <section class="mt-5">
        <h3 class="mb-4"><i class="fas fa-layer-group"></i> Related Products</h3>
        <div class="row">
            <?php foreach ($relatedProducts as $related): ?>
            <div class="col-md-3 col-6 mb-4">
                <div class="card product-card h-100">
                    <img src="<?php echo UPLOAD_URL . ($related['screenshots'] ? explode(',', $related['screenshots'])[0] : 'placeholder.jpg'); ?>" 
                         class="card-img-top" alt="<?php echo htmlspecialchars($related['title']); ?>">
                    <div class="card-body">
                        <h6 class="card-title"><?php echo htmlspecialchars($related['title']); ?></h6>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-primary"><?php echo formatCurrency($related['price']); ?></span>
                            <a href="<?php echo SITE_URL; ?>/product-detail.php?id=<?php echo $related['id']; ?>" 
                               class="btn btn-sm btn-primary">View</a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
