<?php
require_once '../config/config.php';
requireAdmin();
$pageTitle = 'Edit Course Video - Admin';

$videoId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get video details
$stmt = $conn->prepare("
    SELECT v.*, s.title as section_title, s.product_id, p.title as product_title
    FROM course_videos v
    JOIN course_sections s ON v.section_id = s.id
    JOIN products p ON v.product_id = p.id
    WHERE v.id = ?
");
$stmt->bind_param("i", $videoId);
$stmt->execute();
$video = $stmt->get_result()->fetch_assoc();

if (!$video) {
    header('Location: ' . SITE_URL . '/admin/products.php');
    exit;
}

$success = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = clean($_POST['title']);
    $description = clean($_POST['description']);
    $orderIndex = (int)$_POST['order_index'];
    $isPreview = isset($_POST['is_preview']) ? 1 : 0;
    
    // Handle notes upload
    $notesPath = $video['notes_path'];
    if (isset($_FILES['notes']) && $_FILES['notes']['error'] == 0) {
        // Delete old notes if exists
        if ($notesPath && file_exists(UPLOAD_PATH . $notesPath)) {
            unlink(UPLOAD_PATH . $notesPath);
        }
        $notesPath = uploadFile($_FILES['notes'], 'courses/notes');
    }
    
    // Handle delete notes
    if (isset($_POST['delete_notes']) && $notesPath) {
        if (file_exists(UPLOAD_PATH . $notesPath)) {
            unlink(UPLOAD_PATH . $notesPath);
        }
        $notesPath = null;
    }
    
    $stmt = $conn->prepare("UPDATE course_videos SET title = ?, description = ?, notes_path = ?, order_index = ?, is_preview = ? WHERE id = ?");
    $stmt->bind_param("sssiii", $title, $description, $notesPath, $orderIndex, $isPreview, $videoId);
    
    if ($stmt->execute()) {
        $success = 'Video updated successfully!';
        // Refresh video data
        $stmt = $conn->prepare("SELECT * FROM course_videos WHERE id = ?");
        $stmt->bind_param("i", $videoId);
        $stmt->execute();
        $video = $stmt->get_result()->fetch_assoc();
    } else {
        $error = 'Failed to update video.';
    }
}

include 'includes/admin-header.php';
?>

<div class="container-fluid my-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-edit"></i> Edit Video</h2>
        <a href="<?php echo SITE_URL; ?>/admin/course-videos.php?product=<?php echo $video['product_id']; ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back
        </a>
    </div>
    
    <?php if ($success): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo $success; ?>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
        </div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-8">
                        <div class="form-outline mb-4">
                            <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($video['title']); ?>" required>
                            <label class="form-label">Video Title *</label>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-outline mb-4">
                            <input type="number" name="order_index" class="form-control" value="<?php echo $video['order_index']; ?>" required>
                            <label class="form-label">Order *</label>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-check mt-3">
                            <input class="form-check-input" type="checkbox" name="is_preview" id="is_preview" <?php echo $video['is_preview'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_preview">
                                Free Preview
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="form-outline mb-4">
                    <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($video['description']); ?></textarea>
                    <label class="form-label">Description</label>
                </div>
                
                <div class="row mb-4">
                    <div class="col-md-6">
                        <strong>Video File:</strong> <?php echo basename($video['video_path']); ?><br>
                        <strong>Duration:</strong> <?php echo $video['video_duration']; ?><br>
                        <strong>Size:</strong> <?php echo number_format($video['video_size'] / 1048576, 2); ?> MB
                    </div>
                </div>
                
                <!-- Current Notes -->
                <?php if ($video['notes_path']): ?>
                <div class="alert alert-info mb-4">
                    <strong>Current Notes:</strong> 
                    <a href="<?php echo UPLOAD_URL . $video['notes_path']; ?>" target="_blank">
                        <i class="fas fa-file-pdf"></i> View Notes
                    </a>
                    <button type="submit" name="delete_notes" class="btn btn-sm btn-danger ms-3" 
                            onclick="return confirm('Delete notes file?')">
                        <i class="fas fa-trash"></i> Delete Notes
                    </button>
                </div>
                <?php endif; ?>
                
                <!-- Upload New Notes -->
                <div class="mb-4">
                    <label class="form-label"><strong>Upload New Notes (PDF)</strong></label>
                    <input type="file" name="notes" class="form-control" accept=".pdf">
                    <small class="text-muted">Upload new PDF notes (will replace existing notes)</small>
                </div>
                
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-save"></i> Update Video
                </button>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.form-outline').forEach((formOutline) => {
        new mdb.Input(formOutline).init();
    });
});
</script>

<?php include 'includes/admin-footer.php'; ?>
