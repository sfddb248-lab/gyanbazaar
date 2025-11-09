<?php
// Automatic Database Setup for Course System
require_once 'config/config.php';

echo "=================================================\n";
echo "   COURSE VIDEO SYSTEM - DATABASE SETUP\n";
echo "=================================================\n\n";

// Read SQL file
$sqlFile = 'update-database-courses.sql';
if (!file_exists($sqlFile)) {
    die("❌ Error: SQL file not found: $sqlFile\n");
}

$sql = file_get_contents($sqlFile);
echo "✓ SQL file loaded\n";

// Split SQL into individual statements
$statements = array_filter(
    array_map('trim', 
    preg_split('/;[\r\n]+/', $sql)),
    function($stmt) {
        return !empty($stmt) && 
               !preg_match('/^--/', $stmt) && 
               !preg_match('/^\/\*/', $stmt);
    }
);

echo "✓ Found " . count($statements) . " SQL statements\n\n";

// Execute each statement
$success = 0;
$errors = 0;

foreach ($statements as $index => $statement) {
    $statement = trim($statement);
    if (empty($statement)) continue;
    
    // Get table name for display
    if (preg_match('/CREATE TABLE.*?`?(\w+)`?/i', $statement, $matches)) {
        $tableName = $matches[1];
        echo "Creating table: $tableName ... ";
        
        try {
            if ($conn->query($statement)) {
                echo "✓ Success\n";
                $success++;
            } else {
                // Check if table already exists
                if (strpos($conn->error, 'already exists') !== false) {
                    echo "⚠ Already exists\n";
                    $success++;
                } else {
                    echo "❌ Error: " . $conn->error . "\n";
                    $errors++;
                }
            }
        } catch (Exception $e) {
            echo "❌ Exception: " . $e->getMessage() . "\n";
            $errors++;
        }
    } elseif (preg_match('/CREATE INDEX/i', $statement)) {
        echo "Creating index ... ";
        try {
            if ($conn->query($statement)) {
                echo "✓ Success\n";
                $success++;
            } else {
                if (strpos($conn->error, 'Duplicate key') !== false) {
                    echo "⚠ Already exists\n";
                    $success++;
                } else {
                    echo "❌ Error: " . $conn->error . "\n";
                    $errors++;
                }
            }
        } catch (Exception $e) {
            echo "❌ Exception: " . $e->getMessage() . "\n";
            $errors++;
        }
    } else {
        // Execute other statements
        try {
            if ($conn->query($statement)) {
                $success++;
            }
        } catch (Exception $e) {
            $errors++;
        }
    }
}

echo "\n=================================================\n";
echo "   SETUP COMPLETE\n";
echo "=================================================\n";
echo "✓ Successful: $success\n";
if ($errors > 0) {
    echo "❌ Errors: $errors\n";
}

// Verify tables exist
echo "\n=================================================\n";
echo "   VERIFYING TABLES\n";
echo "=================================================\n";

$tables = ['course_sections', 'course_videos', 'user_video_progress'];
$allExist = true;

foreach ($tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result && $result->num_rows > 0) {
        echo "✓ Table exists: $table\n";
    } else {
        echo "❌ Table missing: $table\n";
        $allExist = false;
    }
}

echo "\n=================================================\n";
if ($allExist) {
    echo "   ✅ ALL TABLES CREATED SUCCESSFULLY!\n";
} else {
    echo "   ⚠ SOME TABLES ARE MISSING\n";
}
echo "=================================================\n";

// Check upload folders
echo "\n=================================================\n";
echo "   VERIFYING UPLOAD FOLDERS\n";
echo "=================================================\n";

$folders = [
    'assets/uploads/courses',
    'assets/uploads/courses/videos',
    'assets/uploads/courses/notes'
];

foreach ($folders as $folder) {
    if (is_dir($folder)) {
        echo "✓ Folder exists: $folder\n";
    } else {
        echo "❌ Folder missing: $folder\n";
        mkdir($folder, 0777, true);
        echo "  → Created: $folder\n";
    }
}

echo "\n=================================================\n";
echo "   🎉 COURSE SYSTEM READY TO USE!\n";
echo "=================================================\n";
echo "\nNext steps:\n";
echo "1. Go to Admin Panel: http://localhost/DigitalKhazana/admin\n";
echo "2. Create a product with type 'Course'\n";
echo "3. Click the video icon to manage videos\n";
echo "4. Start uploading your course content!\n\n";
?>
