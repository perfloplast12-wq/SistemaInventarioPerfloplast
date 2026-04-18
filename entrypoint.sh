#!/bin/bash
set -e

echo "🚀 Starting Perfloplast..."

# Generate app key if not set
if [ -z "$APP_KEY" ]; then
    echo "⚠️  APP_KEY not set, generating..."
    php artisan key:generate --force
fi

# Run Laravel caching (needs env vars available at runtime)
echo "⚙️  Optimizing Laravel..."
php artisan filament:optimize --ansi 2>/dev/null || true
php artisan config:cache --ansi || true
php artisan route:cache --ansi || true
php artisan view:cache --ansi || true

echo "✅ Ready! Starting Apache..."
exec apache2-foreground
