@echo off
:: ==============================================================
:: EasyTSK v2 - cPanel Deployment Configuration Example
:: Copy this file to 'deploy.config.bat' and fill in your details
:: ==============================================================

:: cPanel Host or Domain (e.g. ftp.yourdomain.com or server IP)
set FTP_HOST=ftp.yourdomain.com

:: cPanel FTP Username
set FTP_USER=your_cpanel_ftp_user

:: cPanel FTP Password
set FTP_PASS=your_cpanel_ftp_password

:: FTP Port (Default: 21 for FTP, 22 for SFTP)
set FTP_PORT=21

:: Protocol: 'ftp' (standard cPanel FTP) or 'sftp' (SSH FTP) or 'ftps' (Explicit TLS)
set FTP_PROTOCOL=ftp

:: Remote Directory on cPanel where Easytsk is located (e.g. /public_html or /easytsk)
set FTP_REMOTE_PATH=/public_html

:: Automatically build frontend assets (npm run build) before uploading (true/false)
set BUILD_ASSETS=true

:: Automatically clear remote Laravel cache (bootstrap/cache files) after deploy (true/false)
set CLEAR_REMOTE_CACHE=true
