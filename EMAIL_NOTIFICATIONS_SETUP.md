# Email Notifications Setup Guide

## ✅ Email Function Added!

The system now has a working email notification system for order updates and payment approvals.

---

## 🎯 What Emails Are Sent

### 1. Customer Submits Transaction ID
**To:** Customer
**When:** After entering transaction ID
**Subject:** Payment Submitted - Order #XXX
**Content:** Confirmation that payment is being verified

### 2. Admin Notification
**To:** Admin
**When:** New payment submitted
**Subject:** New Payment to Verify - Order #XXX
**Content:** Alert to verify new payment

### 3. Payment Approved
**To:** Customer
**When:** Admin approves payment
**Subject:** Payment Approved - Order #XXX
**Content:** Confirmation and access to products

### 4. Payment Rejected
**To:** Customer
**When:** Admin rejects payment
**Subject:** Payment Verification Failed - Order #XXX
**Content:** Rejection reason and next steps

---

## ⚙️ Email Configuration

### Step 1: Set Admin Email

1. Go to **Admin Panel → Settings**
2. Find **"Admin Email"** field
3. Enter your email address
4. Click **"Save Settings"**

### Step 2: Configure PHP Mail (if needed)

#### For XAMPP (Windows):

1. **Open php.ini**
   ```
   C:\xampp\php\php.ini
   ```

2. **Find and update these lines:**
   ```ini
   [mail function]
   SMTP = smtp.gmail.com
   smtp_port = 587
   sendmail_from = your-email@gmail.com
   sendmail_path = "\"C:\xampp\sendmail\sendmail.exe\" -t"
   ```

3. **Open sendmail.ini**
   ```
   C:\xampp\sendmail\sendmail.ini
   ```

4. **Update these lines:**
   ```ini
   smtp_server=smtp.gmail.com
   smtp_port=587
   auth_username=your-email@gmail.com
   auth_password=your-app-password
   force_sender=your-email@gmail.com
   ```

5. **Restart Apache**

#### For Gmail App Password:

1. Go to Google Account Settings
2. Security → 2-Step Verification
3. App Passwords
4. Generate new app password
5. Use this password in sendmail.ini

---

## 🔧 Email Function Details

### Function Signature:
```php
sendEmail($to, $subject, $message)
```

### Parameters:
- **$to** - Recipient email address
- **$subject** - Email subject line
- **$message** - Email body (plain text, will be converted to HTML)

### Features:
- ✅ HTML formatted emails
- ✅ Professional template
- ✅ Site branding
- ✅ Responsive design
- ✅ Error logging

### Email Template:
```html
┌─────────────────────────────────┐
│     [Site Name]                 │
├─────────────────────────────────┤
│                                 │
│  [Message Content]              │
│                                 │
├─────────────────────────────────┤
│  This is an automated email     │
│  Please do not reply            │
└─────────────────────────────────┘
```

---

## 🧪 Testing Emails

### Test 1: Place Order
1. Place an order with UPI payment
2. Submit transaction ID
3. Check customer email → Should receive "Payment Submitted"
4. Check admin email → Should receive "New Payment to Verify"

### Test 2: Approve Payment
1. Go to Admin → Verify Payments
2. Click "Approve" on a pending payment
3. Check customer email → Should receive "Payment Approved"

### Test 3: Reject Payment
1. Go to Admin → Verify Payments
2. Click "Reject" on a pending payment
3. Enter reason
4. Check customer email → Should receive "Payment Verification Failed"

---

## 🐛 Troubleshooting

### Emails Not Sending?

**Check 1: PHP Mail Configuration**
```php
// Test if mail function works
if (function_exists('mail')) {
    echo "Mail function available";
} else {
    echo "Mail function not available";
}
```

**Check 2: SMTP Settings**
- Verify SMTP server address
- Check port (usually 587 or 465)
- Verify username/password
- Check firewall settings

**Check 3: Error Logs**
```
Check: C:\xampp\apache\logs\error.log
Look for: mail() errors
```

**Check 4: Spam Folder**
- Emails might be in spam
- Check junk/spam folder
- Add sender to contacts

### Common Issues:

**Issue 1: "Failed to connect to mailserver"**
```
Solution:
- Check SMTP server address
- Verify port number
- Check internet connection
- Disable firewall temporarily
```

**Issue 2: "Authentication failed"**
```
Solution:
- Verify email/password
- Use app password for Gmail
- Enable "Less secure apps" (not recommended)
- Use OAuth2 (advanced)
```

**Issue 3: "Emails go to spam"**
```
Solution:
- Set proper From address
- Add SPF/DKIM records (advanced)
- Use professional email service
- Avoid spam trigger words
```

---

## 📧 Alternative: Use Email Service

### Option 1: PHPMailer (Recommended)

```php
// Install via Composer
composer require phpmailer/phpmailer

// Use in sendEmail function
use PHPMailer\PHPMailer\PHPMailer;

$mail = new PHPMailer(true);
$mail->isSMTP();
$mail->Host = 'smtp.gmail.com';
$mail->SMTPAuth = true;
$mail->Username = 'your-email@gmail.com';
$mail->Password = 'your-app-password';
$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
$mail->Port = 587;
```

### Option 2: SendGrid

```php
// Use SendGrid API
$apiKey = 'your-sendgrid-api-key';
// Implement SendGrid integration
```

### Option 3: Mailgun

```php
// Use Mailgun API
$apiKey = 'your-mailgun-api-key';
// Implement Mailgun integration
```

---

## 📊 Email Logs

### Check Logs:
```php
// Logs are written to PHP error log
error_log("Email sent to: $to | Subject: $subject | Status: Success");
```

### View Logs:
```
Windows: C:\xampp\apache\logs\error.log
Linux: /var/log/apache2/error.log
```

### Log Format:
```
[Date Time] Email sent to: customer@example.com | Subject: Payment Approved | Status: Success
```

---

## ✅ Email Checklist

### Setup:
- [ ] Admin email configured in settings
- [ ] PHP mail function working
- [ ] SMTP settings configured (if using SMTP)
- [ ] Test email sent successfully

### Testing:
- [ ] Customer receives submission confirmation
- [ ] Admin receives new payment notification
- [ ] Customer receives approval email
- [ ] Customer receives rejection email
- [ ] Emails not going to spam
- [ ] Email formatting looks good

### Production:
- [ ] Use professional email address
- [ ] Configure SPF/DKIM records
- [ ] Monitor email delivery
- [ ] Check spam reports
- [ ] Keep logs for debugging

---

## 💡 Best Practices

### For Email Content:
1. **Clear Subject Lines**
   - Include order number
   - State purpose clearly
   - Keep it short

2. **Professional Tone**
   - Friendly but professional
   - Clear and concise
   - Include next steps

3. **Important Information**
   - Order number
   - Transaction ID
   - Amount
   - Date/time
   - Contact information

### For Delivery:
1. **Use Real Email**
   - Not noreply@localhost
   - Professional domain
   - Verified sender

2. **Avoid Spam Triggers**
   - No ALL CAPS
   - No excessive punctuation!!!
   - No spam words (FREE, WIN, etc.)

3. **Test Regularly**
   - Send test emails
   - Check delivery
   - Monitor spam folder

---

## 🎉 Summary

✅ **Email Function** - Added to functions.php
✅ **Admin Email** - Configurable in settings
✅ **4 Email Types** - Submission, notification, approval, rejection
✅ **HTML Template** - Professional design
✅ **Error Logging** - Track email status
✅ **Easy Testing** - Test with real orders

### Quick Setup:
1. Set admin email in settings
2. Configure PHP mail (if needed)
3. Test with an order
4. Check email delivery

**Emails are now working!** 📧

---

## 📞 Support

### If Emails Still Not Working:

1. **Check PHP Configuration**
   ```
   Create file: test-email.php
   Content: <?php mail('your-email@example.com', 'Test', 'Test message'); ?>
   Visit: http://localhost/DigitalKhazana/test-email.php
   ```

2. **Use Alternative**
   - Consider PHPMailer
   - Use email service (SendGrid, Mailgun)
   - Contact hosting support

3. **Temporary Solution**
   - Manually check orders
   - Notify customers manually
   - Set up email service later

**Note:** For production, always use a professional email service for better deliverability!
