# GyanBazaar - InfinityFree Deployment Guide

## Quick Deployment Steps

### 1. Create InfinityFree Account

1. Visit: https://infinityfree.net
2. Click **"Sign Up"** (Free)
3. Verify your email
4. Login to Client Area

### 2. Create Hosting Account

1. Click **"Create Account"**
2. Choose subdomain: `gyanbazaar` (will become `gyanbazaar.infinityfreeapp.com`)
3. Or use your own domain if you have one
4. Click **"Create Account"**
5. Wait 2-5 minutes for activation

### 3. Get Your FTP Credentials

1. Go to **Control Panel**
2. Find **FTP Details**:
   - **FTP Hostname**: `ftpupload.net`
   - **FTP Username**: `epiz_xxxxx` (copy this)
   - **FTP Password**: (set during account creation)
   - **Port**: 21

### 4. Download FileZilla (FTP Client)

1. Download: https://filezilla-project.org/download.php?type=client
2. Install FileZilla
3. Open FileZilla

### 5. Connect to InfinityFree via FTP

In FileZilla:
1. **Host**: `ftpupload.net`
2. **Username**: `epiz_xxxxx` (your username)
3. **Password**: (your password)
4. **Port**: `21`
5. Click **"Quickconnect"**

### 6. Upload Files

**Method A: Direct Upload from Local**
1. In FileZilla, navigate to `/htdocs/` folder (right side)
2. On left side, go to `C:\xampp\htdocs\GyanBazaar`
3. Select all files and folders
4. Right-click → Upload
5. Wait for upload to complete (may take 10-20 minutes)

**Method B: Using Deployment Script**
1. Run `deploy-to-infinityfree.bat`
2. Upload contents of `deploy-package` folder to `/htdocs/`

### 7. Create MySQL Database

1. Go to InfinityFree **Control Panel**
2. Click **"MySQL Databases"**
3. Create new database:
   - Database Name: `gyanbazaar`
   - Click **"Create Database"**
4. Note down:
   - Database Name: `epiz_xxxxx_gyanbazaar`
   - Database User: `epiz_xxxxx`
   - Database Password: (your password)
   - MySQL Hostname: `sqlxxx.infinityfreeapp.com`

### 8. Import Database

1. In Control Panel, click **"phpMyAdmin"**
2. Select your database: `epiz_xxxxx_gyanbazaar`
3. Click **"Import"** tab
4. Choose file: `database.sql` from your local folder
5. Click **"Go"**
6. Wait for import to complete

### 9. Update Configuration Files

**Edit `config/database.php` on server:**
```php
<?php
define('DB_HOST', 'sqlxxx.infinityfreeapp.com'); // Your MySQL hostname
define('DB_USER', 'epiz_xxxxx');                  // Your database user
define('DB_PASS', 'your_password');               // Your database password
define('DB_NAME', 'epiz_xxxxx_gyanbazaar');      // Your database name
?>
```

**Edit `config/config.php` on server:**
```php
<?php
define('SITE_URL', 'https://gyanbazaar.infinityfreeapp.com'); // Your domain
define('SITE_NAME', 'GyanBazaar');
?>
```

### 10. Set File Permissions

In FileZilla:
1. Right-click on `assets/uploads/` folder
2. Select **"File Permissions"**
3. Set to `755`
4. Check **"Recurse into subdirectories"**
5. Click OK

Repeat for:
- `uploads/` folder
- `assets/uploads/courses/videos/` folder
- `assets/uploads/profiles/` folder

### 11. Test Your Website

Visit your site:
- **Frontend**: `https://gyanbazaar.infinityfreeapp.com`
- **Admin Panel**: `https://gyanbazaar.infinityfreeapp.com/admin`

**Admin Login:**
- Email: `admin@gyanbazaar.com`
- Password: `admin123`

## Troubleshooting

### Database Connection Error
- Verify database credentials in `config/database.php`
- Check MySQL hostname (it's NOT `localhost` on InfinityFree)
- Ensure database was imported successfully

### 404 Error / Page Not Found
- Check if files are in `/htdocs/` folder (not in a subfolder)
- Verify `.htaccess` file was uploaded

### File Upload Not Working
- Check folder permissions (755 for folders, 644 for files)
- InfinityFree has file size limits (10MB per file)

### Slow Performance
- InfinityFree is free, so expect some slowness
- Consider upgrading to paid hosting for better performance

### Large Files (Videos/PDFs) Not Working
- InfinityFree has storage limits
- Consider using external storage (Google Drive, Cloudinary) for large files

## Updating Your Site

When you make changes locally:

1. Commit to GitHub:
   ```bash
   git add .
   git commit -m "Your changes"
   git push origin main
   ```

2. Run deployment script:
   ```bash
   deploy-to-infinityfree.bat
   ```

3. Upload changed files via FileZilla

## Alternative: Use Git on Server (Advanced)

Some hosting providers support SSH/Git. InfinityFree doesn't, but if you upgrade to paid hosting:

```bash
# SSH into server
ssh user@yourserver.com

# Clone repository
cd /public_html
git clone https://github.com/nitin9917/gyanbazaar.git .

# Pull updates
git pull origin main
```

## Need Help?

- InfinityFree Forum: https://forum.infinityfree.net
- GitHub Issues: https://github.com/nitin9917/gyanbazaar/issues

---

**Your site will be live at:** `https://gyanbazaar.infinityfreeapp.com`

Good luck! 🚀
