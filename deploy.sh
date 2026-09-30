#!/bin/bash
git pull origin main

# Execute automated public sync, asset copying, migrations & cache optimization
php artisan deploy:sync --migrate

echo "🚀 Live Site Updated Successfully!"

