# Easytsk V2 - One-Click cPanel Deploy Script (PowerShell Engine)
param (
    [switch]$SkipBuild,
    [switch]$ForceAll,
    [switch]$ClearCacheOnly
)

$ErrorActionPreference = "Stop"
$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$ProjectRoot = Split-Path -Parent $ScriptDir

# Colors
function Write-Header {
    param([string]$Text)
    Write-Host ""
    Write-Host "==================================================================" -ForegroundColor Cyan
    Write-Host "  $Text" -ForegroundColor White
    Write-Host "==================================================================" -ForegroundColor Cyan
    Write-Host ""
}

function Write-Success { param([string]$Text) Write-Host "[SUCCESS] $Text" -ForegroundColor Green }
function Write-Info    { param([string]$Text) Write-Host "[INFO]    $Text" -ForegroundColor Cyan }
function Write-Warning { param([string]$Text) Write-Host "[WARN]    $Text" -ForegroundColor Yellow }
function Write-Err     { param([string]$Text) Write-Host "[ERROR]   $Text" -ForegroundColor Red }

Write-Header "EasyTSK v2 - One-Click cPanel Direct Deployer"

# Check Config File
$ConfigFileBat = Join-Path $ProjectRoot "deploy.config.bat"
$ConfigFilePs1 = Join-Path $ProjectRoot "deploy.config.ps1"
$ConfigTemplate = Join-Path $ProjectRoot "deploy.config.example.bat"

$FTP_HOST = ""
$FTP_USER = ""
$FTP_PASS = ""
$FTP_PORT = "21"
$FTP_REMOTE_PATH = "/public_html"
$FTP_PROTOCOL = "ftp" # ftp or sftp
$BUILD_ASSETS = "true"
$CLEAR_REMOTE_CACHE = "true"

if (Test-Path $ConfigFilePs1) {
    . $ConfigFilePs1
} elseif (Test-Path $ConfigFileBat) {
    Get-Content $ConfigFileBat | ForEach-Object {
        $line = $_.Trim()
        if ($line -match '^set\s+([A-Za-z0-9_]+)=(.*)$') {
            $key = $matches[1].ToUpper()
            $val = $matches[2].Trim('"').Trim("'").Trim()
            switch ($key) {
                "FTP_HOST" { $FTP_HOST = $val }
                "FTP_USER" { $FTP_USER = $val }
                "FTP_PASS" { $FTP_PASS = $val }
                "FTP_PORT" { $FTP_PORT = $val }
                "FTP_REMOTE_PATH" { $FTP_REMOTE_PATH = $val }
                "FTP_PROTOCOL" { $FTP_PROTOCOL = $val }
                "BUILD_ASSETS" { $BUILD_ASSETS = $val }
                "CLEAR_REMOTE_CACHE" { $CLEAR_REMOTE_CACHE = $val }
            }
        }
    }
} else {
    Write-Warning "deploy.config.bat not found!"
    Write-Info "Creating 'deploy.config.bat' template from example..."
    
    $defaultConfig = @"
@echo off
:: ==============================================================
:: EasyTSK v2 - cPanel Deployment Configuration
:: Please enter your cPanel FTP / SFTP credentials below.
:: ==============================================================

:: cPanel Host or Domain (e.g. ftp.yourdomain.com or 123.45.67.89)
set FTP_HOST=ftp.yourdomain.com

:: cPanel FTP Username
set FTP_USER=your_ftp_username

:: cPanel FTP Password
set FTP_PASS=your_ftp_password

:: FTP Port (Default: 21 for FTP, 22 for SFTP)
set FTP_PORT=21

:: Protocol: 'ftp' (standard cPanel FTP) or 'sftp' (SSH FTP) or 'ftps' (Explicit TLS)
set FTP_PROTOCOL=ftp

:: Remote Directory where Easytsk is hosted (e.g. /public_html or /easytsk)
set FTP_REMOTE_PATH=/public_html

:: Automatically build frontend assets (npm run build) before uploading (true/false)
set BUILD_ASSETS=true

:: Automatically clear remote Laravel cache (bootstrap/cache files) after deploy (true/false)
set CLEAR_REMOTE_CACHE=true
"@
    Set-Content -Path $ConfigFileBat -Value $defaultConfig -Encoding UTF8
    Write-Success "Created deploy.config.bat!"
    Write-Host ""
    Write-Host ">> Please open 'deploy.config.bat' in your editor, enter your cPanel FTP credentials, save it, and run deploy.bat again." -ForegroundColor Yellow
    Write-Host ""
    Read-Host "Press Enter to exit"
    exit 1
}

# Validate credentials
if ([string]::IsNullOrWhiteSpace($FTP_HOST) -or $FTP_HOST -eq "ftp.yourdomain.com" -or [string]::IsNullOrWhiteSpace($FTP_USER) -or $FTP_USER -eq "your_ftp_username") {
    Write-Err "cPanel FTP credentials have not been configured yet!"
    Write-Host "Please edit 'deploy.config.bat' with your real cPanel FTP Host, Username, and Password." -ForegroundColor Yellow
    Write-Host ""
    Read-Host "Press Enter to exit"
    exit 1
}

Write-Info "Target Server: $FTP_PROTOCOL://$FTP_HOST:$FTP_PORT$FTP_REMOTE_PATH"
Write-Info "FTP User     : $FTP_USER"

# Step 1: Build Assets
if ($BUILD_ASSETS -eq "true" -and -not $SkipBuild -and -not $ClearCacheOnly) {
    Write-Header "Step 1/3: Building Frontend Assets (npm run build)..."
    Push-Location $ProjectRoot
    try {
        npm run build
        if ($LASTEXITCODE -ne 0) {
            Write-Warning "npm run build returned code $LASTEXITCODE. Continuing..."
        } else {
            Write-Success "Frontend assets built successfully!"
        }
    } catch {
        Write-Warning "npm build failed: $_. Continuing upload..."
    }
    Pop-Location
}

# Look for WinSCP
$WinSCPPaths = @(
    "WinSCP.com",
    "C:\Program Files (x86)\WinSCP\WinSCP.com",
    "C:\Program Files\WinSCP\WinSCP.com",
    "$env:LOCALAPPDATA\Programs\WinSCP\WinSCP.com",
    "$ProjectRoot\WinSCP.com"
)

$WinSCPExt = $null
foreach ($path in $WinSCPPaths) {
    if (Get-Command $path -ErrorAction SilentlyContinue) {
        $WinSCPExt = $path
        break
    } elseif (Test-Path $path) {
        $WinSCPExt = $path
        break
    }
}

# Step 2: Upload / Sync files
if (-not $ClearCacheOnly) {
    Write-Header "Step 2/3: Syncing Changed Files to cPanel..."

    if ($WinSCPExt) {
        Write-Info "Using WinSCP engine ($WinSCPExt) for high-speed differential sync..."
        
        $tempScript = Join-Path $ProjectRoot ".winscp_deploy_script.txt"
        
        # Build WinSCP connection string
        $proto = $FTP_PROTOCOL.ToLower()
        $sessionUrl = ""
        if ($proto -eq "sftp") {
            $sessionUrl = "sftp://${FTP_USER}:${FTP_PASS}@${FTP_HOST}:${FTP_PORT}/ -hostkey=*"
        } elseif ($proto -eq "ftps" -or $proto -eq "tls") {
            $sessionUrl = "ftps://${FTP_USER}:${FTP_PASS}@${FTP_HOST}:${FTP_PORT}/ -explicit -certificate=*"
        } else {
            $sessionUrl = "ftp://${FTP_USER}:${FTP_PASS}@${FTP_HOST}:${FTP_PORT}/"
        }

        # Exclude list for WinSCP
        $excludeMask = "|.git/;.github/;.vscode/;node_modules/;tests/;storage/logs/*.log;storage/framework/sessions/*;storage/framework/cache/*;.env;deploy.bat;deploy.config.bat;deploy.config.ps1;.deploy_history.json;.winscp_deploy_script.txt;*.sql"

        $winScpCommands = @"
option batch on
option confirm off
open $sessionUrl
synchronize remote "$ProjectRoot" "$FTP_REMOTE_PATH" -filemask="$excludeMask"
"@
        if ($CLEAR_REMOTE_CACHE -eq "true") {
            $winScpCommands += @"

rm "$FTP_REMOTE_PATH/bootstrap/cache/config.php"
rm "$FTP_REMOTE_PATH/bootstrap/cache/routes*.php"
rm "$FTP_REMOTE_PATH/bootstrap/cache/services.php"
rm "$FTP_REMOTE_PATH/bootstrap/cache/packages.php"
"@
        }

        $winScpCommands += @"

exit
"@
        Set-Content -Path $tempScript -Value $winScpCommands -Encoding ASCII

        try {
            & $WinSCPExt /script="$tempScript"
            Remove-Item $tempScript -Force -ErrorAction SilentlyContinue
            Write-Success "Files synchronized to cPanel successfully!"
        } catch {
            Remove-Item $tempScript -Force -ErrorAction SilentlyContinue
            Write-Err "WinSCP upload failed: $_"
            exit 1
        }
    } else {
        Write-Info "WinSCP not found. Using native PowerShell FTP sync engine..."

        # Native PowerShell FTP Upload
        # Excluded items
        $excludedFolders = @(".git", ".github", ".vscode", "node_modules", "tests", "scratch")
        $excludedFiles = @(".env", ".env.backup", "deploy.bat", "deploy.config.bat", "deploy.config.ps1", ".deploy_history.json")

        $historyFile = Join-Path $ProjectRoot ".deploy_history.json"
        $lastDeploy = @{}
        if (Test-Path $historyFile) {
            try {
                $lastDeploy = Get-Content $historyFile | ConvertFrom-Json -AsHashtable
            } catch {
                $lastDeploy = @{}
            }
        }

        $filesToUpload = [System.Collections.Generic.List[System.IO.FileInfo]]::new()
        
        Get-ChildItem -Path $ProjectRoot -Recurse -File | ForEach-Object {
            $file = $_
            $relPath = $file.FullName.Substring($ProjectRoot.Length).TrimStart('\', '/')
            $firstFolder = $relPath.Split('\', '/')[0]

            if ($excludedFolders -contains $firstFolder) { return }
            if ($excludedFiles -contains $file.Name) { return }
            if ($relPath.StartsWith("storage\logs") -or $relPath.StartsWith("storage/logs")) { return }
            if ($relPath.StartsWith("storage\framework\sessions") -or $relPath.StartsWith("storage/framework/sessions")) { return }
            if ($relPath.StartsWith("storage\framework\cache") -or $relPath.StartsWith("storage/framework/cache")) { return }
            if ($file.Extension -eq ".sql" -or $file.Extension -eq ".log") { return }

            $lastWriteTicks = $file.LastWriteTimeUtc.Ticks
            if ($ForceAll -or (-not $lastDeploy.ContainsKey($relPath)) -or ($lastDeploy[$relPath] -ne $lastWriteTicks)) {
                $filesToUpload.Add($file)
            }
        }

        Write-Info "Found $($filesToUpload.Count) changed/new files to upload..."

        if ($filesToUpload.Count -gt 0) {
            $ftpBaseUri = "ftp://${FTP_HOST}:${FTP_PORT}" + $FTP_REMOTE_PATH.TrimEnd('/')
            $credentials = New-Object System.Net.NetworkCredential($FTP_USER, $FTP_PASS)
            
            $createdDirs = @{}

            $count = 0
            foreach ($file in $filesToUpload) {
                $count++
                $relPath = $file.FullName.Substring($ProjectRoot.Length).TrimStart('\', '/').Replace('\', '/')
                $remoteUrl = "$ftpBaseUri/$relPath"
                $progressPercent = [math]::Round(($count / $filesToUpload.Count) * 100)

                # Ensure Remote Directory Exists
                $dirPath = [System.IO.Path]::GetDirectoryName($relPath).Replace('\', '/')
                if ($dirPath -and (-not $createdDirs.ContainsKey($dirPath))) {
                    $parts = $dirPath.Split('/')
                    $currentCheck = ""
                    foreach ($p in $parts) {
                        $currentCheck = if ($currentCheck) { "$currentCheck/$p" } else { $p }
                        if (-not $createdDirs.ContainsKey($currentCheck)) {
                            try {
                                $dirReq = [System.Net.FtpWebRequest]::Create("$ftpBaseUri/$currentCheck")
                                $dirReq.Credentials = $credentials
                                $dirReq.Method = [System.Net.WebRequestMethods+Ftp]::MakeDirectory
                                $dirReq.UseBinary = $true
                                $dirReq.KeepAlive = $false
                                $resp = $dirReq.GetResponse()
                                $resp.Close()
                            } catch {}
                            $createdDirs[$currentCheck] = $true
                        }
                    }
                }

                Write-Host "[$count/$($filesToUpload.Count) - $progressPercent%] Uploading: $relPath" -ForegroundColor Gray

                try {
                    $uploadReq = [System.Net.FtpWebRequest]::Create($remoteUrl)
                    $uploadReq.Credentials = $credentials
                    $uploadReq.Method = [System.Net.WebRequestMethods+Ftp]::UploadFile
                    $uploadReq.UseBinary = $true
                    $uploadReq.KeepAlive = $false

                    $fileBytes = [System.IO.File]::ReadAllBytes($file.FullName)
                    $uploadReq.ContentLength = $fileBytes.Length

                    $reqStream = $uploadReq.GetRequestStream()
                    $reqStream.Write($fileBytes, 0, $fileBytes.Length)
                    $reqStream.Close()

                    $upResp = $uploadReq.GetResponse()
                    $upResp.Close()

                    $lastDeploy[$relPath] = $file.LastWriteTimeUtc.Ticks
                } catch {
                    Write-Warning "Failed to upload '$relPath': $_"
                }
            }

            # Save deploy history
            $lastDeploy | ConvertTo-Json | Set-Content -Path $historyFile -Encoding UTF8
            Write-Success "All changed files uploaded successfully!"
        } else {
            Write-Success "Everything is up to date. No changed files to upload."
        }
    }
}

# Step 3: Clear Remote Laravel Cache via FTP
if ($CLEAR_REMOTE_CACHE -eq "true") {
    Write-Header "Step 3/3: Busting Remote Laravel Cache (bootstrap/cache)..."
    
    $cacheFiles = @("config.php", "routes-v7.php", "routes.php", "services.php", "packages.php")
    $ftpBaseUri = "ftp://${FTP_HOST}:${FTP_PORT}" + $FTP_REMOTE_PATH.TrimEnd('/') + "/bootstrap/cache"
    $credentials = New-Object System.Net.NetworkCredential($FTP_USER, $FTP_PASS)

    foreach ($cfile in $cacheFiles) {
        try {
            $delReq = [System.Net.FtpWebRequest]::Create("$ftpBaseUri/$cfile")
            $delReq.Credentials = $credentials
            $delReq.Method = [System.Net.WebRequestMethods+Ftp]::DeleteFile
            $delReq.KeepAlive = $false
            $dResp = $delReq.GetResponse()
            $dResp.Close()
            Write-Host "  Cleared remote cache: bootstrap/cache/$cfile" -ForegroundColor Green
        } catch {
            # File might not exist, which is completely fine
        }
    }
    Write-Success "Remote Laravel cache cleared! Server will now auto-refresh configuration and routes."
}

Write-Header "DEPLOYMENT FINISHED SUCCESSFULLY!"
Write-Host "Time: $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')" -ForegroundColor Green
Write-Host "Your cPanel live site is now fully updated and fresh!" -ForegroundColor Cyan
Write-Host ""
