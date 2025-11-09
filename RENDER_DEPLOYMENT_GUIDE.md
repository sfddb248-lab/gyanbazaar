# 🚀 Deploy GyanBazaar to Render.com

## Why Render.com?
- ✅ **Free tier** available
- ✅ **Supports PHP** with Docker
- ✅ **GitHub integration** - auto-deploy
- ✅ **PostgreSQL/MySQL** database
- ✅ **Custom domain** support
- ✅ **Free SSL** (HTTPS)
- ✅ **Easy to use**

---

## 📋 Step-by-Step Deployment

### Step 1: Push Code to GitHub

```bash
# Navigate to your project
cd C:\xampp\htdocs\GyanBazaar

# Initialize git (if not already)
git init

# Add all files
git add .

# Commit
git commit -m "Initial commit - GyanBazaar for Render"

# Create repository on GitHub
# Go to: https://github.com/new
# Create a new repository named: gyanbazaar

# Push to GitHub
git remote add origin https://github.com/YOUR_USERNAME/gyanbazaar.git
git branch -M main
git push -u origin main
```

### Step 2: Sign Up for Render

1. Go to: https://render.com/
2. Click "Get Started"
3. Sign up with GitHub
4. Authorize Render to access your repositories

### Step 3: Create New Web Service

1. Click "New +" → "Web Service"
2. Connect your GitHub repository: `gyanbazaar`
3. Render will detect the configuration

### Step 4: Configure Web Service

**Basic Settings:**
- **Name**: gyanbazaar
- **Environment**: Docker
- **Region**: Choose closest to you
- **Branch**: main
- **Plan**: Free

**Build Settings:**
- Render will use the `Dockerfile` automatically

**Environment Variables:**
(Render will auto-populate from `render.yaml`)

### Step 5: Create Database

1. In Render dashboard
2. Click "New +" → "PostgreSQL" or "MySQL"
3. **Name**: gyanbazaar-db
4. **Plan**: Free
5. Click "Create Database"

**Note:** Render's free tier includes PostgreSQL. For MySQL, you might need to use an external service like PlanetScale (free).

### Step 6: Connect Database

1. Go to your Web Service
2. Click "Environment"
3. Add these variables (from your database):
   - `DB_HOST`
   - `DB_USER`
   - `DB_PASS`
   - `DB_NAME`
   - `DB_PORT`

### Step 7: Deploy!

1. Click "Manual Deploy" → "Deploy latest commit"
2. Wait 3-5 minutes for build
3. Your site is live!
4. URL: `https://gyanbazaar.onrender.com`

---

## 🗄️ Database Setup

### Option A: Use PostgreSQL (Render's Free Database)

Render provides free PostgreSQL. You'll need to:
1. Convert your MySQL database to PostgreSQL
2. Or use a tool like `pgloader`

### Option B: Use External MySQL (Recommended)

Use **PlanetScale** (free MySQL):
1. Sign up at: https://planetscale.com/
2. Create database
3. Get connection details
4. Add to Render environment variables

### Import Your Data

```bash
# Connect to database
mysql -h [HOST] -u [USER] -p [DATABASE] < database.sql
```

---

## 🌐 Custom Domain

1. In Render dashboard
2. Go to your service → Settings
3. Click "Custom Domain"
4. Add your domain
5. Update DNS records (Render provides instructions)
6. SSL automatically configured!

---

## 📁 Files Created for Render

✅ `render.yaml` - Render configuration
✅ `Dockerfile` - Container configuration
✅ `config/database.render.php` - Database config
✅ `.gitignore` - Files to ignore
✅ `composer.json` - PHP dependencies

---

## 🔧 Environment Variables

Add these in Render dashboard:

```
DB_HOST=your-db-host
DB_USER=your-db-user
DB_PASS=your-db-password
DB_NAME=gyanbazaar
DB_PORT=3306
SITE_URL=https://gyanbazaar.onrender.com
```

---

## 💰 Pricing

**Free Tier Includes:**
- 750 hours/month
- Automatic SSL
- GitHub integration
- Custom domains
- PostgreSQL database (1GB)

**Limitations:**
- Spins down after 15 min of inactivity
- Slower cold starts
- 100GB bandwidth/month

---

## 🎉 Advantages

✅ Free tier with good limits
✅ GitHub auto-deploy
✅ Free SSL/HTTPS
✅ Custom domains
✅ Better than InfinityFree
✅ Professional hosting
✅ No ads or restrictions

---

## 🚀 Quick Start Commands

```bash
# Clone your repo
git clone https://github.com/YOUR_USERNAME/gyanbazaar.git
cd gyanbazaar

# Make changes
# ... edit files ...

# Push to deploy
git add .
git commit -m "Update"
git push

# Render auto-deploys!
```

---

## 📊 Monitoring

Render provides:
- Real-time logs
- Metrics dashboard
- Deploy history
- Health checks

---

## 🔄 Auto-Deploy

Every time you push to GitHub:
1. Render detects the push
2. Builds your Docker container
3. Deploys automatically
4. Your site updates!

---

## Next Steps

1. Push code to GitHub
2. Sign up for Render
3. Create Web Service
4. Add database
5. Deploy!

Your site will be live at: `https://gyanbazaar.onrender.com`
