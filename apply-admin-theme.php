<?php
/**
 * Apply Ultra Modern Theme to All Admin Pages
 * This script adds the modern sidebar and topbar to all admin pages
 */

echo "🎨 Applying Ultra Modern Theme to Admin Pages\n";
echo "=============================================\n\n";

$adminPages = [
    'products.php',
    'orders.php',
    'users.php',
    'coupons.php',
    'reports.php',
    'settings.php'
];

$sidebarCode = <<<'SIDEBAR'
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
            <a href="index.php" class="admin-sidebar-link DASHBOARD_ACTIVE">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
        </li>
        <li class="admin-sidebar-item">
            <a href="products.php" class="admin-sidebar-link PRODUCTS_ACTIVE">
                <i class="fas fa-box"></i>
                <span>Products</span>
            </a>
        </li>
        <li class="admin-sidebar-item">
            <a href="orders.php" class="admin-sidebar-link ORDERS_ACTIVE">
                <i class="fas fa-shopping-cart"></i>
                <span>Orders</span>
            </a>
        </li>
        <li class="admin-sidebar-item">
            <a href="users.php" class="admin-sidebar-link USERS_ACTIVE">
                <i class="fas fa-users"></i>
                <span>Users</span>
            </a>
        </li>
        <li class="admin-sidebar-item">
            <a href="coupons.php" class="admin-sidebar-link COUPONS_ACTIVE">
                <i class="fas fa-tags"></i>
                <span>Coupons</span>
            </a>
        </li>
        <li class="admin-sidebar-item">
            <a href="reports.php" class="admin-sidebar-link REPORTS_ACTIVE">
                <i class="fas fa-chart-line"></i>
                <span>Reports</span>
            </a>
        </li>
        <li class="admin-sidebar-item">
            <a href="settings.php" class="admin-sidebar-link SETTINGS_ACTIVE">
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
        <input type="text" placeholder="Search...">
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

SIDEBAR;

$sidebarScript = <<<'SCRIPT'

<script>
function toggleSidebar() {
    document.getElementById('adminSidebar').classList.toggle('collapsed');
}
</script>

SCRIPT;

$changes = [];
$errors = [];

foreach ($adminPages as $page) {
    $filePath = 'admin/' . $page;
    
    if (!file_exists($filePath)) {
        $errors[] = "❌ File not found: $filePath";
        continue;
    }
    
    // Backup original
    $backupPath = 'admin/' . str_replace('.php', '-old.php', $page);
    if (!file_exists($backupPath)) {
        copy($filePath, $backupPath);
        $changes[] = "✅ Backed up: $page";
    }
    
    // Read content
    $content = file_get_contents($filePath);
    
    // Determine active page
    $pageName = strtoupper(str_replace('.php', '', $page));
    $activeSidebar = str_replace($pageName . '_ACTIVE', 'active', $sidebarCode);
    $activeSidebar = preg_replace('/[A-Z]+_ACTIVE/', '', $activeSidebar);
    
    // Replace container-fluid with admin-main wrapper
    if (strpos($content, '<div class="container-fluid') !== false) {
        // Add sidebar and topbar before container
        $content = preg_replace(
            '/<div class="container-fluid/',
            $activeSidebar . '<div class="container-fluid',
            $content,
            1
        );
        
        // Close admin-main div before footer
        $content = str_replace(
            '<?php include \'includes/admin-footer.php\'; ?>',
            '</div>' . "\n\n" . $sidebarScript . "\n\n" . '<?php include \'includes/admin-footer.php\'; ?>',
            $content
        );
        
        // Add animation classes to main elements
        $content = str_replace(
            '<div class="card">',
            '<div class="modern-card animate-fade-in-up">',
            $content
        );
        
        $content = str_replace(
            '<div class="card ',
            '<div class="modern-card ',
            $content
        );
        
        // Update alerts
        $content = str_replace(
            '<div class="alert alert-success',
            '<div class="alert-modern success animate-slide-in-down',
            $content
        );
        
        $content = str_replace(
            '<div class="alert alert-danger',
            '<div class="alert-modern danger animate-slide-in-down',
            $content
        );
        
        // Update tables
        $content = str_replace(
            '<div class="table-responsive">',
            '<div class="table-modern"><div class="table-responsive">',
            $content
        );
        
        $content = str_replace(
            '</table>
            </div>
        </div>
    </div>',
            '</table>
            </div></div>',
            $content
        );
        
        // Update buttons
        $content = str_replace(
            'class="btn btn-primary"',
            'class="btn-modern gradient-primary"',
            $content
        );
        
        $content = str_replace(
            'class="btn btn-sm btn-primary"',
            'class="table-action-btn edit"',
            $content
        );
        
        $content = str_replace(
            'class="btn btn-sm btn-danger"',
            'class="table-action-btn delete"',
            $content
        );
        
        // Save modified content
        file_put_contents($filePath, $content);
        $changes[] = "✅ Updated: $page with ultra-modern theme";
    } else {
        $errors[] = "⚠️  Skipped: $page (no container-fluid found)";
    }
}

// Display results
echo "📋 CHANGES MADE:\n";
echo "================\n";
foreach ($changes as $change) {
    echo "$change\n";
}

if (!empty($errors)) {
    echo "\n⚠️  WARNINGS:\n";
    echo "============\n";
    foreach ($errors as $error) {
        echo "$error\n";
    }
}

echo "\n";
echo "================================\n";
echo "🎉 THEME APPLICATION COMPLETE!\n";
echo "================================\n\n";

echo "📝 NEXT STEPS:\n";
echo "1. Visit admin panel: http://localhost/DigitalKhazana/admin/\n";
echo "2. Clear browser cache (Ctrl+Shift+R)\n";
echo "3. Navigate through all admin pages\n";
echo "4. Enjoy the ultra-modern design!\n\n";

echo "🔄 TO REVERT:\n";
echo "Backup files are saved as *-old.php\n";
echo "Simply rename them back if needed\n\n";

echo "✨ All admin pages now have the ultra-modern theme!\n";
?>
