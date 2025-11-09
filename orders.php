<?php
require_once 'config/config.php';
requireLogin();
$pageTitle = 'My Orders - ' . getSetting('site_name');

$userId = $_SESSION['user_id'];

// Get user orders
$stmt = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $userId);
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

include 'includes/header.php';
?>

<div class="container my-4">
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle"></i> Order placed successfully! You can now download your products.
            <button type="button" class="btn-close" data-mdb-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <h2 class="mb-4"><i class="fas fa-box"></i> My Orders</h2>
    
    <?php if (empty($orders)): ?>
        <div class="alert alert-info text-center">
            <i class="fas fa-box-open fa-3x mb-3"></i>
            <h4>No orders yet</h4>
            <p>Start shopping to see your orders here!</p>
            <a href="<?php echo SITE_URL; ?>/products.php" class="btn btn-primary">
                <i class="fas fa-shopping-bag"></i> Browse Products
            </a>
        </div>
    <?php else: ?>
        
        <?php foreach ($orders as $order): ?>
            <?php
            // Get order items with product type
            $stmt = $conn->prepare("SELECT oi.*, p.title, p.file_path, p.screenshots, p.product_type FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
            $stmt->bind_param("i", $order['id']);
            $stmt->execute();
            $orderItems = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            ?>
            
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h5 class="mb-0">
                                <i class="fas fa-receipt"></i> Order #<?php echo $order['order_number']; ?>
                            </h5>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <span class="badge bg-light text-dark">
                                <?php echo ucfirst($order['payment_status']); ?>
                            </span>
                            <small class="ms-2"><?php echo date('M d, Y', strtotime($order['created_at'])); ?></small>
                        </div>
                    </div>
                </div>
                
                <div class="card-body">
                    <?php foreach ($orderItems as $item): ?>
                    <div class="row mb-3 pb-3 border-bottom">
                        <div class="col-md-2">
                            <img src="<?php echo getCourseImage($item); ?>" 
                                 class="img-fluid rounded" alt="<?php echo htmlspecialchars($item['title']); ?>"
                                 style="height: 120px; width: 100%; object-fit: cover;"
                                 onerror="this.src='https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=400&h=300&fit=crop'">
                        </div>
                        <div class="col-md-6">
                            <h6><?php echo htmlspecialchars($item['title']); ?></h6>
                            
                            <?php if ($item['product_type'] == 'course'): ?>
                                <?php
                                // Get lecture count for course
                                $lectureCount = $conn->query("SELECT COUNT(*) as count FROM course_videos WHERE product_id = {$item['product_id']}")->fetch_assoc()['count'];
                                $sectionCount = $conn->query("SELECT COUNT(*) as count FROM course_sections WHERE product_id = {$item['product_id']}")->fetch_assoc()['count'];
                                ?>
                                <p class="text-muted mb-0">
                                    <small>
                                        <i class="fas fa-video"></i> <?php echo $lectureCount; ?> Lectures in <?php echo $sectionCount; ?> Sections
                                    </small>
                                </p>
                            <?php else: ?>
                                <p class="text-muted mb-0">
                                    <small>
                                        <i class="fas fa-download"></i> Downloads: <?php echo $item['download_count']; ?> / <?php echo MAX_DOWNLOAD_COUNT; ?>
                                    </small>
                                </p>
                                <?php if ($item['download_expiry']): ?>
                                <p class="text-muted mb-0">
                                    <small>
                                        <i class="fas fa-clock"></i> Expires: <?php echo date('M d, Y', strtotime($item['download_expiry'])); ?>
                                    </small>
                                </p>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-2 text-center">
                            <h6 class="text-primary"><?php echo formatCurrency($item['price']); ?></h6>
                        </div>
                        <div class="col-md-2 text-center">
                            <?php if ($order['payment_status'] == 'completed'): ?>
                                <?php if ($item['product_type'] == 'course'): ?>
                                    <!-- View Course Button for Courses -->
                                    <a href="<?php echo SITE_URL; ?>/course-viewer.php?id=<?php echo $item['product_id']; ?>" 
                                       class="btn btn-primary btn-sm mb-2">
                                        <i class="fas fa-play-circle"></i> View Course
                                    </a>
                                <?php else: ?>
                                    <!-- Download Button for Digital Products/Ebooks -->
                                    <?php if ($item['download_count'] < MAX_DOWNLOAD_COUNT && (!$item['download_expiry'] || strtotime($item['download_expiry']) > time())): ?>
                                        <a href="<?php echo SITE_URL; ?>/download.php?item=<?php echo $item['id']; ?>" 
                                           class="btn btn-success btn-sm">
                                            <i class="fas fa-download"></i> Download
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">
                                            <i class="fas fa-ban"></i> Expired
                                        </span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                    <div class="row mt-3">
                        <div class="col-md-8">
                            <p class="mb-1"><strong>Payment Method:</strong> <?php echo ucfirst($order['payment_method']); ?></p>
                            <?php if ($order['transaction_id']): ?>
                            <p class="mb-1"><strong>Transaction ID:</strong> <?php echo $order['transaction_id']; ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-4 text-md-end">
                            <p class="mb-1">Subtotal: <?php echo formatCurrency($order['total_amount']); ?></p>
                            <?php if ($order['discount_amount'] > 0): ?>
                            <p class="mb-1 text-success">Discount: -<?php echo formatCurrency($order['discount_amount']); ?></p>
                            <?php endif; ?>
                            <p class="mb-1">Tax: <?php echo formatCurrency($order['tax_amount']); ?></p>
                            <h5 class="text-primary">Total: <?php echo formatCurrency($order['final_amount']); ?></h5>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
