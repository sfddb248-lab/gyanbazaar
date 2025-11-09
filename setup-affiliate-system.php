<?php
require_once 'config/config.php';

echo "<h2>Affiliate Marketing System Setup</h2>";
echo "<p>Setting up affiliate marketing system...</p>";

// Read and execute SQL file
$sqlFile = file_get_contents('affiliate-system-database.sql');
$queries = explode(';', $sqlFile);

$success = 0;
$errors = 0;

foreach ($queries as $query) {
    $query = trim($query);
    if (empty($query)) continue;
    
    if ($conn->query($query)) {
        $success++;
    } else {
        $errors++;
        echo "<p style='color: red;'>Error: " . $conn->error . "</p>";
    }
}

echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
echo "<h3 style='color: #155724;'>✓ Setup Complete!</h3>";
echo "<p><strong>Queries executed:</strong> $success successful, $errors errors</p>";
echo "</div>";

echo "<h3>Affiliate System Features:</h3>";
echo "<ul>";
echo "<li>✓ Unique referral links for each affiliate</li>";
echo "<li>✓ Commission tracking (percentage or flat rate)</li>";
echo "<li>✓ Affiliate dashboard with earnings and stats</li>";
echo "<li>✓ Payout management system</li>";
echo "<li>✓ Multi-level affiliate program (MLM)</li>";
echo "<li>✓ Promotional material downloads</li>";
echo "<li>✓ Click and conversion tracking</li>";
echo "<li>✓ Admin panel for managing affiliates</li>";
echo "</ul>";

echo "<h3>Next Steps:</h3>";
echo "<ol>";
echo "<li>Go to <a href='admin/affiliate-settings.php'>Admin → Affiliate Settings</a> to configure commission rates</li>";
echo "<li>Upload promotional materials at <a href='admin/affiliate-materials.php'>Admin → Promotional Materials</a></li>";
echo "<li>Users can register as affiliates at <a href='affiliate-dashboard.php'>Affiliate Dashboard</a></li>";
echo "<li>Manage affiliates at <a href='admin/affiliates.php'>Admin → Affiliates</a></li>";
echo "<li>Process payouts at <a href='admin/affiliate-payouts.php'>Admin → Payouts</a></li>";
echo "</ol>";

echo "<h3>File Structure:</h3>";
echo "<pre>";
echo "affiliate-dashboard.php          - Affiliate dashboard for users
affiliate-payout.php             - Payout request page
affiliate-materials.php          - Promotional materials page
includes/affiliate-functions.php - Core affiliate functions
admin/affiliates.php             - Manage affiliates
admin/affiliate-commissions.php  - View and approve commissions
admin/affiliate-payouts.php      - Process payout requests
admin/affiliate-materials.php    - Manage promotional materials
admin/affiliate-settings.php     - Configure affiliate settings
</pre>";

echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
echo "<h4>⚠️ Important Notes:</h4>";
echo "<ul>";
echo "<li>Update your checkout.php to track affiliate referrals</li>";
echo "<li>Add affiliate menu items to your navigation</li>";
echo "<li>Configure MLM settings if you want multi-level commissions</li>";
echo "<li>Set minimum payout amount in settings</li>";
echo "</ul>";
echo "</div>";

echo "<p><a href='index.php' class='btn btn-primary'>Go to Homepage</a> ";
echo "<a href='admin/affiliate-settings.php' class='btn btn-success'>Configure Settings</a></p>";

echo "<style>
body { font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px; }
.btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin: 5px; }
.btn-success { background: #28a745; }
pre { background: #f4f4f4; padding: 15px; border-radius: 5px; overflow-x: auto; }
</style>";
?>
