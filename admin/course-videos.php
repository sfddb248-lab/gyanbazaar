<?php
require_once '../config/config.php';
requireAdmin();
$pageTitle = 'Manage Course Videos - Admin';

$productId = isset($_GET['product']) ? (int)$_GET['product'] : 0;

// Get product details
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ? AND product_type = 'course'");
$stmt->bind_param("i", $productId);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    header('Location: ' . SITE_URL . '/admin/products.php');
    exit;
}

$success = '';
$error = '';

// Handle section creation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_section'])) {
    $title = clean($_POST['section_title']);
    $description = clean($_POST['section_description']);
    $orderIndex = (int)$_POST['order_index'];
    
    $stmt = $conn->prepare("INSERT INTO course_sections (product_id, title, description, order_index) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("issi", $productId, $title, $description, $orderIndex);
    
    if ($stmt->execute()) {
        $success = 'Section added successfully!';
    } else {
        $error = 'Failed to add section.';
    }
}

// Handle section deletion
if (isset($_GET['delete_section'])) {
    $sectionId = (int)$_GET['delete_section'];
    $stmt = $conn->prepare("DELETE FROM course_sections WHERE id = ? AND product_id = ?");
    $stmt->bind_param("ii", $sectionId, $productId);
    $stmt->execute();
    header('Location: ' . SITE_URL . '/admin/course-videos.php?product=' . $productId);
    exit;
}

// Get all sections with video count
$sections = $conn->query("
    SELECT s.*, COUNT(v.id) as video_count
    FROM course_sections s
    LEFT JOIN course_videos v ON s.id = v.section_id
    WHERE s.product_id = $productId
    GROUP BY s.id
    ORDER BY s.order_index ASC
")->fetch_all(MYSQLI_ASSOC);

include 'includes/admin-header.php';
?>

<div class="container-fluid my-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>
            <i class="fas fa-video"></i> Course Videos: <?php echo htmlspecialchars($product['title']); ?>
        </h2>
        <a href="<?php echo SITE_URL; ?>/admin/products.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Products
        </a>
    </div>
    
    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            <button type="button" class="btn-close" data-mdb-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            <button type="button" class="btn-close" data-mdb-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <!-- Add Section Form -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-plus"></i> Add New Section/Module</h5>
        </div>
        <div class="card-body">
            <form method="POST">
                <div class="row">
                    <div class="col-md-5">
                        <div class="form-outline mb-3">
                            <input type="text" name="section_title" class="form-control" required>
                            <label class="form-label">Section Title</label>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="form-outline mb-3">
                            <input type="text" name="section_description" class="form-control">
                            <label class="form-label">Description (Optional)</label>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-outline mb-3">
                            <input type="number" name="order_index" class="form-control" value="<?php echo count($sections) + 1; ?>">
                            <label class="form-label">Order</label>
                        </div>
                    </div>
                </div>
                <button type="submit" name="add_section" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add Section
                </button>
            </form>
        </div>
    </div>
    
    <!-- Sections List -->
    <?php if (empty($sections)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> No sections created yet. Add your first section above.
        </div>
    <?php else: ?>
        <?php foreach ($sections as $section): ?>
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-folder"></i> <?php echo htmlspecialchars($section['title']); ?>
                    <span class="badge bg-info"><?php echo $section['video_count']; ?> videos</span>
                </h5>
                <div>
                    <a href="<?php echo SITE_URL; ?>/admin/upload-course-video.php?section=<?php echo $section['id']; ?>&product=<?php echo $productId; ?>" 
                       class="btn btn-sm btn-success">
                        <i class="fas fa-upload"></i> Upload Video
                    </a>
                    <a href="?product=<?php echo $productId; ?>&delete_section=<?php echo $section['id']; ?>" 
                       class="btn btn-sm btn-danger"
                       onclick="return confirm('Delete this section and all its videos?')">
                        <i class="fas fa-trash"></i>
                    </a>
                </div>
            </div>
            <div class="card-body">
                <?php
                // Get videos for this section
                $videos = $conn->query("
                    SELECT * FROM course_videos 
                    WHERE section_id = {$section['id']} 
                    ORDER BY order_index ASC
                ")->fetch_all(MYSQLI_ASSOC);
                ?>
                
                <?php if (empty($videos)): ?>
                    <p class="text-muted">No videos in this section yet.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Order</th>
                                    <th>Title</th>
                                    <th>Duration</th>
                                    <th>Size</th>
                                    <th>Notes</th>
                                    <th>Preview</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($videos as $video): ?>
                                <tr>
                                    <td><?php echo $video['order_index']; ?></td>
                                    <td><?php echo htmlspecialchars($video['title']); ?></td>
                                    <td><?php echo $video['video_duration'] ?: 'N/A'; ?></td>
                                    <td><?php echo $video['video_size'] ? number_format($video['video_size'] / 1048576, 2) . ' MB' : 'N/A'; ?></td>
                                    <td>
                                        <?php if ($video['notes_path']): ?>
                                            <a href="<?php echo UPLOAD_URL . $video['notes_path']; ?>" target="_blank">
                                                <i class="fas fa-file-pdf text-danger"></i> View
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">No notes</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $video['is_preview'] ? 'success' : 'secondary'; ?>">
                                            <?php echo $video['is_preview'] ? 'Yes' : 'No'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="<?php echo SITE_URL; ?>/admin/edit-course-video.php?id=<?php echo $video['id']; ?>" 
                                           class="btn btn-sm btn-primary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="<?php echo SITE_URL; ?>/admin/delete-course-video.php?id=<?php echo $video['id']; ?>&product=<?php echo $productId; ?>" 
                                           class="btn btn-sm btn-danger"
                                           onclick="return confirm('Delete this video?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.form-outline').forEach((formOutline) => {
        new mdb.Input(formOutline).init();
    });
});
</script>

<?php include 'includes/admin-footer.php'; ?>
