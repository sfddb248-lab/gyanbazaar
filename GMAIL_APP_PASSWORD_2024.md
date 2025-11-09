# 🔐 How to Get Gmail App Password (Updated 2024)

## Problem: Can't Find "App Passwords"?

Google has changed the location and requirements for App Passwords. Here's the updated guide.

---

## ✅ Method 1: Direct Link (Easiest)

### Step 1: Use This Direct Link
**Click here:** https://myaccount.google.com/apppasswords

This takes you directly to the App Passwords page.

### Step 2: Sign In
- Enter your Gmail password
- Complete 2-Step Verification if prompted

### Step 3: Create App Password
1. You'll see "App passwords" page
2. In the "App name" field, type: **DigitalKhazana**
3. Click **Create**
4. Copy the 16-character password
5. Remove spaces: `abcd efgh ijkl mnop` → `abcdefghijklmnop`

---

## ✅ Method 2: Manual Navigation (If Direct Link Doesn't Work)

### Step 1: Enable 2-Step Verification First
1. Go to: https://myaccount.google.com/security
2. Scroll to "How you sign in to Google"
3. Click **2-Step Verification**
4. If it says "OFF", click **Get Started** and follow the setup
5. Complete the 2-Step Verification setup

### Step 2: Access App Passwords
1. Go back to: https://myaccount.google.com/security
2. Scroll to "How you sign in to Google"
3. Look for **App passwords** (it appears ONLY after 2-Step is enabled)
4. Click **App passwords**
5. You may need to sign in again

### Step 3: Generate Password
1. In "Select app" dropdown: Choose **Mail**
2. In "Select device" dropdown: Choose **Windows Computer**
3. Click **Generate**
4. Copy the 16-character password
5. Click **Done**

---

## ⚠️ Troubleshooting: Still Can't Find It?

### Issue 1: "App passwords" Option Not Showing

**Reason:** 2-Step Verification is not enabled

**Solution:**
1. Go to: https://myaccount.google.com/security
2. Enable 2-Step Verification first
3. Wait 5 minutes
4. Refresh the page
5. "App passwords" should now appear

### Issue 2: Using Work/School Gmail Account

**Reason:** Your organization may have disabled App Passwords

**Solution:**
- Use a personal Gmail account instead
- OR ask your IT administrator to enable App Passwords
- OR use Alternative Method 3 below

### Issue 3: "App passwords" Grayed Out

**Reason:** Advanced Protection is enabled

**Solution:**
- You cannot use App Passwords with Advanced Protection
- Use Alternative Method 3 below

---

## 🔄 Alternative Method 3: Use PHPMailer with OAuth2

If you can't get App Passwords, use PHPMailer with OAuth2 (more complex but more secure).

### Quick Setup:

1. **Download PHPMailer:**
   - Visit: https://github.com/PHPMailer/PHPMailer
   - Download ZIP and extract to `vendor/phpmailer/`

2. **Update `includes/functions.php`:**

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
        $mail->Username = 'your-email@gmail.com'; // Your Gmail
        $mail->Password = 'your-regular-password'; // Your regular Gmail password
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

**Note:** This may not work if you have 2-Step Verification enabled.

---

## 🎯 Alternative Method 4: Use Different Email Service

### Option A: Use Outlook/Hotmail (Easier)

Outlook doesn't require App Passwords for basic SMTP.

**Configuration:**
```ini
# php.ini
SMTP = smtp-mail.outlook.com
smtp_port = 587
sendmail_from = your-email@outlook.com

# sendmail.ini
smtp_server=smtp-mail.outlook.com
smtp_port=587
auth_username=your-email@outlook.com
auth_password=your-outlook-password
force_sender=your-email@outlook.com
```

### Option B: Use SendGrid (Free 100 emails/day)

1. Sign up: https://sendgrid.com/
2. Get API key
3. Use SendGrid's SMTP or API

**Configuration:**
```ini
# sendmail.ini
smtp_server=smtp.sendgrid.net
smtp_port=587
auth_username=apikey
auth_password=YOUR_SENDGRID_API_KEY
force_sender=your-verified-email@domain.com
```

### Option C: Use Mailgun (Free 5,000 emails/month)

1. Sign up: https://mailgun.com/
2. Verify domain or use sandbox
3. Get SMTP credentials

---

## 📸 Visual Guide: Finding App Passwords

### Step-by-Step Screenshots Guide:

1. **Go to Google Account Security**
   ```
   URL: https://myaccount.google.com/security
   ```

2. **Look for this section:**
   ```
   ┌─────────────────────────────────────────┐
   │ How you sign in to Google               │
   ├─────────────────────────────────────────┤
   │ Password                                │
   │ Last changed: [date]                    │
   │                                         │
   │ 2-Step Verification                     │
   │ On                                      │
   │                                         │
   │ App passwords                           │  ← LOOK FOR THIS
   │ Manage app passwords                    │
   └─────────────────────────────────────────┘
   ```

3. **If you don't see "App passwords":**
   - 2-Step Verification is OFF
   - Enable it first, then "App passwords" will appear

---

## 🚀 Quick Solution: Use the Direct Link

**The easiest way:**

1. Click: https://myaccount.google.com/apppasswords
2. Sign in if prompted
3. Type app name: **DigitalKhazana**
4. Click **Create**
5. Copy password
6. Done!

---

## 📝 Summary

### If You Can Access App Passwords:
✅ Use Gmail with App Password (most secure)

### If You Can't Access App Passwords:
1. ✅ Try direct link: https://myaccount.google.com/apppasswords
2. ✅ Enable 2-Step Verification first
3. ✅ Use Outlook/Hotmail instead (easier)
4. ✅ Use SendGrid or Mailgun (free tier)
5. ✅ Use PHPMailer with regular password (less secure)

---

## 🆘 Still Having Issues?

### Quick Test:
1. Visit: https://myaccount.google.com/apppasswords
2. What do you see?

**If you see:** "App passwords"
- ✅ Great! Create one and use it

**If you see:** "2-Step Verification is off"
- ⚠️ Enable 2-Step Verification first

**If you see:** "This setting is not available for your account"
- ❌ Use Alternative Method (Outlook, SendGrid, etc.)

---

## 💡 Recommended Solution

**For localhost development (easiest):**

Use **Mailtrap** or **MailHog** - they're designed for testing emails locally without needing real SMTP.

**Mailtrap (Recommended):**
1. Sign up: https://mailtrap.io/ (free)
2. Get SMTP credentials
3. Use in sendmail.ini
4. All emails go to Mailtrap inbox (not real users)
5. Perfect for testing!

---

## 🎉 Next Steps

Once you have your App Password (or alternative):

1. Visit: http://localhost/DigitalKhazana/auto-configure-email.php
2. Enter your email and password
3. Follow the wizard
4. Test your setup

---

**Need more help? Check the auto-configurator - it has updated instructions!**
