<?php
require_once 'config/config.php';

$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$productId) {
    header('Location: ' . SITE_URL . '/products.php');
    exit;
}

// Get product details
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param("i", $productId);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    header('Location: ' . SITE_URL . '/products.php');
    exit;
}

// Check if product has a file to view
if (empty($product['file_path'])) {
    $_SESSION['error'] = 'This product does not have a viewable file.';
    header('Location: ' . SITE_URL . '/product-detail.php?id=' . $productId);
    exit;
}

// Check if user has purchased
$hasPurchased = false;
if (isLoggedIn()) {
    $userId = $_SESSION['user_id'];
    $stmt = $conn->prepare("
        SELECT oi.* FROM order_items oi 
        JOIN orders o ON oi.order_id = o.id 
        WHERE o.user_id = ? AND oi.product_id = ? AND o.payment_status = 'completed'
    ");
    $stmt->bind_param("ii", $userId, $productId);
    $stmt->execute();
    $hasPurchased = $stmt->get_result()->num_rows > 0;
}

$pageTitle = $product['title'] . ' - ' . getSetting('site_name');
include 'includes/header.php';
?>

<style>
/* Fullscreen styles for PDF viewer */
#pdfViewer:fullscreen {
    width: 100vw !important;
    height: 100vh !important;
    max-width: 100vw !important;
    padding: 0;
    margin: 0;
    border: none !important;
}

#pdfViewer:-webkit-full-screen {
    width: 100vw !important;
    height: 100vh !important;
    max-width: 100vw !important;
    padding: 0;
    margin: 0;
    border: none !important;
}

#pdfViewer:-moz-full-screen {
    width: 100vw !important;
    height: 100vh !important;
    max-width: 100vw !important;
    padding: 0;
    margin: 0;
    border: none !important;
}

#pdfViewer:fullscreen iframe {
    width: 100% !important;
    height: 100% !important;
}
</style>

<div class="container my-4">
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="mb-0"><?php echo htmlspecialchars($product['title']); ?></h2>
                        <button id="openFullscreen" class="btn btn-primary">
                            <i class="fas fa-expand"></i> Open in Fullscreen
                        </button>
                    </div>
                    
                    <?php if (!$hasPurchased && $product['preview_pages'] > 0): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> 
                            You're viewing a preview (<?php echo $product['preview_pages']; ?> of <?php echo $product['total_pages']; ?> pages).
                            <a href="<?php echo SITE_URL; ?>/product-detail.php?slug=<?php echo $product['slug']; ?>">Purchase</a> to access the full content.
                        </div>
                    <?php endif; ?>
                    
                    <div id="pdfViewer" class="border" style="height: 800px; overflow: auto; background: #525659;">
                        <?php if ($product['file_path'] && file_exists($product['file_path'])): ?>
                            <iframe 
                                src="<?php echo SITE_URL; ?>/pdf-viewer.php?id=<?php echo $productId; ?>" 
                                width="100%" 
                                height="100%" 
                                style="border: none;"
                                onload="console.log('PDF viewer loaded')"
                                onerror="console.error('Error loading PDF viewer')">
                            </iframe>
                        <?php else: ?>
                            <div class="text-center p-5">
                                <i class="fas fa-file-pdf fa-5x text-muted mb-3"></i>
                                <p>PDF file not available</p>
                                <p class="text-muted small">
                                    File path: <?php echo htmlspecialchars($product['file_path'] ?? 'Not set'); ?><br>
                                    File exists: <?php echo file_exists($product['file_path'] ?? '') ? 'Yes' : 'No'; ?>
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card sticky-top" style="top: 20px;">
                <div class="card-body">
                    <h5 class="card-title">About this eBook</h5>
                    <p class="card-text"><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                    
                    <hr>
                    
                    <div class="mb-3">
                        <strong>Total Pages:</strong> <?php echo $product['total_pages']; ?>
                    </div>
                    
                    <?php if (!$hasPurchased): ?>
                        <div class="mb-3">
                            <strong>Preview Pages:</strong> <?php echo $product['preview_pages']; ?>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <a href="<?php echo SITE_URL; ?>/product-detail.php?slug=<?php echo $product['slug']; ?>" 
                               class="btn btn-primary btn-lg">
                                <i class="fas fa-shopping-cart"></i> Buy Full eBook
                                <br><small><?php echo formatCurrency($product['price']); ?></small>
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> You own this eBook
                        </div>
                        
                        <div class="d-grid gap-2">
                            <a href="<?php echo SITE_URL; ?>/download.php?id=<?php echo $productId; ?>" 
                               class="btn btn-success">
                                <i class="fas fa-download"></i> Download PDF
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Fullscreen button functionality
document.getElementById('openFullscreen').addEventListener('click', function() {
    const pdfViewer = document.getElementById('pdfViewer');
    
    if (pdfViewer.requestFullscreen) {
        pdfViewer.requestFullscreen();
    } else if (pdfViewer.webkitRequestFullscreen) {
        pdfViewer.webkitRequestFullscreen();
    } else if (pdfViewer.msRequestFullscreen) {
        pdfViewer.msRequestFullscreen();
    }
    
    // Change button text when in fullscreen
    this.innerHTML = '<i class="fas fa-compress"></i> Exit Fullscreen';
});

// Listen for fullscreen changes
document.addEventListener('fullscreenchange', function() {
    const btn = document.getElementById('openFullscreen');
    if (!document.fullscreenElement) {
        btn.innerHTML = '<i class="fas fa-expand"></i> Open in Fullscreen';
    }
});

// Also handle webkit and moz prefixes
document.addEventListener('webkitfullscreenchange', function() {
    const btn = document.getElementById('openFullscreen');
    if (!document.webkitFullscreenElement) {
        btn.innerHTML = '<i class="fas fa-expand"></i> Open in Fullscreen';
    }
});
</script>

<?php include 'includes/footer.php'; ?>
