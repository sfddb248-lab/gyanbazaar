<?php
/**
 * Convert MySQL database to PostgreSQL
 * This creates a PostgreSQL-compatible SQL file
 */

echo "Converting MySQL to PostgreSQL...\n\n";

$mysql_file = 'database-fixed.sql';
$pgsql_file = 'database-postgresql.sql';

if (!file_exists($mysql_file)) {
    die("Error: $mysql_file not found!\n");
}

$sql = file_get_contents($mysql_file);

// Convert MySQL syntax to PostgreSQL
$conversions = [
    // Auto increment
    '/AUTO_INCREMENT/i' => '',
    '/auto_increment/i' => '',
    
    // Engine
    '/ENGINE=InnoDB/i' => '',
    '/ENGINE=MyISAM/i' => '',
    
    // Character set
    '/DEFAULT CHARSET=utf8mb4/i' => '',
    '/COLLATE=utf8mb4_unicode_ci/i' => '',
    '/CHARACTER SET utf8mb4/i' => '',
    
    // Data types
    '/INT\((\d+)\)/i' => 'INTEGER',
    '/TINYINT\(1\)/i' => 'BOOLEAN',
    '/TINYINT\((\d+)\)/i' => 'SMALLINT',
    '/DATETIME/i' => 'TIMESTAMP',
    '/LONGTEXT/i' => 'TEXT',
    '/MEDIUMTEXT/i' => 'TEXT',
    
    // Backticks to quotes
    '/`([^`]+)`/' => '"$1"',
    
    // UNSIGNED
    '/UNSIGNED/i' => '',
    
    // ON UPDATE CURRENT_TIMESTAMP
    '/ON UPDATE CURRENT_TIMESTAMP/i' => '',
];

foreach ($conversions as $pattern => $replacement) {
    $sql = preg_replace($pattern, $replacement, $sql);
}

// Add SERIAL for auto-increment primary keys
$sql = preg_replace(
    '/"id" INTEGER NOT NULL,\s*PRIMARY KEY \("id"\)/i',
    '"id" SERIAL PRIMARY KEY',
    $sql
);

// Fix ENUM types (PostgreSQL doesn't have ENUM in same way)
$sql = preg_replace(
    '/ENUM\([^)]+\)/i',
    'VARCHAR(50)',
    $sql
);

// Add IF NOT EXISTS
$sql = preg_replace(
    '/CREATE TABLE "([^"]+)"/i',
    'CREATE TABLE IF NOT EXISTS "$1"',
    $sql
);

// Remove duplicate semicolons
$sql = preg_replace('/;;+/', ';', $sql);

// Save PostgreSQL file
file_put_contents($pgsql_file, $sql);

echo "✅ Conversion complete!\n";
echo "Created: $pgsql_file\n";
echo "File size: " . filesize($pgsql_file) . " bytes\n\n";
echo "Now push this to GitHub and Render will use it!\n";
?>
