# 🚀 Quick Start - Make GyanBazaar Live in 15 Minutes

## What You Need
- ✅ GitHub repository (already done)
- ✅ InfinityFree account (free)
- ✅ FileZilla FTP client (free)

---

## Step-by-Step (Copy & Paste Ready)

### 1️⃣ Create InfinityFree Account (2 minutes)

**Go to:** https://infinityfree.net/sign-up

**Fill in:**
- Email: `your-email@gmail.com`
- Password: `YourPassword123`
- Click **"Sign Up"**
- Verify email

---

### 2️⃣ Create Hosting (3 minutes)

**After login:**
1. Click **"Create Account"**
2. **Subdomain**: `gyanbazaar` 
3. Click **"Create Account"**
4. ⏳ Wait 2-5 minutes for activation

**Your site will be:** `https://gyanbazaar.infinityfreeapp.com`

---

### 3️⃣ Get FTP Details (1 minute)

**In Control Panel, find:**
```
FTP Hostname: ftpupload.net
FTP Username: epiz_xxxxx (copy this!)
FTP Password: (your password)
Port: 21
```

**📝 Write these down!**

---

### 4️⃣ Download FileZilla (2 minutes)

**Download:** https://filezilla-project.org/download.php?type=client

**Install and open FileZilla**

---

### 5️⃣ Connect & Upload (5 minutes)

**In FileZilla, enter at top:**
```
Host: ftpupload.net
Username: epiz_xxxxx (your username)
Password: (your password)
Port: 21
```
Click **"Quickconnect"**

**Upload files:**
1. Right side: Navigate to `/htdocs/` folder
2. Left side: Go to `C:\xampp\htdocs\GyanBazaar`
3. Select ALL files and folders (Ctrl+A)
4. Right-click → **"Upload"**
5. ⏳ Wait 5-10 minutes

---

### 6️⃣ Create Database (2 minutes)

**In InfinityFree Control Panel:**
1. Click **"MySQL Databases"**
2. Database name: `gyanbazaar`
3. Click **"Create Database"**

**📝 Copy these details:**
```
Database Name: epiz_xxxxx_gyanbazaar
Database User: epiz_xxxxx
Database Password: (your password)
MySQL Hostname: sqlxxx.infinityfreeapp.com
```

---

### 7️⃣ Import Database (2 minutes)

**In Control Panel:**
1. Click **"phpMyAdmin"**
2. Select database: `epiz_xxxxx_gyanbazaar`
3. Click **"Import"** tab
4. Choose: `C:\xampp\htdocs\GyanBazaar\database.sql`
5. Click **"Go"**
6. ✅ Success message

---

### 8️⃣ Update Config Files (3 minutes)

**In FileZilla, edit `config/database.php`:**

Right-click → View/Edit → Change:
```php
<?php
define('DB_HOST', 'sqlxxx.infinityfreeapp.com');  // ← Change this
define('DB_USER', 'epiz_xxxxx');                   // ← Change this
define('DB_PASS', 'your_password');                // ← Change this
define('DB_NAME', 'epiz_xxxxx_gyanbazaar');       // ← Change this
?>
```

**Save and upload when prompted**

**Edit `config/config.php`:**
```php
<?php
define('SITE_URL', 'https://gyanbazaar.infinityfreeapp.com'); // ← Change this
define('SITE_NAME', 'GyanBazaar');
?>
```

**Save and upload**

---

### 9️⃣ Test Your Site! 🎉

**Visit:**
- **Frontend:** https://gyanbazaar.infinityfreeapp.com
- **Admin:** https://gyanbazaar.infinityfreeapp.com/admin

**Admin Login:**
```
Email: admin@gyanbazaar.com
Password: admin123
```

---

## ✅ Done! Your Site is LIVE!

Share your link: `https://gyanbazaar.infinityfreeapp.com`

---

## 🔄 How to Update Later

**When you make changes:**

1. **Commit to GitHub:**
   ```bash
   git add .
   git commit -m "Updated features"
   git push origin main
   ```

2. **Upload changed files via FileZilla**

---

## ⚠️ Common Issues

### "Database connection error"
- Check `config/database.php` has correct details
- MySQL hostname is NOT `localhost`

### "Page not found"
- Files must be in `/htdocs/` (not in subfolder)
- Check `.htaccess` was uploaded

### "Can't upload files"
- Set folder permissions to 755:
  - Right-click folder → File Permissions → 755

---

## 🆙 Want Better Performance?

InfinityFree is free but has limits. Upgrade to:
- **Hostinger** ($2.99/month) - Fast & reliable
- **Namecheap** ($2.88/month) - Good support
- **DigitalOcean** ($6/month) - Full control

---

## 📞 Need Help?

- Check: `DEPLOYMENT_GUIDE.md` (detailed guide)
- InfinityFree Forum: https://forum.infinityfree.net
- GitHub: https://github.com/nitin9917/gyanbazaar

---

**🎊 Congratulations! Your GyanBazaar is now accessible from anywhere!**
