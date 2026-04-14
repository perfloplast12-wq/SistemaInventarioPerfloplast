FROM serversideup/php:8.2-fpm-nginx

# Switch to root to install system dependencies
USER root

# Install system dependencies and PHP extensions for Laravel
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libicu-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    unzip \
    git \
    curl \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install extra PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd bcmath intl

# 1. Install SSL Certificate into System CA Store
COPY DigiCertGlobalRootG2.crt.pem /usr/local/share/ca-certificates/DigiCertGlobalRootG2.crt.pem
RUN chmod 644 /usr/local/share/ca-certificates/DigiCertGlobalRootG2.crt.pem && update-ca-certificates

# 2. Copy Nginx Configuration into the image
COPY nginx_default /etc/nginx/sites-available/default
COPY nginx_default /etc/nginx/sites-enabled/default

# 3. Copy application files and set permissions early
COPY --chown=www-data:www-data . /var/www/html

# 4. Install dependencies as root to ensure all tools are available, then fix permissions
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts --no-progress

# 5. Fix permissions for storage and cache
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# 6. Final cleanup and environment
USER www-data

