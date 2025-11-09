<?php
require_once 'config/config.php';
requireLogin();
$pageTitle = 'Shopping Cart - ' . getSetting('site_name');

// Handle remove from cart
if (isset($_GET['remove'])) {
    $removeId = (int)$_GET['remove'];
    if (isset($_SESSION['cart'][$removeId])) {
        unset($_SESSION['cart'][$removeId]);
    }
    header('Location: ' . SITE_URL . '/cart.php');
    exit;
}

$cartItems = getCartItems();
$subtotal = getCartTotal();
$tax = calculateTax($subtotal);
$total = $subtotal + $tax;

include 'includes/header.php';
?>

<div class="container my-4">
    <h2 class="mb-4"><i class="fas fa-shopping-cart"></i> Shopping Cart</h2>
    
    <?php if (empty($cartItems)): ?>
        <div class="alert alert-info text-center">
            <i class="fas fa-shopping-cart fa-3x mb-3"></i>
            <h4>Your cart is empty</h4>
            <p>Start shopping and add products to your cart!</p>
            <a href="<?php echo SITE_URL; ?>/products.php" class="btn btn-primary">
                <i class="fas fa-shopping-bag"></i> Browse Products
            </a>
        </div>
    <?php else: ?>
        
        <div class="row">
            <div class="col-lg-8">
                <?php foreach ($cartItems as $item): ?>
                <div class="card mb-3">
                    <div class="row g-0">
                        <div class="col-md-3">
                            <img src="<?php echo getCourseImage($item); ?>" 
                                 class="img-fluid rounded-start" alt="<?php echo htmlspecialchars($item['title']); ?>"
                                 style="height: 150px; object-fit: cover; width: 100%;"
                                 onerror="this.src='https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=400&h=300&fit=crop'">
                        </div>
                        <div class="col-md-9">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <h5 class="card-title"><?php echo htmlspecialchars($item['title']); ?></h5>
                                    <a href="?remove=<?php echo $item['id']; ?>" class="text-danger">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                                <p class="card-text"><?php echo htmlspecialchars(substr($item['description'], 0, 100)); ?>...</p>
                                <h5 class="text-primary"><?php echo formatCurrency($item['price']); ?></h5>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-4">Order Summary</h5>
                        
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal:</span>
                            <span><?php echo formatCurrency($subtotal); ?></span>
                        </div>
                        
                        <div class="d-flex justify-content-between mb-2">
                            <span>Tax (<?php echo getSetting('tax_percentage'); ?>%):</span>
                            <span><?php echo formatCurrency($tax); ?></span>
                        </div>
                        
                        <hr>
                        
                        <div class="d-flex justify-content-between mb-4">
                            <strong>Total:</strong>
                            <strong class="text-primary"><?php echo formatCurrency($total); ?></strong>
                        </div>
                        
                        <a href="<?php echo SITE_URL; ?>/checkout.php" class="btn btn-primary btn-lg w-100 mb-2">
                            <i class="fas fa-lock"></i> Proceed to Checkout
                        </a>
                        
                        <a href="<?php echo SITE_URL; ?>/products.php" class="btn btn-outline-primary w-100">
                            <i class="fas fa-arrow-left"></i> Continue Shopping
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
