<?php
require_once '../config/config.php';
$pageTitle = 'Manage Products - Admin';

$success = '';
$error = '';

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    // Check if product has orders
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM order_items WHERE product_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $orderCount = $stmt->get_result()->fetch_assoc()['count'];
    
    if ($orderCount > 0) {
        $error = "Cannot delete this product. It has $orderCount order(s) associated with it. You can set it to inactive instead.";
    } else {
        // Check if it's a course and delete related data
        $stmt = $conn->prepare("SELECT product_type FROM products WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $product = $stmt->get_result()->fetch_assoc();
        
        if ($product && $product['product_type'] == 'course') {
            // Delete course videos and sections (CASCADE should handle this, but let's be explicit)
            $conn->query("DELETE FROM course_videos WHERE product_id = $id");
            $conn->query("DELETE FROM course_sections WHERE product_id = $id");
        }
        
        // Now delete the product
        $conn->query("DELETE FROM products WHERE id = $id");
        $success = 'Product deleted successfully';
    }
}

// Handle add/edit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $title = clean($_POST['title']);
    $description = clean($_POST['description']);
    $price = (float)$_POST['price'];
    $categoryId = (int)$_POST['category_id'];
    $productType = clean($_POST['product_type']);
    $demoUrl = clean($_POST['demo_url']);
    $filePath = clean($_POST['file_path']);
    $previewPages = (int)$_POST['preview_pages'];
    $totalPages = (int)$_POST['total_pages'];
    $status = clean($_POST['status']);
    $slug = generateSlug($title);
    
    if ($id > 0) {
        // Update
        $stmt = $conn->prepare("UPDATE products SET title=?, slug=?, description=?, price=?, category_id=?, product_type=?, file_path=?, demo_url=?, preview_pages=?, total_pages=?, status=? WHERE id=?");
        $stmt->bind_param("sssdisssiisi", $title, $slug, $description, $price, $categoryId, $productType, $filePath, $demoUrl, $previewPages, $totalPages, $status, $id);
        $stmt->execute();
        $success = 'Product updated successfully';
    } else {
        // Insert
        $stmt = $conn->prepare("INSERT INTO products (title, slug, description, price, category_id, product_type, file_path, demo_url, preview_pages, total_pages, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssdisssiis", $title, $slug, $description, $price, $categoryId, $productType, $filePath, $demoUrl, $previewPages, $totalPages, $status);
        $stmt->execute();
        $success = 'Product added successfully';
    }
}

// Get products
$products = $conn->query("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.created_at DESC")->fetch_all(MYSQLI_ASSOC);

// Get categories
$categories = $conn->query("SELECT * FROM categories ORDER BY name")->fetch_all(MYSQLI_ASSOC);

include 'includes/admin-header.php';
?>

<div class="container-fluid my-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-box"></i> Manage Products</h2>
        <button class="btn btn-primary" data-mdb-target="#productModal">
            <i class="fas fa-plus"></i> Add Product
        </button>
    </div>
    
    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            <button type="button" class="btn-close" data-mdb-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Type</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Preview/Total</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                        <tr>
                            <td><?php echo $product['id']; ?></td>
                            <td><?php echo htmlspecialchars($product['title']); ?></td>
                            <td>
                                <span class="badge bg-info">
                                    <?php echo ucfirst($product['product_type'] ?? 'digital'); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                            <td><?php echo formatCurrency($product['price']); ?></td>
                            <td>
                                <?php if (($product['product_type'] ?? 'digital') == 'ebook'): ?>
                                    <?php echo $product['preview_pages']; ?> / <?php echo $product['total_pages']; ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $product['status'] == 'active' ? 'success' : 'secondary'; ?>">
                                    <?php echo ucfirst($product['status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($product['product_type'] == 'course'): ?>
                                    <a href="<?php echo SITE_URL; ?>/admin/course-videos.php?product=<?php echo $product['id']; ?>" 
                                       class="btn btn-sm btn-success" 
                                       title="Manage Videos">
                                        <i class="fas fa-video"></i>
                                    </a>
                                <?php endif; ?>
                                <button class="btn btn-sm btn-primary" onclick='editProduct(<?php echo json_encode($product); ?>)'>
                                    <i class="fas fa-edit"></i>
                                </button>
                                <a href="?delete=<?php echo $product['id']; ?>" 
                                   class="btn btn-sm btn-danger" 
                                   onclick="return confirm('Delete this product?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Product Modal -->
<div class="modal fade" id="productModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Add Product</h5>
                <button type="button" class="btn-close" data-mdb-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="id" id="productId">
                    <input type="hidden" name="file_path" id="filePath">
                    
                    <div class="form-outline mb-3">
                        <input type="text" id="title" name="title" class="form-control" required>
                        <label class="form-label" for="title">Product Title</label>
                    </div>
                    
                    <div class="form-outline mb-3">
                        <textarea id="description" name="description" class="form-control" rows="4" required></textarea>
                        <label class="form-label" for="description">Description</label>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-outline mb-3">
                                <input type="number" id="price" name="price" class="form-control" step="0.01" required>
                                <label class="form-label" for="price">Price</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <select name="category_id" id="categoryId" class="form-select mb-3" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <select name="product_type" id="productType" class="form-select mb-3" required onchange="toggleEbookFields()">
                                <option value="digital">Digital Product</option>
                                <option value="ebook">eBook/Notes</option>
                                <option value="course">Course</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- File Upload -->
                    <div class="mb-3">
                        <label class="form-label">Upload File (PDF/ZIP)</label>
                        <input type="file" id="fileUpload" class="form-control" accept=".pdf,.zip">
                        <div id="uploadProgress" class="mt-2" style="display:none;">
                            <div class="progress">
                                <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                            </div>
                        </div>
                        <small id="currentFile" class="text-muted"></small>
                    </div>
                    
                    <!-- eBook Specific Fields -->
                    <div id="ebookFields" style="display:none;">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-outline mb-3">
                                    <input type="number" id="previewPages" name="preview_pages" class="form-control" value="0" min="0">
                                    <label class="form-label" for="previewPages">Free Preview Pages</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-outline mb-3">
                                    <input type="number" id="totalPages" name="total_pages" class="form-control" value="0" min="0">
                                    <label class="form-label" for="totalPages">Total Pages</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-outline mb-3">
                        <input type="url" id="demo_url" name="demo_url" class="form-control">
                        <label class="form-label" for="demo_url">Demo URL (Optional)</label>
                    </div>
                    
                    <select name="status" id="productStatus" class="form-select mb-3" required>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-mdb-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Product</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let productModal;

document.addEventListener('DOMContentLoaded', function() {
    // Initialize modal
    const modalElement = document.getElementById('productModal');
    productModal = new mdb.Modal(modalElement);
    
    // Add event listener to Add Product button
    document.querySelector('[data-mdb-target="#productModal"]').addEventListener('click', function() {
        resetForm();
        productModal.show();
    });
    
    // File upload handler
    document.getElementById('fileUpload').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (!file) return;
        
        const formData = new FormData();
        formData.append('file', file);
        
        const progressDiv = document.getElementById('uploadProgress');
        const progressBar = progressDiv.querySelector('.progress-bar');
        progressDiv.style.display = 'block';
        
        fetch('upload-file.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('filePath').value = data.filepath;
                document.getElementById('currentFile').textContent = 'Uploaded: ' + data.filename;
                progressBar.style.width = '100%';
                progressBar.classList.add('bg-success');
            } else {
                alert('Upload failed: ' + data.message);
                progressBar.classList.add('bg-danger');
            }
        })
        .catch(error => {
            alert('Upload error: ' + error);
            progressBar.classList.add('bg-danger');
        });
    });
});

function toggleEbookFields() {
    const productType = document.getElementById('productType').value;
    const ebookFields = document.getElementById('ebookFields');
    ebookFields.style.display = productType === 'ebook' ? 'block' : 'none';
}

function resetForm() {
    document.getElementById('modalTitle').textContent = 'Add Product';
    document.getElementById('productId').value = '';
    document.getElementById('filePath').value = '';
    document.getElementById('currentFile').textContent = '';
    document.getElementById('uploadProgress').style.display = 'none';
    document.querySelector('#productModal form').reset();
    toggleEbookFields();
}

function editProduct(product) {
    document.getElementById('modalTitle').textContent = 'Edit Product';
    document.getElementById('productId').value = product.id;
    document.getElementById('title').value = product.title;
    document.getElementById('description').value = product.description;
    document.getElementById('price').value = product.price;
    document.getElementById('categoryId').value = product.category_id;
    document.getElementById('productType').value = product.product_type || 'digital';
    document.getElementById('filePath').value = product.file_path || '';
    document.getElementById('demo_url').value = product.demo_url || '';
    document.getElementById('previewPages').value = product.preview_pages || 0;
    document.getElementById('totalPages').value = product.total_pages || 0;
    document.getElementById('productStatus').value = product.status;
    
    if (product.file_path) {
        document.getElementById('currentFile').textContent = 'Current file: ' + product.file_path;
    }
    
    toggleEbookFields();
    productModal.show();
}
</script>

<?php include 'includes/admin-footer.php'; ?>
