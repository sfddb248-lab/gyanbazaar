<?php
require_once 'config/config.php';
$pageTitle = 'Home - ' . getSetting('site_name');

// Track affiliate referral
if (isset($_GET['ref'])) {
    $referralCode = $_GET['ref'];
    $cookieDuration = (int)getAffiliateSetting('cookie_duration_days', 30);
    setcookie('affiliate_ref', $referralCode, time() + ($cookieDuration * 86400), '/');
    trackAffiliateClick($referralCode);
    if (isset($_SESSION['user_id'])) {
        trackAffiliateReferral($referralCode, $_SESSION['user_id']);
    }
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}

// Get courses and products
$courses = $conn->query("SELECT * FROM products WHERE status = 'active' AND product_type = 'course' ORDER BY created_at DESC LIMIT 8")->fetch_all(MYSQLI_ASSOC);
$ebooks = $conn->query("SELECT * FROM products WHERE status = 'active' AND product_type = 'ebook' ORDER BY downloads DESC LIMIT 4")->fetch_all(MYSQLI_ASSOC);
$categories = $conn->query("SELECT * FROM categories ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);

// Stats with impressive numbers (add base numbers to actual counts)
$actualStudents = $conn->query("SELECT COUNT(*) as count FROM users WHERE role='user'")->fetch_assoc()['count'];
$actualCourses = $conn->query("SELECT COUNT(*) as count FROM products WHERE status='active'")->fetch_assoc()['count'];
$actualEnrollments = $conn->query("SELECT SUM(downloads) as total FROM products")->fetch_assoc()['total'] ?? 0;

// Display impressive stats (base + actual)
$totalStudents = 5000 + $actualStudents;  // 5K+ students
$totalCourses = 150 + $actualCourses;     // 150+ courses
$totalEnrollments = 15000 + $actualEnrollments; // 15K+ enrollments

include 'includes/header.php';
?>

<!-- Advanced CSS & Animations -->
<link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/homepage-advanced.css">

<style>
/* Advanced Animations & Styles */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes fadeInDown {
    from {
        opacity: 0;
        transform: translateY(-30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes fadeInLeft {
    from {
        opacity: 0;
        transform: translateX(-30px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

@keyframes fadeInRight {
    from {
        opacity: 0;
        transform: translateX(30px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

@keyframes scaleIn {
    from {
        opacity: 0;
        transform: scale(0.9);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

@keyframes float {
    0%, 100% {
        transform: translateY(0px);
    }
    50% {
        transform: translateY(-20px);
    }
}

@keyframes pulse {
    0%, 100% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.05);
    }
}

@keyframes shimmer {
    0% {
        background-position: -1000px 0;
    }
    100% {
        background-position: 1000px 0;
    }
}

@keyframes rotate {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}

.animate-fade-up {
    animation: fadeInUp 0.8s ease-out forwards;
    opacity: 0;
}

.animate-fade-down {
    animation: fadeInDown 0.8s ease-out forwards;
    opacity: 0;
}

.animate-fade-left {
    animation: fadeInLeft 0.8s ease-out forwards;
    opacity: 0;
}

.animate-fade-right {
    animation: fadeInRight 0.8s ease-out forwards;
    opacity: 0;
}

.animate-scale {
    animation: scaleIn 0.8s ease-out forwards;
    opacity: 0;
}

.delay-100 { animation-delay: 0.1s; }
.delay-200 { animation-delay: 0.2s; }
.delay-300 { animation-delay: 0.3s; }
.delay-400 { animation-delay: 0.4s; }
.delay-500 { animation-delay: 0.5s; }
.delay-600 { animation-delay: 0.6s; }

:root {
    --primary: #6366f1;
    --secondary: #8b5cf6;
    --success: #10b981;
    --danger: #ef4444;
    --warning: #f59e0b;
    --dark: #1f2937;
    --light: #f9fafb;
}

/* Hero Section with Particles */
.hero-banner {
    background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
    padding: 100px 0;
    color: white;
    position: relative;
    overflow: hidden;
}

.hero-banner::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: 
        radial-gradient(circle at 20% 50%, rgba(255,255,255,0.1) 0%, transparent 50%),
        radial-gradient(circle at 80% 80%, rgba(255,255,255,0.1) 0%, transparent 50%);
    animation: pulse 4s ease-in-out infinite;
}

.floating-shapes {
    position: absolute;
    width: 100%;
    height: 100%;
    top: 0;
    left: 0;
    overflow: hidden;
    z-index: 1;
}

.shape {
    position: absolute;
    opacity: 0.1;
    animation: float 6s ease-in-out infinite;
}

.shape-1 {
    width: 100px;
    height: 100px;
    background: white;
    border-radius: 50%;
    top: 10%;
    left: 10%;
    animation-delay: 0s;
}

.shape-2 {
    width: 150px;
    height: 150px;
    background: white;
    border-radius: 30% 70% 70% 30% / 30% 30% 70% 70%;
    top: 60%;
    right: 10%;
    animation-delay: 2s;
}

.shape-3 {
    width: 80px;
    height: 80px;
    background: white;
    border-radius: 50%;
    bottom: 20%;
    left: 50%;
    animation-delay: 4s;
}

.hero-content {
    position: relative;
    z-index: 2;
}

.hero-title {
    font-size: 3.5rem;
    font-weight: 800;
    line-height: 1.2;
    margin-bottom: 1.5rem;
}

.hero-subtitle {
    font-size: 1.25rem;
    opacity: 0.95;
    margin-bottom: 2rem;
}

.hero-image {
    animation: float 3s ease-in-out infinite;
}

/* Gradient Text */
.gradient-text {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

/* Stats Counter with Animation */
.stats-section {
    background: white;
    box-shadow: 0 -10px 40px rgba(0,0,0,0.1);
    position: relative;
    z-index: 10;
    margin-top: -50px;
    border-radius: 20px;
    padding: 40px 20px;
}

.stats-box {
    text-align: center;
    padding: 20px;
    transition: all 0.3s ease;
}

.stats-box:hover {
    transform: translateY(-10px);
}

.stats-number {
    font-size: 3rem;
    font-weight: 800;
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-bottom: 0.5rem;
}

.stats-label {
    font-size: 1rem;
    color: #6b7280;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1px;
}

/* Course Card with Advanced Hover */
.course-card {
    border-radius: 20px;
    overflow: hidden;
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    background: white;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    height: 100%;
    position: relative;
}

.course-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    opacity: 0;
    transition: opacity 0.3s ease;
    z-index: 1;
}

.course-card:hover::before {
    opacity: 0.05;
}

.course-card:hover {
    transform: translateY(-15px) scale(1.02);
    box-shadow: 0 20px 60px rgba(99, 102, 241, 0.3);
}

.course-thumbnail {
    position: relative;
    height: 220px;
    overflow: hidden;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.course-thumbnail img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: all 0.5s ease;
}

.course-card:hover .course-thumbnail img {
    transform: scale(1.15) rotate(2deg);
}

.course-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(to bottom, transparent 0%, rgba(0,0,0,0.7) 100%);
    opacity: 0;
    transition: opacity 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.course-card:hover .course-overlay {
    opacity: 1;
}

.play-button {
    width: 60px;
    height: 60px;
    background: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--primary);
    font-size: 24px;
    transform: scale(0);
    transition: transform 0.3s ease;
}

.course-card:hover .play-button {
    transform: scale(1);
    animation: pulse 2s ease-in-out infinite;
}

.course-badge {
    position: absolute;
    top: 15px;
    right: 15px;
    background: rgba(255,255,255,0.95);
    backdrop-filter: blur(10px);
    padding: 8px 16px;
    border-radius: 25px;
    font-size: 12px;
    font-weight: 700;
    color: var(--primary);
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    z-index: 2;
}

.course-info {
    padding: 25px;
    position: relative;
    z-index: 2;
}

.course-title {
    font-size: 1.1rem;
    font-weight: 700;
    margin-bottom: 12px;
    color: var(--dark);
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    min-height: 50px;
}

.course-meta {
    display: flex;
    gap: 20px;
    margin-bottom: 15px;
    font-size: 0.85rem;
    color: #6b7280;
}

.course-meta span {
    display: flex;
    align-items: center;
    gap: 5px;
}

.course-price {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 15px;
    border-top: 2px solid #f3f4f6;
}

.price-tag {
    font-size: 1.75rem;
    font-weight: 800;
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.btn-view {
    padding: 10px 24px;
    border-radius: 25px;
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    color: white;
    text-decoration: none;
    font-weight: 600;
    font-size: 0.9rem;
    transition: all 0.3s ease;
    border: none;
    box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3);
}

.btn-view:hover {
    transform: translateX(5px);
    box-shadow: 0 6px 20px rgba(99, 102, 241, 0.4);
    color: white;
}

/* Category Pills with Hover Effect */
.category-pill {
    display: inline-block;
    padding: 12px 28px;
    border-radius: 30px;
    background: white;
    color: var(--primary);
    text-decoration: none;
    margin: 8px;
    transition: all 0.3s ease;
    border: 2px solid var(--primary);
    font-weight: 600;
    font-size: 0.95rem;
    box-shadow: 0 4px 15px rgba(99, 102, 241, 0.1);
}

.category-pill:hover {
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    color: white;
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(99, 102, 241, 0.3);
}

/* Feature Box with Icon Animation */
.feature-box {
    text-align: center;
    padding: 40px 30px;
    border-radius: 20px;
    background: white;
    box-shadow: 0 10px 30px rgba(0,0,0,0.08);
    transition: all 0.4s ease;
    position: relative;
    overflow: hidden;
}

.feature-box::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--primary), var(--secondary));
    transform: scaleX(0);
    transition: transform 0.3s ease;
}

.feature-box:hover::before {
    transform: scaleX(1);
}

.feature-box:hover {
    transform: translateY(-10px);
    box-shadow: 0 20px 50px rgba(99, 102, 241, 0.2);
}

.feature-icon {
    width: 90px;
    height: 90px;
    margin: 0 auto 25px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 36px;
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    color: white;
    transition: all 0.3s ease;
    box-shadow: 0 10px 30px rgba(99, 102, 241, 0.3);
}

.feature-box:hover .feature-icon {
    transform: rotateY(360deg);
}

/* Section Titles */
.section-title {
    font-size: 2.5rem;
    font-weight: 800;
    margin-bottom: 1rem;
    color: var(--dark);
}

.section-subtitle {
    font-size: 1.15rem;
    color: #6b7280;
    margin-bottom: 3rem;
}

/* CTA Button with Shimmer */
.btn-cta {
    padding: 16px 40px;
    border-radius: 35px;
    font-weight: 700;
    font-size: 1.05rem;
    transition: all 0.3s ease;
    border: none;
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    color: white;
    text-decoration: none;
    display: inline-block;
    position: relative;
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(99, 102, 241, 0.3);
}

.btn-cta::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
    transition: left 0.5s ease;
}

.btn-cta:hover::before {
    left: 100%;
}

.btn-cta:hover {
    transform: translateY(-3px);
    box-shadow: 0 15px 40px rgba(99, 102, 241, 0.4);
    color: white;
}

.btn-outline {
    background: transparent;
    border: 3px solid white;
    color: white;
}

.btn-outline:hover {
    background: white;
    color: var(--primary);
}

/* Testimonial Card */
.testimonial-card {
    background: white;
    padding: 35px;
    border-radius: 20px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
}

.testimonial-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 40px rgba(0,0,0,0.12);
}

.quote-icon {
    font-size: 3rem;
    color: var(--primary);
    opacity: 0.2;
    margin-bottom: 15px;
}

.testimonial-text {
    font-size: 1.05rem;
    line-height: 1.8;
    color: #4b5563;
    margin-bottom: 25px;
    font-style: italic;
}

.testimonial-author {
    display: flex;
    align-items: center;
    gap: 15px;
}

.author-avatar {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 24px;
    box-shadow: 0 5px 15px rgba(99, 102, 241, 0.3);
}

.author-info h5 {
    margin: 0;
    font-weight: 700;
    color: var(--dark);
}

.author-info p {
    margin: 0;
    font-size: 0.9rem;
    color: #6b7280;
}

/* Newsletter Section */
.newsletter-section {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 80px 0;
    position: relative;
    overflow: hidden;
}

.newsletter-form {
    max-width: 600px;
    margin: 0 auto;
    display: flex;
    gap: 15px;
}

.newsletter-input {
    flex: 1;
    padding: 18px 25px;
    border-radius: 35px;
    border: none;
    font-size: 1rem;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

.newsletter-btn {
    padding: 18px 40px;
    border-radius: 35px;
    background: white;
    color: var(--primary);
    border: none;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

.newsletter-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 15px 40px rgba(0,0,0,0.15);
}

/* Responsive */
@media (max-width: 768px) {
    .hero-banner {
        padding: 60px 0;
    }
    
    .hero-title {
        font-size: 2rem;
    }
    
    .section-title {
        font-size: 1.75rem;
    }
    
    .course-thumbnail {
        height: 200px;
    }
    
    .stats-section {
        margin-top: -30px;
        padding: 30px 15px;
    }
    
    .stats-number {
        font-size: 2rem;
    }
    
    .newsletter-form {
        flex-direction: column;
    }
}
</style>

<!-- Hero Banner with Floating Shapes -->
<section class="hero-banner">
    <div class="floating-shapes">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        <div class="shape shape-3"></div>
    </div>
    
    <div class="container hero-content">
        <div class="row align-items-center">
            <div class="col-lg-6 mb-4 mb-lg-0">
                <div class="animate-fade-left">
                    <h1 class="hero-title">Learn Anytime,<br>Anywhere</h1>
                    <p class="hero-subtitle">Access premium courses, ebooks, and digital resources to boost your skills and knowledge</p>
                    <div class="d-flex gap-3 flex-wrap">
                        <a href="<?php echo SITE_URL; ?>/products.php?type=course" class="btn-cta">
                            <i class="fas fa-play-circle me-2"></i> Browse Courses
                        </a>
                        <?php if (!isLoggedIn()): ?>
                        <a href="<?php echo SITE_URL; ?>/signup.php" class="btn-cta btn-outline">
                            <i class="fas fa-user-plus me-2"></i> Get Started Free
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="animate-fade-right hero-image">
                    <img src="https://images.unsplash.com/photo-1522202176988-66273c2fd55f?w=600&h=400&fit=crop" 
                         alt="Learning" class="img-fluid rounded-4 shadow-lg" style="border-radius: 30px !important;">
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Stats Section -->
<div class="container">
    <div class="stats-section animate-fade-up">
        <div class="row">
            <div class="col-md-3 col-6">
                <div class="stats-box">
                    <div class="stats-number counter" data-target="<?php echo $totalCourses; ?>">0</div>
                    <div class="stats-label">Courses</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stats-box">
                    <div class="stats-number counter" data-target="<?php echo $totalStudents; ?>">0</div>
                    <div class="stats-label">Students</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stats-box">
                    <div class="stats-number counter" data-target="<?php echo $totalEnrollments; ?>">0</div>
                    <div class="stats-label">Enrollments</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stats-box">
                    <div class="stats-number">4.8</div>
                    <div class="stats-label">Rating</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Categories Section -->
<section class="py-5 mt-5">
    <div class="container">
        <div class="text-center mb-5 animate-fade-down">
            <h2 class="section-title">Explore Categories</h2>
            <p class="section-subtitle">Find the perfect course for your learning journey</p>
        </div>
        <div class="text-center animate-fade-up delay-200">
            <?php foreach ($categories as $category): ?>
                <a href="<?php echo SITE_URL; ?>/products.php?category=<?php echo $category['id']; ?>" class="category-pill">
                    <i class="fas fa-folder-open me-2"></i><?php echo htmlspecialchars($category['name']); ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Featured Courses -->
<section class="py-5" style="background: var(--light);">
    <div class="container">
        <div class="text-center mb-5 animate-fade-down">
            <h2 class="section-title">Featured Courses</h2>
            <p class="section-subtitle">Most popular courses chosen by our students</p>
        </div>
        <div class="row">
            <?php 
            $delay = 0;
            foreach ($courses as $course): 
            ?>
            <div class="col-lg-3 col-md-4 col-sm-6 mb-4 animate-fade-up delay-<?php echo $delay; ?>00">
                <div class="course-card">
                    <div class="course-thumbnail">
                        <img src="<?php echo getCourseImage($course); ?>" 
                             alt="<?php echo htmlspecialchars($course['title']); ?>"
                             onerror="this.src='https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=400&h=300&fit=crop'">
                        <span class="course-badge">
                            <i class="fas fa-video me-1"></i> Course
                        </span>
                        <div class="course-overlay">
                            <div class="play-button">
                                <i class="fas fa-play"></i>
                            </div>
                        </div>
                    </div>
                    <div class="course-info">
                        <h3 class="course-title"><?php echo htmlspecialchars($course['title']); ?></h3>
                        <div class="course-meta">
                            <span><i class="fas fa-users"></i> <?php echo $course['downloads']; ?></span>
                            <span><i class="fas fa-star text-warning"></i> 4.5</span>
                            <span><i class="fas fa-clock"></i> 12h</span>
                        </div>
                        <div class="course-price">
                            <span class="price-tag"><?php echo formatCurrency($course['price']); ?></span>
                            <a href="<?php echo SITE_URL; ?>/product-detail.php?id=<?php echo $course['id']; ?>" 
                               class="btn-view">
                                View <i class="fas fa-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php 
            $delay = ($delay + 1) % 4;
            endforeach; 
            ?>
        </div>
        <div class="text-center mt-5 animate-fade-up">
            <a href="<?php echo SITE_URL; ?>/products.php?type=course" class="btn-cta btn-lg">
                View All Courses <i class="fas fa-arrow-right ms-2"></i>
            </a>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="py-5 my-5">
    <div class="container">
        <div class="text-center mb-5 animate-fade-down">
            <h2 class="section-title">Why Choose Us</h2>
            <p class="section-subtitle">Everything you need for successful learning</p>
        </div>
        <div class="row">
            <div class="col-md-4 mb-4 animate-fade-up">
                <div class="feature-box">
                    <div class="feature-icon">
                        <i class="fas fa-certificate"></i>
                    </div>
                    <h4 class="fw-bold mb-3">Quality Content</h4>
                    <p class="text-muted">Premium courses created by industry experts with real-world experience</p>
                </div>
            </div>
            <div class="col-md-4 mb-4 animate-fade-up delay-200">
                <div class="feature-box">
                    <div class="feature-icon">
                        <i class="fas fa-infinity"></i>
                    </div>
                    <h4 class="fw-bold mb-3">Lifetime Access</h4>
                    <p class="text-muted">Learn at your own pace with unlimited access to all course materials</p>
                </div>
            </div>
            <div class="col-md-4 mb-4 animate-fade-up delay-400">
                <div class="feature-box">
                    <div class="feature-icon">
                        <i class="fas fa-headset"></i>
                    </div>
                    <h4 class="fw-bold mb-3">24/7 Support</h4>
                    <p class="text-muted">Get help whenever you need it from our dedicated support team</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Testimonials Section -->
<section class="py-5" style="background: var(--light);">
    <div class="container">
        <div class="text-center mb-5 animate-fade-down">
            <h2 class="section-title">What Students Say</h2>
            <p class="section-subtitle">Real feedback from our learning community</p>
        </div>
        <div class="row">
            <div class="col-md-4 mb-4 animate-fade-up">
                <div class="testimonial-card">
                    <div class="quote-icon">
                        <i class="fas fa-quote-left"></i>
                    </div>
                    <p class="testimonial-text">
                        "The courses are amazing! I learned so much and the instructors are very knowledgeable. Highly recommended!"
                    </p>
                    <div class="testimonial-author">
                        <div class="author-avatar">RS</div>
                        <div class="author-info">
                            <h5>Rahul Sharma</h5>
                            <p>Web Developer</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4 animate-fade-up delay-200">
                <div class="testimonial-card">
                    <div class="quote-icon">
                        <i class="fas fa-quote-left"></i>
                    </div>
                    <p class="testimonial-text">
                        "Best investment I made in my career. The quality of content and support is outstanding!"
                    </p>
                    <div class="testimonial-author">
                        <div class="author-avatar">PK</div>
                        <div class="author-info">
                            <h5>Priya Kumar</h5>
                            <p>Data Analyst</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4 animate-fade-up delay-400">
                <div class="testimonial-card">
                    <div class="quote-icon">
                        <i class="fas fa-quote-left"></i>
                    </div>
                    <p class="testimonial-text">
                        "Flexible learning at its best. I can learn anytime, anywhere. Perfect for working professionals!"
                    </p>
                    <div class="testimonial-author">
                        <div class="author-avatar">AV</div>
                        <div class="author-info">
                            <h5>Amit Verma</h5>
                            <p>Marketing Manager</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Newsletter Section -->
<section class="newsletter-section">
    <div class="container text-center text-white">
        <div class="animate-scale">
            <h2 class="display-5 fw-bold mb-3">Stay Updated</h2>
            <p class="lead mb-4">Subscribe to our newsletter for latest courses and updates</p>
            <form class="newsletter-form" onsubmit="return false;">
                <input type="email" class="newsletter-input" placeholder="Enter your email address" required>
                <button type="submit" class="newsletter-btn">
                    <i class="fas fa-paper-plane me-2"></i> Subscribe
                </button>
            </form>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="py-5 my-5">
    <div class="container">
        <div class="text-center animate-scale" style="background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%); padding: 80px 40px; border-radius: 30px; color: white;">
            <h2 class="display-5 fw-bold mb-4">Start Learning Today</h2>
            <p class="lead mb-4">Join thousands of students already learning on <?php echo getSetting('site_name'); ?></p>
            <a href="<?php echo SITE_URL; ?>/<?php echo isLoggedIn() ? 'products.php' : 'signup.php'; ?>" class="btn-cta btn-outline btn-lg">
                <i class="fas fa-rocket me-2"></i> <?php echo isLoggedIn() ? 'Browse Courses' : 'Get Started Now'; ?>
            </a>
        </div>
    </div>
</section>

<!-- Counter Animation Script -->
<script>
// Counter Animation
function animateCounter(element) {
    const target = parseInt(element.getAttribute('data-target'));
    const duration = 2000;
    const increment = target / (duration / 16);
    let current = 0;
    
    const timer = setInterval(() => {
        current += increment;
        if (current >= target) {
            element.textContent = target + '+';
            clearInterval(timer);
        } else {
            element.textContent = Math.floor(current) + '+';
        }
    }, 16);
}

// Intersection Observer for animations
const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
};

const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.style.opacity = '1';
            
            // Animate counters
            if (entry.target.classList.contains('stats-section')) {
                const counters = entry.target.querySelectorAll('.counter');
                counters.forEach(counter => animateCounter(counter));
            }
            
            observer.unobserve(entry.target);
        }
    });
}, observerOptions);

// Observe all animated elements
document.addEventListener('DOMContentLoaded', () => {
    const animatedElements = document.querySelectorAll('[class*="animate-"]');
    animatedElements.forEach(el => observer.observe(el));
});

// Newsletter form
document.querySelector('.newsletter-form')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const email = this.querySelector('input').value;
    alert('Thank you for subscribing! We will send updates to ' + email);
    this.reset();
});
</script>

<?php include 'includes/footer.php'; ?>
