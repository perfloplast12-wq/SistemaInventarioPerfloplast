FROM serversideup/php:8.2-fpm-nginx

# Switch to root to install system dependencies
USER root

# Install system dependencies and PHP extensions for Laravel
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    unzip \
    git \
    curl \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install extra PHP extensions if needed
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd bcmath

# Set up SSL Certificate for MySQL (DigiCert required by Azure)
COPY DigiCertGlobalRootG2.crt.pem /usr/local/share/ca-certificates/DigiCertGlobalRootG2.crt.pem
RUN chmod 644 /usr/local/share/ca-certificates/DigiCertGlobalRootG2.crt.pem && update-ca-certificates

# Copy application files with correct ownership
COPY --chown=www-data:www-data . /var/www/html

# Set Web Root to /public (Essential for Laravel)
ENV WEB_ROOT=/var/www/html/public
ENV PHP_OPCACHE_ENABLE=1

# Expose port (Azure App Service often uses 8080 for Docker)
EXPOSE 8080

# Switch back to the unprivileged user for security
USER www-data

# Optimization: install dependencies and cache settings
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts
RUN php artisan config:cache && php artisan route:cache && php artisan view:cache
