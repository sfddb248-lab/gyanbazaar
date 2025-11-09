# Configure Email Sending - Complete Guide

## Problem: Emails Not Sending from XAMPP

XAMPP's PHP mail() function doesn't work by default. You need to configure SMTP.

---

## ✅ SOLUTION: Use Gmail SMTP (Easiest Method)

### Step 1: Get Gmail App Password

1. **Go to Google Account**
   - Visit: https://myaccount.google.com/

2. **Enable 2-Step Verification**
   - Security → 2-Step Verification
   - Turn it ON if not already enabled

3. **Create App Password**
   - Security → App passwords
   - Select app: "Mail"
   - Select device: "Windows Computer"
   - Click "Generate"
   - **Copy the 16-character password** (e.g., abcd efgh ijkl mnop)

---

### Step 2: Configure XAMPP Sendmail

#### A. Edit php.ini

**File Location:** `C:\xampp\php\php.ini`

**Find and Update These Lines:**

```ini
[mail function]
; For Win32 only.
SMTP = smtp.gmail.com
smtp_port = 587

; For Win32 only.
sendmail_from = your-email@gmail.com

; For Unix only.
sendmail_path = "\"C:\xampp\sendmail\sendmail.exe\" -t"
```

**Replace:**
- `your-email@gmail.com` with YOUR Gmail address

---

#### B. Edit sendmail.ini

**File Location:** `C:\xampp\sendmail\sendmail.ini`

**Find and Update These Lines:**

```ini
[sendmail]

smtp_server=smtp.gmail.com
smtp_port=587
error_logfile=error.log
debug_logfile=debug.log

auth_username=your-email@gmail.com
auth_password=your-app-password-here

force_sender=your-email@gmail.com
```

**Replace:**
- `your-email@gmail.com` with YOUR Gmail address (2 places)
- `your-app-password-here` with the 16-character app password (remove spaces)

**Example:**
```ini
auth_username=nitinkumar@gmail.com
auth_password=abcdefghijklmnop
force_sender=nitinkumar@gmail.com
```

---

### Step 3: Restart Apache

1. Open XAMPP Control Panel
2. Click "Stop" on Apache
3. Wait 2 seconds
4. Click "Start" on Apache

---

### Step 4: Test Email

Visit: `http://localhost/DigitalKhazana/test-email-send.php`

Or create this test file:

```php
<?php
$to = 'your-email@gmail.com'; // Your email
$subject = 'Test Email from XAMPP';
$message = 'If you receive this, email is working!';
$headers = 'From: DigitalKhazana <your-email@gmail.com>';

if (mail($to, $subject, $message, $headers)) {
    echo "✓ Email sent successfully! Check your inbox.";
} else {
    echo "✗ Email failed to send.";
}
?>
```

---

## Alternative: Use PHPMailer (More Reliable)

If above doesn't work, use PHPMailer library:

### Install PHPMailer:

**Download:**
1. Go to: https://github.com/PHPMailer/PHPMailer
2. Download ZIP
3. Extract to: `C:\xampp\htdocs\DigitalKhazana\vendor\phpmailer`

**Or use Composer:**
```bash
composer require phpmailer/phpmailer
```

### Update sendEmail() function:

**File:** `includes/functions.php`

```php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/phpmailer/src/Exception.php';
require 'vendor/phpmailer/src/PHPMailer.php';
require 'vendor/phpmailer/src/SMTP.php';

function sendEmail($to, $subject, $message) {
    $mail = new PHPMailer(true);
    
    try {
        // SMTP settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'your-email@gmail.com';
        $mail->Password = 'your-app-password';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        
        // Email settings
        $mail->setFrom('your-email@gmail.com', 'DigitalKhazana');
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $message;
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email error: {$mail->ErrorInfo}");
        return false;
    }
}
```

---

## Quick Setup Checklist

### For Gmail SMTP:

- [ ] Enable 2-Step Verification in Google Account
- [ ] Generate App Password
- [ ] Edit `C:\xampp\php\php.ini`
- [ ] Edit `C:\xampp\sendmail\sendmail.ini`
- [ ] Replace `your-email@gmail.com` with your Gmail
- [ ] Replace `your-app-password` with 16-char password
- [ ] Restart Apache in XAMPP
- [ ] Test with test-email-send.php
- [ ] Check inbox (and spam folder)

---

## Troubleshooting

### Issue 1: "Could not connect to SMTP host"

**Solution:**
- Check internet connection
- Verify smtp.gmail.com is accessible
- Check firewall settings
- Try port 465 instead of 587

### Issue 2: "Authentication failed"

**Solution:**
- Verify Gmail address is correct
- Check app password (no spaces)
- Ensure 2-Step Verification is ON
- Generate new app password

### Issue 3: Emails go to spam

**Solution:**
- Check spam folder
- Add sender to contacts
- Use professional "From" name
- Avoid spam trigger words

### Issue 4: Still not working

**Solution:**
- Check error logs: `C:\xampp\sendmail\error.log`
- Check debug logs: `C:\xampp\sendmail\debug.log`
- Try PHPMailer instead
- Use email service (SendGrid, Mailgun)

---

## Production Alternative: Email Services

### Option 1: SendGrid (Free 100 emails/day)

1. Sign up: https://sendgrid.com/
2. Get API key
3. Use SendGrid PHP library
4. More reliable than SMTP

### Option 2: Mailgun (Free 5,000 emails/month)

1. Sign up: https://mailgun.com/
2. Get API key
3. Use Mailgun PHP library
4. Better deliverability

### Option 3: AWS SES (Very cheap)

1. Sign up: https://aws.amazon.com/ses/
2. Verify domain
3. Use AWS SDK
4. Enterprise-grade

---

## After Configuration

### Test OTP System:

1. **Configure email** (follow steps above)
2. **Restart Apache**
3. **Test email:** Visit test-email-send.php
4. **Create account:** Go to signup.php
5. **Check email:** Look for OTP code
6. **Verify:** Enter OTP on verification page
7. **Success:** Account activated!

---

## Quick Start (Gmail)

**1. Get App Password:**
```
Google Account → Security → App passwords → Generate
Copy: abcdefghijklmnop
```

**2. Edit sendmail.ini:**
```ini
auth_username=your-email@gmail.com
auth_password=abcdefghijklmnop
```

**3. Restart Apache**

**4. Test:**
```
Visit: test-email-send.php
Check: Your Gmail inbox
```

**Done!** 🎉

---

## Important Notes

- **Use App Password**, not your regular Gmail password
- **2-Step Verification** must be enabled
- **Less Secure Apps** option is deprecated, don't use it
- **Check spam folder** for test emails
- **Restart Apache** after any config changes

---

## Need Help?

1. Check error logs: `C:\xampp\sendmail\error.log`
2. Check debug logs: `C:\xampp\sendmail\debug.log`
3. Test with simple mail() function first
4. Try PHPMailer if SMTP doesn't work
5. Consider email service for production

**Once configured, OTP emails will be sent automatically!** 📧✅
