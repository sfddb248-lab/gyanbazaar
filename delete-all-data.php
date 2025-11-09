<?php
require_once 'config/database.php';

echo "=== PERMANENT DATA DELETION ===\n";
echo "WARNING: This will delete ALL data from the database!\n";
echo "Tables will remain, but all records will be deleted.\n\n";

// Disable foreign key checks temporarily
echo "Disabling foreign key checks...\n";
$conn->exec("SET FOREIGN_KEY_CHECKS = 0");

try {
    // List of tables to truncate (in order to avoid FK issues)
    $tables = [
        'course_progress',
        'course_videos',
        'commissions',
        'referrals',
        'affiliates',
        'support_tickets',
        'orders',
        'products',
        'users',
        'settings',
        'payment_gateways'
    ];
    
    echo "Deleting data from tables...\n\n";
    
    foreach ($tables as $table) {
        try {
            $conn->exec("TRUNCATE TABLE `$table`");
            echo "✓ Cleared: $table\n";
        } catch (PDOException $e) {
            // Table might not exist, skip it
            echo "⚠ Skipped: $table (table doesn't exist or error)\n";
        }
    }
    
    echo "\n=== DELETION COMPLETE ===\n";
    echo "All data has been permanently deleted.\n";
    echo "Database tables still exist but are empty.\n";
    echo "Auto-increment counters have been reset.\n\n";
    
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
} finally {
    // Re-enable foreign key checks
    echo "Re-enabling foreign key checks...\n";
    $conn->exec("SET FOREIGN_KEY_CHECKS = 1");
}
?>
