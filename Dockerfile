# --- Stage 1: Build PHP Dependencies ---
FROM composer:latest AS php-builder
WORKDIR /app
COPY composer.json composer.lock ./
# We need the whole app for artisan/autoloader hooks if any
COPY . .
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts --ignore-platform-reqs

# --- Stage 2: Build Visual Assets ---
FROM node:20-slim AS assets-builder
WORKDIR /app
COPY package*.json ./
RUN npm install
COPY . .
# CRITICAL: Copy vendor from Stage 1 because Filament theme building needs it!
COPY --from=php-builder /app/vendor /app/vendor
RUN npm run build

# --- Stage 3: Final Production Image ---
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

# 4. Copy vendor from Stage 1
COPY --from=php-builder --chown=www-data:www-data /app/vendor /var/www/html/vendor

# 5. Copy compiled assets from Stage 2
COPY --from=assets-builder --chown=www-data:www-data /app/public/build /var/www/html/public/build

# 6. Fix permissions for storage and cache
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# 7. Final cleanup and environment
USER www-data

# Expose port (Azure uses 8080)
EXPOSE 8080
