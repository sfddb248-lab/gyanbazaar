<?php
require_once 'config/config.php';
$pageTitle = 'All Courses - ' . getSetting('site_name');

// Get filters
$category = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'latest';

// Build query
$where = ["status = 'active'", "product_type = 'course'"];
$params = [];
$types = '';

if ($category > 0) {
    $where[] = "category_id = ?";
    $params[] = $category;
    $types .= 'i';
}

if ($search) {
    $where[] = "(title LIKE ? OR description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= 'ss';
}

$whereClause = implode(' AND ', $where);

// Sorting
$orderBy = match($sort) {
    'popular' => 'downloads DESC',
    'price_low' => 'price ASC',
    'price_high' => 'price DESC',
    default => 'created_at DESC'
};

$sql = "SELECT * FROM products WHERE $whereClause ORDER BY $orderBy";
$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$courses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get categories
$categories = $conn->query("SELECT * FROM categories ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);

include 'includes/header.php';
?>

<style>
:root {
    --primary: #6366f1;
    --secondary: #8b5cf6;
    --dark: #1f2937;
}

.filter-bar {
    background: white;
    padding: 30px;
    border-radius: 20px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.08);
    margin-bottom: 40px;
}

.filter-bar label {
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 8px;
}

.filter-bar .form-control,
.filter-bar .form-select {
    border-radius: 12px;
    border: 2px solid #e5e7eb;
    padding: 12px 18px;
    transition: all 0.3s ease;
}

.filter-bar .form-control:focus,
.filter-bar .form-select:focus {
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

.course-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 30px;
}

/* Course Card Styles */
.course-card {
    border-radius: 20px;
    overflow: hidden;
    transition: all 0.4s ease;
    background: white;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    height: 100%;
}

.course-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 20px 60px rgba(99, 102, 241, 0.3);
}

.course-thumbnail {
    position: relative;
    height: 220px;
    overflow: hidden;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.course-thumbnail img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: all 0.5s ease;
}

.course-card:hover .course-thumbnail img {
    transform: scale(1.15);
}

.course-badge {
    position: absolute;
    top: 15px;
    right: 15px;
    background: rgba(255,255,255,0.95);
    backdrop-filter: blur(10px);
    padding: 8px 16px;
    border-radius: 25px;
    font-size: 12px;
    font-weight: 700;
    color: var(--primary);
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    z-index: 2;
}

.course-info {
    padding: 25px;
}

.course-title {
    font-size: 1.1rem;
    font-weight: 700;
    margin-bottom: 12px;
    color: var(--dark);
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    min-height: 50px;
}

.course-meta {
    display: flex;
    gap: 20px;
    margin-bottom: 15px;
    font-size: 0.85rem;
    color: #6b7280;
}

.course-meta span {
    display: flex;
    align-items: center;
    gap: 5px;
}

.course-price {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 15px;
    border-top: 2px solid #f3f4f6;
}

.price-tag {
    font-size: 1.75rem;
    font-weight: 800;
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.course-price .btn {
    padding: 10px 24px;
    border-radius: 25px;
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    color: white;
    text-decoration: none;
    font-weight: 600;
    font-size: 0.9rem;
    transition: all 0.3s ease;
    border: none;
    box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3);
}

.course-price .btn:hover {
    transform: translateX(5px);
    box-shadow: 0 6px 20px rgba(99, 102, 241, 0.4);
    color: white;
}

@media (max-width: 768px) {
    .course-grid {
        grid-template-columns: 1fr;
    }
    
    .filter-bar {
        padding: 20px;
    }
    
    .course-thumbnail {
        height: 200px;
    }
}
</style>

<!-- Page Header -->
<section class="py-5" style="background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);">
    <div class="container text-center text-white">
        <h1 class="display-4 fw-bold mb-3">All Courses</h1>
        <p class="lead">Explore our comprehensive collection of courses</p>
    </div>
</section>

<div class="container my-5">
    <!-- Filter Bar -->
    <div class="filter-bar">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label fw-bold">Search</label>
                <input type="text" name="search" class="form-control" placeholder="Search courses..." 
                       value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">Category</label>
                <select name="category" class="form-select">
                    <option value="0">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo $category == $cat['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">Sort By</label>
                <select name="sort" class="form-select">
                    <option value="latest" <?php echo $sort == 'latest' ? 'selected' : ''; ?>>Latest</option>
                    <option value="popular" <?php echo $sort == 'popular' ? 'selected' : ''; ?>>Most Popular</option>
                    <option value="price_low" <?php echo $sort == 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                    <option value="price_high" <?php echo $sort == 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-filter"></i> Filter
                </button>
            </div>
        </form>
    </div>

    <!-- Results Count -->
    <div class="mb-4">
        <h5 class="text-muted">Found <?php echo count($courses); ?> courses</h5>
    </div>

    <!-- Course Grid -->
    <?php if (empty($courses)): ?>
        <div class="text-center py-5">
            <i class="fas fa-search fa-4x text-muted mb-3"></i>
            <h4>No courses found</h4>
            <p class="text-muted">Try adjusting your filters</p>
        </div>
    <?php else: ?>
        <div class="course-grid">
            <?php foreach ($courses as $course): ?>
            <div class="course-card">
                <div class="course-thumbnail">
                    <img src="<?php echo getCourseImage($course); ?>" 
                         alt="<?php echo htmlspecialchars($course['title']); ?>"
                         onerror="this.src='https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=400&h=300&fit=crop'">
                    <span class="course-badge">
                        <i class="fas fa-video"></i> Course
                    </span>
                </div>
                <div class="course-info">
                    <h3 class="course-title"><?php echo htmlspecialchars($course['title']); ?></h3>
                    <p class="text-muted small mb-3" style="height: 40px; overflow: hidden;">
                        <?php echo htmlspecialchars(substr($course['description'], 0, 80)); ?>...
                    </p>
                    <div class="course-meta">
                        <span><i class="fas fa-users"></i> <?php echo $course['downloads']; ?></span>
                        <span><i class="fas fa-star text-warning"></i> 4.5</span>
                    </div>
                    <div class="course-price">
                        <span class="price-tag"><?php echo formatCurrency($course['price']); ?></span>
                        <a href="<?php echo SITE_URL; ?>/product-detail.php?id=<?php echo $course['id']; ?>" 
                           class="btn btn-sm btn-primary">
                            View <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
