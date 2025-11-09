<?php
require_once 'config/config.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header('Location: ' . SITE_URL . '/orders.php');
    exit;
}

$orderId = (int)$_POST['order_id'];
$transactionId = clean($_POST['transaction_id']);
$userId = $_SESSION['user_id'];

// Verify order belongs to user
$stmt = $conn->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $orderId, $userId);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    $_SESSION['error'] = 'Invalid order';
    header('Location: ' . SITE_URL . '/orders.php');
    exit;
}

// Update order with transaction ID but keep status as pending (requires admin approval)
$stmt = $conn->prepare("UPDATE orders SET transaction_id = ?, payment_status = 'pending' WHERE id = ?");
$stmt->bind_param("si", $transactionId, $orderId);

if ($stmt->execute()) {
    // Send notification email to customer
    sendEmail($_SESSION['user_email'], 'Payment Submitted - ' . $order['order_number'], 
              "Thank you for submitting your payment details.\n\nTransaction ID: $transactionId\nOrder Number: " . $order['order_number'] . "\n\nYour payment is being verified by our team. You will receive a confirmation email once approved.\n\nThis usually takes a few minutes to a few hours.");
    
    // Send notification to admin (optional)
    $adminEmail = getSetting('admin_email', 'admin@gyanbazaar.com');
    sendEmail($adminEmail, 'New Payment to Verify - Order ' . $order['order_number'], 
              "A new UPI payment needs verification.\n\nOrder: " . $order['order_number'] . "\nAmount: " . formatCurrency($order['final_amount']) . "\nTransaction ID: $transactionId\n\nPlease verify this payment in the admin panel.");
    
    $_SESSION['success'] = 'Transaction ID submitted successfully! Your payment is being verified. You will be notified once approved.';
    header('Location: ' . SITE_URL . '/orders.php?order=' . $orderId);
} else {
    $_SESSION['error'] = 'Failed to submit transaction ID. Please try again or contact support.';
    header('Location: ' . SITE_URL . '/upi-payment.php?order=' . $orderId);
}
exit;
?>
