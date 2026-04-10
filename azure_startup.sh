#!/bin/bash

# Configuration
LOG_FILE="/home/site/wwwroot/startup_log.txt"
exec > >(tee -a "$LOG_FILE") 2>&1

echo "--- Startup script started at $(date) ---"

# 1. Clean up default Azure welcome page
if [ -f "/home/site/wwwroot/hostingstart.html" ]; then
    echo "Removing hostingstart.html..."
    rm "/home/site/wwwroot/hostingstart.html"
fi

# 1.5 Download SSL Certificate for MySQL (Azure Flexible Server Requirement)
CERT_URL="https://dl.cacerts.digicert.com/DigiCertGlobalRootG2.crt.pem"
CERT_PATH="/home/site/wwwroot/DigiCertGlobalRootG2.crt.pem"
if [ ! -f "$CERT_PATH" ]; then
    echo "Downloading DigiCert Global Root G2 certificate..."
    curl -sL -o "$CERT_PATH" "$CERT_URL"
    chmod 644 "$CERT_PATH"
fi

# 2. Install custom Nginx configuration
NGINX_CONF_DEST="/etc/nginx/sites-available/default"
NGINX_CONF_ENABLED="/etc/nginx/sites-enabled/default"
NGINX_CONF_SRC="/home/site/wwwroot/nginx_default"

if [ -f "$NGINX_CONF_SRC" ]; then
    echo "Installing custom Nginx configuration..."
    cp "$NGINX_CONF_SRC" "$NGINX_CONF_DEST"
    # Force overwrite in sites-enabled to bypass any symlink issues
    cp "$NGINX_CONF_SRC" "$NGINX_CONF_ENABLED"
    
    echo "Reloading Nginx..."
    service nginx reload || nginx -s reload || echo "Manual Nginx reload failed"
else
    echo "ERROR: Custom Nginx configuration source not found at $NGINX_CONF_SRC"
fi

# 3. Create all required Laravel directories
echo "Creating required directories..."
mkdir -p /home/site/wwwroot/storage/framework/cache
mkdir -p /home/site/wwwroot/storage/framework/sessions
mkdir -p /home/site/wwwroot/storage/framework/views
mkdir -p /home/site/wwwroot/storage/logs
mkdir -p /home/site/wwwroot/bootstrap/cache

# 4. Fix folder permissions
echo "Setting permissions..."
chmod -R 777 /home/site/wwwroot/storage
chmod -R 777 /home/site/wwwroot/bootstrap/cache
chown -R www-data:www-data /home/site/wwwroot/storage /home/site/wwwroot/bootstrap/cache || echo "Chown skipped"

# 5. Run Laravel tasks
echo "Running Laravel tasks..."
# We use --no-interaction to avoid hangs
php /home/site/wwwroot/artisan migrate --force --no-interaction
php /home/site/wwwroot/artisan config:cache
php /home/site/wwwroot/artisan route:cache
php /home/site/wwwroot/artisan view:cache

echo "--- Startup script finished at $(date) ---"
