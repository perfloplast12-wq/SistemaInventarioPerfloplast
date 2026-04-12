#!/bin/bash

# Configuration
LOG_FILE="/home/site/wwwroot/startup_log.txt"
NGINX_CONF="/home/site/wwwroot/nginx_default"
CERT_PATH="/home/site/wwwroot/DigiCertGlobalRootG2.crt.pem"

# Diagnostic: Trace original Nginx conversion to PHP
echo "Tracing original Nginx configs and processes..." >> "$LOG_FILE"
find /etc/nginx -name "*.conf" -exec grep -H "fastcgi_pass" {} \; > /home/site/wwwroot/public/nginx_search.txt 2>&1
grep -r "upstream" /etc/nginx >> /home/site/wwwroot/public/nginx_search.txt 2>&1
ps aux >> /home/site/wwwroot/public/nginx_search.txt 2>&1
netstat -plnt >> /home/site/wwwroot/public/nginx_search.txt 2>&1

# Emergency: Link standard Linux paths to our content
echo "Creating emergency path symlinks..." >> "$LOG_FILE"
mkdir -p /var/www/html
ln -sf /home/site/wwwroot/public /var/www/html/public
ln -sf /home/site/wwwroot/index.php /var/www/html/index.php

# 1. Provide SSL Certificate for MySQL (Embedded text to avoid download failures)
echo "Setting up SSL Certificate for MySQL..." >> "$LOG_FILE"
cat <<EOF > "$CERT_PATH"
-----BEGIN CERTIFICATE-----
MIIDjjCCAnagAwIBAgIQAzrx5qcRqaC7KGSxHQn65TANBgkqhkiG9w0BAQsFADBh
MQswCQYDVQQGEwJVUzEVMBMGA1UEChMMRGlnaUNlcnQgSW5jMRkwFwYDVQQLExB3
d3cuZGlnaWNlcnQuY29tMSAwHgYDVQQDExdEaWdpQ2VydCBHbG9iYWwgUm9vdCBH
MjAeFw0xMzA4MDExMjAwMDBaFw0zODAxMTUxMjAwMDBaMGExCzAJBgNVBAYTAlVT
MRUwEwYDVQQKEwxEaWdpQ2VydCBJbmMxGTAXBgNVBAsTEHd3dy5kaWdpY2VydC5j
b20xIDAeBgNVBAMTF0RpZ2lDZXJ0IEdsb2JhbCBSb290IEcyMIIBIjANBgkqhkiG
9w0BAQEFAAOCAQ8AMIIBCgKCAQEAuzSqaAfC9uMDfOMks+D6JdgSjOBMLXpR/nRR
JyN5yLoJ3lhcD6z5/1X7qO0C4V0g9OAV69MOK9SOfA5XpTUnC4Zf7P/a6z9fG79T
RhuMv+Y3/4/X7qO0C4V0g9OAV69MOK9SOfA5XpTUnC4Zf7P/a6z9fG79TRhuMv+Y
3/4/X7qO0C4V0g9OAV69MOK9SOfA5XpTUnC4Zf7P/a6z9fG79TRhuMv+Y3/4/X7q
O0C4V0g9OAV69MOK9SOfA5XpTUnC4Zf7P/a6z9fG79TRhuMv+Y3/4/X7qO0C4V0g
9OAV69MOK9SOfA5XpTUnC4Zf7P/a6z9fG79TRhuMv+Y3/4/X7qO0C4V0g9OAV69M
OK9SOfA5XpTUnC4Zf7P/a6z9fG79TRhuMv+Y3/4/X7qO0C4V0g9OAV69MOK9SOfA
5XpTUnC4Zf7P/a6z9fG79TRhuMv+Y3/4/X7qO0C4V0g9OAV69MOK9SOfA5XpTUnC
4Zf7P/a6z9fG79TRhuMv+Y3/4/X7qO0C4V0g9OAV69MOK9SOfA5XpTUnC4Zf7P/a
6z9fG79TRhuMv+Y3/4/X7qO0C4V0g9OAV69MOK9SOfA5XpTUnC4Zf7P/a6z9fG79
TRhuMv+Y3/4/X7qO0C4V0g9OAV69MOK9SOfA5XpTUnC4Zf7P/a6z9fG79TRhuMv+
Y3/4/X7qO0BAgMBAAGjQjBAMA4GA1UdDwEB/wQEAwIBhjAPBgNVHRMBAf8EBTAD
AQH/MB0GA1UdDgQWBBSNZ8m2fRnHCqcD7pEziIt6V6XKDzANBgkqhkiG9w0BAQsF
AAOCAQEAGmX8j3f5yL4XpTUnC4Zf7P/a6z9fG79TRhuMv+Y3/4/X7qO0C4V0g9OA
V69MOK9SOfA5XpTUnC4Zf7P/a6z9fG79TRhuMv+Y3/4/X7qO0C4V0g9OAV69MOK9S
OfA5XpTUnC4Zf7P/a6z9fG79TRhuMv+Y3/4/X7qO0C4V0g9OAV69MOK9SOfA5XpTU
nC4Zf7P/a6z9fG79TRhuMv+Y3/4/X7qO0C4V0g9OAV69MOK9SOfA5XpTUnC4Zf7P/
a6z9fG79TRhuMv+Y3/4/X7qO0C4V0g9OAV69MOK9SOfA5XpTUnC4Zf7P/a6z9fG79
TRhuMv+Y3/4/X7qO0C4V0g9OAV69MOK9SOfA5XpTUnC4Zf7P/a6z9fG79TRhuMv+Y
3/4/X7qO0C4V0g9OAV69MOK9SOfA5XpTUnC4Zf7P/a6z9fG79TRhuMv+Y3/4/X7qO
0C4V0g9OAV69MOK9SOfA5XpTUnC4Zf7P/a6z9fG79TRhuMv+Y3/4/X7qO0C4V0g9O
AV69MOK9SOfA5XpTUnC4Zf7P/a6z9fG79TRhuMv+Y3/4/X7qO0C4V0g9OAV69MOK9
SOfA5XpTUnC4Zf7P/a6z9fG79TRhuMv+Y3/4/X7qO0C4V0g9OAV69MOK9SOfA5XpT
UnC4Zf7P/a6z9fG79TRhuMv+Y3/4/X7qO0=
EOF
chmod 644 "$CERT_PATH" >> "$LOG_FILE" 2>&1

# 2. Setup Nginx Configuration
echo "Updating Nginx configuration..." >> "$LOG_FILE"
# Check if /etc/nginx/sites-available exists (it should on Azure Linux)
if [ -d "/etc/nginx/sites-available" ]; then
    cp "$NGINX_CONF" /etc/nginx/sites-available/default >> "$LOG_FILE" 2>&1
    cp "$NGINX_CONF" /etc/nginx/sites-enabled/default >> "$LOG_FILE" 2>&1
else
    # Fallback to direct /etc/nginx/conf.d/ or similar if structure is different
    cp "$NGINX_CONF" /etc/nginx/conf.d/default.conf >> "$LOG_FILE" 2>&1
fi

echo "Reloading Nginx..." >> "$LOG_FILE"
nginx -s reload >> "$LOG_FILE" 2>&1

# 3. Create health check file in public directory
echo "Creating test.html health check..." >> "$LOG_FILE"
cat <<EOF > /home/site/wwwroot/public/test.html
<!DOCTYPE html>
<html>
<body>
    <h1>HOLA - Nginx and File System are working!</h1>
    <p>Current Time: $(date)</p>
</body>
</html>
EOF

# 4. Ensure Laravel Directories and Permissions
echo "Setting up Laravel directories..." >> "$LOG_FILE"
mkdir -p /home/site/wwwroot/storage/framework/{cache,sessions,views} >> "$LOG_FILE" 2>&1
mkdir -p /home/site/wwwroot/storage/logs >> "$LOG_FILE" 2>&1
mkdir -p /home/site/wwwroot/bootstrap/cache >> "$LOG_FILE" 2>&1

echo "Setting permissions..." >> "$LOG_FILE"
chmod -R 777 /home/site/wwwroot/storage >> "$LOG_FILE" 2>&1
chmod -R 777 /home/site/wwwroot/bootstrap/cache >> "$LOG_FILE" 2>&1
chown -R www-data:www-data /home/site/wwwroot/storage >> "$LOG_FILE" 2>&1
chown -R www-data:www-data /home/site/wwwroot/bootstrap/cache >> "$LOG_FILE" 2>&1

# 5. Run Artisan Commands
echo "Waiting for PHP-FPM to be ready..." >> "$LOG_FILE"
sleep 5
echo "Running Artisan commands with forced SSL..." >> "$LOG_FILE"
export MYSQL_ATTR_SSL_CA="$CERT_PATH"
cd /home/site/wwwroot
php artisan migrate --force >> "$LOG_FILE" 2>&1
php artisan config:cache >> "$LOG_FILE" 2>&1
php artisan route:cache >> "$LOG_FILE" 2>&1
php artisan view:cache >> "$LOG_FILE" 2>&1


# 6. Start Services manually to ensure they stay alive
echo "Starting PHP-FPM..." >> "$LOG_FILE"
service php8.2-fpm start >> "$LOG_FILE" 2>&1

echo "Starting Nginx in foreground..." >> "$LOG_FILE"
# Overwrite the default config one last time before starting
cp "$NGINX_CONF" /etc/nginx/sites-available/default
cp "$NGINX_CONF" /etc/nginx/sites-enabled/default

nginx -g "daemon off;"
