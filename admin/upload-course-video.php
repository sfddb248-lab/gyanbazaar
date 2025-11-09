<?php
require_once '../config/config.php';
requireAdmin();
$pageTitle = 'Upload Course Video - Admin';

$sectionId = isset($_GET['section']) ? (int)$_GET['section'] : 0;
$productId = isset($_GET['product']) ? (int)$_GET['product'] : 0;

// Get section details
$stmt = $conn->prepare("SELECT s.*, p.title as product_title FROM course_sections s JOIN products p ON s.product_id = p.id WHERE s.id = ? AND s.product_id = ?");
$stmt->bind_param("ii", $sectionId, $productId);
$stmt->execute();
$section = $stmt->get_result()->fetch_assoc();

if (!$section) {
    header('Location: ' . SITE_URL . '/admin/products.php');
    exit;
}

$success = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_video'])) {
    $title = clean($_POST['title']);
    $description = clean($_POST['description']);
    $videoPath = clean($_POST['video_path']);
    $duration = clean($_POST['duration']);
    $videoSize = (int)$_POST['video_size'];
    $orderIndex = (int)$_POST['order_index'];
    $isPreview = isset($_POST['is_preview']) ? 1 : 0;
    
    // Handle notes upload
    $notesPath = null;
    if (isset($_FILES['notes']) && $_FILES['notes']['error'] == 0) {
        $notesPath = uploadFile($_FILES['notes'], 'courses/notes');
    }
    
    $stmt = $conn->prepare("INSERT INTO course_videos (section_id, product_id, title, description, video_path, video_duration, video_size, notes_path, order_index, is_preview) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iissssisii", $sectionId, $productId, $title, $description, $videoPath, $duration, $videoSize, $notesPath, $orderIndex, $isPreview);
    
    if ($stmt->execute()) {
        $success = 'Video uploaded successfully!';
        header('refresh:2;url=' . SITE_URL . '/admin/course-videos.php?product=' . $productId);
    } else {
        $error = 'Failed to save video details.';
    }
}

// Get next order index
$nextOrder = $conn->query("SELECT COALESCE(MAX(order_index), 0) + 1 as next_order FROM course_videos WHERE section_id = $sectionId")->fetch_assoc()['next_order'];

include 'includes/admin-header.php';
?>

<div class="container-fluid my-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-upload"></i> Upload Video</h2>
        <a href="<?php echo SITE_URL; ?>/admin/course-videos.php?product=<?php echo $productId; ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back
        </a>
    </div>
    
    <div class="alert alert-info">
        <strong>Course:</strong> <?php echo htmlspecialchars($section['product_title']); ?><br>
        <strong>Section:</strong> <?php echo htmlspecialchars($section['title']); ?>
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
            <form method="POST" enctype="multipart/form-data" id="videoUploadForm">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-outline mb-4">
                            <input type="text" name="title" class="form-control" required>
                            <label class="form-label">Video Title *</label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-outline mb-4">
                            <input type="number" name="order_index" class="form-control" value="<?php echo $nextOrder; ?>" required>
                            <label class="form-label">Order *</label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-check mt-3">
                            <input class="form-check-input" type="checkbox" name="is_preview" id="is_preview">
                            <label class="form-check-label" for="is_preview">
                                Free Preview Video
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="form-outline mb-4">
                    <textarea name="description" class="form-control" rows="3"></textarea>
                    <label class="form-label">Description</label>
                </div>
                
                <!-- Video Upload -->
                <div class="mb-4">
                    <label class="form-label"><strong>Upload Video (Max 500MB) *</strong></label>
                    <input type="file" class="form-control" id="videoFile" accept="video/*" required>
                    <div class="mt-2">
                        <div class="progress" style="display: none;">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                 role="progressbar" style="width: 0%">0%</div>
                        </div>
                        <div id="uploadStatus" class="mt-2"></div>
                    </div>
                    <input type="hidden" name="video_path" id="video_path">
                    <input type="hidden" name="duration" id="duration">
                    <input type="hidden" name="video_size" id="video_size">
                </div>
                
                <!-- Notes Upload -->
                <div class="mb-4">
                    <label class="form-label"><strong>Upload Notes (PDF, Optional)</strong></label>
                    <input type="file" name="notes" class="form-control" accept=".pdf">
                    <small class="text-muted">Upload PDF notes for this video (optional)</small>
                </div>
                
                <button type="submit" name="save_video" class="btn btn-primary btn-lg" id="submitBtn" disabled>
                    <i class="fas fa-save"></i> Save Video
                </button>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    new mdb.Input(document.querySelector('.form-outline')).init();
    
    const videoFile = document.getElementById('videoFile');
    const progressBar = document.querySelector('.progress');
    const progressBarInner = document.querySelector('.progress-bar');
    const uploadStatus = document.getElementById('uploadStatus');
    const submitBtn = document.getElementById('submitBtn');
    
    videoFile.addEventListener('change', function() {
        const file = this.files[0];
        if (!file) return;
        
        // Check file size (500MB = 524288000 bytes)
        if (file.size > 524288000) {
            alert('File size exceeds 500MB limit!');
            this.value = '';
            return;
        }
        
        // Get video duration
        const video = document.createElement('video');
        video.preload = 'metadata';
        video.onloadedmetadata = function() {
            window.URL.revokeObjectURL(video.src);
            const duration = Math.floor(video.duration);
            const minutes = Math.floor(duration / 60);
            const seconds = duration % 60;
            document.getElementById('duration').value = minutes + ':' + (seconds < 10 ? '0' : '') + seconds;
        }
        video.src = URL.createObjectURL(file);
        
        // Set file size
        document.getElementById('video_size').value = file.size;
        
        // Upload file
        uploadVideo(file);
    });
    
    function uploadVideo(file) {
        const formData = new FormData();
        formData.append('video', file);
        formData.append('section_id', <?php echo $sectionId; ?>);
        formData.append('product_id', <?php echo $productId; ?>);
        
        progressBar.style.display = 'block';
        uploadStatus.innerHTML = '<span class="text-info"><i class="fas fa-spinner fa-spin"></i> Uploading...</span>';
        
        const xhr = new XMLHttpRequest();
        
        xhr.upload.addEventListener('progress', function(e) {
            if (e.lengthComputable) {
                const percentComplete = Math.round((e.loaded / e.total) * 100);
                progressBarInner.style.width = percentComplete + '%';
                progressBarInner.textContent = percentComplete + '%';
            }
        });
        
        xhr.addEventListener('load', function() {
            if (xhr.status === 200) {
                const response = JSON.parse(xhr.responseText);
                if (response.success) {
                    document.getElementById('video_path').value = response.path;
                    uploadStatus.innerHTML = '<span class="text-success"><i class="fas fa-check-circle"></i> Upload complete!</span>';
                    submitBtn.disabled = false;
                } else {
                    uploadStatus.innerHTML = '<span class="text-danger"><i class="fas fa-exclamation-circle"></i> ' + response.error + '</span>';
                }
            } else {
                uploadStatus.innerHTML = '<span class="text-danger"><i class="fas fa-exclamation-circle"></i> Upload failed!</span>';
            }
        });
        
        xhr.addEventListener('error', function() {
            uploadStatus.innerHTML = '<span class="text-danger"><i class="fas fa-exclamation-circle"></i> Upload error!</span>';
        });
        
        xhr.open('POST', '<?php echo SITE_URL; ?>/admin/ajax-upload-video.php', true);
        xhr.send(formData);
    }
});
</script>

<?php include 'includes/admin-footer.php'; ?>
