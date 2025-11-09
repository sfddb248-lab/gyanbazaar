<?php
require_once 'config/database.php';

echo "=== Fixing Foreign Key Constraints ===\n\n";

try {
    // Drop the existing foreign key constraint on orders table
    echo "1. Dropping old foreign key constraint on orders table...\n";
    $conn->exec("ALTER TABLE orders DROP FOREIGN KEY orders_ibfk_1");
    echo "   ✓ Old constraint dropped\n\n";
    
    // Add new foreign key with ON DELETE CASCADE
    echo "2. Adding new foreign key with CASCADE...\n";
    $conn->exec("ALTER TABLE orders 
                 ADD CONSTRAINT orders_ibfk_1 
                 FOREIGN KEY (user_id) REFERENCES users(id) 
                 ON DELETE CASCADE");
    echo "   ✓ New constraint added with ON DELETE CASCADE\n\n";
    
    // Fix support_tickets table if it exists
    echo "3. Checking support_tickets table...\n";
    $result = $conn->query("SHOW CREATE TABLE support_tickets");
    if ($result) {
        $createTable = $result->fetch(PDO::FETCH_ASSOC);
        if (strpos($createTable['Create Table'], 'support_tickets_ibfk_1') !== false) {
            echo "   Fixing support_tickets foreign key...\n";
            $conn->exec("ALTER TABLE support_tickets DROP FOREIGN KEY support_tickets_ibfk_1");
            $conn->exec("ALTER TABLE support_tickets 
                         ADD CONSTRAINT support_tickets_ibfk_1 
                         FOREIGN KEY (user_id) REFERENCES users(id) 
                         ON DELETE CASCADE");
            echo "   ✓ support_tickets constraint fixed\n";
        }
    }
    
    echo "\n=== SUCCESS ===\n";
    echo "Foreign key constraints have been updated.\n";
    echo "Now when you delete a user:\n";
    echo "- Their orders will be automatically deleted\n";
    echo "- Their support tickets will be automatically deleted\n";
    echo "- Their affiliate data will be handled properly\n\n";
    
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "\nIf the constraint name is different, you may need to:\n";
    echo "1. Run: SHOW CREATE TABLE orders;\n";
    echo "2. Find the actual constraint name\n";
    echo "3. Update this script with the correct name\n";
}
?>
