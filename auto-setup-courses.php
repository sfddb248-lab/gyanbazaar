<?php
// Automatic Course System Setup
require_once 'config/config.php';

echo "=================================================\n";
echo "   COURSE VIDEO SYSTEM - AUTO SETUP\n";
echo "=================================================\n\n";

// Create course_sections table
echo "Creating course_sections table ... ";
$sql = "CREATE TABLE IF NOT EXISTS course_sections (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    order_index INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_section_product (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conn->query($sql)) {
    echo "✓ Success\n";
} else {
    echo "❌ Error: " . $conn->error . "\n";
}

// Create course_videos table
echo "Creating course_videos table ... ";
$sql = "CREATE TABLE IF NOT EXISTS course_videos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    section_id INT NOT NULL,
    product_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    video_path VARCHAR(500) NOT NULL,
    video_duration VARCHAR(20),
    video_size BIGINT,
    notes_path VARCHAR(500),
    order_index INT DEFAULT 0,
    is_preview BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (section_id) REFERENCES course_sections(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_video_section (section_id),
    INDEX idx_video_product (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conn->query($sql)) {
    echo "✓ Success\n";
} else {
    echo "❌ Error: " . $conn->error . "\n";
}

// Create user_video_progress table
echo "Creating user_video_progress table ... ";
$sql = "CREATE TABLE IF NOT EXISTS user_video_progress (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    video_id INT NOT NULL,
    product_id INT NOT NULL,
    watched_duration INT DEFAULT 0,
    total_duration INT DEFAULT 0,
    completed BOOLEAN DEFAULT FALSE,
    last_watched TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (video_id) REFERENCES course_videos(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_video (user_id, video_id),
    INDEX idx_progress_user (user_id),
    INDEX idx_progress_video (video_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conn->query($sql)) {
    echo "✓ Success\n";
} else {
    echo "❌ Error: " . $conn->error . "\n";
}

// Verify tables
echo "\n=================================================\n";
echo "   VERIFYING TABLES\n";
echo "=================================================\n";

$tables = ['course_sections', 'course_videos', 'user_video_progress'];
$allExist = true;

foreach ($tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result && $result->num_rows > 0) {
        echo "✓ Table exists: $table\n";
        
        // Count rows
        $count = $conn->query("SELECT COUNT(*) as cnt FROM $table")->fetch_assoc()['cnt'];
        echo "  → Records: $count\n";
    } else {
        echo "❌ Table missing: $table\n";
        $allExist = false;
    }
}

// Create upload folders
echo "\n=================================================\n";
echo "   CREATING UPLOAD FOLDERS\n";
echo "=================================================\n";

$folders = [
    'assets/uploads/courses',
    'assets/uploads/courses/videos',
    'assets/uploads/courses/notes'
];

foreach ($folders as $folder) {
    if (!is_dir($folder)) {
        mkdir($folder, 0777, true);
        echo "✓ Created: $folder\n";
    } else {
        echo "✓ Already exists: $folder\n";
    }
}

// Check PHP configuration
echo "\n=================================================\n";
echo "   PHP CONFIGURATION CHECK\n";
echo "=================================================\n";

$uploadMax = ini_get('upload_max_filesize');
$postMax = ini_get('post_max_size');
$memoryLimit = ini_get('memory_limit');
$maxExecution = ini_get('max_execution_time');

echo "upload_max_filesize: $uploadMax\n";
echo "post_max_size: $postMax\n";
echo "memory_limit: $memoryLimit\n";
echo "max_execution_time: $maxExecution\n";

// Convert to bytes for comparison
function convertToBytes($val) {
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

$uploadBytes = convertToBytes($uploadMax);
$requiredBytes = 500 * 1024 * 1024; // 500MB

if ($uploadBytes >= $requiredBytes) {
    echo "\n✓ PHP configured for 500MB uploads\n";
} else {
    echo "\n⚠ WARNING: upload_max_filesize is less than 500MB\n";
    echo "  Current: $uploadMax\n";
    echo "  Required: 500M\n";
    echo "  Please update php.ini or .htaccess\n";
}

echo "\n=================================================\n";
if ($allExist) {
    echo "   ✅ SETUP COMPLETE - SYSTEM READY!\n";
} else {
    echo "   ⚠ SETUP INCOMPLETE - CHECK ERRORS ABOVE\n";
}
echo "=================================================\n";

echo "\n📋 NEXT STEPS:\n";
echo "1. Admin Panel: http://localhost/DigitalKhazana/admin/login.php\n";
echo "2. Go to Products → Add Product\n";
echo "3. Set Product Type to 'Course'\n";
echo "4. Click the video icon (🎥) to manage videos\n";
echo "5. Add sections and upload videos\n";
echo "\n🎉 Happy course creating!\n\n";
?>
