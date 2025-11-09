<?php
/**
 * Restore Old Admin Panel
 * This script restores all admin pages to their original state
 */

echo "🔄 Restoring Old Admin Panel\n";
echo "============================\n\n";

$adminPages = [
    'index.php',
    'products.php',
    'orders.php',
    'users.php',
    'coupons.php',
    'reports.php',
    'settings.php'
];

$restored = [];
$errors = [];

foreach ($adminPages as $page) {
    $currentFile = 'admin/' . $page;
    $backupFile = 'admin/' . str_replace('.php', '-old.php', $page);
    
    if (file_exists($backupFile)) {
        // Restore from backup
        if (copy($backupFile, $currentFile)) {
            $restored[] = "✅ Restored: $page from backup";
        } else {
            $errors[] = "❌ Failed to restore: $page";
        }
    } else {
        $errors[] = "⚠️  No backup found for: $page";
    }
}

// Remove ultra-modern theme CSS link from admin header
$adminHeaderFile = 'admin/includes/admin-header.php';
if (file_exists($adminHeaderFile)) {
    $content = file_get_contents($adminHeaderFile);
    
    // Remove ultra theme CSS links
    $content = preg_replace(
        '/<link href="[^"]*admin-ultra-theme\.css[^>]*>\s*/i',
        '',
        $content
    );
    
    $content = preg_replace(
        '/<link href="[^"]*ultra-modern-theme\.css[^>]*>\s*/i',
        '',
        $content
    );
    
    file_put_contents($adminHeaderFile, $content);
    $restored[] = "✅ Removed ultra theme CSS from admin header";
}

// Delete new pages that weren't in original
$newPages = ['notifications.php', 'messages.php'];
foreach ($newPages as $page) {
    $filePath = 'admin/' . $page;
    if (file_exists($filePath)) {
        unlink($filePath);
        $restored[] = "✅ Removed new page: $page";
    }
}

// Display results
echo "📋 RESTORATION RESULTS:\n";
echo "=======================\n";
foreach ($restored as $item) {
    echo "$item\n";
}

if (!empty($errors)) {
    echo "\n⚠️  WARNINGS:\n";
    echo "=============\n";
    foreach ($errors as $error) {
        echo "$error\n";
    }
}

echo "\n";
echo "================================\n";
echo "🎉 RESTORATION COMPLETE!\n";
echo "================================\n\n";

echo "✅ WHAT WAS RESTORED:\n";
echo "1. All admin pages restored to original state\n";
echo "2. Ultra-modern theme CSS removed\n";
echo "3. New pages (notifications, messages) removed\n";
echo "4. Original simple design restored\n\n";

echo "📝 NEXT STEPS:\n";
echo "1. Clear browser cache (Ctrl+Shift+R)\n";
echo "2. Visit: http://localhost/DigitalKhazana/admin/\n";
echo "3. You should see your old admin panel\n\n";

echo "💡 NOTE:\n";
echo "- Backup files are still available (*-old.php)\n";
echo "- You can switch back to modern theme anytime\n";
echo "- Website theme remains unchanged\n\n";

echo "✨ Your old admin panel is back!\n";
?>
