@echo off
echo ========================================
echo GyanBazaar - Deploy to InfinityFree
echo ========================================
echo.

REM Update from GitHub
echo [1/4] Pulling latest code from GitHub...
git pull origin main
if errorlevel 1 (
    echo Error: Failed to pull from GitHub
    pause
    exit /b 1
)
echo Done!
echo.

REM Create deployment package
echo [2/4] Creating deployment package...
if exist deploy-package rmdir /s /q deploy-package
mkdir deploy-package

REM Copy all files except git and large files
xcopy /E /I /Y /EXCLUDE:deploy-exclude.txt . deploy-package
echo Done!
echo.

REM Instructions for FTP upload
echo [3/4] Ready to upload to InfinityFree
echo.
echo NEXT STEPS:
echo 1. Download FileZilla from: https://filezilla-project.org/
echo 2. Connect with your InfinityFree FTP credentials:
echo    - Host: ftpupload.net
echo    - Username: epiz_xxxxx (your InfinityFree username)
echo    - Password: (your InfinityFree password)
echo    - Port: 21
echo.
echo 3. Upload contents of 'deploy-package' folder to '/htdocs/' on server
echo.
echo 4. Create MySQL database in InfinityFree control panel
echo 5. Import database.sql via phpMyAdmin
echo 6. Update config/database.php with new credentials
echo.
echo [4/4] Deployment package ready in 'deploy-package' folder
echo.
pause
