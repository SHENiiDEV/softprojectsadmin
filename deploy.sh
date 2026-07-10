#!/bin/bash
set -e

echo "🚀 Starting deployment..."

# Make sure we are in the application directory
cd "$(dirname "$0")"

# Turn on maintenance mode
echo "⏳ Entering maintenance mode..."
php artisan down || true

# Pull the latest changes from the git repository
echo "📥 Pulling latest code..."
git pull origin main

# Install PHP dependencies
echo "📦 Installing PHP dependencies..."
composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev

# Run database migrations
echo "🗄️ Running migrations..."
php artisan migrate --force

# Clear and optimize cache
echo "⚡ Optimizing configuration and routes..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Install Node dependencies and compile assets
if [ -f "package.json" ]; then
    echo "🎨 Building frontend assets..."
    npm ci
    npm run build
fi

# Turn off maintenance mode
echo "✅ Leaving maintenance mode..."
php artisan up

echo "🎉 Deployment complete!"
