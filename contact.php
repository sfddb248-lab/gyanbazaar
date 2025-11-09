<?php
require_once 'config/config.php';
$pageTitle = 'Contact Us - ' . getSetting('site_name');

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = clean($_POST['name']);
    $email = clean($_POST['email']);
    $subject = clean($_POST['subject']);
    $message = clean($_POST['message']);
    
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error = 'All fields are required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address';
    } else {
        // If user is logged in, create support ticket
        if (isLoggedIn()) {
            $userId = $_SESSION['user_id'];
            $stmt = $conn->prepare("INSERT INTO support_tickets (user_id, subject, message) VALUES (?, ?, ?)");
            $fullMessage = "From: $name ($email)\n\n$message";
            $stmt->bind_param("iss", $userId, $subject, $fullMessage);
            $stmt->execute();
            $success = 'Your message has been sent successfully! We will get back to you soon.';
        } else {
            // Send email for non-logged in users
            sendEmail(getSetting('site_name') . ' <admin@gyanbazaar.com>', 
                     "Contact Form: $subject", 
                     "From: $name ($email)\n\n$message");
            $success = 'Your message has been sent successfully!';
        }
    }
}

include 'includes/header.php';
?>

<div class="container my-5">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <h2 class="text-center mb-4"><i class="fas fa-envelope"></i> Contact Us</h2>
            
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
            
            <div class="card shadow">
                <div class="card-body p-5">
                    <form method="POST" action="">
                        <div class="form-outline mb-4">
                            <input type="text" id="name" name="name" class="form-control" required>
                            <label class="form-label" for="name">Your Name</label>
                        </div>
                        
                        <div class="form-outline mb-4">
                            <input type="email" id="email" name="email" class="form-control" required>
                            <label class="form-label" for="email">Email Address</label>
                        </div>
                        
                        <div class="form-outline mb-4">
                            <input type="text" id="subject" name="subject" class="form-control" required>
                            <label class="form-label" for="subject">Subject</label>
                        </div>
                        
                        <div class="form-outline mb-4">
                            <textarea id="message" name="message" class="form-control" rows="6" required></textarea>
                            <label class="form-label" for="message">Message</label>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-lg w-100">
                            <i class="fas fa-paper-plane"></i> Send Message
                        </button>
                    </form>
                </div>
            </div>
            
            <div class="row mt-5">
                <div class="col-md-4 text-center mb-4">
                    <i class="fas fa-map-marker-alt fa-3x text-primary mb-3"></i>
                    <h5>Address</h5>
                    <p>123 Digital Street<br>Tech City, TC 12345</p>
                </div>
                <div class="col-md-4 text-center mb-4">
                    <i class="fas fa-phone fa-3x text-primary mb-3"></i>
                    <h5>Phone</h5>
                    <p>+1 (555) 123-4567<br>Mon-Fri 9am-6pm</p>
                </div>
                <div class="col-md-4 text-center mb-4">
                    <i class="fas fa-envelope fa-3x text-primary mb-3"></i>
                    <h5>Email</h5>
                    <p>support@gyanbazaar.com<br>info@gyanbazaar.com</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
