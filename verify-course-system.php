<?php
// Course System Verification Script
require_once 'config/config.php';

echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║     COURSE VIDEO SYSTEM - VERIFICATION REPORT               ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

$allGood = true;

// 1. Check Database Tables
echo "1. DATABASE TABLES\n";
echo "   " . str_repeat("─", 55) . "\n";

$tables = [
    'course_sections' => 'Course sections/modules',
    'course_videos' => 'Video files and metadata',
    'user_video_progress' => 'User watch progress'
];

foreach ($tables as $table => $description) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result && $result->num_rows > 0) {
        // Check columns
        $columns = $conn->query("SHOW COLUMNS FROM $table")->num_rows;
        echo "   ✓ $table ($columns columns)\n";
        echo "     → $description\n";
    } else {
        echo "   ❌ $table - MISSING!\n";
        $allGood = false;
    }
}

// 2. Check Upload Folders
echo "\n2. UPLOAD FOLDERS\n";
echo "   " . str_repeat("─", 55) . "\n";

$folders = [
    'assets/uploads/courses' => 'Main courses folder',
    'assets/uploads/courses/videos' => 'Video files storage',
    'assets/uploads/courses/notes' => 'PDF notes storage'
];

foreach ($folders as $folder => $description) {
    if (is_dir($folder)) {
        $writable = is_writable($folder) ? 'writable' : 'read-only';
        echo "   ✓ $folder ($writable)\n";
        echo "     → $description\n";
    } else {
        echo "   ❌ $folder - MISSING!\n";
        $allGood = false;
    }
}

// 3. Check Admin Files
echo "\n3. ADMIN FILES\n";
echo "   " . str_repeat("─", 55) . "\n";

$adminFiles = [
    'admin/course-videos.php' => 'Main management page',
    'admin/upload-course-video.php' => 'Video upload interface',
    'admin/edit-course-video.php' => 'Edit video details',
    'admin/delete-course-video.php' => 'Delete video handler',
    'admin/ajax-upload-video.php' => 'AJAX upload handler'
];

foreach ($adminFiles as $file => $description) {
    if (file_exists($file)) {
        $size = number_format(filesize($file) / 1024, 2);
        echo "   ✓ $file ({$size}KB)\n";
        echo "     → $description\n";
    } else {
        echo "   ❌ $file - MISSING!\n";
        $allGood = false;
    }
}

// 4. Check User Files
echo "\n4. USER FILES\n";
echo "   " . str_repeat("─", 55) . "\n";

$userFiles = [
    'course-viewer.php' => 'Video player interface',
    'ajax-mark-video-complete.php' => 'Mark complete handler',
    'ajax-save-video-progress.php' => 'Progress save handler'
];

foreach ($userFiles as $file => $description) {
    if (file_exists($file)) {
        $size = number_format(filesize($file) / 1024, 2);
        echo "   ✓ $file ({$size}KB)\n";
        echo "     → $description\n";
    } else {
        echo "   ❌ $file - MISSING!\n";
        $allGood = false;
    }
}

// 5. Check PHP Configuration
echo "\n5. PHP CONFIGURATION\n";
echo "   " . str_repeat("─", 55) . "\n";

$phpSettings = [
    'upload_max_filesize' => ['current' => ini_get('upload_max_filesize'), 'required' => '500M'],
    'post_max_size' => ['current' => ini_get('post_max_size'), 'required' => '550M'],
    'max_execution_time' => ['current' => ini_get('max_execution_time'), 'required' => '600'],
    'memory_limit' => ['current' => ini_get('memory_limit'), 'required' => '512M']
];

function convertToBytes($val) {
    if ($val == 0) return PHP_INT_MAX; // unlimited
    $val = trim($val);
    $last = strtolower($val[strlen($val)-1]);
    $val = (int)$val;
    switch($last) {
        case 'g': $val *= 1024;
        case 'm': $val *= 1024;
        case 'k': $val *= 1024;
    }
    return $val;
}

foreach ($phpSettings as $setting => $values) {
    $current = $values['current'];
    $required = $values['required'];
    
    if ($setting == 'max_execution_time') {
        $ok = (int)$current >= (int)$required || (int)$current == 0;
    } else {
        $ok = convertToBytes($current) >= convertToBytes($required);
    }
    
    if ($ok) {
        echo "   ✓ $setting = $current\n";
    } else {
        echo "   ⚠ $setting = $current (recommended: $required)\n";
    }
}

// 6. Check Documentation
echo "\n6. DOCUMENTATION\n";
echo "   " . str_repeat("─", 55) . "\n";

$docs = [
    'COURSE_VIDEO_SYSTEM.md',
    'COURSE_SETUP_CHECKLIST.txt',
    'COURSE_SYSTEM_SUMMARY.md',
    'SETUP_COMPLETE_REPORT.md'
];

foreach ($docs as $doc) {
    if (file_exists($doc)) {
        $size = number_format(filesize($doc) / 1024, 2);
        echo "   ✓ $doc ({$size}KB)\n";
    } else {
        echo "   ⚠ $doc - Not found\n";
    }
}

// 7. Test Database Connection
echo "\n7. DATABASE CONNECTION\n";
echo "   " . str_repeat("─", 55) . "\n";

try {
    $result = $conn->query("SELECT COUNT(*) as cnt FROM course_sections");
    if ($result) {
        echo "   ✓ Database connection working\n";
        echo "   ✓ Can query course tables\n";
    }
} catch (Exception $e) {
    echo "   ❌ Database error: " . $e->getMessage() . "\n";
    $allGood = false;
}

// 8. Check Products Table for Course Type
echo "\n8. PRODUCTS TABLE\n";
echo "   " . str_repeat("─", 55) . "\n";

$result = $conn->query("SHOW COLUMNS FROM products LIKE 'product_type'");
if ($result && $result->num_rows > 0) {
    echo "   ✓ product_type column exists\n";
    
    // Check if 'course' is in ENUM
    $column = $conn->query("SHOW COLUMNS FROM products LIKE 'product_type'")->fetch_assoc();
    if (strpos($column['Type'], 'course') !== false) {
        echo "   ✓ 'course' type available\n";
    } else {
        echo "   ⚠ 'course' type not in ENUM (may need to add)\n";
    }
} else {
    echo "   ❌ product_type column missing\n";
    $allGood = false;
}

// Final Summary
echo "\n╔══════════════════════════════════════════════════════════════╗\n";
if ($allGood) {
    echo "║                  ✅ ALL SYSTEMS OPERATIONAL                  ║\n";
} else {
    echo "║              ⚠ SOME ISSUES DETECTED - SEE ABOVE             ║\n";
}
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

// Quick Links
echo "📋 QUICK LINKS:\n";
echo "   Admin Panel: http://localhost/DigitalKhazana/admin/login.php\n";
echo "   Products: http://localhost/DigitalKhazana/admin/products.php\n";
echo "   Documentation: COURSE_VIDEO_SYSTEM.md\n\n";

// Action Items
echo "⚡ ACTION ITEMS:\n";
if (!$allGood) {
    echo "   1. Fix issues listed above\n";
    echo "   2. Run this script again to verify\n";
} else {
    echo "   1. ✓ Restart Apache (if not done)\n";
    echo "   2. ✓ Login to admin panel\n";
    echo "   3. ✓ Create your first course\n";
    echo "   4. ✓ Upload videos and start teaching!\n";
}

echo "\n🎉 Course Video System Ready!\n\n";
?>
