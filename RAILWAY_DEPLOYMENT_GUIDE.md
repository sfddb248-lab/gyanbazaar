# 🚀 Deploy GyanBazaar to Railway.app

## Why Railway?
- ✅ **Free tier** with $5 credit/month
- ✅ **Supports PHP** natively
- ✅ **GitHub integration** - auto-deploy on push
- ✅ **MySQL database** included
- ✅ **Custom domain** support
- ✅ **HTTPS** automatically

---

## 📋 Prerequisites

1. GitHub account
2. Railway account (sign up with GitHub)
3. Your code ready

---

## 🎯 Step-by-Step Deployment

### Step 1: Push Code to GitHub

```bash
# Initialize git (if not already)
cd C:\xampp\htdocs\GyanBazaar
git init

# Add all files
git add .

# Commit
git commit -m "Initial commit - GyanBazaar"

# Create repository on GitHub (github.com/new)
# Then push
git remote add origin https://github.com/YOUR_USERNAME/gyanbazaar.git
git branch -M main
git push -u origin main
```

### Step 2: Sign Up for Railway

1. Go to: https://railway.app/
2. Click "Start a New Project"
3. Sign in with GitHub
4. Authorize Railway

### Step 3: Create New Project

1. Click "New Project"
2. Select "Deploy from GitHub repo"
3. Choose your `gyanbazaar` repository
4. Railway will detect it's a PHP project

### Step 4: Add MySQL Database

1. In your Railway project
2. Click "New" → "Database" → "Add MySQL"
3. Railway creates a MySQL database
4. Note the connection details

### Step 5: Configure Environment Variables

In Railway dashboard, add these variables:

```
DB_HOST=mysql.railway.internal
DB_USER=(from Railway MySQL)
DB_PASS=(from Railway MySQL)
DB_NAME=(from Railway MySQL)
DB_PORT=3306
```

### Step 6: Create Railway Configuration

Railway needs these files in your project root.

### Step 7: Deploy!

1. Railway automatically deploys
2. Wait 2-3 minutes
3. Your site is live!
4. Railway gives you a URL like: `gyanbazaar.up.railway.app`

---

## 📁 Required Files for Railway

I'll create these files for you:
- `railway.json` - Railway configuration
- `Procfile` - Start command
- `.railwayignore` - Files to ignore
- `composer.json` - PHP dependencies

---

## 🔧 Database Setup

After deployment:

1. Access Railway MySQL via phpMyAdmin or CLI
2. Import your `database.sql`
3. Or use Railway's built-in database tools

---

## 🌐 Custom Domain

1. In Railway dashboard
2. Go to Settings → Domains
3. Add your custom domain
4. Update DNS records
5. HTTPS automatically configured!

---

## 💰 Pricing

- **Free tier**: $5 credit/month
- Enough for small to medium traffic
- Upgrade if needed

---

## 🎉 Advantages

✅ GitHub integration - push to deploy
✅ Automatic HTTPS
✅ Free MySQL database
✅ No server management
✅ Automatic scaling
✅ Better than InfinityFree!

---

## Next Steps

I'll create all the necessary configuration files for Railway deployment!
