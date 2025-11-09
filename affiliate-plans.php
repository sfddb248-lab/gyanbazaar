<?php
require_once 'config/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];

// Get user's affiliate account
$stmt = $conn->prepare("SELECT * FROM affiliates WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$affiliate = $stmt->get_result()->fetch_assoc();

// Get current subscription if exists
$currentSubscription = null;
if ($affiliate && $affiliate['subscription_id']) {
    $stmt = $conn->prepare("SELECT s.*, p.plan_name, p.badge_color, p.badge_icon 
                           FROM affiliate_subscriptions s 
                           JOIN affiliate_subscription_plans p ON s.plan_id = p.id 
                           WHERE s.affiliate_id = ? AND s.status = 'active' 
                           ORDER BY s.end_date DESC LIMIT 1");
    $stmt->bind_param("i", $affiliate['id']);
    $stmt->execute();
    $currentSubscription = $stmt->get_result()->fetch_assoc();
}

// Get all available plans
$plans = $conn->query("SELECT * FROM affiliate_subscription_plans WHERE status = 'active' ORDER BY display_order")->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Affiliate Subscription Plans - ' . getSetting('site_name');
include 'includes/header.php';
?>

<div class="container my-5">
    <div class="text-center mb-5">
        <h1 class="display-4 fw-bold mb-3">
            <i class="fas fa-rocket"></i> Affiliate Subscription Plans
        </h1>
        <p class="lead text-muted">Choose the perfect plan to supercharge your affiliate earnings</p>
    </div>
    
    <?php if ($currentSubscription): ?>
        <div class="alert alert-success shadow-sm mb-5">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h5 class="mb-2">
                        <i class="fas <?php echo $currentSubscription['badge_icon']; ?>" style="color: <?php echo $currentSubscription['badge_color']; ?>;"></i>
                        Current Plan: <strong><?php echo $currentSubscription['plan_name']; ?></strong>
                    </h5>
                    <p class="mb-0">
                        <i class="fas fa-calendar-check"></i> Active until: 
                        <strong><?php echo date('F d, Y', strtotime($currentSubscription['end_date'])); ?></strong>
                    </p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="affiliate-dashboard.php" class="btn btn-outline-success">
                        <i class="fas fa-tachometer-alt"></i> Go to Dashboard
                    </a>
                </div>
            </div>
        </div>
    <?php elseif (!$affiliate): ?>
        <div class="alert alert-info shadow-sm mb-5">
            <h5><i class="fas fa-info-circle"></i> Not an Affiliate Yet?</h5>
            <p class="mb-0">Register as an affiliate first, then choose your subscription plan.</p>
            <a href="affiliate-dashboard.php" class="btn btn-primary mt-3">
                <i class="fas fa-user-plus"></i> Become an Affiliate
            </a>
        </div>
    <?php endif; ?>
    
    <!-- Pricing Cards -->
    <div class="row g-4 mb-5">
        <?php foreach ($plans as $plan): 
            $features = json_decode($plan['features'], true);
            $isCurrentPlan = $currentSubscription && $currentSubscription['plan_id'] == $plan['id'];
        ?>
            <div class="col-lg-4 col-md-6">
                <div class="card h-100 shadow-lg border-0 <?php echo $isCurrentPlan ? 'border-success' : ''; ?>" 
                     style="border-top: 5px solid <?php echo $plan['badge_color']; ?> !important;">
                    <?php if ($plan['plan_slug'] === 'gold'): ?>
                        <div class="position-absolute top-0 end-0 m-3">
                            <span class="badge bg-warning text-dark">
                                <i class="fas fa-star"></i> POPULAR
                            </span>
                        </div>
                    <?php endif; ?>
                    
                    <div class="card-body p-4">
                        <div class="text-center mb-4">
                            <div class="mb-3">
                                <i class="fas <?php echo $plan['badge_icon']; ?> fa-4x" 
                                   style="color: <?php echo $plan['badge_color']; ?>;"></i>
                            </div>
                            <h3 class="fw-bold" style="color: <?php echo $plan['badge_color']; ?>;">
                                <?php echo $plan['plan_name']; ?>
                            </h3>
                            <div class="my-3">
                                <h2 class="display-4 fw-bold mb-0">
                                    ₹<?php echo number_format($plan['price'], 0); ?>
                                </h2>
                                <small class="text-muted">/month</small>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Commission Rate:</span>
                                <strong class="text-success"><?php echo $plan['commission_rate']; ?>%</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">MLM Levels:</span>
                                <strong><?php echo $plan['mlm_levels']; ?> Levels</strong>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Max Referrals:</span>
                                <strong><?php echo $plan['max_referrals'] > 0 ? number_format($plan['max_referrals']) : 'Unlimited'; ?></strong>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <ul class="list-unstyled mb-4">
                            <?php foreach ($features as $feature): ?>
                                <li class="mb-2">
                                    <i class="fas fa-check-circle text-success"></i>
                                    <?php echo $feature; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        
                        <?php if ($isCurrentPlan): ?>
                            <button class="btn btn-success w-100 disabled">
                                <i class="fas fa-check"></i> Current Plan
                            </button>
                        <?php elseif ($affiliate): ?>
                            <form action="checkout.php" method="GET">
                                <input type="hidden" name="plan_id" value="<?php echo $plan['id']; ?>">
                                <input type="hidden" name="type" value="subscription">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-shopping-cart"></i> Subscribe Now
                                </button>
                            </form>
                        <?php else: ?>
                            <a href="affiliate-dashboard.php" class="btn btn-outline-primary w-100">
                                <i class="fas fa-user-plus"></i> Register First
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Comparison Table -->
    <div class="card shadow-lg border-0 mb-5">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0"><i class="fas fa-table"></i> Plan Comparison</h4>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Feature</th>
                            <?php foreach ($plans as $plan): ?>
                                <th class="text-center" style="color: <?php echo $plan['badge_color']; ?>;">
                                    <i class="fas <?php echo $plan['badge_icon']; ?>"></i>
                                    <?php echo $plan['plan_name']; ?>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>Monthly Price</strong></td>
                            <?php foreach ($plans as $plan): ?>
                                <td class="text-center">
                                    <strong>₹<?php echo number_format($plan['price'], 0); ?></strong>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                        <tr>
                            <td><strong>Commission Rate</strong></td>
                            <?php foreach ($plans as $plan): ?>
                                <td class="text-center">
                                    <span class="badge bg-success"><?php echo $plan['commission_rate']; ?>%</span>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                        <tr>
                            <td><strong>MLM Levels</strong></td>
                            <?php foreach ($plans as $plan): ?>
                                <td class="text-center"><?php echo $plan['mlm_levels']; ?> Levels</td>
                            <?php endforeach; ?>
                        </tr>
                        <tr>
                            <td><strong>Max Referrals</strong></td>
                            <?php foreach ($plans as $plan): ?>
                                <td class="text-center">
                                    <?php echo $plan['max_referrals'] > 0 ? number_format($plan['max_referrals']) : '<span class="badge bg-warning text-dark">Unlimited</span>'; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- FAQ Section -->
    <div class="card shadow-lg border-0">
        <div class="card-header bg-info text-white">
            <h4 class="mb-0"><i class="fas fa-question-circle"></i> Frequently Asked Questions</h4>
        </div>
        <div class="card-body">
            <div class="accordion" id="faqAccordion">
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                            How do subscription plans work?
                        </button>
                    </h2>
                    <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            Choose a plan, make payment, and your affiliate account will be upgraded immediately with the plan's benefits including higher commission rates and more MLM levels.
                        </div>
                    </div>
                </div>
                
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                            Can I upgrade my plan later?
                        </button>
                    </h2>
                    <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            Yes! You can upgrade to a higher plan anytime. The new benefits will apply immediately.
                        </div>
                    </div>
                </div>
                
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                            What happens when my subscription expires?
                        </button>
                    </h2>
                    <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            Your account will revert to the free tier with basic commission rates. You can renew anytime to restore your benefits.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.card {
    transition: transform 0.3s, box-shadow 0.3s;
}

.card:hover {
    transform: translateY(-10px);
    box-shadow: 0 15px 40px rgba(0,0,0,0.2) !important;
}
</style>

<?php include 'includes/footer.php'; ?>
