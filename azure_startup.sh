#!/bin/bash

# Configuration
LOG_FILE="/home/site/wwwroot/startup_log.txt"

echo "Starting startup process at $(date)..." > "$LOG_FILE"

# 1. Setup Storage Permissions
mkdir -p /var/www/html/storage/framework/{cache,sessions,views} >> "$LOG_FILE" 2>&1
mkdir -p /var/www/html/storage/logs >> "$LOG_FILE" 2>&1
chmod -R 777 /var/www/html/storage >> "$LOG_FILE" 2>&1
chmod -R 777 /var/www/html/bootstrap/cache >> "$LOG_FILE" 2>&1

# 2. Run Artisan commands from project root
cd /var/www/html
echo "Running Artisan commands from $(pwd)..." >> "$LOG_FILE"

# Regenerate autoload classmap
composer dump-autoload --optimize >> "$LOG_FILE" 2>&1

# Run database migrations
php artisan migrate --force >> "$LOG_FILE" 2>&1

# Seed database
echo "Running db:seed..." >> "$LOG_FILE"
php artisan db:seed --force >> "$LOG_FILE" 2>&1
if [ $? -eq 0 ]; then echo "Seeding successful." >> "$LOG_FILE"; else echo "Seeding FAILED." >> "$LOG_FILE"; fi

# Create storage symlink
php artisan storage:link >> "$LOG_FILE" 2>&1

# Publish Filament assets
php artisan filament:assets >> "$LOG_FILE" 2>&1

# Cache configuration for performance
php artisan config:cache >> "$LOG_FILE" 2>&1
php artisan route:cache >> "$LOG_FILE" 2>&1
php artisan view:cache >> "$LOG_FILE" 2>&1

echo "Startup process complete." >> "$LOG_FILE"

# 3. Start Services
echo "Starting PHP-FPM..." >> "$LOG_FILE"
service php8.2-fpm start >> "$LOG_FILE" 2>&1

echo "Starting Nginx in foreground..." >> "$LOG_FILE"
nginx -g "daemon off;"
