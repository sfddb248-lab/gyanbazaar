<?php
require_once '../config/config.php';
$pageTitle = 'Reports & Analytics - Admin';

// Get date range
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Sales statistics
$salesStats = $conn->query("
    SELECT 
        COUNT(*) as total_orders,
        SUM(final_amount) as total_revenue,
        SUM(tax_amount) as total_tax,
        AVG(final_amount) as avg_order_value
    FROM orders 
    WHERE payment_status = 'completed' 
    AND DATE(created_at) BETWEEN '$startDate' AND '$endDate'
")->fetch_assoc();

// Daily sales
$dailySales = $conn->query("
    SELECT DATE(created_at) as date, COUNT(*) as orders, SUM(final_amount) as revenue
    FROM orders 
    WHERE payment_status = 'completed'
    AND DATE(created_at) BETWEEN '$startDate' AND '$endDate'
    GROUP BY DATE(created_at)
    ORDER BY date DESC
")->fetch_all(MYSQLI_ASSOC);

// Top products
$topProducts = $conn->query("
    SELECT p.title, COUNT(oi.id) as sales, SUM(oi.price) as revenue
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    JOIN orders o ON oi.order_id = o.id
    WHERE o.payment_status = 'completed'
    AND DATE(o.created_at) BETWEEN '$startDate' AND '$endDate'
    GROUP BY p.id
    ORDER BY sales DESC
    LIMIT 10
")->fetch_all(MYSQLI_ASSOC);

// Payment methods
$paymentMethods = $conn->query("
    SELECT payment_method, COUNT(*) as count, SUM(final_amount) as total
    FROM orders
    WHERE payment_status = 'completed'
    AND DATE(created_at) BETWEEN '$startDate' AND '$endDate'
    GROUP BY payment_method
")->fetch_all(MYSQLI_ASSOC);

include 'includes/admin-header.php';
?>

<div class="container-fluid my-4">
    <h2 class="mb-4"><i class="fas fa-chart-bar"></i> Reports & Analytics</h2>
    
    <!-- Date Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Start Date</label>
                    <input type="date" name="start_date" class="form-control" value="<?php echo $startDate; ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">End Date</label>
                    <input type="date" name="end_date" class="form-control" value="<?php echo $endDate; ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter"></i> Apply Filter
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h6>Total Orders</h6>
                    <h2><?php echo $salesStats['total_orders'] ?? 0; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6>Total Revenue</h6>
                    <h2><?php echo formatCurrency($salesStats['total_revenue'] ?? 0); ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h6>Avg Order Value</h6>
                    <h2><?php echo formatCurrency($salesStats['avg_order_value'] ?? 0); ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h6>Total Tax Collected</h6>
                    <h2><?php echo formatCurrency($salesStats['total_tax'] ?? 0); ?></h2>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Daily Sales -->
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-calendar-day"></i> Daily Sales</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Orders</th>
                                    <th>Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($dailySales as $day): ?>
                                <tr>
                                    <td><?php echo date('M d, Y', strtotime($day['date'])); ?></td>
                                    <td><?php echo $day['orders']; ?></td>
                                    <td><?php echo formatCurrency($day['revenue']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Top Products -->
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-trophy"></i> Top Selling Products</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Sales</th>
                                    <th>Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($topProducts as $product): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($product['title']); ?></td>
                                    <td><?php echo $product['sales']; ?></td>
                                    <td><?php echo formatCurrency($product['revenue']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Payment Methods -->
    <div class="card">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0"><i class="fas fa-credit-card"></i> Payment Methods</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <?php foreach ($paymentMethods as $method): ?>
                <div class="col-md-4 mb-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <h6><?php echo ucfirst($method['payment_method']); ?></h6>
                            <p class="mb-1"><strong><?php echo $method['count']; ?></strong> transactions</p>
                            <h5 class="text-primary"><?php echo formatCurrency($method['total']); ?></h5>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/admin-footer.php'; ?>
