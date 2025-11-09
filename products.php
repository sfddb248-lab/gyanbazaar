<?php
require_once 'config/config.php';
$pageTitle = 'Products - ' . getSetting('site_name');

// Get filters
$search = isset($_GET['search']) ? clean($_GET['search']) : '';
$category = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$sort = isset($_GET['sort']) ? clean($_GET['sort']) : 'latest';

// Build query
$where = ["status = 'active'"];
$params = [];
$types = '';

if ($search) {
    $where[] = "(title LIKE ? OR description LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= 'ss';
}

if ($category) {
    $where[] = "category_id = ?";
    $params[] = $category;
    $types .= 'i';
}

$whereClause = implode(' AND ', $where);

// Sorting
$orderBy = match($sort) {
    'price_low' => 'price ASC',
    'price_high' => 'price DESC',
    'popular' => 'downloads DESC',
    default => 'created_at DESC'
};

$query = "SELECT * FROM products WHERE $whereClause ORDER BY $orderBy";
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get categories
$categories = $conn->query("SELECT * FROM categories ORDER BY name")->fetch_all(MYSQLI_ASSOC);

include 'includes/header.php';
?>

<style>
/* Fix for clickable buttons */
.product-card .btn {
    position: relative;
    z-index: 100 !important;
    pointer-events: auto !important;
    cursor: pointer !important;
}

.product-card .card-body {
    position: relative;
    z-index: 10;
}

.product-card a {
    text-decoration: none;
}

.product-card .btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}

/* Ensure card doesn't block buttons */
.product-card {
    position: relative;
}

.product-card::after {
    content: none !important;
}
</style>

<div class="container my-4">
    <!-- Search and Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="form-outline">
                            <input type="text" id="search" name="search" class="form-control" 
                                   value="<?php echo htmlspecialchars($search); ?>">
                            <label class="form-label" for="search">Search Products</label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select name="category" id="categoryFilter" class="form-select" onchange="this.form.submit()">
                            <option value="0">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" <?php echo $category == $cat['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="sort" id="sortFilter" class="form-select" onchange="this.form.submit()">
                            <option value="latest" <?php echo $sort == 'latest' ? 'selected' : ''; ?>>Latest</option>
                            <option value="popular" <?php echo $sort == 'popular' ? 'selected' : ''; ?>>Most Popular</option>
                            <option value="price_low" <?php echo $sort == 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                            <option value="price_high" <?php echo $sort == 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100 mb-2">
                            <i class="fas fa-search"></i> Search
                        </button>
                        <?php if ($search || $category || $sort != 'latest'): ?>
                        <a href="<?php echo SITE_URL; ?>/products.php" class="btn btn-outline-secondary w-100 btn-sm">
                            <i class="fas fa-times"></i> Clear
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <h2 class="mb-4">
        <i class="fas fa-box-open"></i> 
        <?php echo $search ? "Search Results for '$search'" : 'All Products'; ?>
        <span class="badge bg-primary"><?php echo count($products); ?></span>
    </h2>
    
    <?php if (empty($products)): ?>
        <div class="alert alert-info text-center">
            <i class="fas fa-info-circle"></i> No products found. Try adjusting your filters.
        </div>
    <?php else: ?>
        
        <!-- Desktop Grid -->
        <div class="row d-none d-md-flex">
            <?php foreach ($products as $product): ?>
            <div class="col-md-4 col-lg-3 mb-4">
                <div class="card product-card h-100">
                    <div class="position-relative">
                        <img src="<?php echo getCourseImage($product); ?>" 
                             class="card-img-top" alt="<?php echo htmlspecialchars($product['title']); ?>"
                             style="height: 200px; object-fit: cover; width: 100%;"
                             onerror="this.src='https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=400&h=300&fit=crop'">
                        <!-- Product Type Badge -->
                        <div class="position-absolute top-0 end-0 m-2">
                            <?php
                            $productType = $product['product_type'] ?? 'digital';
                            $badges = [
                                'course' => ['icon' => 'fa-video', 'color' => 'success', 'text' => 'Course'],
                                'ebook' => ['icon' => 'fa-book', 'color' => 'info', 'text' => 'eBook'],
                                'digital' => ['icon' => 'fa-file-download', 'color' => 'primary', 'text' => 'Digital']
                            ];
                            $badge = $badges[$productType] ?? $badges['digital'];
                            ?>
                            <span class="badge bg-<?php echo $badge['color']; ?> shadow">
                                <i class="fas <?php echo $badge['icon']; ?>"></i> <?php echo $badge['text']; ?>
                            </span>
                        </div>
                    </div>
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title"><?php echo htmlspecialchars($product['title']); ?></h5>
                        <p class="card-text text-truncate flex-grow-1">
                            <?php echo htmlspecialchars(substr($product['description'], 0, 80)); ?>...
                        </p>
                        <div class="d-flex justify-content-between align-items-center mt-auto">
                            <span class="h5 mb-0 text-primary"><?php echo formatCurrency($product['price']); ?></span>
                            <a href="<?php echo SITE_URL; ?>/product-detail.php?id=<?php echo $product['id']; ?>" 
                               class="btn btn-primary btn-sm" style="position: relative; z-index: 10; pointer-events: auto;">View</a>
                        </div>
                        <small class="text-muted mt-2">
                            <i class="fas fa-download"></i> <?php echo $product['downloads']; ?> downloads
                        </small>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Mobile List -->
        <div class="d-md-none">
            <?php foreach ($products as $product): ?>
            <div class="card mobile-product-card">
                <div class="position-relative">
                    <img src="<?php echo getCourseImage($product); ?>" 
                         alt="<?php echo htmlspecialchars($product['title']); ?>"
                         style="height: 180px; object-fit: cover; width: 100%;"
                         onerror="this.src='https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=400&h=300&fit=crop'">
                    <!-- Product Type Badge -->
                    <div class="position-absolute top-0 end-0 m-2">
                        <?php
                        $productType = $product['product_type'] ?? 'digital';
                        $badges = [
                            'course' => ['icon' => 'fa-video', 'color' => 'success', 'text' => 'Course'],
                            'ebook' => ['icon' => 'fa-book', 'color' => 'info', 'text' => 'eBook'],
                            'digital' => ['icon' => 'fa-file-download', 'color' => 'primary', 'text' => 'Digital']
                        ];
                        $badge = $badges[$productType] ?? $badges['digital'];
                        ?>
                        <span class="badge bg-<?php echo $badge['color']; ?> shadow">
                            <i class="fas <?php echo $badge['icon']; ?>"></i> <?php echo $badge['text']; ?>
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <h5 class="card-title"><?php echo htmlspecialchars($product['title']); ?></h5>
                    <p class="card-text"><?php echo htmlspecialchars(substr($product['description'], 0, 100)); ?>...</p>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="h5 mb-0 text-primary"><?php echo formatCurrency($product['price']); ?></span>
                        <a href="<?php echo SITE_URL; ?>/product-detail.php?id=<?php echo $product['id']; ?>" 
                           class="btn btn-primary" style="position: relative; z-index: 10; pointer-events: auto;">View Details</a>
                    </div>
                    <small class="text-muted">
                        <i class="fas fa-download"></i> <?php echo $product['downloads']; ?> downloads
                    </small>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
    <?php endif; ?>
</div>

<script>
// Initialize MDB form inputs
document.addEventListener('DOMContentLoaded', function() {
    // Initialize form outline
    document.querySelectorAll('.form-outline').forEach((formOutline) => {
        new mdb.Input(formOutline).init();
    });
    
    // Log current filters for debugging
    console.log('Current Filters:', {
        search: '<?php echo $search; ?>',
        category: <?php echo $category; ?>,
        sort: '<?php echo $sort; ?>'
    });
});
</script>

<?php include 'includes/footer.php'; ?>
