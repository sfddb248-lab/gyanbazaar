<?php
require_once '../config/config.php';
$pageTitle = 'Manage Products - Admin';

$success = '';
$error = '';

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM order_items WHERE product_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $orderCount = $stmt->get_result()->fetch_assoc()['count'];
    
    if ($orderCount > 0) {
        $error = "Cannot delete this product. It has $orderCount order(s) associated with it.";
    } else {
        $stmt = $conn->prepare("SELECT product_type FROM products WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $product = $stmt->get_result()->fetch_assoc();
        
        if ($product && $product['product_type'] == 'course') {
            $conn->query("DELETE FROM course_videos WHERE product_id = $id");
            $conn->query("DELETE FROM course_sections WHERE product_id = $id");
        }
        
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
        $stmt = $conn->prepare("UPDATE products SET title=?, slug=?, description=?, price=?, category_id=?, product_type=?, file_path=?, demo_url=?, preview_pages=?, total_pages=?, status=? WHERE id=?");
        $stmt->bind_param("sssdisssiisi", $title, $slug, $description, $price, $categoryId, $productType, $filePath, $demoUrl, $previewPages, $totalPages, $status, $id);
        $stmt->execute();
        $success = 'Product updated successfully';
    } else {
        $stmt = $conn->prepare("INSERT INTO products (title, slug, description, price, category_id, product_type, file_path, demo_url, preview_pages, total_pages, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssdisssiis", $title, $slug, $description, $price, $categoryId, $productType, $filePath, $demoUrl, $previewPages, $totalPages, $status);
        $stmt->execute();
        $success = 'Product added successfully';
    }
}

$products = $conn->query("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.created_at DESC")->fetch_all(MYSQLI_ASSOC);
$categories = $conn->query("SELECT * FROM categories ORDER BY name")->fetch_all(MYSQLI_ASSOC);

include 'includes/admin-header.php';
?>

<div class="admin-sidebar" id="adminSidebar">
    <div class="admin-sidebar-header">
        <div class="admin-sidebar-logo">
            <i class="fas fa-gem"></i> Admin
        </div>
        <button class="sidebar-toggle" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>
    </div>
    
    <ul class="admin-sidebar-menu">
        <li class="admin-sidebar-item">
            <a href="index.php" class="admin-sidebar-link">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
        </li>
        <li class="admin-sidebar-item">
            <a href="products.php" class="admin-sidebar-link active">
                <i class="fas fa-box"></i>
                <span>Products</span>
            </a>
        </li>
        <li class="admin-sidebar-item">
            <a href="orders.php" class="admin-sidebar-link">
                <i class="fas fa-shopping-cart"></i>
                <span>Orders</span>
            </a>
        </li>
        <li class="admin-sidebar-item">
            <a href="users.php" class="admin-sidebar-link">
                <i class="fas fa-users"></i>
                <span>Users</span>
            </a>
        </li>
        <li class="admin-sidebar-item">
            <a href="coupons.php" class="admin-sidebar-link">
                <i class="fas fa-tags"></i>
                <span>Coupons</span>
            </a>
        </li>
        <li class="admin-sidebar-item">
            <a href="reports.php" class="admin-sidebar-link">
                <i class="fas fa-chart-line"></i>
                <span>Reports</span>
            </a>
        </li>
        <li class="admin-sidebar-item">
            <a href="settings.php" class="admin-sidebar-link">
                <i class="fas fa-cog"></i>
                <span>Settings</span>
            </a>
        </li>
        <li class="admin-sidebar-item mt-4">
            <a href="<?php echo SITE_URL; ?>" class="admin-sidebar-link">
                <i class="fas fa-globe"></i>
                <span>View Website</span>
            </a>
        </li>
        <li class="admin-sidebar-item">
            <a href="<?php echo SITE_URL; ?>/logout.php" class="admin-sidebar-link text-danger">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </li>
    </ul>
</div>

<div class="admin-topbar">
    <div class="admin-search">
        <i class="fas fa-search"></i>
        <input type="text" placeholder="Search products...">
    </div>
    
    <div class="admin-topbar-actions">
        <div class="admin-topbar-icon">
            <i class="fas fa-bell"></i>
            <span class="badge">3</span>
        </div>
        <div class="admin-user-menu">
            <div class="admin-user-avatar">
                <?php echo strtoupper(substr($_SESSION['user_name'], 0, 1)); ?>
            </div>
            <div class="d-none d-md-block">
                <div class="fw-bold" style="font-size: 0.9rem;"><?php echo $_SESSION['user_name']; ?></div>
                <div class="text-muted" style="font-size: 0.75rem;">Administrator</div>
            </div>
        </div>
    </div>
</div>

<div class="admin-main">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 animate-fade-in-down">
        <div>
            <h2 class="fw-bold mb-1">Manage Products</h2>
            <nav class="breadcrumb-modern">
                <a href="index.php"><i class="fas fa-home"></i> Home</a>
                <i class="fas fa-chevron-right"></i>
                <span class="active">Products</span>
            </nav>
        </div>
        <button class="btn-modern gradient-primary" onclick="openAddModal()">
            <i class="fas fa-plus"></i> Add Product
        </button>
    </div>
    
    <!-- Alerts -->
    <?php if ($success): ?>
        <div class="alert-modern success animate-slide-in-down">
            <i class="fas fa-check-circle"></i>
            <div><strong>Success!</strong> <?php echo $success; ?></div>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert-modern danger animate-slide-in-down">
            <i class="fas fa-exclamation-circle"></i>
            <div><strong>Error!</strong> <?php echo $error; ?></div>
        </div>
    <?php endif; ?>
    
    <!-- Products Table -->
    <div class="table-modern animate-fade-in-up">
        <div class="p-4 border-bottom">
            <h5 class="fw-bold mb-0">All Products</h5>
        </div>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Type</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $product): ?>
                <tr>
                    <td><strong>#<?php echo $product['id']; ?></strong></td>
                    <td><?php echo htmlspecialchars($product['title']); ?></td>
                    <td>
                        <?php
                        $typeColors = [
                            'course' => 'success',
                            'ebook' => 'primary',
                            'digital' => 'secondary'
                        ];
                        $type = $product['product_type'] ?? 'digital';
                        $color = $typeColors[$type] ?? 'secondary';
                        ?>
                        <span class="badge-modern gradient-<?php echo $color; ?>">
                            <?php echo ucfirst($type); ?>
                        </span>
                    </td>
                    <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                    <td><strong><?php echo formatCurrency($product['price']); ?></strong></td>
                    <td>
                        <span class="badge-modern gradient-<?php echo $product['status'] == 'active' ? 'success' : 'secondary'; ?>">
                            <?php echo ucfirst($product['status']); ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($product['product_type'] == 'course'): ?>
                            <a href="course-videos.php?product=<?php echo $product['id']; ?>" 
                               class="table-action-btn edit" title="Manage Videos">
                                <i class="fas fa-video"></i>
                            </a>
                        <?php endif; ?>
                        <button class="table-action-btn edit" onclick='editProduct(<?php echo json_encode($product); ?>)'>
                            <i class="fas fa-edit"></i>
                        </button>
                        <a href="?delete=<?php echo $product['id']; ?>" 
                           class="table-action-btn delete" 
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

<!-- Product Modal -->
<div class="modal fade modal-modern" id="productModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="modalTitle">Add Product</h5>
                <button type="button" class="btn-close" data-mdb-dismiss="modal"></button>
            </div>
            <form method="POST" action="" class="form-modern">
                <div class="modal-body">
                    <input type="hidden" name="id" id="productId">
                    <input type="hidden" name="file_path" id="filePath">
                    
                    <div class="form-group-modern">
                        <label class="form-label-modern">Product Title</label>
                        <input type="text" id="title" name="title" class="form-control-modern" required>
                    </div>
                    
                    <div class="form-group-modern">
                        <label class="form-label-modern">Description</label>
                        <textarea id="description" name="description" class="form-control-modern" rows="4" required></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group-modern">
                                <label class="form-label-modern">Price</label>
                                <input type="number" id="price" name="price" class="form-control-modern" step="0.01" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group-modern">
                                <label class="form-label-modern">Category</label>
                                <div class="select-modern">
                                    <select name="category_id" id="categoryId" class="form-control-modern" required>
                                        <option value="">Select Category</option>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group-modern">
                                <label class="form-label-modern">Product Type</label>
                                <div class="select-modern">
                                    <select name="product_type" id="productType" class="form-control-modern" required onchange="toggleEbookFields()">
                                        <option value="digital">Digital Product</option>
                                        <option value="ebook">eBook/Notes</option>
                                        <option value="course">Course</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div id="ebookFields" style="display:none;">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group-modern">
                                    <label class="form-label-modern">Preview Pages</label>
                                    <input type="number" id="previewPages" name="preview_pages" class="form-control-modern" value="0" min="0">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group-modern">
                                    <label class="form-label-modern">Total Pages</label>
                                    <input type="number" id="totalPages" name="total_pages" class="form-control-modern" value="0" min="0">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group-modern">
                        <label class="form-label-modern">Demo URL (Optional)</label>
                        <input type="url" id="demo_url" name="demo_url" class="form-control-modern">
                    </div>
                    
                    <div class="form-group-modern">
                        <label class="form-label-modern">Status</label>
                        <div class="select-modern">
                            <select name="status" id="productStatus" class="form-control-modern" required>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-modern gradient-secondary" data-mdb-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-modern gradient-primary">Save Product</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let productModal;

document.addEventListener('DOMContentLoaded', function() {
    productModal = new mdb.Modal(document.getElementById('productModal'));
});

function toggleSidebar() {
    document.getElementById('adminSidebar').classList.toggle('collapsed');
}

function toggleEbookFields() {
    const productType = document.getElementById('productType').value;
    document.getElementById('ebookFields').style.display = productType === 'ebook' ? 'block' : 'none';
}

function openAddModal() {
    document.getElementById('modalTitle').textContent = 'Add Product';
    document.getElementById('productId').value = '';
    document.getElementById('filePath').value = '';
    document.querySelector('#productModal form').reset();
    toggleEbookFields();
    productModal.show();
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
    
    toggleEbookFields();
    productModal.show();
}
</script>

<?php include 'includes/admin-footer.php'; ?>
