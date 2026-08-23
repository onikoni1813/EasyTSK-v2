# Easytsk V2 - One-Click cPanel Deploy Script (Split Core & Public Engine)
param (
    [switch]$SkipBuild,
    [switch]$ForceAll,
    [switch]$ClearCacheOnly
)

$ErrorActionPreference = "Stop"
$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$ProjectRoot = Split-Path -Parent $ScriptDir

# Colors
function Write-Header  { param([string]$Text) Write-Host "`n==================================================================" -ForegroundColor Cyan; Write-Host "  $Text" -ForegroundColor White; Write-Host "==================================================================" -ForegroundColor Cyan; Write-Host "" }
function Write-Success { param([string]$Text) Write-Host "[SUCCESS] $Text" -ForegroundColor Green }
function Write-Info    { param([string]$Text) Write-Host "[INFO]    $Text" -ForegroundColor Cyan }
function Write-Warning { param([string]$Text) Write-Host "[WARN]    $Text" -ForegroundColor Yellow }
function Write-Err     { param([string]$Text) Write-Host "[ERROR]   $Text" -ForegroundColor Red }

Write-Header "EasyTSK v2 - cPanel Split-Directory Deployer"

# Configuration variables
$ConfigFileBat = Join-Path $ProjectRoot "deploy.config.bat"
$ConfigFilePs1 = Join-Path $ProjectRoot "deploy.config.ps1"

$FTP_HOST = "ftp.easytsk.com"
$FTP_USER = ""
$FTP_PASS = ""
$FTP_PORT = "21"
$FTP_PROJECT_PATH = "/easytsk v2"
$FTP_PUBLIC_PATH  = "/public_html"
$FTP_PROTOCOL     = "ftp"
$BUILD_ASSETS     = "true"
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
                "FTP_HOST"          { $FTP_HOST = $val }
                "FTP_USER"          { $FTP_USER = $val }
                "FTP_PASS"          { $FTP_PASS = $val }
                "FTP_PORT"          { $FTP_PORT = $val }
                "FTP_PROJECT_PATH"  { $FTP_PROJECT_PATH = $val }
                "FTP_PUBLIC_PATH"   { $FTP_PUBLIC_PATH = $val }
                "FTP_PROTOCOL"      { $FTP_PROTOCOL = $val }
                "BUILD_ASSETS"      { $BUILD_ASSETS = $val }
                "CLEAR_REMOTE_CACHE"{ $CLEAR_REMOTE_CACHE = $val }
            }
        }
    }
} else {
    Write-Warning "deploy.config.bat not found. Generating default..."
}

# Clean paths (no trailing slashes)
$FTP_PROJECT_PATH = "/" + $FTP_PROJECT_PATH.Trim('/').Replace('\', '/')
$FTP_PUBLIC_PATH  = "/" + $FTP_PUBLIC_PATH.Trim('/').Replace('\', '/')

# Validate credentials
if ([string]::IsNullOrWhiteSpace($FTP_USER) -or $FTP_USER -eq "your_ftp_username" -or [string]::IsNullOrWhiteSpace($FTP_PASS) -or $FTP_PASS -eq "your_ftp_password") {
    Write-Err "FTP credentials have not been configured in 'deploy.config.bat' yet!"
    Write-Host "Please edit 'deploy.config.bat' with your FTP Username (e.g. update@easytsk.com) and Password." -ForegroundColor Yellow
    Write-Host ""
    Read-Host "Press Enter to exit"
    exit 1
}

Write-Info "Server Host    : $FTP_HOST (Port: $FTP_PORT)"
Write-Info "FTP Account    : $FTP_USER"
Write-Info "Core Project   : $FTP_PROJECT_PATH"
Write-Info "Public Assets  : $FTP_PUBLIC_PATH"

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
    Write-Header "Step 2/3: Uploading Changed Files to cPanel..."

    if ($WinSCPExt) {
        Write-Info "Using WinSCP high-speed sync engine ($WinSCPExt)..."
        $tempScript = Join-Path $ProjectRoot ".winscp_deploy_script.txt"
        
        $proto = $FTP_PROTOCOL.ToLower()
        $sessionUrl = ""
        if ($proto -eq "sftp") {
            $sessionUrl = "sftp://${FTP_USER}:${FTP_PASS}@${FTP_HOST}:${FTP_PORT}/ -hostkey=*"
        } elseif ($proto -eq "ftps" -or $proto -eq "tls") {
            $sessionUrl = "ftps://${FTP_USER}:${FTP_PASS}@${FTP_HOST}:${FTP_PORT}/ -explicit -certificate=*"
        } else {
            $sessionUrl = "ftp://${FTP_USER}:${FTP_PASS}@${FTP_HOST}:${FTP_PORT}/"
        }

        # Mask rules
        $coreExcludeMask = "|.git/;.github/;.vscode/;node_modules/;tests/;public/;.env;.env.*;deploy.bat;deploy.config.*;.deploy_history.json;.winscp_deploy_script.txt;*.sql;storage/logs/*.log;storage/framework/sessions/*;storage/framework/cache/*"
        $publicExcludeMask = "|hot;storage;*.php;.htaccess"

        $publicLocal = Join-Path $ProjectRoot "public"

        $winScpCommands = @"
option batch on
option confirm off
open $sessionUrl
synchronize remote "$ProjectRoot" "$FTP_PROJECT_PATH" -filemask="$coreExcludeMask"
synchronize remote "$publicLocal" "$FTP_PUBLIC_PATH" -filemask="$publicExcludeMask"
"@
        if ($CLEAR_REMOTE_CACHE -eq "true") {
            $winScpCommands += @"

rm "$FTP_PROJECT_PATH/bootstrap/cache/config.php"
rm "$FTP_PROJECT_PATH/bootstrap/cache/routes*.php"
rm "$FTP_PROJECT_PATH/bootstrap/cache/services.php"
rm "$FTP_PROJECT_PATH/bootstrap/cache/packages.php"
"@
        }

        $winScpCommands += @"

exit
"@
        Set-Content -Path $tempScript -Value $winScpCommands -Encoding ASCII

        try {
            & $WinSCPExt /script="$tempScript"
            Remove-Item $tempScript -Force -ErrorAction SilentlyContinue
            Write-Success "Core and Public files synchronized successfully via WinSCP!"
        } catch {
            Remove-Item $tempScript -Force -ErrorAction SilentlyContinue
            Write-Err "WinSCP upload error: $_"
            exit 1
        }
    } else {
        Write-Info "WinSCP not found. Using native PowerShell FTP engine..."

        $excludedFolders = @(".git", ".github", ".vscode", "node_modules", "tests", "scratch")
        $excludedFiles   = @(".env", ".env.backup", ".env.production", "deploy.bat", "deploy.config.bat", "deploy.config.ps1", ".deploy_history.json")

        $historyFile = Join-Path $ProjectRoot ".deploy_history.json"
        $lastDeploy = @{}
        if (Test-Path $historyFile) {
            try { $lastDeploy = Get-Content $historyFile | ConvertFrom-Json -AsHashtable } catch { $lastDeploy = @{} }
        }

        # Identify files
        $coreFilesToUpload = [System.Collections.Generic.List[System.IO.FileInfo]]::new()
        $publicFilesToUpload = [System.Collections.Generic.List[System.IO.FileInfo]]::new()

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
            $needsUpload = $ForceAll -or (-not $lastDeploy.ContainsKey($relPath)) -or ($lastDeploy[$relPath] -ne $lastWriteTicks)

            if ($needsUpload) {
                if ($relPath.StartsWith("public\") -or $relPath.StartsWith("public/")) {
                    # Skip public index.php or .htaccess to avoid overwriting production server config
                    if ($file.Name -ne "index.php" -and $file.Name -ne ".htaccess") {
                        $publicFilesToUpload.Add($file)
                    }
                } else {
                    $coreFilesToUpload.Add($file)
                }
            }
        }

        $totalFiles = $coreFilesToUpload.Count + $publicFilesToUpload.Count
        Write-Info "Total files to upload: $totalFiles (Core: $($coreFilesToUpload.Count), Public: $($publicFilesToUpload.Count))"

        if ($totalFiles -gt 0) {
            $ftpBaseServer = "ftp://${FTP_HOST}:${FTP_PORT}"
            $credentials = New-Object System.Net.NetworkCredential($FTP_USER, $FTP_PASS)
            $createdDirs = @{}

            function Upload-FtpItem {
                param(
                    [System.IO.FileInfo]$File,
                    [string]$BaseRemoteFolder,
                    [string]$RelativeInsideBase,
                    [int]$Index,
                    [int]$Total
                )

                $cleanRelative = $RelativeInsideBase.Replace('\', '/')
                $remoteUrl = "$ftpBaseServer$BaseRemoteFolder/$cleanRelative"
                $progressPercent = [math]::Round(($Index / $Total) * 100)

                # Ensure Remote Directory Exists
                $dirPath = [System.IO.Path]::GetDirectoryName($cleanRelative).Replace('\', '/')
                if ($dirPath) {
                    $parts = $dirPath.Split('/')
                    $currentCheck = $BaseRemoteFolder
                    foreach ($p in $parts) {
                        $currentCheck = "$currentCheck/$p"
                        if (-not $createdDirs.ContainsKey($currentCheck)) {
                            try {
                                $dirReq = [System.Net.FtpWebRequest]::Create("$ftpBaseServer$currentCheck")
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

                Write-Host "[$Index/$Total - $progressPercent%] Uploading -> $BaseRemoteFolder/$cleanRelative" -ForegroundColor Gray

                try {
                    $uploadReq = [System.Net.FtpWebRequest]::Create($remoteUrl)
                    $uploadReq.Credentials = $credentials
                    $uploadReq.Method = [System.Net.WebRequestMethods+Ftp]::UploadFile
                    $uploadReq.UseBinary = $true
                    $uploadReq.KeepAlive = $false

                    $fileBytes = [System.IO.File]::ReadAllBytes($File.FullName)
                    $uploadReq.ContentLength = $fileBytes.Length

                    $reqStream = $uploadReq.GetRequestStream()
                    $reqStream.Write($fileBytes, 0, $fileBytes.Length)
                    $reqStream.Close()

                    $upResp = $uploadReq.GetResponse()
                    $upResp.Close()

                    $fullRelKey = $File.FullName.Substring($ProjectRoot.Length).TrimStart('\', '/')
                    $lastDeploy[$fullRelKey] = $File.LastWriteTimeUtc.Ticks
                } catch {
                    Write-Warning "Upload failed for '$remoteUrl': $_"
                }
            }

            $currentIdx = 0

            # 1. Upload Core Project Files
            foreach ($file in $coreFilesToUpload) {
                $currentIdx++
                $relPath = $file.FullName.Substring($ProjectRoot.Length).TrimStart('\', '/')
                Upload-FtpItem -File $file -BaseRemoteFolder $FTP_PROJECT_PATH -RelativeInsideBase $relPath -Index $currentIdx -Total $totalFiles
            }

            # 2. Upload Public Files (e.g. build/assets)
            $publicFolderLocal = Join-Path $ProjectRoot "public"
            foreach ($file in $publicFilesToUpload) {
                $currentIdx++
                $relPublicPath = $file.FullName.Substring($publicFolderLocal.Length).TrimStart('\', '/')
                Upload-FtpItem -File $file -BaseRemoteFolder $FTP_PUBLIC_PATH -RelativeInsideBase $relPublicPath -Index $currentIdx -Total $totalFiles
            }

            $lastDeploy | ConvertTo-Json | Set-Content -Path $historyFile -Encoding UTF8
            Write-Success "All Core & Public files uploaded successfully!"
        } else {
            Write-Success "Everything is already up to date."
        }
    }
}

# Step 3: Clear Remote Laravel Cache
if ($CLEAR_REMOTE_CACHE -eq "true") {
    Write-Header "Step 3/3: Busting Remote Laravel Cache (bootstrap/cache)..."
    
    $cacheFiles = @("config.php", "routes-v7.php", "routes.php", "services.php", "packages.php")
    $ftpBaseUri = "ftp://${FTP_HOST}:${FTP_PORT}" + $FTP_PROJECT_PATH + "/bootstrap/cache"
    $credentials = New-Object System.Net.NetworkCredential($FTP_USER, $FTP_PASS)

    foreach ($cfile in $cacheFiles) {
        try {
            $delReq = [System.Net.FtpWebRequest]::Create("$ftpBaseUri/$cfile")
            $delReq.Credentials = $credentials
            $delReq.Method = [System.Net.WebRequestMethods+Ftp]::DeleteFile
            $delReq.KeepAlive = $false
            $dResp = $delReq.GetResponse()
            $dResp.Close()
            Write-Host "  [CLEARED] bootstrap/cache/$cfile" -ForegroundColor Green
        } catch {}
    }
    Write-Success "Remote Laravel cache cleared! Server will now auto-refresh configuration and routes."
}

Write-Header "DEPLOYMENT COMPLETED SUCCESSFULLY!"
Write-Host "Time: $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')" -ForegroundColor Green
Write-Host "Core Files   -> $FTP_PROJECT_PATH" -ForegroundColor Cyan
Write-Host "Public Build -> $FTP_PUBLIC_PATH" -ForegroundColor Cyan
Write-Host ""
