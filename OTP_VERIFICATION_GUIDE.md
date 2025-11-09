# OTP Email Verification System

## ✅ OTP Verification Added for Signup!

New users must verify their email address with a 6-digit OTP code before they can login.

---

## 🎯 How It Works

### Signup Flow:

1. **User Fills Signup Form**
   - Name, Email, Password, Confirm Password

2. **System Generates OTP**
   - 6-digit random code
   - Valid for 15 minutes

3. **Email Sent**
   - OTP code sent to user's email
   - Professional HTML template

4. **User Redirected**
   - To OTP verification page
   - Must enter 6-digit code

5. **Verification**
   - User enters OTP
   - System validates code
   - Account activated

6. **Login Enabled**
   - User can now login
   - Full access to platform

---

## 📋 Database Changes

### New Fields Added:

```sql
email_verified BOOLEAN DEFAULT FALSE
otp_code VARCHAR(6)
otp_expiry DATETIME
status ENUM('active', 'blocked', 'pending') DEFAULT 'pending'
```

### User States:

**Pending (New User):**
- Status: pending
- Email Verified: FALSE
- Cannot login until verified

**Active (Verified User):**
- Status: active
- Email Verified: TRUE
- Can login and use platform

**Blocked:**
- Status: blocked
- Cannot login

---

## 🎨 OTP Verification Page

### Features:

- ✅ Clean, professional design
- ✅ Large OTP input field
- ✅ Auto-format (numbers only)
- ✅ 6-digit validation
- ✅ Resend OTP button
- ✅ Expiry timer display
- ✅ Error handling
- ✅ Success messages

### Visual Design:

```
┌─────────────────────────────────┐
│     📧 Verify Your Email        │
├─────────────────────────────────┤
│                                 │
│  We've sent a 6-digit OTP to    │
│  user@example.com               │
│                                 │
│  Enter 6-Digit OTP              │
│  ┌─────────────────────────┐   │
│  │   0 0 0 0 0 0           │   │
│  └─────────────────────────┘   │
│  ⏰ OTP expires in 15 minutes   │
│                                 │
│  [✓ Verify OTP]                │
│                                 │
│  Didn't receive the code?       │
│  [🔄 Resend OTP]               │
└─────────────────────────────────┘
```

---

## 📧 Email Template

### OTP Email Content:

```
Subject: Verify Your Email - OTP Code

Welcome to DigitalKhazana!

Your OTP code is: 123456

This code will expire in 15 minutes.

If you didn't create an account, please ignore this email.
```

---

## 🔒 Security Features

### OTP Security:

1. **6-Digit Code**
   - Random generation
   - 1 million combinations

2. **Time Limited**
   - Expires in 15 minutes
   - Must request new after expiry

3. **One-Time Use**
   - Cleared after verification
   - Cannot be reused

4. **Email Validation**
   - Ensures valid email address
   - Prevents fake accounts

### Login Protection:

- Unverified users cannot login
- Clear error message shown
- Link to verification page provided

---

## 🎯 User Experience

### New User Journey:

```
Signup Form
    ↓
Enter Details
    ↓
Submit
    ↓
OTP Sent to Email
    ↓
Redirected to Verification Page
    ↓
Check Email
    ↓
Enter 6-Digit OTP
    ↓
Click Verify
    ↓
Success! Account Activated
    ↓
Redirected to Login
    ↓
Login with Credentials
    ↓
Access Platform
```

### Resend OTP Flow:

```
Didn't Receive OTP?
    ↓
Click "Resend OTP"
    ↓
New OTP Generated
    ↓
New Email Sent
    ↓
Enter New OTP
    ↓
Verify
```

---

## 💡 Features

### OTP Input:

- **Auto-Format** - Only accepts numbers
- **Max Length** - 6 digits only
- **Large Display** - Easy to read
- **Letter Spacing** - Clear digit separation
- **Bold Font** - Professional look

### Validation:

- **Real-Time** - Validates as you type
- **Pattern Check** - Must be 6 digits
- **Expiry Check** - Validates time limit
- **Code Match** - Compares with database

### User Feedback:

- **Success Messages** - Green alerts
- **Error Messages** - Red alerts
- **Info Messages** - Blue alerts
- **Loading States** - Button feedback

---

## 🐛 Error Handling

### Common Errors:

**1. Invalid OTP**
```
Message: "Invalid OTP. Please try again."
Action: User can re-enter code
```

**2. Expired OTP**
```
Message: "OTP has expired. Please request a new one."
Action: Click "Resend OTP" button
```

**3. Email Not Sent**
```
Message: "Account created but failed to send OTP. Please contact support."
Action: Contact admin
```

**4. Unverified Login Attempt**
```
Message: "Please verify your email first. Click here to verify"
Action: Redirects to verification page
```

---

## ⚙️ Configuration

### OTP Settings:

```php
// OTP length
$otp = sprintf("%06d", mt_rand(1, 999999)); // 6 digits

// Expiry time
$otpExpiry = date('Y-m-d H:i:s', strtotime('+15 minutes')); // 15 min

// Email subject
$subject = 'Verify Your Email - OTP Code';
```

### Customization:

You can modify:
- OTP length (change %06d to %08d for 8 digits)
- Expiry time (change +15 minutes to +30 minutes)
- Email template (edit sendEmail content)

---

## 📊 Admin View

### User Management:

Admins can see in users table:
- Email verification status
- OTP code (for support)
- OTP expiry time
- Account status (pending/active/blocked)

### Manual Verification:

Admin can manually verify users:
```sql
UPDATE users 
SET email_verified = TRUE, status = 'active', otp_code = NULL 
WHERE email = 'user@example.com';
```

---

## 🧪 Testing

### Test Signup Flow:

1. Go to signup page
2. Fill in details
3. Submit form
4. Check email for OTP
5. Enter OTP on verification page
6. Verify success message
7. Try logging in
8. Should work!

### Test Resend OTP:

1. On verification page
2. Click "Resend OTP"
3. Check email for new OTP
4. Old OTP should not work
5. New OTP should work

### Test Expiry:

1. Wait 15+ minutes after signup
2. Try entering OTP
3. Should show "expired" error
4. Click "Resend OTP"
5. New OTP should work

---

## 🔧 Troubleshooting

### OTP Email Not Received?

**Check:**
1. Spam/Junk folder
2. Email configuration (see EMAIL_NOTIFICATIONS_SETUP.md)
3. SMTP settings
4. PHP mail function

**Solution:**
- Configure XAMPP sendmail
- Use email service (SendGrid, Mailgun)
- Check error logs

### OTP Not Working?

**Check:**
1. Entered correctly (6 digits)
2. Not expired (< 15 minutes)
3. Using latest OTP (if resent)
4. Database has correct OTP

**Solution:**
- Request new OTP
- Check database otp_code column
- Verify otp_expiry not passed

---

## ✅ Summary

### What Was Added:

1. **Database Fields**
   - email_verified
   - otp_code
   - otp_expiry
   - status (with 'pending')

2. **New Page**
   - verify-otp.php

3. **Updated Pages**
   - signup.php (generates OTP)
   - login.php (checks verification)

4. **Features**
   - 6-digit OTP generation
   - Email sending
   - OTP validation
   - Resend functionality
   - Expiry handling
   - Login protection

### Benefits:

✅ **Security** - Verifies real email addresses
✅ **Spam Prevention** - Reduces fake accounts
✅ **User Trust** - Professional verification process
✅ **Email Validation** - Ensures valid emails
✅ **Account Protection** - Prevents unauthorized access

**Result:** Secure, professional email verification system! 🔒📧
