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

# 3. Copy application files
COPY --chown=www-data:www-data . /var/www/html

# Ensure the SSL certificate is also available in the application root for base_path()
RUN cp /usr/local/share/ca-certificates/DigiCertGlobalRootG2.crt.pem /var/www/html/DigiCertGlobalRootG2.crt.pem && \
    chown www-data:www-data /var/www/html/DigiCertGlobalRootG2.crt.pem

# Set Web Root environment variable (used by serversideup image)
ENV WEB_ROOT=/var/www/html/public
ENV PHP_OPCACHE_ENABLE=1

# Expose port (Azure uses 8080)
EXPOSE 8080

# Optimization: install dependencies
USER www-data
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

