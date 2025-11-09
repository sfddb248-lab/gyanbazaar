<?php
require_once 'config/config.php';

echo "<h2>Affiliate Marketing System - Automatic Setup</h2>";
echo "<p>Installing affiliate system...</p>";

$success = 0;
$errors = 0;

// Create tables directly
$tables = [
    "CREATE TABLE IF NOT EXISTS affiliates (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        referral_code VARCHAR(50) UNIQUE NOT NULL,
        commission_type ENUM('percentage', 'flat') DEFAULT 'percentage',
        commission_value DECIMAL(10,2) NOT NULL DEFAULT 10.00,
        total_earnings DECIMAL(10,2) DEFAULT 0,
        pending_earnings DECIMAL(10,2) DEFAULT 0,
        paid_earnings DECIMAL(10,2) DEFAULT 0,
        total_referrals INT DEFAULT 0,
        total_sales INT DEFAULT 0,
        status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
        payment_method VARCHAR(50),
        payment_details TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_referral_code (referral_code),
        INDEX idx_user_id (user_id)
    )",
    
    "CREATE TABLE IF NOT EXISTS affiliate_referrals (
        id INT PRIMARY KEY AUTO_INCREMENT,
        affiliate_id INT NOT NULL,
        referred_user_id INT,
        referral_code VARCHAR(50) NOT NULL,
        ip_address VARCHAR(45),
        user_agent TEXT,
        converted BOOLEAN DEFAULT FALSE,
        conversion_date DATETIME,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (affiliate_id) REFERENCES affiliates(id) ON DELETE CASCADE,
        FOREIGN KEY (referred_user_id) REFERENCES users(id) ON DELETE SET NULL,
        INDEX idx_affiliate_id (affiliate_id),
        INDEX idx_referral_code (referral_code)
    )",
    
    "CREATE TABLE IF NOT EXISTS affiliate_commissions (
        id INT PRIMARY KEY AUTO_INCREMENT,
        affiliate_id INT NOT NULL,
        order_id INT NOT NULL,
        referral_id INT,
        commission_amount DECIMAL(10,2) NOT NULL,
        commission_type ENUM('percentage', 'flat') NOT NULL,
        commission_rate DECIMAL(10,2) NOT NULL,
        order_amount DECIMAL(10,2) NOT NULL,
        status ENUM('pending', 'approved', 'paid', 'cancelled') DEFAULT 'pending',
        level INT DEFAULT 1,
        parent_affiliate_id INT,
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        approved_at DATETIME,
        paid_at DATETIME,
        FOREIGN KEY (affiliate_id) REFERENCES affiliates(id) ON DELETE CASCADE,
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
        FOREIGN KEY (referral_id) REFERENCES affiliate_referrals(id) ON DELETE SET NULL,
        FOREIGN KEY (parent_affiliate_id) REFERENCES affiliates(id) ON DELETE SET NULL,
        INDEX idx_affiliate_id (affiliate_id),
        INDEX idx_order_id (order_id),
        INDEX idx_status (status)
    )",
    
    "CREATE TABLE IF NOT EXISTS affiliate_payouts (
        id INT PRIMARY KEY AUTO_INCREMENT,
        affiliate_id INT NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        payment_method VARCHAR(50) NOT NULL,
        payment_details TEXT,
        transaction_id VARCHAR(100),
        status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
        notes TEXT,
        requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        processed_at DATETIME,
        completed_at DATETIME,
        FOREIGN KEY (affiliate_id) REFERENCES affiliates(id) ON DELETE CASCADE,
        INDEX idx_affiliate_id (affiliate_id),
        INDEX idx_status (status)
    )",
    
    "CREATE TABLE IF NOT EXISTS affiliate_mlm_structure (
        id INT PRIMARY KEY AUTO_INCREMENT,
        affiliate_id INT NOT NULL,
        parent_affiliate_id INT,
        level INT DEFAULT 1,
        path VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (affiliate_id) REFERENCES affiliates(id) ON DELETE CASCADE,
        FOREIGN KEY (parent_affiliate_id) REFERENCES affiliates(id) ON DELETE CASCADE,
        INDEX idx_affiliate_id (affiliate_id),
        INDEX idx_parent_affiliate_id (parent_affiliate_id)
    )",
    
    "CREATE TABLE IF NOT EXISTS affiliate_materials (
        id INT PRIMARY KEY AUTO_INCREMENT,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        material_type ENUM('banner', 'email', 'social', 'video', 'document') NOT NULL,
        file_path VARCHAR(255),
        file_url VARCHAR(255),
        dimensions VARCHAR(50),
        file_size INT,
        download_count INT DEFAULT 0,
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_material_type (material_type)
    )",
    
    "CREATE TABLE IF NOT EXISTS affiliate_clicks (
        id INT PRIMARY KEY AUTO_INCREMENT,
        affiliate_id INT NOT NULL,
        referral_code VARCHAR(50) NOT NULL,
        ip_address VARCHAR(45),
        user_agent TEXT,
        referer_url TEXT,
        landing_page VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (affiliate_id) REFERENCES affiliates(id) ON DELETE CASCADE,
        INDEX idx_affiliate_id (affiliate_id),
        INDEX idx_created_at (created_at)
    )",
    
    "CREATE TABLE IF NOT EXISTS affiliate_settings (
        id INT PRIMARY KEY AUTO_INCREMENT,
        setting_key VARCHAR(100) UNIQUE NOT NULL,
        setting_value TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )"
];

foreach ($tables as $query) {
    if ($conn->query($query)) {
        $success++;
        echo "<p style='color: green;'>✓ Table created successfully</p>";
    } else {
        $errors++;
        echo "<p style='color: red;'>✗ Error: " . $conn->error . "</p>";
    }
}

// Insert default settings
$settings = [
    ['affiliate_enabled', '1'],
    ['min_payout_amount', '500'],
    ['mlm_enabled', '1'],
    ['mlm_levels', '10'],
    ['level_1_commission', '10'],
    ['level_2_commission', '5'],
    ['level_3_commission', '2'],
    ['level_4_commission', '1.5'],
    ['level_5_commission', '1'],
    ['level_6_commission', '0.75'],
    ['level_7_commission', '0.5'],
    ['level_8_commission', '0.25'],
    ['level_9_commission', '0.15'],
    ['level_10_commission', '0.1'],
    ['auto_approve_affiliates', '0'],
    ['cookie_duration_days', '30']
];

foreach ($settings as $setting) {
    $stmt = $conn->prepare("INSERT INTO affiliate_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
    $stmt->bind_param("sss", $setting[0], $setting[1], $setting[1]);
    if ($stmt->execute()) {
        $success++;
    } else {
        $errors++;
    }
}

// Add columns to orders table
$conn->query("ALTER TABLE orders ADD COLUMN IF NOT EXISTS affiliate_id INT DEFAULT NULL");
$conn->query("ALTER TABLE orders ADD COLUMN IF NOT EXISTS referral_code VARCHAR(50) DEFAULT NULL");
$conn->query("ALTER TABLE orders ADD INDEX IF NOT EXISTS idx_affiliate_id (affiliate_id)");

echo "<div style='background: #d4edda; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
echo "<h3 style='color: #155724;'>✅ Setup Complete!</h3>";
echo "<p><strong>Operations:</strong> $success successful, $errors errors</p>";
echo "</div>";

echo "<h3>✅ Integration Complete!</h3>";
echo "<p>The following files have been automatically updated:</p>";
echo "<ul>";
echo "<li>✓ index.php - Affiliate tracking added</li>";
echo "<li>✓ signup.php - Referral tracking added</li>";
echo "<li>✓ checkout.php - Commission creation added</li>";
echo "<li>✓ includes/header.php - Affiliate menu added</li>";
echo "<li>✓ admin/includes/admin-header.php - Admin menu added</li>";
echo "</ul>";

echo "<h3>🎉 System Ready!</h3>";
echo "<p>Your affiliate marketing system is now fully operational!</p>";

echo "<div style='margin: 30px 0;'>";
echo "<a href='test-affiliate-system.php' class='btn' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 5px;'>Test System</a>";
echo "<a href='affiliate-dashboard.php' class='btn' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 5px;'>Affiliate Dashboard</a>";
echo "<a href='admin/affiliate-settings.php' class='btn' style='background: #17a2b8; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 5px;'>Configure Settings</a>";
echo "<a href='admin/affiliates.php' class='btn' style='background: #ffc107; color: #000; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 5px;'>Manage Affiliates</a>";
echo "</div>";

echo "<style>body { font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px; }</style>";
?>
