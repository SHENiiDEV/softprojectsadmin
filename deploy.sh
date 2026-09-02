#!/usr/bin/env bash

# Exit immediately if a command exits with a non-zero status
set -e

# Allow composer to run cleanly as root if executed by root
export COMPOSER_ALLOW_SUPERUSER=1

echo "========================================"
echo "🚀 Starting Production Deployment"
echo "========================================"

# Make sure we are in the application directory
cd "$(dirname "$0")"

# 0. Malware & Backdoor Cleanup (Remove fake WordPress malware dropped by bots)
echo "🛡️ 0. Cleaning up malicious/untracked backdoor files..."
find . -name "wp-cron.php" -delete 2>/dev/null || true
find . -name "wp-blog-header.php" -delete 2>/dev/null || true
find . -name ".htaccess" ! -path "./public/.htaccess" -delete 2>/dev/null || true

# 1. Pull latest code from Git repository
echo "📥 1. Pulling latest code from Git (main)..."
git pull origin main

# 2. Install / Optimize Composer Dependencies
echo "📦 2. Installing Composer dependencies..."
if command -v composer &> /dev/null; then
    composer install --no-interaction --prefer-dist --optimize-autoloader
elif [ -f "./vendor/bin/sail" ]; then
    ./vendor/bin/sail composer install --no-interaction --prefer-dist --optimize-autoloader
fi

# 3. Run Database Migrations
echo "🗄️ 3. Running Database Migrations..."
php artisan migrate --force

# 4. Clear and Rebuild Laravel Caches
echo "⚡ 4. Clearing and optimizing Laravel caches..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 5. Build Frontend Assets (Vite)
if [ -f "package.json" ]; then
    echo "🎨 5. Building Frontend Assets..."
    if command -v npm &> /dev/null; then
        npm install --no-audit --no-fund
        npm run build
    fi
fi

# 6. Restart Queue Workers & Register Services
echo "🔄 6. Restarting Queue Workers & Registering Services..."
php artisan queue:restart || true
php artisan gmail:watch || true

# 7. Ensure Storage Permissions
echo "🔒 7. Ensuring storage permissions..."
chmod -R 775 storage bootstrap/cache 2>/dev/null || true

echo "========================================"
echo "✅ Deployment completed successfully!"
echo "========================================"
