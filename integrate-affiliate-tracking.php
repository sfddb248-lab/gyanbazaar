<?php
/**
 * Affiliate Tracking Integration Helper
 * 
 * This file contains code snippets to integrate affiliate tracking
 * into your existing checkout and landing pages.
 */

echo "<h2>Affiliate System Integration Guide</h2>";
echo "<p>Copy and paste these code snippets into your files:</p>";

echo "<hr>";
echo "<h3>1. Add to index.php (or any landing page)</h3>";
echo "<p>Add this code at the top of your index.php file, after including config.php:</p>";
echo "<pre style='background: #f4f4f4; padding: 15px; border-radius: 5px;'>";
echo htmlspecialchars("<?php
// Track affiliate referral clicks
if (isset(\$_GET['ref'])) {
    \$referralCode = \$_GET['ref'];
    
    // Set cookie for tracking (30 days default)
    \$cookieDuration = (int)getAffiliateSetting('cookie_duration_days', 30);
    setcookie('affiliate_ref', \$referralCode, time() + (\$cookieDuration * 86400), '/');
    
    // Track click
    trackAffiliateClick(\$referralCode);
    
    // Track referral if user is logged in
    if (isset(\$_SESSION['user_id'])) {
        trackAffiliateReferral(\$referralCode, \$_SESSION['user_id']);
    }
    
    // Redirect to clean URL
    header('Location: ' . strtok(\$_SERVER['REQUEST_URI'], '?'));
    exit;
}
?>");
echo "</pre>";

echo "<hr>";
echo "<h3>2. Add to signup.php</h3>";
echo "<p>Add this code after successful user registration:</p>";
echo "<pre style='background: #f4f4f4; padding: 15px; border-radius: 5px;'>";
echo htmlspecialchars("<?php
// Track affiliate referral on signup
if (isset(\$_COOKIE['affiliate_ref'])) {
    \$referralCode = \$_COOKIE['affiliate_ref'];
    trackAffiliateReferral(\$referralCode, \$newUserId);
}
?>");
echo "</pre>";

echo "<hr>";
echo "<h3>3. Add to checkout.php</h3>";
echo "<p>Add this code after successful order creation:</p>";
echo "<pre style='background: #f4f4f4; padding: 15px; border-radius: 5px;'>";
echo htmlspecialchars("<?php
// Process affiliate commission
if (isset(\$_COOKIE['affiliate_ref'])) {
    \$referralCode = \$_COOKIE['affiliate_ref'];
    \$affiliate = getAffiliateByCode(\$referralCode);
    
    if (\$affiliate) {
        // Update order with affiliate info
        \$stmt = \$conn->prepare(\"UPDATE orders SET affiliate_id = ?, referral_code = ? WHERE id = ?\");
        \$stmt->bind_param(\"isi\", \$affiliate['id'], \$referralCode, \$orderId);
        \$stmt->execute();
        
        // Create commission
        createAffiliateCommission(\$orderId, \$affiliate['id'], \$finalAmount);
        
        // Mark referral as converted
        \$referralStmt = \$conn->prepare(\"SELECT id FROM affiliate_referrals WHERE affiliate_id = ? AND referred_user_id = ? ORDER BY created_at DESC LIMIT 1\");
        \$referralStmt->bind_param(\"ii\", \$affiliate['id'], \$userId);
        \$referralStmt->execute();
        \$referralResult = \$referralStmt->get_result();
        if (\$referral = \$referralResult->fetch_assoc()) {
            markReferralConverted(\$referral['id']);
        }
    }
}
?>");
echo "</pre>";

echo "<hr>";
echo "<h3>4. Add to includes/header.php</h3>";
echo "<p>Add this menu item to your navigation:</p>";
echo "<pre style='background: #f4f4f4; padding: 15px; border-radius: 5px;'>";
echo htmlspecialchars("<?php if (isset(\$_SESSION['user_id'])): ?>
    <li class=\"nav-item\">
        <a class=\"nav-link\" href=\"<?php echo SITE_URL; ?>/affiliate-dashboard.php\">
            <i class=\"fas fa-users\"></i> Affiliate Program
        </a>
    </li>
<?php endif; ?>");
echo "</pre>";

echo "<hr>";
echo "<h3>5. Add to admin/includes/admin-header.php</h3>";
echo "<p>Add these menu items to admin navigation:</p>";
echo "<pre style='background: #f4f4f4; padding: 15px; border-radius: 5px;'>";
echo htmlspecialchars("<li class=\"nav-item dropdown\">
    <a class=\"nav-link dropdown-toggle\" href=\"#\" data-bs-toggle=\"dropdown\">
        <i class=\"fas fa-handshake\"></i> Affiliates
    </a>
    <ul class=\"dropdown-menu\">
        <li><a class=\"dropdown-item\" href=\"affiliates.php\">All Affiliates</a></li>
        <li><a class=\"dropdown-item\" href=\"affiliate-commissions.php\">Commissions</a></li>
        <li><a class=\"dropdown-item\" href=\"affiliate-payouts.php\">Payouts</a></li>
        <li><a class=\"dropdown-item\" href=\"affiliate-materials.php\">Materials</a></li>
        <li><hr class=\"dropdown-divider\"></li>
        <li><a class=\"dropdown-item\" href=\"affiliate-settings.php\">Settings</a></li>
    </ul>
</li>");
echo "</pre>";

echo "<hr>";
echo "<h3>6. Test the System</h3>";
echo "<ol>";
echo "<li>Run the setup script: <a href='setup-affiliate-system.php'>setup-affiliate-system.php</a></li>";
echo "<li>Configure settings: <a href='admin/affiliate-settings.php'>Admin → Affiliate Settings</a></li>";
echo "<li>Register as affiliate: <a href='affiliate-dashboard.php'>Affiliate Dashboard</a></li>";
echo "<li>Test referral link: <code>http://localhost/DigitalKhazana/?ref=YOUR_CODE</code></li>";
echo "<li>Make a test purchase</li>";
echo "<li>Check commission in dashboard</li>";
echo "</ol>";

echo "<hr>";
echo "<h3>Quick Start Checklist</h3>";
echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px;'>";
echo "<input type='checkbox'> Run setup-affiliate-system.php<br>";
echo "<input type='checkbox'> Configure affiliate settings<br>";
echo "<input type='checkbox'> Add tracking code to index.php<br>";
echo "<input type='checkbox'> Add tracking code to signup.php<br>";
echo "<input type='checkbox'> Add commission code to checkout.php<br>";
echo "<input type='checkbox'> Add menu items to navigation<br>";
echo "<input type='checkbox'> Upload promotional materials<br>";
echo "<input type='checkbox'> Test with a referral link<br>";
echo "</div>";

echo "<style>
body { font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px; }
pre { background: #f4f4f4; padding: 15px; border-radius: 5px; overflow-x: auto; white-space: pre-wrap; }
a { color: #007bff; text-decoration: none; }
a:hover { text-decoration: underline; }
</style>";
?>
