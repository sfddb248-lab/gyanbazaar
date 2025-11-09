<?php
/**
 * Automatic Email Configuration Helper
 * This script helps you configure XAMPP email settings
 */

$step = $_GET['step'] ?? 1;
$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['generate_config'])) {
        $email = $_POST['email'] ?? '';
        $appPassword = $_POST['app_password'] ?? '';
        
        if (filter_var($email, FILTER_VALIDATE_EMAIL) && !empty($appPassword)) {
            $_SESSION['email'] = $email;
            $_SESSION['app_password'] = $appPassword;
            $step = 3;
        } else {
            $error = 'Please provide valid email and app password';
        }
    }
}

session_start();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Auto Configure Email - DigitalKhazana</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #1266f1 0%, #0d47a1 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        .header p {
            opacity: 0.9;
            font-size: 16px;
        }
        .content {
            padding: 40px;
        }
        .steps {
            display: flex;
            justify-content: space-between;
            margin-bottom: 40px;
            position: relative;
        }
        .steps::before {
            content: '';
            position: absolute;
            top: 20px;
            left: 0;
            right: 0;
            height: 2px;
            background: #e0e0e0;
            z-index: 0;
        }
        .step-item {
            flex: 1;
            text-align: center;
            position: relative;
            z-index: 1;
        }
        .step-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e0e0e0;
            color: #999;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            font-weight: bold;
            font-size: 18px;
        }
        .step-item.active .step-circle {
            background: #1266f1;
            color: white;
        }
        .step-item.completed .step-circle {
            background: #4caf50;
            color: white;
        }
        .step-label {
            font-size: 12px;
            color: #666;
        }
        .step-item.active .step-label {
            color: #1266f1;
            font-weight: bold;
        }
        .card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 30px;
            margin: 20px 0;
        }
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        .form-group {
            margin: 20px 0;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        input[type="email"],
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        input:focus {
            outline: none;
            border-color: #1266f1;
        }
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: #1266f1;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.3s;
        }
        .btn:hover {
            background: #0d47a1;
        }
        .btn-success {
            background: #4caf50;
        }
        .btn-success:hover {
            background: #388e3c;
        }
        .btn-secondary {
            background: #6c757d;
        }
        .btn-secondary:hover {
            background: #545b62;
        }
        .code-block {
            background: #2d2d2d;
            color: #f8f8f2;
            padding: 20px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            overflow-x: auto;
            margin: 15px 0;
            position: relative;
        }
        .copy-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 5px 15px;
            background: #4caf50;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
        }
        .copy-btn:hover {
            background: #388e3c;
        }
        .instruction-list {
            list-style: none;
            counter-reset: item;
        }
        .instruction-list li {
            counter-increment: item;
            margin: 15px 0;
            padding-left: 40px;
            position: relative;
        }
        .instruction-list li::before {
            content: counter(item);
            position: absolute;
            left: 0;
            top: 0;
            width: 30px;
            height: 30px;
            background: #1266f1;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        .highlight {
            background: #fff3cd;
            padding: 2px 6px;
            border-radius: 3px;
            font-weight: 600;
        }
        .video-link {
            display: inline-block;
            padding: 10px 20px;
            background: #ff0000;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 10px 0;
        }
        .progress-bar {
            width: 100%;
            height: 8px;
            background: #e0e0e0;
            border-radius: 10px;
            overflow: hidden;
            margin: 20px 0;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #1266f1, #4caf50);
            transition: width 0.3s;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚀 Auto Configure Email</h1>
            <p>Set up XAMPP email in 3 easy steps</p>
        </div>
        
        <div class="content">
            <!-- Progress Steps -->
            <div class="steps">
                <div class="step-item <?php echo $step >= 1 ? 'active' : ''; ?> <?php echo $step > 1 ? 'completed' : ''; ?>">
                    <div class="step-circle">1</div>
                    <div class="step-label">Get Password</div>
                </div>
                <div class="step-item <?php echo $step >= 2 ? 'active' : ''; ?> <?php echo $step > 2 ? 'completed' : ''; ?>">
                    <div class="step-circle">2</div>
                    <div class="step-label">Enter Details</div>
                </div>
                <div class="step-item <?php echo $step >= 3 ? 'active' : ''; ?> <?php echo $step > 3 ? 'completed' : ''; ?>">
                    <div class="step-circle">3</div>
                    <div class="step-label">Configure</div>
                </div>
                <div class="step-item <?php echo $step >= 4 ? 'active' : ''; ?>">
                    <div class="step-circle">4</div>
                    <div class="step-label">Test</div>
                </div>
            </div>
            
            <!-- Progress Bar -->
            <div class="progress-bar">
                <div class="progress-fill" style="width: <?php echo ($step / 4) * 100; ?>%"></div>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <strong>❌ Error:</strong> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <strong>✅ Success:</strong> <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <!-- Step 1: Get Gmail App Password -->
            <?php if ($step == 1): ?>
                <div class="card">
                    <h2 style="color: #1266f1; margin-bottom: 20px;">📧 Step 1: Get Gmail App Password</h2>
                    
                    <div class="alert alert-info">
                        <strong>⏱️ Time Required:</strong> 2 minutes
                    </div>
                    
                    <ul class="instruction-list">
                        <li>Go to <a href="https://myaccount.google.com/security" target="_blank" style="color: #1266f1; font-weight: bold;">Google Account Security</a></li>
                        <li>Enable <span class="highlight">2-Step Verification</span> (if not already enabled)</li>
                        <li>Click on <span class="highlight">App passwords</span></li>
                        <li>Select app: <span class="highlight">Mail</span></li>
                        <li>Select device: <span class="highlight">Windows Computer</span></li>
                        <li>Click <span class="highlight">Generate</span></li>
                        <li>Copy the 16-character password (e.g., abcd efgh ijkl mnop)</li>
                    </ul>
                    
                    <div class="alert alert-warning">
                        <strong>⚠️ Important:</strong> You'll use this app password (not your regular Gmail password) in the next step.
                    </div>
                    
                    <div style="text-align: center; margin-top: 30px;">
                        <a href="?step=2" class="btn btn-success">✅ I Have My App Password →</a>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Step 2: Enter Email Details -->
            <?php if ($step == 2): ?>
                <div class="card">
                    <h2 style="color: #1266f1; margin-bottom: 20px;">✍️ Step 2: Enter Your Details</h2>
                    
                    <form method="POST" action="?step=2">
                        <div class="form-group">
                            <label for="email">Your Gmail Address:</label>
                            <input type="email" id="email" name="email" placeholder="your-email@gmail.com" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="app_password">Gmail App Password (16 characters):</label>
                            <input type="text" id="app_password" name="app_password" placeholder="abcdefghijklmnop" required maxlength="16">
                            <small style="color: #666; display: block; margin-top: 5px;">
                                ⚠️ Remove all spaces from the password
                            </small>
                        </div>
                        
                        <div style="text-align: center; margin-top: 30px;">
                            <a href="?step=1" class="btn btn-secondary">← Back</a>
                            <button type="submit" name="generate_config" class="btn btn-success">Generate Configuration →</button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
            
            <!-- Step 3: Configuration Files -->
            <?php if ($step == 3 && isset($_SESSION['email']) && isset($_SESSION['app_password'])): ?>
                <?php
                $email = $_SESSION['email'];
                $appPassword = $_SESSION['app_password'];
                ?>
                <div class="card">
                    <h2 style="color: #1266f1; margin-bottom: 20px;">⚙️ Step 3: Apply Configuration</h2>
                    
                    <div class="alert alert-success">
                        <strong>✅ Configuration Generated!</strong> Follow the instructions below to apply it.
                    </div>
                    
                    <h3 style="margin: 30px 0 15px; color: #333;">📄 File 1: php.ini</h3>
                    <p><strong>Location:</strong> <code>C:\xampp\php\php.ini</code></p>
                    <p>Find the <code>[mail function]</code> section and replace with:</p>
                    
                    <div class="code-block">
                        <button class="copy-btn" onclick="copyToClipboard('php-ini-config')">📋 Copy</button>
                        <pre id="php-ini-config">[mail function]
SMTP = smtp.gmail.com
smtp_port = 587
sendmail_from = <?php echo $email; ?>

sendmail_path = "C:\xampp\sendmail\sendmail.exe -t"</pre>
                    </div>
                    
                    <h3 style="margin: 30px 0 15px; color: #333;">📄 File 2: sendmail.ini</h3>
                    <p><strong>Location:</strong> <code>C:\xampp\sendmail\sendmail.ini</code></p>
                    <p>Find and update these lines:</p>
                    
                    <div class="code-block">
                        <button class="copy-btn" onclick="copyToClipboard('sendmail-ini-config')">📋 Copy</button>
                        <pre id="sendmail-ini-config">smtp_server=smtp.gmail.com
smtp_port=587
auth_username=<?php echo $email; ?>

auth_password=<?php echo $appPassword; ?>

force_sender=<?php echo $email; ?></pre>
                    </div>
                    
                    <div class="alert alert-warning">
                        <strong>📝 Instructions:</strong>
                        <ol style="margin: 10px 0 0 20px;">
                            <li>Click "Copy" buttons above to copy each configuration</li>
                            <li>Open the files in Notepad (Run as Administrator)</li>
                            <li>Find and replace the sections as shown</li>
                            <li>Save both files</li>
                            <li>Restart Apache in XAMPP Control Panel</li>
                        </ol>
                    </div>
                    
                    <div style="text-align: center; margin-top: 30px;">
                        <a href="?step=4" class="btn btn-success">✅ I've Applied the Configuration →</a>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Step 4: Test Configuration -->
            <?php if ($step == 4): ?>
                <div class="card">
                    <h2 style="color: #1266f1; margin-bottom: 20px;">🧪 Step 4: Test Your Configuration</h2>
                    
                    <div class="alert alert-info">
                        <strong>🎉 Almost Done!</strong> Let's test if email is working.
                    </div>
                    
                    <h3 style="margin: 20px 0 10px;">Test 1: Check Configuration</h3>
                    <p>Verify that your settings are detected correctly:</p>
                    <a href="check-email-config.php" target="_blank" class="btn">🔍 Check Configuration</a>
                    
                    <h3 style="margin: 30px 0 10px;">Test 2: Send Test Email</h3>
                    <p>Send a test email to verify it's working:</p>
                    <a href="test-email-send.php" target="_blank" class="btn">📤 Send Test Email</a>
                    
                    <h3 style="margin: 30px 0 10px;">Test 3: Test OTP Signup</h3>
                    <p>Try creating an account with OTP verification:</p>
                    <a href="signup.php" target="_blank" class="btn">👤 Test Signup</a>
                    
                    <div class="alert alert-success" style="margin-top: 30px;">
                        <strong>✅ Success Indicators:</strong>
                        <ul style="margin: 10px 0 0 20px;">
                            <li>Configuration checker shows all green checkmarks</li>
                            <li>Test email arrives in your inbox</li>
                            <li>OTP email received during signup</li>
                            <li>Account verified successfully</li>
                        </ul>
                    </div>
                    
                    <div style="text-align: center; margin-top: 30px;">
                        <a href="index.php" class="btn btn-success">🎉 Done! Go to Homepage</a>
                        <a href="?step=1" class="btn btn-secondary">🔄 Start Over</a>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Help Section -->
            <div class="alert alert-info" style="margin-top: 30px;">
                <strong>📚 Need Help?</strong><br>
                <a href="FIX_EMAIL_NOW.md" style="color: #0c5460;">Quick Fix Guide</a> |
                <a href="CONFIGURE_NOW.txt" style="color: #0c5460;">Detailed Instructions</a> |
                <a href="EMAIL_SETUP_CHECKLIST.txt" style="color: #0c5460;">Setup Checklist</a>
            </div>
        </div>
    </div>
    
    <script>
        function copyToClipboard(elementId) {
            const element = document.getElementById(elementId);
            const text = element.textContent;
            
            navigator.clipboard.writeText(text).then(() => {
                const btn = element.previousElementSibling;
                const originalText = btn.textContent;
                btn.textContent = '✅ Copied!';
                btn.style.background = '#4caf50';
                
                setTimeout(() => {
                    btn.textContent = originalText;
                    btn.style.background = '#4caf50';
                }, 2000);
            });
        }
    </script>
</body>
</html>
