#!/bin/bash

# Configuration
LOG_FILE="/home/site/wwwroot/startup_log.txt"

# Redirect stdout and stderr to the log file
exec > >(tee -a "$LOG_FILE") 2>&1

echo "--- Startup script started at $(date) ---"

# 1. Remove the default Azure welcome page if it exists
if [ -f "/home/site/wwwroot/hostingstart.html" ]; then
    echo "Removing hostingstart.html..."
    rm "/home/site/wwwroot/hostingstart.html"
fi

# 2. Configure Nginx to point to /public
# Azure PHP 8.2 images usually have the config at /etc/nginx/sites-available/default
NGINX_CONF="/etc/nginx/sites-available/default"

if [ -f "$NGINX_CONF" ]; then
    echo "Updating Nginx configuration to use Laravel's /public folder..."
    
    # Replace the root path
    sed -i 's|root /home/site/wwwroot;|root /home/site/wwwroot/public;|g' "$NGINX_CONF"
    
    # Ensure index.php is prioritized
    sed -i 's|index index.html index.htm index.php;|index index.php index.html index.htm;|g' "$NGINX_CONF"
    
    # Try to reload Nginx
    echo "Reloading Nginx..."
    service nginx reload || nginx -s reload || echo "Nginx reload failed, but config was updated."
else
    echo "Warning: Nginx configuration file not found at $NGINX_CONF"
    echo "Looking for other configurations..."
    ls /etc/nginx/sites-available/
fi

# 3. Fix folder permissions
echo "Setting permissions for storage and bootstrap/cache..."
chmod -R 775 /home/site/wwwroot/storage
chmod -R 775 /home/site/wwwroot/bootstrap/cache
chown -R www-data:www-data /home/site/wwwroot/storage /home/site/wwwroot/bootstrap/cache || echo "Chown skipped (normal on some tiers)"

# 4. Run Laravel tasks
echo "Running Laravel artisan commands..."
php /home/site/wwwroot/artisan migrate --force
php /home/site/wwwroot/artisan config:cache
php /home/site/wwwroot/artisan route:cache
php /home/site/wwwroot/artisan view:cache

echo "--- Startup script finished at $(date) ---"
