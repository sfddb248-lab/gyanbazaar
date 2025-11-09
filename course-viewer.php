<?php
require_once 'config/config.php';
requireLogin();

$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Check if user has purchased this course
$stmt = $conn->prepare("
    SELECT o.* FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    WHERE o.user_id = ? AND oi.product_id = ? AND o.payment_status = 'completed'
    LIMIT 1
");
$stmt->bind_param("ii", $_SESSION['user_id'], $productId);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    header('Location: ' . SITE_URL . '/product-detail.php?id=' . $productId);
    exit;
}

// Get product details
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ? AND product_type = 'course'");
$stmt->bind_param("i", $productId);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    header('Location: ' . SITE_URL . '/products.php');
    exit;
}

// Get current video
$currentVideoId = isset($_GET['video']) ? (int)$_GET['video'] : 0;

// Get all sections with videos
$sections = $conn->query("
    SELECT s.*, 
           (SELECT COUNT(*) FROM course_videos WHERE section_id = s.id) as video_count
    FROM course_sections s
    WHERE s.product_id = $productId
    ORDER BY s.order_index ASC
")->fetch_all(MYSQLI_ASSOC);

// Get current video or first video
if (!$currentVideoId && !empty($sections)) {
    $firstVideo = $conn->query("
        SELECT id FROM course_videos 
        WHERE product_id = $productId 
        ORDER BY order_index ASC 
        LIMIT 1
    ")->fetch_assoc();
    if ($firstVideo) {
        $currentVideoId = $firstVideo['id'];
    }
}

// Get current video details
$currentVideo = null;
if ($currentVideoId) {
    $stmt = $conn->prepare("SELECT * FROM course_videos WHERE id = ? AND product_id = ?");
    $stmt->bind_param("ii", $currentVideoId, $productId);
    $stmt->execute();
    $currentVideo = $stmt->get_result()->fetch_assoc();
}

$pageTitle = $product['title'] . ' - Course Viewer';
include 'includes/header.php';
?>

<style>
.course-sidebar {
    height: calc(100vh - 120px);
    overflow-y: auto;
    background: var(--mdb-surface-bg);
    border-right: 1px solid rgba(0,0,0,0.1);
}

.video-container {
    position: relative;
    width: 100%;
    padding-top: 56.25%; /* 16:9 Aspect Ratio */
    background: #000;
    user-select: none;
    -webkit-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
}

.video-container video {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: contain;
    pointer-events: auto;
}

/* Watermark overlay */
.video-container::after {
    content: '<?php echo getSetting('site_name', 'GyanBazaar'); ?>';
    position: absolute;
    bottom: 20px;
    right: 20px;
    color: rgba(255, 255, 255, 0.3);
    font-size: 14px;
    font-weight: bold;
    pointer-events: none;
    z-index: 10;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
}

/* Prevent text selection on video */
.video-container * {
    user-select: none;
    -webkit-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
}

.video-item {
    padding: 12px;
    border-bottom: 1px solid rgba(0,0,0,0.1);
    cursor: pointer;
    transition: background 0.3s;
}

.video-item:hover {
    background: rgba(18, 102, 241, 0.1);
}

.video-item.active {
    background: rgba(18, 102, 241, 0.2);
    border-left: 3px solid #1266f1;
}

.video-item.completed {
    background: rgba(0, 200, 83, 0.1);
}

.section-header {
    background: rgba(0,0,0,0.05);
    padding: 12px;
    font-weight: bold;
    border-bottom: 2px solid rgba(0,0,0,0.1);
}

@media (max-width: 768px) {
    .course-sidebar {
        height: auto;
        max-height: 300px;
    }
}
</style>

<div class="container-fluid p-0">
    <div class="row g-0">
        <!-- Sidebar -->
        <div class="col-md-3 course-sidebar">
            <div class="p-3 border-bottom">
                <h5 class="mb-0"><?php echo htmlspecialchars($product['title']); ?></h5>
            </div>
            
            <?php foreach ($sections as $section): ?>
                <div class="section-header">
                    <i class="fas fa-folder"></i> <?php echo htmlspecialchars($section['title']); ?>
                </div>
                
                <?php
                // Get videos for this section
                $videos = $conn->query("
                    SELECT v.*,
                           COALESCE(p.completed, 0) as is_completed
                    FROM course_videos v
                    LEFT JOIN user_video_progress p ON v.id = p.video_id AND p.user_id = {$_SESSION['user_id']}
                    WHERE v.section_id = {$section['id']}
                    ORDER BY v.order_index ASC
                ")->fetch_all(MYSQLI_ASSOC);
                
                foreach ($videos as $video):
                ?>
                    <a href="?id=<?php echo $productId; ?>&video=<?php echo $video['id']; ?>" 
                       class="video-item <?php echo $video['id'] == $currentVideoId ? 'active' : ''; ?> <?php echo $video['is_completed'] ? 'completed' : ''; ?>"
                       style="text-decoration: none; color: inherit; display: block;">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div>
                                    <?php if ($video['is_completed']): ?>
                                        <i class="fas fa-check-circle text-success"></i>
                                    <?php else: ?>
                                        <i class="fas fa-play-circle"></i>
                                    <?php endif; ?>
                                    <?php echo htmlspecialchars($video['title']); ?>
                                </div>
                                <small class="text-muted"><?php echo $video['video_duration']; ?></small>
                            </div>
                            <?php if ($video['notes_path']): ?>
                                <i class="fas fa-file-pdf text-danger" title="Has notes"></i>
                            <?php endif; ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </div>
        
        <!-- Main Content -->
        <div class="col-md-9">
            <?php if ($currentVideo): ?>
                <!-- Video Player -->
                <div class="video-container">
                    <video 
                        id="courseVideo" 
                        controls 
                        controlsList="nodownload noremoteplayback"
                        disablePictureInPicture
                        oncontextmenu="return false;"
                        preload="metadata"
                        playsinline>
                        <source src="<?php echo SITE_URL; ?>/stream-video.php?video=<?php echo $currentVideo['id']; ?>&product=<?php echo $productId; ?>" type="video/mp4">
                        Your browser does not support the video tag.
                    </video>
                </div>
                
                <!-- Video Info -->
                <div class="p-4">
                    <h3><?php echo htmlspecialchars($currentVideo['title']); ?></h3>
                    <p class="text-muted"><?php echo htmlspecialchars($currentVideo['description']); ?></p>
                    
                    <?php if ($currentVideo['notes_path']): ?>
                        <a href="<?php echo UPLOAD_URL . $currentVideo['notes_path']; ?>" 
                           target="_blank" 
                           class="btn btn-primary">
                            <i class="fas fa-file-pdf"></i> Download Notes
                        </a>
                    <?php endif; ?>
                    
                    <button class="btn btn-success" onclick="markAsCompleted()">
                        <i class="fas fa-check"></i> Mark as Completed
                    </button>
                </div>
            <?php else: ?>
                <div class="p-5 text-center">
                    <i class="fas fa-video fa-3x text-muted mb-3"></i>
                    <h4>No videos available</h4>
                    <p class="text-muted">This course doesn't have any videos yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
const video = document.getElementById('courseVideo');
const videoId = <?php echo $currentVideoId; ?>;
const userId = <?php echo $_SESSION['user_id']; ?>;
const productId = <?php echo $productId; ?>;

// Enhanced download protection
if (video) {
    // Disable right-click
    video.addEventListener('contextmenu', e => {
        e.preventDefault();
        return false;
    });
    
    // Disable keyboard shortcuts for download
    document.addEventListener('keydown', function(e) {
        // Prevent Ctrl+S (Save)
        if (e.ctrlKey && e.key === 's') {
            e.preventDefault();
            return false;
        }
        // Prevent Ctrl+Shift+S (Save As)
        if (e.ctrlKey && e.shiftKey && e.key === 'S') {
            e.preventDefault();
            return false;
        }
    });
    
    // Disable inspect element on video
    video.addEventListener('mousedown', function(e) {
        if (e.button === 2) { // Right click
            e.preventDefault();
            return false;
        }
    });
    
    // Set high quality playback
    video.playbackRate = 1.0;
    video.preload = 'metadata';
    
    // Track video progress
    video.addEventListener('timeupdate', function() {
        const progress = {
            video_id: videoId,
            watched_duration: Math.floor(video.currentTime),
            total_duration: Math.floor(video.duration)
        };
        
        // Save progress every 10 seconds
        if (progress.watched_duration % 10 === 0) {
            saveProgress(progress);
        }
    });
    
    video.addEventListener('ended', function() {
        markAsCompleted();
    });
    
    // Prevent video URL inspection
    video.addEventListener('loadedmetadata', function() {
        // Remove download attribute if present
        video.removeAttribute('download');
    });
}

function saveProgress(progress) {
    fetch('<?php echo SITE_URL; ?>/ajax-save-video-progress.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(progress)
    });
}

function markAsCompleted() {
    fetch('<?php echo SITE_URL; ?>/ajax-mark-video-complete.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({video_id: videoId})
    }).then(response => response.json())
      .then(data => {
          if (data.success) {
              alert('Video marked as completed!');
              location.reload();
          }
      });
}

// Additional protection: Disable DevTools detection (basic)
document.addEventListener('keydown', function(e) {
    // F12, Ctrl+Shift+I, Ctrl+Shift+J, Ctrl+U
    if (e.keyCode === 123 || 
        (e.ctrlKey && e.shiftKey && (e.keyCode === 73 || e.keyCode === 74)) ||
        (e.ctrlKey && e.keyCode === 85)) {
        // Don't completely block, just warn
        console.log('Developer tools detected');
    }
});

// Prevent drag and drop of video
if (video) {
    video.addEventListener('dragstart', function(e) {
        e.preventDefault();
        return false;
    });
}
</script>

<?php include 'includes/footer.php'; ?>
