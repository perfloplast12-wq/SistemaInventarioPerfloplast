FROM serversideup/php:8.2-fpm-nginx

# Switch to root to install system dependencies
USER root

# Install system dependencies, Node.js and PHP extensions
RUN apt-get update && apt-get install -y \
    libpng-dev libicu-dev libjpeg-dev libfreetype6-dev zip unzip git curl \
    && curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install extra PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd bcmath intl

# 1. Install SSL Certificate into System CA Store
COPY DigiCertGlobalRootG2.crt.pem /usr/local/share/ca-certificates/DigiCertGlobalRootG2.crt.pem
RUN chmod 644 /usr/local/share/ca-certificates/DigiCertGlobalRootG2.crt.pem && update-ca-certificates

# 2. Copy application files to /var/www/html (project root IS the Laravel app)
WORKDIR /var/www/html
COPY --chown=www-data:www-data . .

# 3. Install dependencies and build assets from root
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts --ignore-platform-reqs \
    && npm install \
    && npm run build

# 4. Configure Nginx and permissions
COPY nginx_default /etc/nginx/sites-available/default
COPY nginx_default /etc/nginx/sites-enabled/default
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# 5. Final setup
USER www-data
ENV WEB_ROOT=/var/www/html/public
EXPOSE 8080
