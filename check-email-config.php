<?php
// Email Configuration Checker
?>
<!DOCTYPE html>
<html>
<head>
    <title>Email Configuration Checker</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h2 {
            color: #1266f1;
            border-bottom: 2px solid #1266f1;
            padding-bottom: 10px;
        }
        .status {
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
        }
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        .info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #f8f9fa;
            font-weight: bold;
        }
        .code {
            background: #f4f4f4;
            padding: 15px;
            border-radius: 5px;
            font-family: monospace;
            margin: 10px 0;
            overflow-x: auto;
        }
        .step {
            background: #e3f2fd;
            padding: 15px;
            margin: 10px 0;
            border-left: 4px solid #1266f1;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #1266f1;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 5px;
        }
        .btn:hover {
            background: #0d47a1;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>📧 Email Configuration Status</h2>
        
        <?php
        // Check PHP mail function
        $mailFunctionExists = function_exists('mail');
        ?>
        
        <div class="status <?php echo $mailFunctionExists ? 'success' : 'error'; ?>">
            <strong>PHP mail() function:</strong> 
            <?php echo $mailFunctionExists ? '✅ Available' : '❌ Not Available'; ?>
        </div>
        
        <h2>📋 Current PHP Configuration</h2>
        <table>
            <tr>
                <th>Setting</th>
                <th>Current Value</th>
                <th>Status</th>
            </tr>
            <tr>
                <td>SMTP Server</td>
                <td><?php echo ini_get('SMTP') ?: 'Not Set'; ?></td>
                <td><?php echo ini_get('SMTP') == 'smtp.gmail.com' ? '✅' : '⚠️'; ?></td>
            </tr>
            <tr>
                <td>SMTP Port</td>
                <td><?php echo ini_get('smtp_port') ?: 'Not Set'; ?></td>
                <td><?php echo ini_get('smtp_port') == '587' ? '✅' : '⚠️'; ?></td>
            </tr>
            <tr>
                <td>Sendmail From</td>
                <td><?php echo ini_get('sendmail_from') ?: 'Not Set'; ?></td>
                <td><?php echo ini_get('sendmail_from') ? '✅' : '⚠️'; ?></td>
            </tr>
            <tr>
                <td>Sendmail Path</td>
                <td><?php echo ini_get('sendmail_path') ?: 'Not Set'; ?></td>
                <td><?php echo strpos(ini_get('sendmail_path'), 'sendmail.exe') !== false ? '✅' : '⚠️'; ?></td>
            </tr>
        </table>
        
        <?php
        // Check if properly configured
        $isConfigured = (
            ini_get('SMTP') == 'smtp.gmail.com' &&
            ini_get('smtp_port') == '587' &&
            ini_get('sendmail_from') &&
            strpos(ini_get('sendmail_path'), 'sendmail.exe') !== false
        );
        ?>
        
        <?php if ($isConfigured): ?>
            <div class="status success">
                <strong>✅ Configuration Looks Good!</strong><br>
                Your PHP settings are configured for Gmail SMTP.
            </div>
            
            <div class="status warning">
                <strong>⚠️ Next Step:</strong> Configure sendmail.ini with your Gmail credentials<br>
                File: <code>C:\xampp\sendmail\sendmail.ini</code>
            </div>
            
            <div class="step">
                <strong>Test Email Now:</strong><br>
                <a href="test-email-send.php" class="btn">📤 Test Email Sending</a>
            </div>
            
        <?php else: ?>
            <div class="status error">
                <strong>❌ Configuration Incomplete</strong><br>
                Your XAMPP email settings need to be configured.
            </div>
            
            <h2>🔧 How to Fix This</h2>
            
            <div class="step">
                <strong>Step 1: Get Gmail App Password</strong>
                <ol>
                    <li>Go to: <a href="https://myaccount.google.com/security" target="_blank">Google Account Security</a></li>
                    <li>Enable "2-Step Verification" (if not already on)</li>
                    <li>Click "App passwords"</li>
                    <li>Select: Mail + Windows Computer</li>
                    <li>Click "Generate"</li>
                    <li>Copy the 16-character password (e.g., abcd efgh ijkl mnop)</li>
                </ol>
            </div>
            
            <div class="step">
                <strong>Step 2: Edit php.ini</strong>
                <p>File Location: <code>C:\xampp\php\php.ini</code></p>
                <p>Find the [mail function] section and update:</p>
                <div class="code">
[mail function]<br>
SMTP = smtp.gmail.com<br>
smtp_port = 587<br>
sendmail_from = your-email@gmail.com<br>
sendmail_path = "C:\xampp\sendmail\sendmail.exe -t"
                </div>
                <p><strong>Replace:</strong> <code>your-email@gmail.com</code> with YOUR Gmail address</p>
            </div>
            
            <div class="step">
                <strong>Step 3: Edit sendmail.ini</strong>
                <p>File Location: <code>C:\xampp\sendmail\sendmail.ini</code></p>
                <p>Find and update these lines:</p>
                <div class="code">
smtp_server=smtp.gmail.com<br>
smtp_port=587<br>
auth_username=your-email@gmail.com<br>
auth_password=your-16-char-app-password<br>
force_sender=your-email@gmail.com
                </div>
                <p><strong>Replace:</strong></p>
                <ul>
                    <li><code>your-email@gmail.com</code> with YOUR Gmail (2 places)</li>
                    <li><code>your-16-char-app-password</code> with app password (no spaces)</li>
                </ul>
            </div>
            
            <div class="step">
                <strong>Step 4: Restart Apache</strong>
                <ol>
                    <li>Open XAMPP Control Panel</li>
                    <li>Click "Stop" on Apache</li>
                    <li>Wait 2 seconds</li>
                    <li>Click "Start" on Apache</li>
                </ol>
            </div>
            
            <div class="step">
                <strong>Step 5: Test Email</strong>
                <p>After configuration, test your email:</p>
                <a href="test-email-send.php" class="btn">📤 Test Email Sending</a>
            </div>
        <?php endif; ?>
        
        <h2>📚 Detailed Guides</h2>
        <div class="info">
            <strong>Need more help?</strong><br>
            <a href="EMAIL_SETUP_CHECKLIST.txt" class="btn">📋 Setup Checklist</a>
            <a href="CONFIGURE_EMAIL_NOW.md" class="btn">📖 Detailed Guide</a>
            <a href="START_HERE.md" class="btn">🚀 Quick Start</a>
        </div>
        
        <h2>🧪 Quick Test</h2>
        <div class="info">
            <p>After configuring, test these in order:</p>
            <ol>
                <li><a href="check-email-config.php">Refresh this page</a> - Check if config is detected</li>
                <li><a href="test-email-send.php">Test Email Sending</a> - Send a test email</li>
                <li><a href="signup.php">Test OTP Signup</a> - Try creating an account</li>
            </ol>
        </div>
        
        <h2>🆘 Troubleshooting</h2>
        <div class="warning">
            <strong>Common Issues:</strong>
            <ul>
                <li><strong>Email not sending:</strong> Check sendmail.ini has correct Gmail credentials</li>
                <li><strong>Authentication failed:</strong> Verify app password is correct (no spaces)</li>
                <li><strong>Connection failed:</strong> Check internet connection and firewall</li>
                <li><strong>Still not working:</strong> Check error logs at <code>C:\xampp\sendmail\error.log</code></li>
            </ul>
        </div>
        
        <div class="info">
            <strong>💡 Pro Tip:</strong> After making changes to php.ini or sendmail.ini, always restart Apache!
        </div>
    </div>
</body>
</html>
