<?php
require_once 'config/config.php';
$pageTitle = 'Terms & Conditions - ' . getSetting('site_name');

include 'includes/header.php';
?>

<div class="container my-5">
    <div class="row">
        <div class="col-lg-10 mx-auto">
            <h2 class="text-center mb-5"><i class="fas fa-file-contract"></i> Terms & Conditions</h2>
            
            <div class="card shadow">
                <div class="card-body p-5">
                    <p class="text-muted mb-4"><strong>Last Updated:</strong> <?php echo date('F d, Y'); ?></p>
                    
                    <h4 class="mb-3">1. Acceptance of Terms</h4>
                    <p>By accessing and using <?php echo getSetting('site_name'); ?>, you accept and agree to be bound by the terms and provision of this agreement. If you do not agree to these terms, please do not use our service.</p>
                    
                    <h4 class="mb-3 mt-4">2. Use License</h4>
                    <p>Permission is granted to temporarily download one copy of the materials (digital products) on <?php echo getSetting('site_name'); ?> for personal, non-commercial transitory viewing only. This is the grant of a license, not a transfer of title, and under this license you may not:</p>
                    <ul>
                        <li>Modify or copy the materials</li>
                        <li>Use the materials for any commercial purpose or for any public display</li>
                        <li>Attempt to reverse engineer any software contained on <?php echo getSetting('site_name'); ?></li>
                        <li>Remove any copyright or other proprietary notations from the materials</li>
                        <li>Transfer the materials to another person or "mirror" the materials on any other server</li>
                    </ul>
                    
                    <h4 class="mb-3 mt-4">3. Account Registration</h4>
                    <p>To purchase products, you must create an account. You agree to:</p>
                    <ul>
                        <li>Provide accurate, current, and complete information</li>
                        <li>Maintain and update your information to keep it accurate</li>
                        <li>Maintain the security of your password</li>
                        <li>Accept all responsibility for activity that occurs under your account</li>
                        <li>Notify us immediately of any unauthorized use of your account</li>
                    </ul>
                    
                    <h4 class="mb-3 mt-4">4. Digital Products</h4>
                    <p>All products sold on <?php echo getSetting('site_name'); ?> are digital goods. Upon purchase:</p>
                    <ul>
                        <li>You receive a non-exclusive, non-transferable license to use the product</li>
                        <li>Products are delivered electronically via download</li>
                        <li>Download links are valid for <?php echo DOWNLOAD_EXPIRY_DAYS; ?> days</li>
                        <li>Each product can be downloaded up to <?php echo MAX_DOWNLOAD_COUNT; ?> times</li>
                        <li>You are responsible for backing up your downloaded files</li>
                    </ul>
                    
                    <h4 class="mb-3 mt-4">5. Pricing and Payment</h4>
                    <p>All prices are listed in <?php echo getSetting('currency'); ?> and include applicable taxes. We reserve the right to change prices at any time. Payment must be made in full before accessing digital products. We accept payment via:</p>
                    <ul>
                        <li>Credit/Debit Cards (via Razorpay/Stripe)</li>
                        <li>PayPal</li>
                        <li>Other payment methods as displayed at checkout</li>
                    </ul>
                    
                    <h4 class="mb-3 mt-4">6. Refund Policy</h4>
                    <p>Due to the nature of digital products, all sales are final. Refunds are only provided in the following circumstances:</p>
                    <ul>
                        <li>The product is defective or not as described</li>
                        <li>Technical issues prevent you from downloading the product</li>
                        <li>Duplicate purchases made in error</li>
                    </ul>
                    <p>Refund requests must be submitted within 7 days of purchase with a valid reason.</p>
                    
                    <h4 class="mb-3 mt-4">7. Intellectual Property</h4>
                    <p>All content on <?php echo getSetting('site_name'); ?>, including but not limited to text, graphics, logos, images, and software, is the property of <?php echo getSetting('site_name'); ?> or its content suppliers and is protected by copyright laws.</p>
                    
                    <h4 class="mb-3 mt-4">8. Prohibited Activities</h4>
                    <p>You agree not to:</p>
                    <ul>
                        <li>Share your account credentials with others</li>
                        <li>Redistribute or resell purchased products</li>
                        <li>Use products in violation of copyright laws</li>
                        <li>Attempt to hack, disrupt, or damage the website</li>
                        <li>Upload malicious code or viruses</li>
                        <li>Engage in fraudulent activities</li>
                    </ul>
                    
                    <h4 class="mb-3 mt-4">9. Disclaimer</h4>
                    <p>The materials on <?php echo getSetting('site_name'); ?> are provided on an 'as is' basis. <?php echo getSetting('site_name'); ?> makes no warranties, expressed or implied, and hereby disclaims and negates all other warranties including, without limitation, implied warranties or conditions of merchantability, fitness for a particular purpose, or non-infringement of intellectual property or other violation of rights.</p>
                    
                    <h4 class="mb-3 mt-4">10. Limitations</h4>
                    <p>In no event shall <?php echo getSetting('site_name'); ?> or its suppliers be liable for any damages (including, without limitation, damages for loss of data or profit, or due to business interruption) arising out of the use or inability to use the materials on <?php echo getSetting('site_name'); ?>.</p>
                    
                    <h4 class="mb-3 mt-4">11. Privacy</h4>
                    <p>Your use of <?php echo getSetting('site_name'); ?> is also governed by our <a href="<?php echo SITE_URL; ?>/privacy.php">Privacy Policy</a>. Please review our Privacy Policy to understand our practices.</p>
                    
                    <h4 class="mb-3 mt-4">12. Modifications</h4>
                    <p><?php echo getSetting('site_name'); ?> may revise these terms of service at any time without notice. By using this website, you are agreeing to be bound by the then current version of these terms of service.</p>
                    
                    <h4 class="mb-3 mt-4">13. Governing Law</h4>
                    <p>These terms and conditions are governed by and construed in accordance with the laws and you irrevocably submit to the exclusive jurisdiction of the courts in that location.</p>
                    
                    <h4 class="mb-3 mt-4">14. Contact Information</h4>
                    <p>If you have any questions about these Terms & Conditions, please contact us:</p>
                    <ul>
                        <li>Email: support@gyanbazaar.com</li>
                        <li>Contact Form: <a href="<?php echo SITE_URL; ?>/contact.php">Contact Us</a></li>
                    </ul>
                    
                    <div class="alert alert-info mt-4">
                        <i class="fas fa-info-circle"></i> By using <?php echo getSetting('site_name'); ?>, you acknowledge that you have read and understood these terms and conditions and agree to be bound by them.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
