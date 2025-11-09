@echo off
color 0A
title GyanBazaar - One Click Deployment

echo.
echo ========================================
echo   GyanBazaar - One Click Deployment
echo ========================================
echo.

REM Check if config exists
if not exist deploy-config.txt (
    echo [!] Configuration file not found!
    echo.
    echo Creating deploy-config.txt...
    echo Please edit deploy-config.txt with your InfinityFree details
    echo Then run this script again.
    echo.
    notepad deploy-config.txt
    pause
    exit /b 1
)

echo [*] Reading configuration...
echo.

REM Parse config file
for /f "tokens=1,2 delims==" %%a in (deploy-config.txt) do (
    if "%%a"=="FTP_HOST" set FTP_HOST=%%b
    if "%%a"=="FTP_USER" set FTP_USER=%%b
    if "%%a"=="FTP_PASS" set FTP_PASS=%%b
    if "%%a"=="FTP_PORT" set FTP_PORT=%%b
    if "%%a"=="DB_HOST" set DB_HOST=%%b
    if "%%a"=="DB_USER" set DB_USER=%%b
    if "%%a"=="DB_PASS" set DB_PASS=%%b
    if "%%a"=="DB_NAME" set DB_NAME=%%b
    if "%%a"=="SITE_URL" set SITE_URL=%%b
)

REM Check if configured
if "%FTP_USER%"=="epiz_xxxxx" (
    echo [!] Please configure deploy-config.txt first!
    echo.
    echo Opening configuration file...
    notepad deploy-config.txt
    echo.
    echo After editing, save and run this script again.
    pause
    exit /b 1
)

echo Configuration loaded successfully!
echo.
echo FTP Host: %FTP_HOST%
echo FTP User: %FTP_USER%
echo Database: %DB_NAME%
echo Site URL: %SITE_URL%
echo.
echo ========================================
echo.

set /p CONFIRM="Deploy to %SITE_URL%? (Y/N): "
if /i not "%CONFIRM%"=="Y" (
    echo Deployment cancelled.
    pause
    exit /b 0
)

echo.
echo ========================================
echo   Starting Deployment Process
echo ========================================
echo.

REM Step 1: Pull from GitHub
echo [1/7] Pulling latest code from GitHub...
git pull origin main 2>nul
if errorlevel 1 (
    echo [!] Warning: Could not pull from GitHub
) else (
    echo [OK] Done
)
echo.

REM Step 2: Create deployment package
echo [2/7] Creating deployment package...
if exist deploy-package rmdir /s /q deploy-package 2>nul
mkdir deploy-package

REM Copy files excluding unnecessary ones
xcopy /E /I /Y /Q . deploy-package >nul 2>&1
cd deploy-package
rmdir /s /q .git 2>nul
rmdir /s /q .vscode 2>nul
rmdir /s /q deploy-package 2>nul
rmdir /s /q winscp 2>nul
del /q .gitignore .gitattributes 2>nul
del /q *.bat *.ps1 deploy-config.txt 2>nul
cd ..

echo [OK] Done
echo.

REM Step 3: Update config files
echo [3/7] Updating configuration files...

(
echo ^<?php
echo define^('DB_HOST', '%DB_HOST%'^);
echo define^('DB_USER', '%DB_USER%'^);
echo define^('DB_PASS', '%DB_PASS%'^);
echo define^('DB_NAME', '%DB_NAME%'^);
echo.
echo $conn = mysqli_connect^(DB_HOST, DB_USER, DB_PASS, DB_NAME^);
echo if ^(!$conn^) {
echo     die^("Connection failed: " . mysqli_connect_error^(^)^);
echo }
echo ?^>
) > deploy-package\config\database.php

(
echo ^<?php
echo define^('SITE_URL', '%SITE_URL%'^);
echo define^('SITE_NAME', 'GyanBazaar'^);
echo define^('ADMIN_EMAIL', 'admin@gyanbazaar.com'^);
echo ?^>
) > deploy-package\config\config.php

echo [OK] Done
echo.

REM Step 4: Check for WinSCP
echo [4/7] Checking FTP client...

where winscp.com >nul 2>nul
if errorlevel 1 (
    if not exist winscp\WinSCP.com (
        echo [*] Downloading WinSCP portable...
        powershell -Command "& {[Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12; Invoke-WebRequest -Uri 'https://winscp.net/download/WinSCP-5.21.7-Portable.zip' -OutFile 'winscp.zip'}" 2>nul
        if errorlevel 1 (
            echo [!] Failed to download WinSCP
            echo Please download FileZilla and upload manually
            echo Files are ready in: deploy-package folder
            pause
            exit /b 1
        )
        powershell -Command "& {Expand-Archive -Path 'winscp.zip' -DestinationPath 'winscp' -Force}" 2>nul
        del winscp.zip
    )
    set WINSCP=winscp\WinSCP.com
) else (
    set WINSCP=winscp.com
)

echo [OK] FTP client ready
echo.

REM Step 5: Create FTP script
echo [5/7] Preparing FTP upload...

(
echo option batch abort
echo option confirm off
echo open ftp://%FTP_USER%:%FTP_PASS%@%FTP_HOST%/
echo cd /htdocs
echo option transfer binary
echo synchronize remote -delete -criteria=time deploy-package /htdocs
echo close
echo exit
) > ftp-script.txt

echo [OK] Done
echo.

REM Step 6: Upload files
echo [6/7] Uploading files to InfinityFree...
echo This may take 5-15 minutes depending on file size and internet speed...
echo.
echo Progress:
echo.

"%WINSCP%" /script=ftp-script.txt /log=upload.log

if errorlevel 1 (
    echo.
    echo [!] Upload failed!
    echo.
    echo Common issues:
    echo - Wrong FTP credentials in deploy-config.txt
    echo - FTP server not ready (wait 5-10 minutes after account creation)
    echo - Firewall blocking connection
    echo - Internet connection issue
    echo.
    echo Check upload.log for details
    echo.
    pause
    exit /b 1
)

echo.
echo [OK] Upload complete!
echo.

REM Step 7: Cleanup
echo [7/7] Cleaning up...
rmdir /s /q deploy-package 2>nul
del ftp-script.txt 2>nul

echo [OK] Done
echo.

echo ========================================
echo   Deployment Complete!
echo ========================================
echo.
echo Your website is now LIVE at:
echo.
echo    %SITE_URL%
echo.
echo Admin Panel:
echo    %SITE_URL%/admin
echo.
echo Admin Login:
echo    Email: admin@gyanbazaar.com
echo    Password: admin123
echo.
echo ========================================
echo   IMPORTANT: Final Step
echo ========================================
echo.
echo You need to import the database manually:
echo.
echo 1. Go to: InfinityFree Control Panel
echo 2. Click: phpMyAdmin
echo 3. Select database: %DB_NAME%
echo 4. Click: Import tab
echo 5. Choose file: database.sql (from your local folder)
echo 6. Click: Go
echo.
echo After importing, your site will be fully functional!
echo.
echo ========================================
echo.
echo Check upload.log for upload details
echo.

REM Open browser
set /p OPEN="Open website in browser? (Y/N): "
if /i "%OPEN%"=="Y" (
    start %SITE_URL%
)

echo.
echo Thank you for using GyanBazaar!
echo.
pause
