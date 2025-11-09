# GyanBazaar - Automatic PowerShell Deployment Script
# For Windows PowerShell

Write-Host "========================================" -ForegroundColor Green
Write-Host "  GyanBazaar - Automatic Deployment" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
Write-Host ""

# Function to download WinSCP if not installed
function Install-WinSCP {
    if (!(Test-Path "winscp\WinSCPnet.dll")) {
        Write-Host "[*] Downloading WinSCP..." -ForegroundColor Yellow
        $url = "https://winscp.net/download/WinSCP-5.21.7-Automation.zip"
        $output = "winscp.zip"
        
        try {
            Invoke-WebRequest -Uri $url -OutFile $output
            Expand-Archive -Path $output -DestinationPath "winscp" -Force
            Remove-Item $output
            Write-Host "[OK] WinSCP installed" -ForegroundColor Green
        } catch {
            Write-Host "[!] Failed to download WinSCP" -ForegroundColor Red
            Write-Host "Please download manually from: https://winscp.net/eng/download.php" -ForegroundColor Yellow
            exit 1
        }
    }
}

# Install WinSCP
Install-WinSCP

# Load WinSCP .NET assembly
Add-Type -Path "winscp\WinSCPnet.dll"

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  Step 1: InfinityFree Credentials" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

$ftpHost = Read-Host "FTP Host (default: ftpupload.net)"
if ([string]::IsNullOrWhiteSpace($ftpHost)) { $ftpHost = "ftpupload.net" }

$ftpUser = Read-Host "FTP Username (epiz_xxxxx)"
$ftpPassSecure = Read-Host "FTP Password" -AsSecureString
$ftpPass = [Runtime.InteropServices.Marshal]::PtrToStringAuto([Runtime.InteropServices.Marshal]::SecureStringToBSTR($ftpPassSecure))

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  Step 2: Database Configuration" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

$dbHost = Read-Host "MySQL Hostname (sqlxxx.infinityfreeapp.com)"
$dbUser = Read-Host "Database User (default: $ftpUser)"
if ([string]::IsNullOrWhiteSpace($dbUser)) { $dbUser = $ftpUser }

$dbPassSecure = Read-Host "Database Password (default: same as FTP)" -AsSecureString
$dbPassStr = [Runtime.InteropServices.Marshal]::PtrToStringAuto([Runtime.InteropServices.Marshal]::SecureStringToBSTR($dbPassSecure))
if ([string]::IsNullOrWhiteSpace($dbPassStr)) { $dbPass = $ftpPass } else { $dbPass = $dbPassStr }

$dbName = Read-Host "Database Name (epiz_xxxxx_gyanbazaar)"
$siteUrl = Read-Host "Your Site URL (https://yoursite.infinityfreeapp.com)"

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  Step 3: Preparing Files" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Pull from GitHub
Write-Host "[1/6] Pulling latest code from GitHub..." -ForegroundColor Yellow
try {
    git pull origin main 2>&1 | Out-Null
    Write-Host "[OK] Done" -ForegroundColor Green
} catch {
    Write-Host "[!] Warning: Could not pull from GitHub. Using local files." -ForegroundColor Yellow
}

# Create deployment folder
Write-Host "[2/6] Creating deployment package..." -ForegroundColor Yellow
if (Test-Path "deploy-temp") { Remove-Item -Recurse -Force "deploy-temp" }
New-Item -ItemType Directory -Path "deploy-temp" | Out-Null

# Copy files
Copy-Item -Path ".\*" -Destination "deploy-temp" -Recurse -Exclude @(".git", ".vscode", "deploy-temp", "winscp", "*.bat", "*.md", ".gitignore", ".gitattributes")

Write-Host "[OK] Done" -ForegroundColor Green

# Update config files
Write-Host "[3/6] Updating configuration files..." -ForegroundColor Yellow

$databaseConfig = @"
<?php
define('DB_HOST', '$dbHost');
define('DB_USER', '$dbUser');
define('DB_PASS', '$dbPass');
define('DB_NAME', '$dbName');

// Create connection
`$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if (!`$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>
"@

$siteConfig = @"
<?php
define('SITE_URL', '$siteUrl');
define('SITE_NAME', 'GyanBazaar');
define('ADMIN_EMAIL', 'admin@gyanbazaar.com');
?>
"@

Set-Content -Path "deploy-temp\config\database.php" -Value $databaseConfig
Set-Content -Path "deploy-temp\config\config.php" -Value $siteConfig

Write-Host "[OK] Done" -ForegroundColor Green

# Upload via WinSCP
Write-Host "[4/6] Connecting to FTP server..." -ForegroundColor Yellow

try {
    $sessionOptions = New-Object WinSCP.SessionOptions -Property @{
        Protocol = [WinSCP.Protocol]::Ftp
        HostName = $ftpHost
        UserName = $ftpUser
        Password = $ftpPass
    }

    $session = New-Object WinSCP.Session
    
    Write-Host "[5/6] Uploading files to InfinityFree..." -ForegroundColor Yellow
    Write-Host "This may take 5-10 minutes..." -ForegroundColor Yellow
    
    $session.Open($sessionOptions)
    
    $transferOptions = New-Object WinSCP.TransferOptions
    $transferOptions.TransferMode = [WinSCP.TransferMode]::Binary
    
    $synchronizationResult = $session.SynchronizeDirectories(
        [WinSCP.SynchronizationMode]::Remote,
        "deploy-temp",
        "/htdocs",
        $False,
        $False,
        [WinSCP.SynchronizationCriteria]::Time,
        $transferOptions
    )
    
    $synchronizationResult.Check()
    
    Write-Host "[OK] Upload complete!" -ForegroundColor Green
    
} catch {
    Write-Host ""
    Write-Host "[!] Upload failed!" -ForegroundColor Red
    Write-Host $_.Exception.Message -ForegroundColor Red
    Write-Host ""
    Write-Host "Common issues:" -ForegroundColor Yellow
    Write-Host "- Wrong FTP credentials" -ForegroundColor Yellow
    Write-Host "- FTP server not ready (wait 5 minutes after account creation)" -ForegroundColor Yellow
    Write-Host "- Firewall blocking connection" -ForegroundColor Yellow
    exit 1
} finally {
    if ($session) { $session.Dispose() }
}

# Cleanup
Write-Host "[6/6] Cleaning up..." -ForegroundColor Yellow
Remove-Item -Recurse -Force "deploy-temp"
Write-Host "[OK] Done" -ForegroundColor Green

Write-Host ""
Write-Host "========================================" -ForegroundColor Green
Write-Host "  Deployment Complete!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
Write-Host ""
Write-Host "Your website is now LIVE at:" -ForegroundColor Cyan
Write-Host $siteUrl -ForegroundColor White
Write-Host ""
Write-Host "Admin Panel:" -ForegroundColor Cyan
Write-Host "$siteUrl/admin" -ForegroundColor White
Write-Host ""
Write-Host "Admin Login:" -ForegroundColor Cyan
Write-Host "Email: admin@gyanbazaar.com" -ForegroundColor White
Write-Host "Password: admin123" -ForegroundColor White
Write-Host ""
Write-Host "========================================" -ForegroundColor Yellow
Write-Host "  IMPORTANT: Next Steps" -ForegroundColor Yellow
Write-Host "========================================" -ForegroundColor Yellow
Write-Host ""
Write-Host "1. Import Database:" -ForegroundColor Cyan
Write-Host "   - Go to InfinityFree Control Panel"
Write-Host "   - Click 'phpMyAdmin'"
Write-Host "   - Select database: $dbName"
Write-Host "   - Click 'Import' tab"
Write-Host "   - Upload: database.sql"
Write-Host "   - Click 'Go'"
Write-Host ""
Write-Host "2. Test your website:" -ForegroundColor Cyan
Write-Host "   - Visit: $siteUrl"
Write-Host "   - Login to admin panel"
Write-Host "   - Change admin password"
Write-Host ""
Write-Host "========================================" -ForegroundColor Green
Write-Host ""

Read-Host "Press Enter to exit"
