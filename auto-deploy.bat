@echo off
setlocal enabledelayedexpansion
color 0A

echo ========================================
echo   GyanBazaar - Automatic Deployment
echo ========================================
echo.

REM Check if WinSCP is installed
where winscp.com >nul 2>nul
if %errorlevel% neq 0 (
    echo [!] WinSCP not found. Installing...
    echo.
    echo Downloading WinSCP...
    powershell -Command "& {Invoke-WebRequest -Uri 'https://winscp.net/download/WinSCP-5.21.7-Portable.zip' -OutFile 'winscp.zip'}"
    powershell -Command "& {Expand-Archive -Path 'winscp.zip' -DestinationPath 'winscp' -Force}"
    del winscp.zip
    set WINSCP_PATH=winscp\WinSCP.com
) else (
    set WINSCP_PATH=winscp.com
)

echo.
echo ========================================
echo   Step 1: InfinityFree Credentials
echo ========================================
echo.
echo Please enter your InfinityFree FTP details:
echo (You can find these in your InfinityFree Control Panel)
echo.

set /p FTP_HOST="FTP Host (default: ftpupload.net): "
if "%FTP_HOST%"=="" set FTP_HOST=ftpupload.net

set /p FTP_USER="FTP Username (epiz_xxxxx): "
set /p FTP_PASS="FTP Password: "

echo.
echo ========================================
echo   Step 2: Database Configuration
echo ========================================
echo.
echo Please enter your InfinityFree MySQL details:
echo.

set /p DB_HOST="MySQL Hostname (sqlxxx.infinityfreeapp.com): "
set /p DB_USER="Database User (usually same as FTP user): "
if "%DB_USER%"=="" set DB_USER=%FTP_USER%

set /p DB_PASS="Database Password (usually same as FTP pass): "
if "%DB_PASS%"=="" set DB_PASS=%FTP_PASS%

set /p DB_NAME="Database Name (epiz_xxxxx_gyanbazaar): "

set /p SITE_URL="Your Site URL (https://yoursite.infinityfreeapp.com): "

echo.
echo ========================================
echo   Step 3: Preparing Files
echo ========================================
echo.

REM Pull latest from GitHub
echo [1/6] Pulling latest code from GitHub...
git pull origin main
if errorlevel 1 (
    echo [!] Warning: Could not pull from GitHub. Using local files.
)
echo [OK] Done
echo.

REM Create temp deployment folder
echo [2/6] Creating deployment package...
if exist deploy-temp rmdir /s /q deploy-temp
mkdir deploy-temp

REM Copy all files
xcopy /E /I /Y /Q . deploy-temp >nul 2>&1

REM Remove unnecessary files
cd deploy-temp
rmdir /s /q .git 2>nul
rmdir /s /q .vscode 2>nul
rmdir /s /q deploy-temp 2>nul
rmdir /s /q winscp 2>nul
del /q .gitignore .gitattributes 2>nul
del /q *.bat *.md deploy-exclude.txt 2>nul
cd ..

echo [OK] Done
echo.

REM Update config files
echo [3/6] Updating configuration files...

REM Update database.php
(
echo ^<?php
echo define^('DB_HOST', '%DB_HOST%'^);
echo define^('DB_USER', '%DB_USER%'^);
echo define^('DB_PASS', '%DB_PASS%'^);
echo define^('DB_NAME', '%DB_NAME%'^);
echo.
echo // Create connection
echo $conn = mysqli_connect^(DB_HOST, DB_USER, DB_PASS, DB_NAME^);
echo.
echo // Check connection
echo if ^(!$conn^) {
echo     die^("Connection failed: " . mysqli_connect_error^(^)^);
echo }
echo ?^>
) > deploy-temp\config\database.php

REM Update config.php
(
echo ^<?php
echo define^('SITE_URL', '%SITE_URL%'^);
echo define^('SITE_NAME', 'GyanBazaar'^);
echo define^('ADMIN_EMAIL', 'admin@gyanbazaar.com'^);
echo ?^>
) > deploy-temp\config\config.php

echo [OK] Done
echo.

REM Create WinSCP script
echo [4/6] Creating FTP upload script...

(
echo option batch abort
echo option confirm off
echo open ftp://%FTP_USER%:%FTP_PASS%@%FTP_HOST%/
echo cd /htdocs
echo lcd deploy-temp
echo synchronize remote -delete
echo close
echo exit
) > ftp-script.txt

echo [OK] Done
echo.

REM Upload files
echo [5/6] Uploading files to InfinityFree...
echo This may take 5-10 minutes depending on your internet speed...
echo.

%WINSCP_PATH% /script=ftp-script.txt /log=upload.log

if errorlevel 1 (
    echo.
    echo [!] Upload failed! Check upload.log for details.
    echo.
    echo Common issues:
    echo - Wrong FTP credentials
    echo - FTP server not ready yet (wait 5 minutes after account creation)
    echo - Firewall blocking connection
    echo.
    pause
    exit /b 1
)

echo [OK] Upload complete!
echo.

REM Cleanup
echo [6/6] Cleaning up...
rmdir /s /q deploy-temp
del ftp-script.txt

echo [OK] Done
echo.

echo ========================================
echo   Deployment Complete! 
echo ========================================
echo.
echo Your website is now LIVE at:
echo %SITE_URL%
echo.
echo Admin Panel:
echo %SITE_URL%/admin
echo.
echo Admin Login:
echo Email: admin@gyanbazaar.com
echo Password: admin123
echo.
echo ========================================
echo   IMPORTANT: Next Steps
echo ========================================
echo.
echo 1. Import Database:
echo    - Go to InfinityFree Control Panel
echo    - Click "phpMyAdmin"
echo    - Select database: %DB_NAME%
echo    - Click "Import" tab
echo    - Upload: database.sql
echo    - Click "Go"
echo.
echo 2. Test your website:
echo    - Visit: %SITE_URL%
echo    - Login to admin panel
echo    - Change admin password
echo.
echo 3. Set folder permissions (if needed):
echo    - assets/uploads/ = 755
echo    - uploads/ = 755
echo.
echo ========================================
echo.
echo Check upload.log for detailed upload information.
echo.
pause
