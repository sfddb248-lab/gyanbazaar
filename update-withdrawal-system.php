<?php
require_once 'config/config.php';

echo "<h1>Update Withdrawal System Database</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; padding: 20px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
</style>";

try {
    // Check if columns exist and add them if needed
    echo "<h2>Updating affiliate_payouts table...</h2>";
    
    $columns = [
        'tax_amount' => "ALTER TABLE affiliate_payouts ADD COLUMN tax_amount DECIMAL(10,2) DEFAULT 0 AFTER amount",
        'tax_percentage' => "ALTER TABLE affiliate_payouts ADD COLUMN tax_percentage DECIMAL(5,2) DEFAULT 0 AFTER tax_amount",
        'final_amount' => "ALTER TABLE affiliate_payouts ADD COLUMN final_amount DECIMAL(10,2) DEFAULT 0 AFTER tax_percentage",
        'reject_reason' => "ALTER TABLE affiliate_payouts ADD COLUMN reject_reason TEXT NULL AFTER completed_at"
    ];
    
    foreach ($columns as $column => $sql) {
        $check = $conn->query("SHOW COLUMNS FROM affiliate_payouts LIKE '$column'");
        if ($check->num_rows == 0) {
            $conn->query($sql);
            echo "<p class='success'>✓ Added column: $column</p>";
        } else {
            echo "<p class='success'>✓ Column already exists: $column</p>";
        }
    }
    
    // Add default tax settings
    echo "<h2>Adding default tax settings...</h2>";
    
    $settings = [
        'tds_percentage' => 10,
        'min_payout_amount' => 500,
        'max_payout_amount' => 50000
    ];
    
    foreach ($settings as $key => $value) {
        $conn->query("INSERT INTO affiliate_settings (setting_key, setting_value) VALUES ('$key', '$value') ON DUPLICATE KEY UPDATE setting_key = setting_key");
        echo "<p class='success'>✓ Added setting: $key = $value</p>";
    }
    
    echo "<h2 class='success'>✅ Database updated successfully!</h2>";
    echo "<p><a href='admin/withdrawals.php' style='padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; display: inline-block;'>Go to Withdrawals Management</a></p>";
    
} catch (Exception $e) {
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
}
?>
