<?php
require_once 'config/config.php';
require_once 'includes/affiliate-functions.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$userId = (int)$_GET['user_id'];
$currentUserId = $_SESSION['user_id'];

// Get current user's affiliate ID
$affiliate = getAffiliateByUserId($currentUserId);

if (!$affiliate) {
    echo json_encode(['success' => false, 'message' => 'Not an affiliate']);
    exit;
}

// Verify that the requested user is actually referred by this affiliate
$stmt = $conn->prepare("SELECT id FROM users WHERE id = ? AND referred_by = ?");
$stmt->bind_param("ii", $userId, $affiliate['id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid referral']);
    exit;
}

// Get all purchases by this referred user
$stmt = $conn->prepare("
    SELECT 
        o.id,
        o.order_number,
        o.final_amount,
        o.payment_status,
        o.created_at,
        GROUP_CONCAT(p.title SEPARATOR '|') as products,
        COALESCE(SUM(ac.commission_amount), 0) as commission
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    LEFT JOIN products p ON oi.product_id = p.id
    LEFT JOIN affiliate_commissions ac ON o.id = ac.order_id AND ac.affiliate_id = ?
    WHERE o.user_id = ? AND o.payment_status = 'completed'
    GROUP BY o.id
    ORDER BY o.created_at DESC
");
$stmt->bind_param("ii", $affiliate['id'], $userId);
$stmt->execute();
$orders = $stmt->get_result();

$purchases = [];
$totalCommission = 0;

while ($order = $orders->fetch_assoc()) {
    $products = $order['products'] ? explode('|', $order['products']) : [];
    
    $purchases[] = [
        'order_number' => $order['order_number'],
        'order_date' => date('M d, Y', strtotime($order['created_at'])),
        'products' => $products,
        'amount' => number_format($order['final_amount'], 2),
        'commission' => number_format($order['commission'], 2),
        'status' => $order['payment_status']
    ];
    
    $totalCommission += $order['commission'];
}

echo json_encode([
    'success' => true,
    'purchases' => $purchases,
    'total_commission' => number_format($totalCommission, 2)
]);
?>
