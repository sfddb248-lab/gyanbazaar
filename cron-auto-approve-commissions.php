<?php
/**
 * Auto-Approve Commissions Cron Job
 * 
 * This script automatically approves commissions that are older than the configured days.
 * 
 * Run this script:
 * 1. Manually: php cron-auto-approve-commissions.php
 * 2. Via browser: http://localhost/GyanBazaar/cron-auto-approve-commissions.php
 * 3. As cron job: Add to crontab to run daily
 */

require_once 'config/config.php';

echo "<h1>🤖 Auto-Approve Commissions</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; padding: 20px; }
    .success { color: green; font-weight: bold; }
    .info { color: blue; }
    .warning { color: orange; }
</style>";

echo "<p>Running at: " . date('Y-m-d H:i:s') . "</p>";

// Check if auto-approval is enabled
$autoApprove = (int)getAffiliateSetting('auto_approve_commissions', 0);
$autoApproveDays = (int)getAffiliateSetting('auto_approve_days', 7);

echo "<h2>Settings:</h2>";
echo "<p>Auto-Approval: <strong>" . ($autoApprove ? 'ENABLED' : 'DISABLED') . "</strong></p>";
echo "<p>Auto-Approve After: <strong>$autoApproveDays days</strong></p>";

if (!$autoApprove) {
    echo "<p class='warning'>⚠ Auto-approval is disabled. Enable it in admin panel.</p>";
    exit;
}

// Calculate cutoff date
$cutoffDate = date('Y-m-d H:i:s', strtotime("-$autoApproveDays days"));

echo "<h2>Processing:</h2>";
echo "<p>Approving commissions older than: <strong>$cutoffDate</strong></p>";

// Get commissions to approve
$commissionsToApprove = $conn->query("
    SELECT 
        ac.*,
        o.order_number,
        a.referral_code,
        u.name as affiliate_name
    FROM affiliate_commissions ac
    JOIN orders o ON ac.order_id = o.id
    JOIN affiliates a ON ac.affiliate_id = a.id
    JOIN users u ON a.user_id = u.id
    WHERE ac.status = 'pending'
    AND ac.created_at <= '$cutoffDate'
    ORDER BY ac.created_at ASC
")->fetch_all(MYSQLI_ASSOC);

if (count($commissionsToApprove) > 0) {
    echo "<p class='info'>Found " . count($commissionsToApprove) . " commission(s) to approve:</p>";
    
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th>ID</th><th>Order #</th><th>Affiliate</th><th>Amount</th><th>Age (days)</th><th>Status</th>";
    echo "</tr>";
    
    $totalApproved = 0;
    $totalAmount = 0;
    
    foreach ($commissionsToApprove as $comm) {
        $age = floor((time() - strtotime($comm['created_at'])) / 86400);
        
        // Approve the commission
        $conn->query("UPDATE affiliate_commissions SET status = 'approved', approved_at = NOW() WHERE id = {$comm['id']}");
        
        echo "<tr>";
        echo "<td>{$comm['id']}</td>";
        echo "<td>{$comm['order_number']}</td>";
        echo "<td>{$comm['affiliate_name']} ({$comm['referral_code']})</td>";
        echo "<td>₹" . number_format($comm['commission_amount'], 2) . "</td>";
        echo "<td>$age days</td>";
        echo "<td class='success'>✓ Approved</td>";
        echo "</tr>";
        
        $totalApproved++;
        $totalAmount += $comm['commission_amount'];
    }
    
    echo "<tr style='background: #d4edda; font-weight: bold;'>";
    echo "<td colspan='3'>TOTAL</td>";
    echo "<td>₹" . number_format($totalAmount, 2) . "</td>";
    echo "<td>$totalApproved</td>";
    echo "<td class='success'>APPROVED</td>";
    echo "</tr>";
    echo "</table>";
    
    echo "<h2 class='success'>✅ Successfully approved $totalApproved commission(s)!</h2>";
    echo "<p>Total amount: ₹" . number_format($totalAmount, 2) . "</p>";
    
} else {
    echo "<p class='success'>✓ No commissions to approve at this time.</p>";
    echo "<p>All pending commissions are newer than $autoApproveDays days.</p>";
}

echo "<hr>";
echo "<h2>Summary:</h2>";

// Get current statistics
$stats = [
    'Pending' => $conn->query("SELECT COUNT(*) as count, COALESCE(SUM(commission_amount), 0) as amount FROM affiliate_commissions WHERE status = 'pending'")->fetch_assoc(),
    'Approved' => $conn->query("SELECT COUNT(*) as count, COALESCE(SUM(commission_amount), 0) as amount FROM affiliate_commissions WHERE status = 'approved'")->fetch_assoc(),
    'Paid' => $conn->query("SELECT COUNT(*) as count, COALESCE(SUM(commission_amount), 0) as amount FROM affiliate_commissions WHERE status = 'paid'")->fetch_assoc()
];

echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
echo "<tr style='background: #f0f0f0;'><th>Status</th><th>Count</th><th>Amount</th></tr>";
foreach ($stats as $status => $data) {
    echo "<tr>";
    echo "<td><strong>$status</strong></td>";
    echo "<td>{$data['count']}</td>";
    echo "<td>₹" . number_format($data['amount'], 2) . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<hr>";
echo "<p><a href='admin/approve-commissions.php' style='padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px;'>Go to Commission Approval</a></p>";

?>
