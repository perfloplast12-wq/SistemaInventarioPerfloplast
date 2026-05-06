#!/bin/bash
set -e

echo "🚀 Starting Perfloplast with Laravel Octane (FrankenPHP)..."

# Generate app key if not set
if [ -z "$APP_KEY" ]; then
    echo "⚠️  APP_KEY not set, generating..."
    php artisan key:generate --force
fi

# Run database migrations
echo "🗄️  Running database migrations..."
php artisan migrate --force || echo "⚠️ Migration failed but continuing..."

# Optimization (Crucial for Octane)
echo "⚙️  Optimizing Laravel..."
php artisan filament:optimize --ansi || true
php artisan config:cache --ansi || true
php artisan route:cache --ansi || true
php artisan view:cache --ansi || true

echo "✅ Ready! Starting Octane Server on port 8080..."

# Start Octane with FrankenPHP
# We use --host=0.0.0.0 and --port=8080 to match DigitalOcean config
exec php artisan octane:start --server=frankenphp --host=0.0.0.0 --port=8080 --workers=4 --max-requests=500
