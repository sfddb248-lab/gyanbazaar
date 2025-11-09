# 🚀 Simple Manual Deployment (Works 100%)

## What You Need (5 minutes to get):

### 1. InfinityFree Account
- Sign up: https://infinityfree.net/sign-up
- Create hosting account
- Choose subdomain: `gyanbazaar.infinityfreeapp.com`

### 2. FileZilla (Free FTP Client)
- Download: https://filezilla-project.org/download.php?type=client
- Install it

---

## 📋 Step-by-Step Deployment:

### **Step 1: Get Your FTP Details** (2 min)

Login to InfinityFree → Control Panel → Find:

```
FTP Hostname: ftpupload.net
FTP Username: epiz_xxxxx (COPY THIS!)
FTP Password: (your password)
Port: 21
```

---

### **Step 2: Create Database** (2 min)

In Control Panel → MySQL Databases:

1. Database name: `gyanbazaar`
2. Click "Create Database"
3. **COPY THESE 4 DETAILS:**

```
Database Name: epiz_xxxxx_gyanbazaar
Database User: epiz_xxxxx
Database Password: (your password)
MySQL Hostname: sqlxxx.infinityfreeapp.com
```

---

### **Step 3: Connect FileZilla** (1 min)

Open FileZilla, enter at top:

```
Host: ftpupload.net
Username: epiz_xxxxx (your username)
Password: (your password)
Port: 21
```

Click **"Quickconnect"**

---

### **Step 4: Upload Files** (5 min)

**In FileZilla:**

**Left side (Local):**
- Navigate to: `C:\xampp\htdocs\GyanBazaar`

**Right side (Remote):**
- Navigate to: `/htdocs/`
- **DELETE everything in /htdocs/ first!**

**Upload:**
1. Select ALL files on left side (Ctrl+A)
2. Right-click → **"Upload"**
3. Wait 5-10 minutes

---

### **Step 5: Update Config Files** (3 min)

**In FileZilla, right side:**

**Edit `config/database.php`:**
1. Right-click → View/Edit
2. Change these lines:

```php
define('DB_HOST', 'sqlxxx.infinityfreeapp.com');  // ← YOUR MySQL hostname
define('DB_USER', 'epiz_xxxxx');                   // ← YOUR database user
define('DB_PASS', 'your_password');                // ← YOUR password
define('DB_NAME', 'epiz_xxxxx_gyanbazaar');       // ← YOUR database name
```

3. Save (Ctrl+S)
4. FileZilla will ask to upload → Click **"Yes"**

**Edit `config/config.php`:**
1. Right-click → View/Edit
2. Change:

```php
define('SITE_URL', 'https://gyanbazaar.infinityfreeapp.com'); // ← YOUR URL
```

3. Save and upload

---

### **Step 6: Import Database** (2 min)

**In InfinityFree Control Panel:**

1. Click **"phpMyAdmin"**
2. Select database: `epiz_xxxxx_gyanbazaar`
3. Click **"Import"** tab
4. Click **"Choose File"**
5. Select: `C:\xampp\htdocs\GyanBazaar\database.sql`
6. Click **"Go"** at bottom
7. Wait for success message

---

### **Step 7: Test Your Site!** 🎉

**Visit:**
```
https://gyanbazaar.infinityfreeapp.com
```

**Admin Panel:**
```
https://gyanbazaar.infinityfreeapp.com/admin
```

**Login:**
```
Email: admin@gyanbazaar.com
Password: admin123
```

---

## ✅ Checklist:

- [ ] InfinityFree account created
- [ ] Hosting account activated
- [ ] Database created (4 details copied)
- [ ] FileZilla installed
- [ ] Connected to FTP
- [ ] All files uploaded to /htdocs/
- [ ] config/database.php updated
- [ ] config/config.php updated
- [ ] database.sql imported
- [ ] Website tested and working!

---

## 🔧 Troubleshooting:

### "Can't connect to FTP"
- Wait 5-10 minutes after account creation
- Check username/password (no spaces)
- Try again

### "Database connection error"
- Check config/database.php has correct details
- MySQL hostname is NOT "localhost"
- Make sure database was imported

### "Page not found"
- Files must be in /htdocs/ (not in subfolder)
- Check .htaccess was uploaded

### "Can't upload files"
- Some files might be too large
- Upload in batches
- Skip video files if needed

---

## 📱 Your Live URLs:

**Frontend:**
```
https://gyanbazaar.infinityfreeapp.com
```

**Admin:**
```
https://gyanbazaar.infinityfreeapp.com/admin
```

**Share with anyone!** 🌍

---

## 🔄 How to Update Later:

1. Make changes locally
2. Commit to GitHub:
   ```bash
   git add .
   git commit -m "Updates"
   git push origin main
   ```
3. Upload changed files via FileZilla

---

**Total Time: ~15 minutes**

**Need help? Check the files or ask!**
