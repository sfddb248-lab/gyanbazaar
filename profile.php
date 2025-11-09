<?php
require_once 'config/config.php';
requireLogin();
$pageTitle = 'My Profile - ' . getSetting('site_name');

$userId = $_SESSION['user_id'];
$success = '';
$error = '';

// Get user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Handle profile photo upload
if (isset($_POST['upload_photo']) && isset($_FILES['profile_photo'])) {
    $file = $_FILES['profile_photo'];
    
    if ($file['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $file['name'];
        $filetype = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($filetype, $allowed)) {
            if ($file['size'] <= 5000000) { // 5MB max
                $uploadDir = UPLOAD_PATH . 'profiles/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                $newFilename = 'user_' . $userId . '_' . time() . '.' . $filetype;
                $destination = $uploadDir . $newFilename;
                
                if (move_uploaded_file($file['tmp_name'], $destination)) {
                    // Delete old photo if exists
                    if (!empty($user['profile_photo'])) {
                        $oldFile = UPLOAD_PATH . $user['profile_photo'];
                        if (file_exists($oldFile)) {
                            unlink($oldFile);
                        }
                    }
                    
                    // Update database
                    $photoPath = 'profiles/' . $newFilename;
                    $stmt = $conn->prepare("UPDATE users SET profile_photo = ? WHERE id = ?");
                    $stmt->bind_param("si", $photoPath, $userId);
                    $stmt->execute();
                    
                    $success = 'Profile photo updated successfully!';
                    $user['profile_photo'] = $photoPath;
                } else {
                    $error = 'Failed to upload photo';
                }
            } else {
                $error = 'File size must be less than 5MB';
            }
        } else {
            $error = 'Only JPG, JPEG, PNG & GIF files are allowed';
        }
    }
}

// Handle profile update
if (isset($_POST['update_profile'])) {
    $name = clean($_POST['name']);
    $email = clean($_POST['email']);
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (empty($name) || empty($email)) {
        $error = 'Name and email are required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format';
    } else {
        // Check if email is taken by another user
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->bind_param("si", $email, $userId);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $error = 'Email already in use';
        } else {
            // Update basic info
            $stmt = $conn->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
            $stmt->bind_param("ssi", $name, $email, $userId);
            $stmt->execute();
            
            // Update password if provided
            if (!empty($newPassword)) {
                if (!password_verify($currentPassword, $user['password'])) {
                    $error = 'Current password is incorrect';
                } elseif (strlen($newPassword) < 6) {
                    $error = 'New password must be at least 6 characters';
                } elseif ($newPassword !== $confirmPassword) {
                    $error = 'New passwords do not match';
                } else {
                    $hashedPassword = password_hash($newPassword, HASH_ALGO, ['cost' => HASH_COST]);
                    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $stmt->bind_param("si", $hashedPassword, $userId);
                    $stmt->execute();
                }
            }
            
            if (empty($error)) {
                $_SESSION['user_name'] = $name;
                $_SESSION['user_email'] = $email;
                $success = 'Profile updated successfully!';
                
                // Refresh user data
                $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->bind_param("i", $userId);
                $stmt->execute();
                $user = $stmt->get_result()->fetch_assoc();
            }
        }
    }
}

// Get order statistics
$stmt = $conn->prepare("SELECT COUNT(*) as total_orders, SUM(final_amount) as total_spent FROM orders WHERE user_id = ? AND payment_status = 'completed'");
$stmt->bind_param("i", $userId);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

include 'includes/header.php';
?>

<div class="container my-4">
    <h2 class="mb-4"><i class="fas fa-user-circle"></i> My Profile</h2>
    
    <div class="row">
        <div class="col-lg-4 mb-4">
            <div class="card text-center">
                <div class="card-body">
                    <div class="mb-3 position-relative d-inline-block">
                        <?php if (!empty($user['profile_photo'])): ?>
                            <img src="<?php echo UPLOAD_URL . $user['profile_photo']; ?>" 
                                 alt="Profile Photo" 
                                 class="rounded-circle" 
                                 style="width: 150px; height: 150px; object-fit: cover; border: 4px solid #1266f1;">
                        <?php else: ?>
                            <div style="width: 150px; height: 150px; border-radius: 50%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; color: white; font-size: 60px; font-weight: bold; margin: 0 auto;">
                                <?php echo strtoupper(substr($user['name'], 0, 2)); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Photo Upload Form -->
                    <form method="POST" enctype="multipart/form-data" class="mt-3">
                        <input type="file" name="profile_photo" id="profile_photo" accept="image/*" style="display: none;" onchange="this.form.submit()">
                        <label for="profile_photo" class="btn btn-sm btn-primary">
                            <i class="fas fa-camera"></i> Change Photo
                        </label>
                        <input type="hidden" name="upload_photo" value="1">
                    </form>
                    
                    <h4 class="mt-3"><?php echo htmlspecialchars($user['name']); ?></h4>
                    <p class="text-muted"><?php echo htmlspecialchars($user['email']); ?></p>
                    <p class="mb-0">
                        <span class="badge bg-primary"><?php echo ucfirst($user['role']); ?></span>
                    </p>
                </div>
            </div>
            
            <div class="card mt-3">
                <div class="card-body">
                    <h5 class="card-title mb-3">Statistics</h5>
                    <div class="d-flex justify-content-between mb-2">
                        <span><i class="fas fa-shopping-bag text-primary"></i> Total Orders:</span>
                        <strong><?php echo $stats['total_orders'] ?? 0; ?></strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span><i class="fas fa-dollar-sign text-success"></i> Total Spent:</span>
                        <strong><?php echo formatCurrency($stats['total_spent'] ?? 0); ?></strong>
                    </div>
                </div>
            </div>
            
            <?php if (isAdmin()): ?>
            <div class="card mt-3">
                <div class="card-body text-center">
                    <a href="<?php echo SITE_URL; ?>/admin" class="btn btn-primary w-100">
                        <i class="fas fa-cog"></i> Admin Dashboard
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-4">Update Profile</h5>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                            <button type="button" class="btn-close" data-mdb-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                            <button type="button" class="btn-close" data-mdb-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="form-outline mb-4">
                            <input type="text" id="name" name="name" class="form-control" 
                                   value="<?php echo htmlspecialchars($user['name']); ?>" required>
                            <label class="form-label" for="name">Full Name</label>
                        </div>
                        
                        <div class="form-outline mb-4">
                            <input type="email" id="email" name="email" class="form-control" 
                                   value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            <label class="form-label" for="email">Email Address</label>
                        </div>
                        
                        <hr class="my-4">
                        
                        <h6 class="mb-3">Change Password (Optional)</h6>
                        
                        <div class="form-outline mb-4">
                            <input type="password" id="current_password" name="current_password" class="form-control">
                            <label class="form-label" for="current_password">Current Password</label>
                        </div>
                        
                        <div class="form-outline mb-4">
                            <input type="password" id="new_password" name="new_password" class="form-control">
                            <label class="form-label" for="new_password">New Password</label>
                        </div>
                        
                        <div class="form-outline mb-4">
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control">
                            <label class="form-label" for="confirm_password">Confirm New Password</label>
                        </div>
                        
                        <button type="submit" name="update_profile" class="btn btn-primary btn-lg">
                            <i class="fas fa-save"></i> Update Profile
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Initialize MDB form inputs
document.addEventListener('DOMContentLoaded', function() {
    // Initialize all form outlines
    document.querySelectorAll('.form-outline').forEach((formOutline) => {
        new mdb.Input(formOutline).init();
    });
    
    // Update labels for pre-filled inputs
    document.querySelectorAll('.form-outline input').forEach((input) => {
        if (input.value) {
            input.classList.add('active');
            const label = input.parentElement.querySelector('label');
            if (label) {
                label.classList.add('active');
            }
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>
