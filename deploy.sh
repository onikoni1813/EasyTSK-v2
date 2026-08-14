#!/bin/bash
git pull origin main

# Clean and sync compiled Vite assets directly to ~/public_html/build
mkdir -p ~/public_html/build
cp -rf public/build/* ~/public_html/build/

php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan optimize:clear

echo "🚀 Live Site Updated Successfully!"
