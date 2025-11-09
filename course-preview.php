<?php
require_once 'config/config.php';

$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get product details
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ? AND product_type = 'course' AND status = 'active'");
$stmt->bind_param("i", $productId);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    header('Location: ' . SITE_URL . '/products.php');
    exit;
}

// Get the first preview video (only 1 allowed)
$previewVideo = $conn->query("
    SELECT v.*, s.title as section_title
    FROM course_videos v
    JOIN course_sections s ON v.section_id = s.id
    WHERE v.product_id = $productId AND v.is_preview = 1
    ORDER BY s.order_index ASC, v.order_index ASC
    LIMIT 1
")->fetch_assoc();

if (!$previewVideo) {
    // No preview available
    header('Location: ' . SITE_URL . '/product-detail.php?id=' . $productId);
    exit;
}

// Get all sections with video count for sidebar
$sections = $conn->query("
    SELECT s.*, COUNT(v.id) as video_count
    FROM course_sections s
    LEFT JOIN course_videos v ON s.id = v.section_id
    WHERE s.product_id = $productId
    GROUP BY s.id
    ORDER BY s.order_index ASC
")->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Preview: ' . $product['title'];
include 'includes/header.php';
?>

<style>
.preview-banner {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    text-align: center;
    margin-bottom: 20px;
}

.course-sidebar {
    height: calc(100vh - 200px);
    overflow-y: auto;
    background: var(--mdb-surface-bg);
    border-right: 1px solid rgba(0,0,0,0.1);
}

.video-container {
    position: relative;
    width: 100%;
    padding-top: 56.25%;
    background: #000;
    user-select: none;
}

.video-container video {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: contain;
}

.video-container::after {
    content: 'FREE PREVIEW';
    position: absolute;
    bottom: 20px;
    right: 20px;
    color: rgba(255, 255, 255, 0.5);
    font-size: 14px;
    font-weight: bold;
    pointer-events: none;
    z-index: 10;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
}

.locked-video {
    opacity: 0.6;
    cursor: not-allowed;
}

.video-item {
    padding: 12px;
    border-bottom: 1px solid rgba(0,0,0,0.1);
}

.video-item.preview {
    background: rgba(0, 200, 83, 0.1);
    border-left: 3px solid #00c853;
}

.section-header {
    background: rgba(0,0,0,0.05);
    padding: 12px;
    font-weight: bold;
    border-bottom: 2px solid rgba(0,0,0,0.1);
}
</style>

<div class="container-fluid p-0">
    <!-- Preview Banner -->
    <div class="preview-banner">
        <h3><i class="fas fa-play-circle"></i> FREE PREVIEW</h3>
        <p class="mb-0">You're watching 1 free lecture. Purchase the course to access all <?php 
            $totalLectures = $conn->query("SELECT COUNT(*) as count FROM course_videos WHERE product_id = $productId")->fetch_assoc()['count'];
            echo $totalLectures;
        ?> lectures!</p>
    </div>
    
    <div class="row g-0">
        <!-- Sidebar -->
        <div class="col-md-3 course-sidebar">
            <div class="p-3 border-bottom">
                <h5 class="mb-0"><?php echo htmlspecialchars($product['title']); ?></h5>
                <small class="text-muted">Course Preview</small>
            </div>
            
            <?php foreach ($sections as $section): ?>
                <div class="section-header">
                    <i class="fas fa-folder"></i> <?php echo htmlspecialchars($section['title']); ?>
                </div>
                
                <?php
                // Get videos for this section
                $videos = $conn->query("
                    SELECT * FROM course_videos
                    WHERE section_id = {$section['id']}
                    ORDER BY order_index ASC
                ")->fetch_all(MYSQLI_ASSOC);
                
                foreach ($videos as $video):
                    $isPreview = $video['is_preview'];
                    $isCurrent = $video['id'] == $previewVideo['id'];
                ?>
                    <div class="video-item <?php echo $isPreview ? 'preview' : 'locked-video'; ?> <?php echo $isCurrent ? 'bg-light' : ''; ?>">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <?php if ($isPreview): ?>
                                    <i class="fas fa-play-circle text-success"></i>
                                    <?php echo htmlspecialchars($video['title']); ?>
                                    <span class="badge bg-success ms-2">FREE</span>
                                <?php else: ?>
                                    <i class="fas fa-lock text-muted"></i>
                                    <?php echo htmlspecialchars($video['title']); ?>
                                    <span class="badge bg-secondary ms-2">LOCKED</span>
                                <?php endif; ?>
                                <?php if ($video['video_duration']): ?>
                                    <br><small class="text-muted"><?php echo $video['video_duration']; ?></small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </div>
        
        <!-- Main Content -->
        <div class="col-md-9">
            <!-- Video Player -->
            <div class="video-container">
                <video 
                    id="previewVideo" 
                    controls 
                    controlsList="nodownload noremoteplayback"
                    disablePictureInPicture
                    oncontextmenu="return false;"
                    preload="metadata"
                    playsinline>
                    <source src="<?php echo SITE_URL; ?>/stream-video.php?video=<?php echo $previewVideo['id']; ?>&product=<?php echo $productId; ?>&preview=1" type="video/mp4">
                    Your browser does not support the video tag.
                </video>
            </div>
            
            <!-- Video Info -->
            <div class="p-4">
                <div class="alert alert-success mb-3">
                    <i class="fas fa-gift"></i> <strong>Free Preview Lecture</strong>
                    <p class="mb-0">This is a free preview. Purchase the course to unlock all lectures!</p>
                </div>
                
                <h3><?php echo htmlspecialchars($previewVideo['title']); ?></h3>
                <p class="text-muted">Section: <?php echo htmlspecialchars($previewVideo['section_title']); ?></p>
                
                <?php if ($previewVideo['description']): ?>
                <p><?php echo nl2br(htmlspecialchars($previewVideo['description'])); ?></p>
                <?php endif; ?>
                
                <div class="alert alert-warning">
                    <h5><i class="fas fa-star"></i> Want to access all lectures?</h5>
                    <p>Purchase this course to unlock:</p>
                    <ul>
                        <li><?php echo $totalLectures; ?> video lectures</li>
                        <li>Lifetime access</li>
                        <li>Downloadable notes</li>
                        <li>Progress tracking</li>
                    </ul>
                    <a href="<?php echo SITE_URL; ?>/product-detail.php?id=<?php echo $productId; ?>" class="btn btn-primary btn-lg">
                        <i class="fas fa-shopping-cart"></i> Purchase Course - <?php echo formatCurrency($product['price']); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const video = document.getElementById('previewVideo');

// Disable right-click
if (video) {
    video.addEventListener('contextmenu', e => {
        e.preventDefault();
        return false;
    });
    
    // Disable keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey && e.key === 's') {
            e.preventDefault();
            return false;
        }
    });
    
    // Prevent drag
    video.addEventListener('dragstart', function(e) {
        e.preventDefault();
        return false;
    });
}
</script>

<?php include 'includes/footer.php'; ?>
