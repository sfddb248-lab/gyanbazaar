# Admin Panel Access Guide

## Direct Admin Login

### URL
```
http://localhost/DigitalKhazana/admin/login.php
```

Or simply visit:
```
http://localhost/DigitalKhazana/admin
```
(Will automatically redirect to login page)

## Features

✅ **Separate Login System**
- No need to login on main website
- Direct access to admin panel
- Only admin/editor roles can login

✅ **Dedicated Interface**
- Beautiful gradient background
- Secure admin-only authentication
- Clean and professional design

✅ **Smart Redirects**
- Already logged in? → Goes to dashboard
- Not logged in? → Shows admin login
- Logout → Returns to admin login page

## Login Credentials

Use any account with role = 'admin' or 'editor' from your database.

Example:
- Email: admin@example.com
- Password: (your admin password)

## Navigation Flow

```
Admin Login Page
    ↓
Admin Dashboard
    ↓
Admin Features (Products, Orders, Users, etc.)
    ↓
Logout → Back to Admin Login
```

## User Website vs Admin Panel

| Feature | User Website | Admin Panel |
|---------|-------------|-------------|
| Login URL | `/login.php` | `/admin/login.php` |
| Access | All users | Admin/Editor only |
| After Logout | Home page | Admin login page |
| Session | User session | Admin session |

## Quick Links

- **Admin Login:** http://localhost/DigitalKhazana/admin/login.php
- **Admin Dashboard:** http://localhost/DigitalKhazana/admin/index.php
- **Main Website:** http://localhost/DigitalKhazana/
- **User Login:** http://localhost/DigitalKhazana/login.php
