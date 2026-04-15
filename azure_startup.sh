#!/bin/bash

# Configuration
LOG_FILE="/home/site/wwwroot/startup_log.txt"

echo "Starting minimal startup process at $(date)..." > "$LOG_FILE"

# 1. Setup Storage Permissions (Keep persistence for data)
mkdir -p /home/site/wwwroot/storage/framework/{cache,sessions,views} >> "$LOG_FILE" 2>&1
mkdir -p /home/site/wwwroot/storage/logs >> "$LOG_FILE" 2>&1
chmod -R 777 /home/site/wwwroot/storage >> "$LOG_FILE" 2>&1

# 2. Run Artisan commands from the image code path
cd /var/www/html/backend
echo "Running Artisan commands from $(pwd)..." >> "$LOG_FILE"

php artisan migrate --force >> "$LOG_FILE" 2>&1
composer dump-autoload --optimize >> "$LOG_FILE" 2>&1
echo "Running db:seed..." >> "$LOG_FILE"
php artisan db:seed --force >> "$LOG_FILE" 2>&1
if [ $? -eq 0 ]; then echo "Seeding successful." >> "$LOG_FILE"; else echo "Seeding FAILED." >> "$LOG_FILE"; fi
php artisan storage:link >> "$LOG_FILE" 2>&1
php artisan filament:assets >> "$LOG_FILE" 2>&1
php artisan config:cache >> "$LOG_FILE" 2>&1
php artisan route:cache >> "$LOG_FILE" 2>&1
php artisan view:cache >> "$LOG_FILE" 2>&1

echo "Startup process complete." >> "$LOG_FILE"



# 6. Start Services manually to ensure they stay alive
echo "Starting PHP-FPM..." >> "$LOG_FILE"
service php8.2-fpm start >> "$LOG_FILE" 2>&1

echo "Starting Nginx in foreground..." >> "$LOG_FILE"
# Overwrite the default config one last time before starting
cp "$NGINX_CONF" /etc/nginx/sites-available/default
cp "$NGINX_CONF" /etc/nginx/sites-enabled/default

nginx -g "daemon off;"
