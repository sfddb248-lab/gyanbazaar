@echo off
echo ========================================
echo   GyanBazaar - Automatic Deployment
echo ========================================
echo.
echo Starting PowerShell deployment script...
echo.

powershell -ExecutionPolicy Bypass -File "%~dp0auto-deploy.ps1"

if errorlevel 1 (
    echo.
    echo PowerShell script failed. Trying batch version...
    echo.
    call "%~dp0auto-deploy.bat"
)

pause
