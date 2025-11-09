<?php
// Simple email test script

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $testEmail = $_POST['email'] ?? '';
    
    if (filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
        $subject = 'Test Email from GyanBazaar';
        $message = '
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #1266f1; color: white; padding: 20px; text-align: center; }
                .content { background: #f8f9fa; padding: 20px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h2>GyanBazaar</h2>
                </div>
                <div class="content">
                    <h3>✅ Email Configuration Test</h3>
                    <p>If you receive this email, your XAMPP email configuration is working correctly!</p>
                    <p><strong>Test Details:</strong></p>
                    <ul>
                        <li>Sent from: GyanBazaar Platform</li>
                        <li>Date: ' . date('Y-m-d H:i:s') . '</li>
                        <li>Server: XAMPP Local</li>
                    </ul>
                    <p>You can now use OTP email verification for user signups.</p>
                </div>
            </div>
        </body>
        </html>
        ';
        
        $headers = "From: GyanBazaar <noreply@gyanbazaar.com>\r\n";
        $headers .= "Reply-To: noreply@gyanbazaar.com\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();
        
        if (mail($testEmail, $subject, $message, $headers)) {
            $success = true;
        } else {
            $error = 'Failed to send email. Check XAMPP configuration.';
        }
    } else {
        $error = 'Please enter a valid email address.';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Email Configuration</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            padding: 50px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h2 {
            color: #1266f1;
            text-align: center;
        }
        .form-group {
            margin: 20px 0;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="email"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            box-sizing: border-box;
        }
        .btn {
            background: #1266f1;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
        }
        .btn:hover {
            background: #0d47a1;
        }
        .success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .info {
            background: #d1ecf1;
            color: #0c5460;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .steps {
            background: #fff3cd;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .steps h4 {
            margin-top: 0;
            color: #856404;
        }
        .steps ol {
            margin: 10px 0;
        }
        .steps li {
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>📧 Test Email Configuration</h2>
        
        <?php if ($success): ?>
            <div class="success">
                <strong>✅ Email Sent Successfully!</strong><br>
                Check your inbox (and spam folder) for the test email.
            </div>
            
            <div class="info">
                <strong>🎉 Email is now working!</strong><br>
                Your OTP verification system will now send real emails to users.
            </div>
            
            <p style="text-align: center;">
                <a href="signup.php" style="color: #1266f1; text-decoration: none; font-weight: bold;">→ Test OTP Signup</a> |
                <a href="index.php" style="color: #1266f1; text-decoration: none; font-weight: bold;">← Back to Home</a>
            </p>
        <?php elseif ($error): ?>
            <div class="error">
                <strong>❌ Error:</strong> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!$success): ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="email">Enter your email address to test:</label>
                    <input type="email" id="email" name="email" placeholder="your-email@gmail.com" required>
                </div>
                
                <button type="submit" class="btn">📤 Send Test Email</button>
            </form>
        <?php endif; ?>
        
        <div class="steps">
            <h4>⚠️ If email doesn't send, configure XAMPP first:</h4>
            <ol>
                <li><strong>Get Gmail App Password:</strong> Google Account → Security → App passwords</li>
                <li><strong>Edit php.ini:</strong> C:\xampp\php\php.ini</li>
                <li><strong>Edit sendmail.ini:</strong> C:\xampp\sendmail\sendmail.ini</li>
                <li><strong>Restart Apache</strong> in XAMPP Control Panel</li>
                <li><strong>Test again</strong> with this page</li>
            </ol>
            
            <p><strong>📖 Complete Guide:</strong> <a href="CONFIGURE_EMAIL_NOW.md" target="_blank">CONFIGURE_EMAIL_NOW.md</a></p>
        </div>
        
        <div class="info">
            <strong>💡 Quick Check:</strong><br>
            • XAMPP Apache: Running?<br>
            • Gmail 2-Step Verification: Enabled?<br>
            • App Password: Generated?<br>
            • sendmail.ini: Updated?<br>
            • Apache: Restarted after config?
        </div>
    </div>
</body>
</html>
