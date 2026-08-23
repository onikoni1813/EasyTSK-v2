@echo off
:: ==============================================================
:: EasyTSK v2 - cPanel Deployment Configuration (Split Folder)
:: ==============================================================

:: cPanel Host or Domain (e.g. ftp.easytsk.com or server IP)
set FTP_HOST=ftp.easytsk.com

:: cPanel FTP Username (e.g. update@easytsk.com)
set FTP_USER=update@easytsk.com

:: cPanel FTP Password
set FTP_PASS=your_cpanel_ftp_password

:: FTP Port (Default: 21)
set FTP_PORT=21

:: Core Laravel Project Folder on cPanel
set FTP_PROJECT_PATH=/easytsk v2

:: Public Assets Folder on cPanel
set FTP_PUBLIC_PATH=/public_html

:: Automatically build frontend assets (npm run build) before uploading (true/false)
set BUILD_ASSETS=true

:: Automatically clear remote Laravel cache (bootstrap/cache files) after deploy (true/false)
set CLEAR_REMOTE_CACHE=true
