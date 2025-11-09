<?php
require_once 'config/config.php';
require_once 'includes/affiliate-functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$affiliate = getAffiliateByUserId($userId);

if (!$affiliate) {
    header('Location: affiliate-dashboard.php');
    exit;
}

// Get promotional materials
$stmt = $conn->prepare("SELECT * FROM affiliate_materials WHERE status = 'active' ORDER BY material_type, created_at DESC");
$stmt->execute();
$materials = $stmt->get_result();

// Track download
if (isset($_GET['download']) && is_numeric($_GET['download'])) {
    $materialId = intval($_GET['download']);
    $updateStmt = $conn->prepare("UPDATE affiliate_materials SET download_count = download_count + 1 WHERE id = ?");
    $updateStmt->bind_param("i", $materialId);
    $updateStmt->execute();
}

include 'includes/header.php';
?>

<div class="container my-5">
    <div class="row mb-4">
        <div class="col-md-12">
            <h2>Promotional Materials</h2>
            <p class="text-muted">Download banners, templates, and other materials to promote our products</p>
            <a href="affiliate-dashboard.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>
    
    <!-- Quick Links Section -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Your Referral Link</h5>
                    <div class="input-group">
                        <input type="text" class="form-control" id="affiliateLink" 
                               value="<?php echo getAffiliateLink($affiliate['referral_code']); ?>" readonly>
                        <button class="btn btn-primary" onclick="copyLink()">
                            <i class="fas fa-copy"></i> Copy
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Materials by Type -->
    <?php
    $materialTypes = [
        'banner' => ['icon' => 'fa-image', 'title' => 'Banners', 'color' => 'primary'],
        'email' => ['icon' => 'fa-envelope', 'title' => 'Email Templates', 'color' => 'success'],
        'social' => ['icon' => 'fa-share-alt', 'title' => 'Social Media', 'color' => 'info'],
        'video' => ['icon' => 'fa-video', 'title' => 'Videos', 'color' => 'danger'],
        'document' => ['icon' => 'fa-file-pdf', 'title' => 'Documents', 'color' => 'warning']
    ];
    
    $materials->data_seek(0);
    $groupedMaterials = [];
    while ($material = $materials->fetch_assoc()) {
        $groupedMaterials[$material['material_type']][] = $material;
    }
    
    foreach ($materialTypes as $type => $info):
        if (!isset($groupedMaterials[$type])) continue;
    ?>
        <div class="row mb-4">
            <div class="col-md-12">
                <h4 class="mb-3">
                    <i class="fas <?php echo $info['icon']; ?> text-<?php echo $info['color']; ?>"></i>
                    <?php echo $info['title']; ?>
                </h4>
                <div class="row">
                    <?php foreach ($groupedMaterials[$type] as $material): ?>
                        <div class="col-md-4 mb-3">
                            <div class="card h-100">
                                <?php if ($material['file_path'] && in_array($type, ['banner', 'social'])): ?>
                                    <img src="<?php echo UPLOAD_URL . $material['file_path']; ?>" 
                                         class="card-img-top" alt="<?php echo htmlspecialchars($material['title']); ?>">
                                <?php endif; ?>
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($material['title']); ?></h5>
                                    <p class="card-text"><?php echo htmlspecialchars($material['description']); ?></p>
                                    <?php if ($material['dimensions']): ?>
                                        <p class="text-muted small">
                                            <i class="fas fa-ruler-combined"></i> <?php echo $material['dimensions']; ?>
                                        </p>
                                    <?php endif; ?>
                                    <?php if ($material['file_size']): ?>
                                        <p class="text-muted small">
                                            <i class="fas fa-file"></i> <?php echo round($material['file_size'] / 1024, 2); ?> KB
                                        </p>
                                    <?php endif; ?>
                                    <p class="text-muted small">
                                        <i class="fas fa-download"></i> <?php echo $material['download_count']; ?> downloads
                                    </p>
                                </div>
                                <div class="card-footer">
                                    <?php if ($material['file_path']): ?>
                                        <a href="<?php echo UPLOAD_URL . $material['file_path']; ?>" 
                                           class="btn btn-<?php echo $info['color']; ?> btn-sm" 
                                           download
                                           onclick="window.location.href='?download=<?php echo $material['id']; ?>'">
                                            <i class="fas fa-download"></i> Download
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($material['file_url']): ?>
                                        <a href="<?php echo $material['file_url']; ?>" 
                                           class="btn btn-outline-<?php echo $info['color']; ?> btn-sm" 
                                           target="_blank">
                                            <i class="fas fa-external-link-alt"></i> View
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    
    <?php if (empty($groupedMaterials)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> No promotional materials available at the moment. Check back soon!
        </div>
    <?php endif; ?>
    
    <!-- Sample Email Template -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Sample Email Template</h5>
                    <div class="bg-light p-3 rounded">
                        <p><strong>Subject:</strong> Check out these amazing digital products!</p>
                        <hr>
                        <p>Hi [Name],</p>
                        <p>I wanted to share something exciting with you! I've discovered an amazing platform called <?php echo getSetting('site_name', 'GyanBazaar'); ?> that offers high-quality digital products including eBooks, courses, templates, and more.</p>
                        <p>They have some incredible deals right now, and I thought you might be interested.</p>
                        <p><strong>Check it out here:</strong> <?php echo getAffiliateLink($affiliate['referral_code']); ?></p>
                        <p>Let me know what you think!</p>
                        <p>Best regards</p>
                    </div>
                    <button class="btn btn-primary mt-3" onclick="copyEmailTemplate()">
                        <i class="fas fa-copy"></i> Copy Template
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Social Media Posts -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Sample Social Media Posts</h5>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="bg-light p-3 rounded">
                                <h6><i class="fab fa-facebook text-primary"></i> Facebook</h6>
                                <p>🎉 Discover amazing digital products at <?php echo getSetting('site_name', 'GyanBazaar'); ?>! 📚✨<br>
                                Get instant access to eBooks, courses, templates & more!<br>
                                👉 <?php echo getAffiliateLink($affiliate['referral_code']); ?></p>
                                <button class="btn btn-sm btn-primary" onclick="copyText(this.previousElementSibling.innerText)">
                                    <i class="fas fa-copy"></i> Copy
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="bg-light p-3 rounded">
                                <h6><i class="fab fa-twitter text-info"></i> Twitter</h6>
                                <p>🚀 Level up your skills with premium digital products!<br>
                                Check out @<?php echo getSetting('site_name', 'GyanBazaar'); ?> 📚<br>
                                <?php echo getAffiliateLink($affiliate['referral_code']); ?></p>
                                <button class="btn btn-sm btn-info" onclick="copyText(this.previousElementSibling.innerText)">
                                    <i class="fas fa-copy"></i> Copy
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function copyLink() {
    const linkInput = document.getElementById('affiliateLink');
    linkInput.select();
    document.execCommand('copy');
    alert('Link copied to clipboard!');
}

function copyEmailTemplate() {
    const template = document.querySelector('.bg-light.p-3.rounded').innerText;
    navigator.clipboard.writeText(template).then(() => {
        alert('Email template copied to clipboard!');
    });
}

function copyText(text) {
    navigator.clipboard.writeText(text).then(() => {
        alert('Text copied to clipboard!');
    });
}
</script>

<?php include 'includes/footer.php'; ?>
