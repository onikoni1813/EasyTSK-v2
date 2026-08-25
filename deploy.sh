#!/bin/bash
git pull origin main

# Clean and sync compiled Vite assets & public files to ~/public_html
mkdir -p ~/public_html/build
cp -rf public/build/* ~/public_html/build/
cp -f public/favicon.* ~/public_html/ 2>/dev/null || true
cp -f public/icon-*.png ~/public_html/ 2>/dev/null || true
cp -f public/manifest.json ~/public_html/ 2>/dev/null || true
cp -f public/sw.js ~/public_html/ 2>/dev/null || true

php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan optimize:clear

echo "🚀 Live Site Updated Successfully!"
