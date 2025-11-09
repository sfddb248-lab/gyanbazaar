<?php
require_once 'config/config.php';
$pageTitle = 'FAQ - ' . getSetting('site_name');

include 'includes/header.php';
?>

<div class="container my-5">
    <h2 class="text-center mb-5"><i class="fas fa-question-circle"></i> Frequently Asked Questions</h2>
    
    <div class="row">
        <div class="col-lg-8 mx-auto">
            
            <!-- General Questions -->
            <h4 class="mb-4 text-primary">General Questions</h4>
            
            <div class="accordion mb-5" id="generalAccordion">
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button" type="button" data-mdb-toggle="collapse" data-mdb-target="#general1">
                            What is <?php echo getSetting('site_name'); ?>?
                        </button>
                    </h2>
                    <div id="general1" class="accordion-collapse collapse show" data-mdb-parent="#generalAccordion">
                        <div class="accordion-body">
                            <?php echo getSetting('site_name'); ?> is a digital marketplace where you can buy and sell premium digital products including eBooks, templates, graphics, software, and online courses.
                        </div>
                    </div>
                </div>
                
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-mdb-toggle="collapse" data-mdb-target="#general2">
                            How do I create an account?
                        </button>
                    </h2>
                    <div id="general2" class="accordion-collapse collapse" data-mdb-parent="#generalAccordion">
                        <div class="accordion-body">
                            Click on the "Sign Up" button in the navigation menu, fill in your details (name, email, password), and submit the form. You'll be able to start shopping immediately after registration.
                        </div>
                    </div>
                </div>
                
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-mdb-toggle="collapse" data-mdb-target="#general3">
                            Is my personal information secure?
                        </button>
                    </h2>
                    <div id="general3" class="accordion-collapse collapse" data-mdb-parent="#generalAccordion">
                        <div class="accordion-body">
                            Yes! We use industry-standard encryption and security measures to protect your personal information. Your passwords are encrypted, and we never share your data with third parties.
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Payment Questions -->
            <h4 class="mb-4 text-primary">Payment & Pricing</h4>
            
            <div class="accordion mb-5" id="paymentAccordion">
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-mdb-toggle="collapse" data-mdb-target="#payment1">
                            What payment methods do you accept?
                        </button>
                    </h2>
                    <div id="payment1" class="accordion-collapse collapse" data-mdb-parent="#paymentAccordion">
                        <div class="accordion-body">
                            We accept all major payment methods including credit/debit cards via Razorpay, Stripe, and PayPal. All transactions are secure and encrypted.
                        </div>
                    </div>
                </div>
                
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-mdb-toggle="collapse" data-mdb-target="#payment2">
                            Do you offer refunds?
                        </button>
                    </h2>
                    <div id="payment2" class="accordion-collapse collapse" data-mdb-parent="#paymentAccordion">
                        <div class="accordion-body">
                            Due to the nature of digital products, we generally don't offer refunds once a product has been downloaded. However, if you experience technical issues or receive a defective product, please contact our support team within 7 days of purchase.
                        </div>
                    </div>
                </div>
                
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-mdb-toggle="collapse" data-mdb-target="#payment3">
                            Can I use discount coupons?
                        </button>
                    </h2>
                    <div id="payment3" class="accordion-collapse collapse" data-mdb-parent="#paymentAccordion">
                        <div class="accordion-body">
                            Yes! You can apply discount coupons during checkout. Enter your coupon code in the designated field and click "Apply" to see your discount reflected in the order total.
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Download Questions -->
            <h4 class="mb-4 text-primary">Downloads & Access</h4>
            
            <div class="accordion mb-5" id="downloadAccordion">
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-mdb-toggle="collapse" data-mdb-target="#download1">
                            How do I download my purchased products?
                        </button>
                    </h2>
                    <div id="download1" class="accordion-collapse collapse" data-mdb-parent="#downloadAccordion">
                        <div class="accordion-body">
                            After completing your purchase, go to "My Orders" in your profile menu. You'll see all your purchased products with download buttons. Click the download button to get your files instantly.
                        </div>
                    </div>
                </div>
                
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-mdb-toggle="collapse" data-mdb-target="#download2">
                            Is there a download limit?
                        </button>
                    </h2>
                    <div id="download2" class="accordion-collapse collapse" data-mdb-parent="#downloadAccordion">
                        <div class="accordion-body">
                            Yes, each product can be downloaded up to <?php echo MAX_DOWNLOAD_COUNT; ?> times. Your download links are valid for <?php echo DOWNLOAD_EXPIRY_DAYS; ?> days from the date of purchase. Make sure to save your files securely.
                        </div>
                    </div>
                </div>
                
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-mdb-toggle="collapse" data-mdb-target="#download3">
                            What if my download link expires?
                        </button>
                    </h2>
                    <div id="download3" class="accordion-collapse collapse" data-mdb-parent="#downloadAccordion">
                        <div class="accordion-body">
                            If your download link has expired or you've reached the download limit, please contact our support team with your order number. We'll review your case and may provide extended access.
                        </div>
                    </div>
                </div>
                
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-mdb-toggle="collapse" data-mdb-target="#download4">
                            Can I re-download products I've purchased?
                        </button>
                    </h2>
                    <div id="download4" class="accordion-collapse collapse" data-mdb-parent="#downloadAccordion">
                        <div class="accordion-body">
                            Yes! As long as your download link hasn't expired and you haven't exceeded the download limit, you can re-download your products anytime from the "My Orders" page.
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Support Questions -->
            <h4 class="mb-4 text-primary">Support</h4>
            
            <div class="accordion mb-5" id="supportAccordion">
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-mdb-toggle="collapse" data-mdb-target="#support1">
                            How can I contact customer support?
                        </button>
                    </h2>
                    <div id="support1" class="accordion-collapse collapse" data-mdb-parent="#supportAccordion">
                        <div class="accordion-body">
                            You can reach our support team through the <a href="<?php echo SITE_URL; ?>/contact.php">Contact Us</a> page. We typically respond within 24 hours during business days.
                        </div>
                    </div>
                </div>
                
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-mdb-toggle="collapse" data-mdb-target="#support2">
                            What are your support hours?
                        </button>
                    </h2>
                    <div id="support2" class="accordion-collapse collapse" data-mdb-parent="#supportAccordion">
                        <div class="accordion-body">
                            Our support team is available Monday through Friday, 9:00 AM to 6:00 PM (EST). We respond to urgent issues as quickly as possible, even outside business hours.
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="alert alert-info text-center">
                <i class="fas fa-info-circle"></i> Still have questions? 
                <a href="<?php echo SITE_URL; ?>/contact.php" class="alert-link">Contact our support team</a>
            </div>
            
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
