#!/bin/bash

# Deployment script for Azure App Service

echo "Running Deployment Script..."

# Fail on error
set -e

# Install dependencies
if [ -f "composer.json" ]; then
    echo "Installing PHP dependencies..."
    composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev
fi

# Run migrations
echo "Running migrations..."
php artisan migrate --force

# Cache configuration and routes for performance
echo "Caching configuration and routes..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Install and build frontend assets
if [ -f "package.json" ]; then
    echo "Building frontend assets..."
    # Note: On some Azure tiers, you might need to pre-build or use a different build agent
    npm install
    npm run build
fi

echo "Deployment finished successfully!"
