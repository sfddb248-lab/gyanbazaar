# GyanBazaar FTP Upload Script
Write-Host "========================================" -ForegroundColor Green
Write-Host "  Uploading to InfinityFree" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
Write-Host ""

$ftpServer = "ftp://ftpupload.net"
$ftpUsername = "if0_40371517"
$ftpPassword = "Nitin@9917"
$localPath = "deploy-package"
$remotePath = "/htdocs"

function Upload-Directory {
    param (
        [string]$LocalDir,
        [string]$RemoteDir
    )
    
    $files = Get-ChildItem -Path $LocalDir -Recurse -File
    $totalFiles = $files.Count
    $current = 0
    
    foreach ($file in $files) {
        $current++
        $relativePath = $file.FullName.Substring($LocalDir.Length + 1).Replace('\', '/')
        $remoteFile = "$RemoteDir/$relativePath"
        
        Write-Progress -Activity "Uploading files" -Status "$current of $totalFiles" -PercentComplete (($current / $totalFiles) * 100)
        
        try {
            $uri = New-Object System.Uri("$ftpServer$remoteFile")
            $request = [System.Net.FtpWebRequest]::Create($uri)
            $request.Method = [System.Net.WebRequestMethods+Ftp]::UploadFile
            $request.Credentials = New-Object System.Net.NetworkCredential($ftpUsername, $ftpPassword)
            $request.UseBinary = $true
            $request.UsePassive = $true
            
            # Create directory if needed
            $remoteDir = Split-Path $remoteFile -Parent
            
            $fileContent = [System.IO.File]::ReadAllBytes($file.FullName)
            $request.ContentLength = $fileContent.Length
            
            $requestStream = $request.GetRequestStream()
            $requestStream.Write($fileContent, 0, $fileContent.Length)
            $requestStream.Close()
            
            $response = $request.GetResponse()
            $response.Close()
            
            Write-Host "[OK] $relativePath" -ForegroundColor Green
        }
        catch {
            Write-Host "[!] Failed: $relativePath - $($_.Exception.Message)" -ForegroundColor Red
        }
    }
}

Write-Host "Starting upload..." -ForegroundColor Yellow
Write-Host ""

Upload-Directory -LocalDir $localPath -RemoteDir $remotePath

Write-Host ""
Write-Host "========================================" -ForegroundColor Green
Write-Host "  Upload Complete!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
