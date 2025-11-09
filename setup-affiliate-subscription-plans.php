<?php
require_once 'config/config.php';

echo "<h2>Affiliate Subscription Plans Setup</h2>";
echo "<p>Creating subscription plan system...</p>";

$success = 0;
$errors = 0;

// Create affiliate_plans table
$query = "CREATE TABLE IF NOT EXISTS affiliate_plans (
    id INT PRIMARY KEY AUTO_INCREMENT,
    plan_name VARCHAR(50) NOT NULL,
    plan_slug VARCHAR(50) UNIQUE NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    duration_days INT NOT NULL DEFAULT 365,
    commission_rate DECIMAL(5,2) NOT NULL,
    mlm_levels INT NOT NULL DEFAULT 3,
    priority_support BOOLEAN DEFAULT FALSE,
    promotional_materials BOOLEAN DEFAULT TRUE,
    analytics_access BOOLEAN DEFAULT TRUE,
    custom_landing_page BOOLEAN DEFAULT FALSE,
    dedicated_manager BOOLEAN DEFAULT FALSE,
    max_referrals INT DEFAULT NULL,
    features TEXT,
    badge_color VARCHAR(20),
    badge_icon VARCHAR(50),
    display_order INT DEFAULT 0,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($query)) {
    $success++;
    echo "<p style='color: green;'>✓ affiliate_plans table created</p>";
} else {
    $errors++;
    echo "<p style='color: red;'>✗ Error: " . $conn->error . "</p>";
}

// Create affiliate_subscriptions table
$query = "CREATE TABLE IF NOT EXISTS affiliate_subscriptions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    affiliate_id INT NOT NULL,
    plan_id INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('active', 'expired', 'cancelled') DEFAULT 'active',
    payment_amount DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(50),
    transaction_id VARCHAR(100),
    auto_renew BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (affiliate_id) REFERENCES affiliates(id) ON DELETE CASCADE,
    FOREIGN KEY (plan_id) REFERENCES affiliate_plans(id),
    INDEX idx_affiliate_id (affiliate_id),
    INDEX idx_status (status),
    INDEX idx_end_date (end_date)
)";

if ($conn->query($query)) {
    $success++;
    echo "<p style='color: green;'>✓ affiliate_subscriptions table created</p>";
} else {
    $errors++;
    echo "<p style='color: red;'>✗ Error: " . $conn->error . "</p>";
}

// Insert default plans
$plans = [
    [
        'plan_name' => 'Bronze',
        'plan_slug' => 'bronze',
        'price' => 0.00,
        'duration_days' => 365,
        'commission_rate' => 10.00,
        'mlm_levels' => 3,
        'priority_support' => 0,
        'promotional_materials' => 1,
        'analytics_access' => 1,
        'custom_landing_page' => 0,
        'dedicated_manager' => 0,
        'max_referrals' => NULL,
        'features' => json_encode([
            '10% Commission Rate',
            '3 MLM Levels',
            'Basic Analytics',
            'Promotional Materials',
            'Email Support',
            'Standard Dashboard'
        ]),
        'badge_color' => '#cd7f32',
        'badge_icon' => 'fa-medal',
        'display_order' => 1
    ],
    [
        'plan_name' => 'Silver',
        'plan_slug' => 'silver',
        'price' => 999.00,
        'duration_days' => 365,
        'commission_rate' => 15.00,
        'mlm_levels' => 5,
        'priority_support' => 1,
        'promotional_materials' => 1,
        'analytics_access' => 1,
        'custom_landing_page' => 1,
        'dedicated_manager' => 0,
        'max_referrals' => NULL,
        'features' => json_encode([
            '15% Commission Rate',
            '5 MLM Levels',
            'Advanced Analytics',
            'Premium Materials',
            'Priority Support',
            'Custom Landing Page',
            'Weekly Reports'
        ]),
        'badge_color' => '#c0c0c0',
        'badge_icon' => 'fa-award',
        'display_order' => 2
    ],
    [
        'plan_name' => 'Gold',
        'plan_slug' => 'gold',
        'price' => 2499.00,
        'duration_days' => 365,
        'commission_rate' => 20.00,
        'mlm_levels' => 10,
        'priority_support' => 1,
        'promotional_materials' => 1,
        'analytics_access' => 1,
        'custom_landing_page' => 1,
        'dedicated_manager' => 1,
        'max_referrals' => NULL,
        'features' => json_encode([
            '20% Commission Rate',
            '10 MLM Levels',
            'Premium Analytics',
            'Exclusive Materials',
            '24/7 Priority Support',
            'Custom Landing Page',
            'Dedicated Account Manager',
            'Daily Reports',
            'API Access',
            'White Label Options'
        ]),
        'badge_color' => '#ffd700',
        'badge_icon' => 'fa-crown',
        'display_order' => 3
    ]
];

foreach ($plans as $plan) {
    $stmt = $conn->prepare("INSERT INTO affiliate_plans (
        plan_name, plan_slug, price, duration_days, commission_rate, mlm_levels,
        priority_support, promotional_materials, analytics_access, custom_landing_page,
        dedicated_manager, max_referrals, features, badge_color, badge_icon, display_order
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE 
        price = VALUES(price),
        commission_rate = VALUES(commission_rate),
        mlm_levels = VALUES(mlm_levels)");
    
    $stmt->bind_param(
        "ssdidiiiiiiisssi",
        $plan['plan_name'],
        $plan['plan_slug'],
        $plan['price'],
        $plan['duration_days'],
        $plan['commission_rate'],
        $plan['mlm_levels'],
        $plan['priority_support'],
        $plan['promotional_materials'],
        $plan['analytics_access'],
        $plan['custom_landing_page'],
        $plan['dedicated_manager'],
        $plan['max_referrals'],
        $plan['features'],
        $plan['badge_color'],
        $plan['badge_icon'],
        $plan['display_order']
    );
    
    if ($stmt->execute()) {
        $success++;
        echo "<p style='color: green;'>✓ {$plan['plan_name']} plan created</p>";
    } else {
        $errors++;
        echo "<p style='color: red;'>✗ Error creating {$plan['plan_name']}: " . $conn->error . "</p>";
    }
}

// Add plan_id column to affiliates table
$conn->query("ALTER TABLE affiliates ADD COLUMN IF NOT EXISTS plan_id INT DEFAULT 1");
$conn->query("ALTER TABLE affiliates ADD COLUMN IF NOT EXISTS plan_expires_at DATE DEFAULT NULL");

echo "<div style='background: #d4edda; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
echo "<h3 style='color: #155724;'>✅ Setup Complete!</h3>";
echo "<p><strong>Operations:</strong> $success successful, $errors errors</p>";
echo "</div>";

echo "<h3>📊 Subscription Plans Created:</h3>";
echo "<div style='display: flex; gap: 20px; margin: 20px 0;'>";

// Display plans
$result = $conn->query("SELECT * FROM affiliate_plans ORDER BY display_order");
while ($plan = $result->fetch_assoc()) {
    $features = json_decode($plan['features'], true);
    echo "<div style='border: 2px solid {$plan['badge_color']}; border-radius: 10px; padding: 20px; flex: 1; background: white;'>";
    echo "<div style='text-align: center;'>";
    echo "<i class='fas {$plan['badge_icon']} fa-3x' style='color: {$plan['badge_color']};'></i>";
    echo "<h3 style='color: {$plan['badge_color']};'>{$plan['plan_name']}</h3>";
    echo "<h2>₹" . number_format($plan['price'], 2) . "</h2>";
    echo "<p style='color: #666;'>per year</p>";
    echo "</div>";
    echo "<hr>";
    echo "<ul style='list-style: none; padding: 0;'>";
    foreach ($features as $feature) {
        echo "<li style='padding: 5px 0;'><i class='fas fa-check' style='color: green;'></i> $feature</li>";
    }
    echo "</ul>";
    echo "</div>";
}
echo "</div>";

echo "<h3>🔗 Quick Links:</h3>";
echo "<div style='margin: 20px 0;'>";
echo "<a href='affiliate-plans.php' class='btn' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 5px;'>View Plans (User)</a>";
echo "<a href='admin/affiliate-plans-admin.php' class='btn' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 5px;'>Manage Plans (Admin)</a>";
echo "<a href='affiliate-dashboard.php' class='btn' style='background: #17a2b8; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 5px;'>Affiliate Dashboard</a>";
echo "</div>";

echo "<style>
body { font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px; }
.btn:hover { opacity: 0.9; }
</style>";

// Add Font Awesome
echo "<link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css' rel='stylesheet'>";
?>
