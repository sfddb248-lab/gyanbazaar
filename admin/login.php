<?php
require_once '../config/config.php';

// If already logged in as admin, redirect to dashboard
if (isLoggedIn() && isAdmin()) {
    header('Location: ' . SITE_URL . '/admin/index.php');
    exit;
}

$pageTitle = 'Admin Login - ' . getSetting('site_name');
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = clean($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $error = 'All fields are required';
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND role IN ('admin', 'editor')");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($user = $result->fetch_assoc()) {
            if (password_verify($password, $user['password'])) {
                // Login successful - set session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                
                header('Location: ' . SITE_URL . '/admin/index.php');
                exit;
            } else {
                $error = 'Invalid email or password';
            }
        } else {
            $error = 'Invalid admin credentials';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-mdb-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    
    <!-- MDBootstrap CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/7.1.0/mdb.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .login-card {
            max-width: 450px;
            width: 100%;
            margin: 20px;
        }
        
        .admin-logo {
            font-size: 64px;
            color: #667eea;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    
    <div class="login-card">
        <div class="card shadow-lg">
            <div class="card-body p-5">
                <div class="text-center">
                    <i class="fas fa-shield-alt admin-logo"></i>
                    <h2 class="mb-4">Admin Panel</h2>
                    <p class="text-muted mb-4">Sign in to access the admin dashboard</p>
                </div>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                        <button type="button" class="btn-close" data-mdb-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="form-outline mb-4">
                        <input type="email" id="email" name="email" class="form-control form-control-lg" required>
                        <label class="form-label" for="email">Admin Email</label>
                    </div>
                    
                    <div class="form-outline mb-4">
                        <input type="password" id="password" name="password" class="form-control form-control-lg" required>
                        <label class="form-label" for="password">Password</label>
                    </div>
                    
                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" id="remember" name="remember">
                        <label class="form-check-label" for="remember">Remember me</label>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-lg btn-block mb-3">
                        <i class="fas fa-sign-in-alt"></i> Sign In
                    </button>
                    
                    <div class="text-center">
                        <a href="<?php echo SITE_URL; ?>" class="text-muted">
                            <i class="fas fa-arrow-left"></i> Back to Website
                        </a>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="text-center mt-3">
            <p class="text-white">
                <i class="fas fa-lock"></i> Secure Admin Access
            </p>
        </div>
    </div>
    
    <!-- MDBootstrap JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/7.1.0/mdb.min.js"></script>
    
    <script>
    // Initialize MDB form inputs
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.form-outline').forEach((formOutline) => {
            new mdb.Input(formOutline).init();
        });
    });
    </script>
    
</body>
</html>
