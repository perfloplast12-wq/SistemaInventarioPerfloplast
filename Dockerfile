FROM serversideup/php:8.2-fpm-nginx

USER root

# Install system dependencies, Node.js and PHP extensions
RUN apt-get update && apt-get install -y \
    libpng-dev libicu-dev libjpeg-dev libfreetype6-dev zip unzip git curl \
    && curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd bcmath intl

# SSL Certificate
COPY DigiCertGlobalRootG2.crt.pem /usr/local/share/ca-certificates/DigiCertGlobalRootG2.crt.pem
RUN chmod 644 /usr/local/share/ca-certificates/DigiCertGlobalRootG2.crt.pem && update-ca-certificates

# Copy application
WORKDIR /var/www/html
COPY --chown=www-data:www-data . .

# Debug: show what's in Models directory
RUN echo "=== Files in app/Models/ ===" && ls -la app/Models/ && echo "=== END ==="

# Install PHP dependencies
RUN composer install --no-dev --no-interaction --no-scripts --ignore-platform-reqs

# Force regenerate optimized classmap
RUN composer dump-autoload --optimize --no-interaction --no-scripts

# Debug: check classmap (|| true so build doesn't fail)
RUN grep -i "AuditLog" vendor/composer/autoload_classmap.php || echo "WARNING: AuditLog NOT found in classmap"

# Install frontend and build
RUN npm install && npm run build

# Nginx config
COPY nginx_default /etc/nginx/sites-available/default
COPY nginx_default /etc/nginx/sites-enabled/default

# Permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

USER www-data
ENV WEB_ROOT=/var/www/html/public
EXPOSE 8080
