# Configure XAMPP to Send Emails

## Quick Setup for Gmail

### Step 1: Edit php.ini

1. Open: `C:\xampp\php\php.ini`
2. Find `[mail function]` section
3. Update these lines:

```ini
[mail function]
SMTP=smtp.gmail.com
smtp_port=587
sendmail_from=your-email@gmail.com
sendmail_path="\"C:\xampp\sendmail\sendmail.exe\" -t"
```

### Step 2: Edit sendmail.ini

1. Open: `C:\xampp\sendmail\sendmail.ini`
2. Update these lines:

```ini
smtp_server=smtp.gmail.com
smtp_port=587
auth_username=your-email@gmail.com
auth_password=your-app-password
force_sender=your-email@gmail.com
```

### Step 3: Get Gmail App Password

1. Go to: https://myaccount.google.com/security
2. Enable 2-Step Verification
3. Go to: https://myaccount.google.com/apppasswords
4. Generate new app password
5. Copy the 16-character password
6. Use this in sendmail.ini (not your regular password)

### Step 4: Restart Apache

1. Open XAMPP Control Panel
2. Stop Apache
3. Start Apache

### Step 5: Test

Run: `http://localhost/DigitalKhazana/test-email-send.php`
