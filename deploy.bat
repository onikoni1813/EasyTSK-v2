@echo off
title EasyTSK v2 - One-Click cPanel Direct Deployer
setlocal enabledelayedexpansion

cd /d "%~dp0"

echo.
echo ==================================================================
echo   EasyTSK v2 - One-Click cPanel Direct Deployer
echo ==================================================================
echo.

if not exist "deploy.config.bat" (
    echo [INFO] Creating 'deploy.config.bat' configuration file...
    copy "deploy.config.example.bat" "deploy.config.bat" >nul
    echo.
    echo ==================================================================
    echo [!] SETUP REQUIRED:
    echo 'deploy.config.bat' has been created.
    echo Please open 'deploy.config.bat' in your text editor (Notepad, VS Code),
    echo enter your cPanel FTP Host, Username, and Password, then run this again.
    echo ==================================================================
    echo.
    pause
    exit /b 1
)

echo Select deployment mode:
echo   [1] Full Deploy (npm build + sync changed files + clear remote cache) [Default]
echo   [2] Fast Deploy (sync changed files only without rebuild)
echo   [3] Clear Remote Cache Only (busts config/route cache on cPanel)
echo   [4] Build Frontend Only (npm run build)
echo   [5] Force Upload ALL files (re-upload everything)
echo.
set /p MODE="Choose option (1-5) [1]: "

if "%MODE%"=="" set MODE=1
if "%MODE%"=="1" goto RUN_FULL
if "%MODE%"=="2" goto RUN_FAST
if "%MODE%"=="3" goto RUN_CACHE
if "%MODE%"=="4" goto RUN_BUILD
if "%MODE%"=="5" goto RUN_FORCE

:RUN_FULL
echo.
echo [STARTING] Running Full Deploy...
powershell -NoProfile -ExecutionPolicy Bypass -File "scripts\deploy.ps1"
goto FINISH

:RUN_FAST
echo.
echo [STARTING] Running Fast Deploy (Skipping build)...
powershell -NoProfile -ExecutionPolicy Bypass -File "scripts\deploy.ps1" -SkipBuild
goto FINISH

:RUN_CACHE
echo.
echo [STARTING] Clearing Remote Laravel Cache on cPanel...
powershell -NoProfile -ExecutionPolicy Bypass -File "scripts\deploy.ps1" -ClearCacheOnly
goto FINISH

:RUN_BUILD
echo.
echo [STARTING] Building Frontend Assets...
call npm run build
goto FINISH

:RUN_FORCE
echo.
echo [STARTING] Force Uploading ALL files...
powershell -NoProfile -ExecutionPolicy Bypass -File "scripts\deploy.ps1" -ForceAll
goto FINISH

:FINISH
echo.
echo ==================================================================
echo   Deployment Process Completed!
echo ==================================================================
echo.
pause
