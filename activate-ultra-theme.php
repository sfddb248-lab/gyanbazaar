<?php
/**
 * Ultra Modern Theme Activation Script
 * This script activates the enhanced pages with ultra-modern design
 */

echo "🎨 Ultra Modern Theme Activation\n";
echo "================================\n\n";

$changes = [];
$errors = [];

// 1. Activate enhanced homepage
if (file_exists('index.php') && file_exists('index-enhanced.php')) {
    // Backup current index
    if (!file_exists('index-old.php')) {
        if (rename('index.php', 'index-old.php')) {
            $changes[] = "✅ Backed up index.php to index-old.php";
        } else {
            $errors[] = "❌ Failed to backup index.php";
        }
    }
    
    // Activate enhanced version
    if (copy('index-enhanced.php', 'index.php')) {
        $changes[] = "✅ Activated enhanced homepage (index.php)";
    } else {
        $errors[] = "❌ Failed to activate enhanced homepage";
    }
} else {
    $errors[] = "❌ index-enhanced.php not found";
}

// 2. Activate enhanced admin dashboard
if (file_exists('admin/index.php') && file_exists('admin/index-enhanced.php')) {
    // Backup current admin index
    if (!file_exists('admin/index-old.php')) {
        if (rename('admin/index.php', 'admin/index-old.php')) {
            $changes[] = "✅ Backed up admin/index.php to admin/index-old.php";
        } else {
            $errors[] = "❌ Failed to backup admin/index.php";
        }
    }
    
    // Activate enhanced version
    if (copy('admin/index-enhanced.php', 'admin/index.php')) {
        $changes[] = "✅ Activated enhanced admin dashboard (admin/index.php)";
    } else {
        $errors[] = "❌ Failed to activate enhanced admin dashboard";
    }
} else {
    $errors[] = "❌ admin/index-enhanced.php not found";
}

// 3. Verify CSS files exist
$cssFiles = [
    'assets/css/ultra-modern-theme.css',
    'assets/css/admin-ultra-theme.css',
    'assets/css/advanced-theme.css'
];

foreach ($cssFiles as $file) {
    if (file_exists($file)) {
        $changes[] = "✅ CSS file exists: $file";
    } else {
        $errors[] = "❌ CSS file missing: $file";
    }
}

// 4. Check if CSS is linked in headers
$headerFile = 'includes/header.php';
if (file_exists($headerFile)) {
    $content = file_get_contents($headerFile);
    if (strpos($content, 'ultra-modern-theme.css') !== false) {
        $changes[] = "✅ Ultra theme CSS linked in header.php";
    } else {
        $errors[] = "❌ Ultra theme CSS not linked in header.php";
    }
}

$adminHeaderFile = 'admin/includes/admin-header.php';
if (file_exists($adminHeaderFile)) {
    $content = file_get_contents($adminHeaderFile);
    if (strpos($content, 'admin-ultra-theme.css') !== false) {
        $changes[] = "✅ Admin ultra theme CSS linked in admin-header.php";
    } else {
        $errors[] = "❌ Admin ultra theme CSS not linked in admin-header.php";
    }
}

// Display results
echo "📋 CHANGES MADE:\n";
echo "================\n";
foreach ($changes as $change) {
    echo "$change\n";
}

if (!empty($errors)) {
    echo "\n⚠️  ERRORS:\n";
    echo "===========\n";
    foreach ($errors as $error) {
        echo "$error\n";
    }
}

echo "\n";
echo "================================\n";
echo "🎉 ACTIVATION COMPLETE!\n";
echo "================================\n\n";

echo "📝 NEXT STEPS:\n";
echo "1. Visit your website: http://localhost/DigitalKhazana/\n";
echo "2. Visit admin panel: http://localhost/DigitalKhazana/admin/\n";
echo "3. Clear browser cache (Ctrl+Shift+R)\n";
echo "4. Enjoy the ultra-modern design!\n\n";

echo "📚 DOCUMENTATION:\n";
echo "- Read ULTRA_MODERN_THEME_GUIDE.md for complete guide\n";
echo "- Check PROJECT_STATUS.md for system overview\n\n";

echo "🔄 TO REVERT:\n";
echo "If you want to go back to the old design:\n";
echo "1. Rename index-old.php to index.php\n";
echo "2. Rename admin/index-old.php to admin/index.php\n\n";

echo "✨ Your website now has an ultra-modern design!\n";
?>
