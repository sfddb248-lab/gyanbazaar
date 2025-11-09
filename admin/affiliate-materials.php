<?php
require_once '../config/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Handle material upload
if (isset($_POST['add_material'])) {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $materialType = $_POST['material_type'];
    $fileUrl = $_POST['file_url'];
    $dimensions = $_POST['dimensions'];
    
    $filePath = '';
    $fileSize = 0;
    
    // Handle file upload
    if (isset($_FILES['file']) && $_FILES['file']['error'] === 0) {
        $uploadDir = '../assets/uploads/affiliate-materials/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $fileName = time() . '_' . basename($_FILES['file']['name']);
        $targetPath = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['file']['tmp_name'], $targetPath)) {
            $filePath = 'affiliate-materials/' . $fileName;
            $fileSize = $_FILES['file']['size'];
        }
    }
    
    $stmt = $conn->prepare("INSERT INTO affiliate_materials (title, description, material_type, file_path, file_url, dimensions, file_size) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssi", $title, $description, $materialType, $filePath, $fileUrl, $dimensions, $fileSize);
    
    if ($stmt->execute()) {
        $message = "Material added successfully";
    } else {
        $error = "Failed to add material";
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM affiliate_materials WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header('Location: affiliate-materials.php');
    exit;
}

// Get all materials
$materials = $conn->query("SELECT * FROM affiliate_materials ORDER BY created_at DESC");

include 'includes/admin-header.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-md-12">
            <h2>Promotional Materials</h2>
            <?php if (isset($message)): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMaterialModal">
                <i class="fas fa-plus"></i> Add Material
            </button>
        </div>
    </div>
    
    <!-- Materials Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Preview</th>
                            <th>Title</th>
                            <th>Type</th>
                            <th>Dimensions</th>
                            <th>Downloads</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($material = $materials->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $material['id']; ?></td>
                                <td>
                                    <?php if ($material['file_path'] && in_array($material['material_type'], ['banner', 'social'])): ?>
                                        <img src="<?php echo UPLOAD_URL . $material['file_path']; ?>" 
                                             style="max-width: 100px; max-height: 60px;">
                                    <?php else: ?>
                                        <i class="fas fa-file fa-2x"></i>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($material['title']); ?></strong><br>
                                    <small class="text-muted"><?php echo htmlspecialchars(substr($material['description'], 0, 50)); ?>...</small>
                                </td>
                                <td>
                                    <span class="badge bg-info"><?php echo ucfirst($material['material_type']); ?></span>
                                </td>
                                <td><?php echo $material['dimensions'] ?: '-'; ?></td>
                                <td><?php echo $material['download_count']; ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $material['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                        <?php echo ucfirst($material['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($material['file_path']): ?>
                                        <a href="<?php echo UPLOAD_URL . $material['file_path']; ?>" 
                                           class="btn btn-sm btn-info" target="_blank">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    <?php endif; ?>
                                    <a href="?delete=<?php echo $material['id']; ?>" 
                                       class="btn btn-sm btn-danger" 
                                       onclick="return confirm('Are you sure?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Material Modal -->
<div class="modal fade" id="addMaterialModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title">Add Promotional Material</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Material Type</label>
                        <select name="material_type" class="form-select" required>
                            <option value="banner">Banner</option>
                            <option value="email">Email Template</option>
                            <option value="social">Social Media</option>
                            <option value="video">Video</option>
                            <option value="document">Document</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Upload File</label>
                        <input type="file" name="file" class="form-control">
                        <small class="text-muted">Upload banner, image, or document file</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Or External URL</label>
                        <input type="url" name="file_url" class="form-control" 
                               placeholder="https://example.com/file.pdf">
                        <small class="text-muted">For external files or videos</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Dimensions (optional)</label>
                        <input type="text" name="dimensions" class="form-control" 
                               placeholder="e.g., 728x90, 1200x628">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_material" class="btn btn-primary">Add Material</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/admin-footer.php'; ?>
