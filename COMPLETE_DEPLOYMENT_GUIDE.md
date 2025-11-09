# Complete Automatic Deployment Guide

## 🚀 One-Click Deployment

### Method 1: Fully Automatic (Recommended)

**Step 1:** Double-click `ONE_CLICK_DEPLOY_COMPLETE.bat`

**Step 2:** Enter your credentials when prompted:
- FTP Username: `epiz_40371517`
- FTP Password: (your InfinityFree password)
- Database Host: `sql301.infinityfreeapp.com`
- Database Username: `if0_40371517`
- Database Password: (your database password)
- Database Name: `if0_40371517_gyanbazaar`

**Step 3:** Wait for upload to complete (5-10 minutes)

**Step 4:** Visit https://gyanbazaar.infinityfree.me/

**Done!** Your site is live! 🎉

---

## 📋 What the Script Does

### Automatic Process:
1. ✅ Updates `config/database.php` with your credentials
2. ✅ Tests FTP connection
3. ✅ Creates necessary directories on server
4. ✅ Uploads all files from `deploy-final` folder
5. ✅ Uploads critical files first (config, .htaccess, index.php)
6. ✅ Skips unnecessary files (.md, .txt, .bat)
7. ✅ Shows progress during upload
8. ✅ Opens your site in browser when done

---

## 🔧 Alternative Methods

### Method 2: Semi-Automatic (If Python not available)

**Step 1:** Get your credentials from InfinityFree
- Go to https://app.infinityfree.com/
- Find "MySQL Databases" section
- Copy all credentials

**Step 2:** Upload `check-credentials.php`
- Upload `deploy-final/check-credentials.php` to your hosting
- Visit: https://gyanbazaar.infinityfree.me/check-credentials.php
- Enter credentials and test

**Step 3:** Upload files manually
- Use InfinityFree File Manager
- Upload all files from `deploy-final` folder to `htdocs`

### Method 3: Manual Upload (Most Reliable)

Follow the guide in: `VISUAL_FIX_GUIDE.txt`

---

## 🔑 Where to Get Credentials

### FTP Credentials:
1. Go to https://app.infinityfree.com/
2. Click on your account
3. Find "FTP Details" section
4. Copy:
   - FTP Hostname: `ftpupload.net`
   - FTP Username: `epiz_40371517`
   - FTP Password: (your account password)

### Database Credentials:
1. In InfinityFree control panel
2. Find "MySQL Databases" section
3. Copy:
   - MySQL Hostname: `sql301.infinityfreeapp.com`
   - MySQL Username: `if0_40371517`
   - MySQL Password: (your database password)
   - MySQL Database: `if0_40371517_gyanbazaar`

**Note:** If database doesn't exist, create it first!

---

## ⚠️ Troubleshooting

### Issue 1: Python Not Found
**Solution:**
- Download from: https://www.python.org/downloads/
- Install with "Add Python to PATH" checked
- Restart the script

### Issue 2: FTP Connection Failed
**Solutions:**
- Check FTP password is correct
- Try using InfinityFree File Manager instead
- Check if your IP is blocked (use VPN)

### Issue 3: Database Connection Failed
**Solutions:**
- Verify database password
- Check if database exists (create if needed)
- Import database.sql via phpMyAdmin

### Issue 4: Upload Timeout
**Solutions:**
- Use InfinityFree File Manager for large files
- Upload in smaller batches
- Check internet connection

---

## 📁 Files Being Uploaded

### Critical Files (Uploaded First):
- `config/database.php` - Database configuration
- `config/config.php` - Site configuration
- `.htaccess` - Server configuration
- `index.php` - Homepage

### All Other Files:
- `includes/` - PHP functions
- `admin/` - Admin panel
- `assets/` - CSS, JS, images
- All PHP pages (products.php, login.php, etc.)

### Files NOT Uploaded:
- Documentation (.md, .txt files)
- Scripts (.bat, .py files)
- Development files

---

## ✅ After Deployment

### Step 1: Verify Site Works
Visit: https://gyanbazaar.infinityfree.me/

**Expected:** Homepage loads with courses

### Step 2: Test Database Connection
Visit: https://gyanbazaar.infinityfree.me/check-credentials.php

**Expected:** All tests pass

### Step 3: Import Database (If Needed)
If tables are missing:
1. Go to InfinityFree Control Panel
2. Click "phpMyAdmin"
3. Select your database
4. Click "Import"
5. Upload `database.sql`
6. Click "Go"

### Step 4: Clean Up
Delete these test files:
- `check-credentials.php`
- `test-connection.php`
- `check-connection.php`

### Step 5: Test All Features
- ✅ Homepage
- ✅ Products page
- ✅ Login/Signup
- ✅ Admin panel
- ✅ Affiliate system

---

## 🎯 Quick Reference

### One Command Deployment:
```
ONE_CLICK_DEPLOY_COMPLETE.bat
```

### Manual Upload Location:
```
Local: deploy-final/*
Remote: htdocs/*
```

### Test URLs:
```
Site: https://gyanbazaar.infinityfree.me/
Test: https://gyanbazaar.infinityfree.me/check-credentials.php
Admin: https://gyanbazaar.infinityfree.me/admin/
```

### Support Files:
- `VISUAL_FIX_GUIDE.txt` - Visual step-by-step guide
- `FIX_DATABASE_ERROR.txt` - Database troubleshooting
- `GET_CORRECT_CREDENTIALS.md` - How to get credentials

---

## 📞 Need Help?

### If automatic deployment fails:
1. Read error message carefully
2. Check credentials are correct
3. Try manual upload method
4. Use check-credentials.php tool

### If site shows errors:
1. Visit check-credentials.php
2. Verify database connection
3. Import database.sql if needed
4. Check error logs in InfinityFree

### If database connection fails:
1. Verify credentials in InfinityFree panel
2. Create database if it doesn't exist
3. Reset database password if needed
4. Update config/database.php

---

## 🎉 Success Checklist

- [ ] Ran ONE_CLICK_DEPLOY_COMPLETE.bat
- [ ] Entered correct credentials
- [ ] Upload completed successfully
- [ ] Site loads at https://gyanbazaar.infinityfree.me/
- [ ] Database connection works
- [ ] All tables exist (or imported database.sql)
- [ ] Deleted test files
- [ ] Tested login/signup
- [ ] Tested admin panel
- [ ] Site is fully functional

---

## 📊 Deployment Timeline

| Step | Time | Description |
|------|------|-------------|
| 1 | 1 min | Enter credentials |
| 2 | 1 min | Update config files |
| 3 | 1 min | Test FTP connection |
| 4 | 5-10 min | Upload all files |
| 5 | 1 min | Verify site works |
| **Total** | **10-15 min** | **Complete deployment** |

---

## 🔒 Security Notes

- Credentials are NOT saved in plain text
- Test files should be deleted after deployment
- Change default admin password after first login
- Keep database password secure
- Use HTTPS (already configured)

---

**Your site will be live in 10-15 minutes!** 🚀
