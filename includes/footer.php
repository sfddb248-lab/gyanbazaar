
    
    <!-- Desktop Footer -->
    <footer class="bg-light text-center text-lg-start mt-5 desktop-nav">
        <div class="container p-4">
            <div class="row">
                <div class="col-lg-4 col-md-6 mb-4 mb-md-0">
                    <h5 class="text-uppercase"><i class="fas fa-gem text-primary"></i> <?php echo getSetting('site_name', 'GyanBazaar'); ?></h5>
                    <p>Your trusted marketplace for premium digital products.</p>
                </div>
                
                <div class="col-lg-2 col-md-6 mb-4 mb-md-0">
                    <h5 class="text-uppercase">Quick Links</h5>
                    <ul class="list-unstyled mb-0">
                        <li><a href="<?php echo SITE_URL; ?>" class="text-dark">Home</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/products.php" class="text-dark">Products</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/contact.php" class="text-dark">Contact</a></li>
                    </ul>
                </div>
                
                <div class="col-lg-3 col-md-6 mb-4 mb-md-0">
                    <h5 class="text-uppercase">Support</h5>
                    <ul class="list-unstyled mb-0">
                        <li><a href="<?php echo SITE_URL; ?>/faq.php" class="text-dark">FAQ</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/terms.php" class="text-dark">Terms</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/privacy.php" class="text-dark">Privacy</a></li>
                    </ul>
                </div>
                
                <div class="col-lg-3 col-md-6 mb-4 mb-md-0">
                    <h5 class="text-uppercase">Follow Us</h5>
                    <a href="#" class="btn btn-primary btn-floating m-1"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="btn btn-primary btn-floating m-1"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="btn btn-primary btn-floating m-1"><i class="fab fa-instagram"></i></a>
                </div>
            </div>
        </div>
        
        <div class="text-center p-3" style="background-color: rgba(0, 0, 0, 0.05);">
            © <?php echo date('Y'); ?> <?php echo getSetting('site_name', 'GyanBazaar'); ?>. All rights reserved.
        </div>
    </footer>
    
    <!-- MDBootstrap JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/7.1.0/mdb.umd.min.js"></script>
    
    <script>
        // Theme Toggle
        function toggleTheme() {
            const html = document.documentElement;
            const currentTheme = html.getAttribute('data-mdb-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            html.setAttribute('data-mdb-theme', newTheme);
            document.cookie = `theme=${newTheme}; path=/; max-age=31536000`;
            
            // Update icon
            document.querySelectorAll('.theme-toggle i').forEach(icon => {
                icon.className = newTheme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
            });
        }
        
        // Mobile Menu Toggle
        function toggleMobileMenu() {
            const menu = document.querySelector('.mobile-menu-overlay');
            const backdrop = document.querySelector('.mobile-menu-backdrop');
            const hamburger = document.querySelector('.hamburger-menu');
            
            menu.classList.toggle('active');
            backdrop.classList.toggle('active');
            hamburger.classList.toggle('active');
            
            // Prevent body scroll when menu is open
            if (menu.classList.contains('active')) {
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = '';
            }
        }
        
        // Initialize theme icon and dropdowns
        document.addEventListener('DOMContentLoaded', function() {
            const isDark = document.documentElement.getAttribute('data-mdb-theme') === 'dark';
            document.querySelectorAll('.theme-toggle i').forEach(icon => {
                icon.className = isDark ? 'fas fa-sun' : 'fas fa-moon';
            });
            
            // Initialize all dropdowns
            const dropdownElementList = document.querySelectorAll('[data-mdb-toggle="dropdown"]');
            const dropdownList = [...dropdownElementList].map(dropdownToggleEl => new mdb.Dropdown(dropdownToggleEl));
        });
    </script>
</body>
</html>
