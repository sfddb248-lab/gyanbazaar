@echo off
echo ========================================
echo   GyanBazaar - Automatic Deployment
echo ========================================
echo.
echo Starting PowerShell deployment script...
echo.

powershell -ExecutionPolicy Bypass -File auto-deploy.ps1

pause
