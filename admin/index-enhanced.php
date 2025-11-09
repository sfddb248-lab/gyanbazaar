<?php
require_once '../config/config.php';
$pageTitle = 'Dashboard - Admin';

// Get statistics
$totalProducts = $conn->query("SELECT COUNT(*) as count FROM products")->fetch_assoc()['count'];
$totalOrders = $conn->query("SELECT COUNT(*) as count FROM orders")->fetch_assoc()['count'];
$totalUsers = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'user'")->fetch_assoc()['count'];
$totalRevenue = $conn->query("SELECT SUM(total_amount) as total FROM orders WHERE payment_status = 'completed'")->fetch_assoc()['total'] ?? 0;

// Recent orders
$recentOrders = $conn->query("SELECT o.*, u.name as user_name FROM orders o 
                               JOIN users u ON o.user_id = u.id 
                               ORDER BY o.created_at DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);

// Top products
$topProducts = $conn->query("SELECT * FROM products ORDER BY downloads DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);

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
            <a href="index.php" class="admin-sidebar-link active">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
        </li>
        <li class="admin-sidebar-item">
            <a href="products.php" class="admin-sidebar-link">
                <i class="fas fa-box"></i>
                <span>Products</span>
                <span class="sidebar-badge"><?php echo $totalProducts; ?></span>
            </a>
        </li>
        <li class="admin-sidebar-item">
            <a href="orders.php" class="admin-sidebar-link">
                <i class="fas fa-shopping-cart"></i>
                <span>Orders</span>
                <span class="sidebar-badge"><?php echo $totalOrders; ?></span>
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
        <input type="text" placeholder="Search products, orders, users...">
    </div>
    
    <div class="admin-topbar-actions">
        <div class="admin-topbar-icon" data-tooltip="Notifications">
            <i class="fas fa-bell"></i>
            <span class="badge">3</span>
        </div>
        <div class="admin-topbar-icon" data-tooltip="Messages">
            <i class="fas fa-envelope"></i>
            <span class="badge">5</span>
        </div>
        <div class="admin-user-menu">
            <div class="admin-user-avatar">
                <?php echo strtoupper(substr($_SESSION['user_name'], 0, 1)); ?>
            </div>
            <div class="d-none d-md-block">
                <div class="fw-bold" style="font-size: 0.9rem;"><?php echo $_SESSION['user_name']; ?></div>
                <div class="text-muted" style="font-size: 0.75rem;">Administrator</div>
            </div>
            <i class="fas fa-chevron-down ms-2"></i>
        </div>
    </div>
</div>

<div class="admin-main">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 animate-fade-in-down">
        <div>
            <h2 class="fw-bold mb-1">Dashboard</h2>
            <nav class="breadcrumb-modern">
                <a href="index.php"><i class="fas fa-home"></i> Home</a>
                <i class="fas fa-chevron-right"></i>
                <span class="active">Dashboard</span>
            </nav>
        </div>
        <div>
            <button class="btn-modern gradient-primary">
                <i class="fas fa-download"></i> Export Report
            </button>
        </div>
    </div>
    
    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4 animate-fade-in-up">
            <div class="stats-card">
                <div class="stats-card-icon primary">
                    <i class="fas fa-box"></i>
                </div>
                <div class="stats-card-value"><?php echo $totalProducts; ?></div>
                <div class="stats-card-label">Total Products</div>
                <span class="stats-card-trend up">
                    <i class="fas fa-arrow-up"></i> 12% this month
                </span>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4 animate-fade-in-up delay-100">
            <div class="stats-card">
                <div class="stats-card-icon success">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="stats-card-value"><?php echo $totalOrders; ?></div>
                <div class="stats-card-label">Total Orders</div>
                <span class="stats-card-trend up">
                    <i class="fas fa-arrow-up"></i> 8% this month
                </span>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4 animate-fade-in-up delay-200">
            <div class="stats-card">
                <div class="stats-card-icon danger">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stats-card-value"><?php echo $totalUsers; ?></div>
                <div class="stats-card-label">Total Users</div>
                <span class="stats-card-trend up">
                    <i class="fas fa-arrow-up"></i> 15% this month
                </span>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4 animate-fade-in-up delay-300">
            <div class="stats-card">
                <div class="stats-card-icon warning">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="stats-card-value"><?php echo formatCurrency($totalRevenue); ?></div>
                <div class="stats-card-label">Total Revenue</div>
                <span class="stats-card-trend up">
                    <i class="fas fa-arrow-up"></i> 23% this month
                </span>
            </div>
        </div>
    </div>
    
    <!-- Charts Row -->
    <div class="row mb-4">
        <div class="col-lg-8 mb-4 animate-fade-in-left">
            <div class="chart-card">
                <div class="chart-header">
                    <h5 class="chart-title">Revenue Overview</h5>
                    <div class="chart-filter">
                        <button class="chart-filter-btn active">Week</button>
                        <button class="chart-filter-btn">Month</button>
                        <button class="chart-filter-btn">Year</button>
                    </div>
                </div>
                <div style="height: 300px; display: flex; align-items: center; justify-content: center; background: #f7fafc; border-radius: 12px;">
                    <div class="text-center">
                        <i class="fas fa-chart-line fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Chart will be displayed here</p>
                        <small class="text-muted">Integrate Chart.js or similar library</small>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4 mb-4 animate-fade-in-right">
            <div class="chart-card">
                <div class="chart-header">
                    <h5 class="chart-title">Product Categories</h5>
                </div>
                <div style="height: 300px; display: flex; align-items: center; justify-content: center; background: #f7fafc; border-radius: 12px;">
                    <div class="text-center">
                        <i class="fas fa-chart-pie fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Pie chart here</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Orders & Top Products -->
    <div class="row">
        <div class="col-lg-7 mb-4 animate-fade-in-up">
            <div class="table-modern">
                <div class="p-4 border-bottom">
                    <h5 class="fw-bold mb-0">Recent Orders</h5>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentOrders as $order): ?>
                        <tr>
                            <td><strong>#<?php echo $order['id']; ?></strong></td>
                            <td><?php echo htmlspecialchars($order['user_name']); ?></td>
                            <td><strong><?php echo formatCurrency($order['total_amount']); ?></strong></td>
                            <td>
                                <?php
                                $statusColors = [
                                    'completed' => 'success',
                                    'pending' => 'warning',
                                    'failed' => 'danger'
                                ];
                                $color = $statusColors[$order['payment_status']] ?? 'secondary';
                                ?>
                                <span class="badge-modern gradient-<?php echo $color; ?>">
                                    <?php echo ucfirst($order['payment_status']); ?>
                                </span>
                            </td>
                            <td>
                                <button class="table-action-btn edit">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="col-lg-5 mb-4 animate-fade-in-up delay-200">
            <div class="modern-card p-4">
                <h5 class="fw-bold mb-4">Top Products</h5>
                <?php foreach ($topProducts as $index => $product): ?>
                <div class="d-flex align-items-center mb-3 p-3 hover-lift" style="background: #f7fafc; border-radius: 12px; transition: all 0.3s ease;">
                    <div class="me-3">
                        <div class="fw-bold text-primary" style="font-size: 1.5rem;">#<?php echo $index + 1; ?></div>
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-bold mb-1"><?php echo htmlspecialchars($product['title']); ?></div>
                        <small class="text-muted">
                            <i class="fas fa-download"></i> <?php echo $product['downloads']; ?> downloads
                        </small>
                    </div>
                    <div class="text-end">
                        <div class="fw-bold text-success"><?php echo formatCurrency($product['price']); ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script>
function toggleSidebar() {
    const sidebar = document.getElementById('adminSidebar');
    sidebar.classList.toggle('collapsed');
}

// Add smooth scroll animations
document.addEventListener('DOMContentLoaded', function() {
    // Animate elements on scroll
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);
    
    document.querySelectorAll('.animate-fade-in-up, .animate-fade-in-left, .animate-fade-in-right').forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(20px)';
        el.style.transition = 'all 0.6s ease';
        observer.observe(el);
    });
});
</script>

<?php include 'includes/admin-footer.php'; ?>
