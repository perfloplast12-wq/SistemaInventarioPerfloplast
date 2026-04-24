# --- Stage 1: Install Composer Dependencies ---
FROM composer:2 AS composer-builder
WORKDIR /app
COPY composer.* ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist
COPY . .
RUN composer dump-autoload --optimize

# --- Stage 2: Build Assets ---
FROM node:22-alpine AS node-builder
WORKDIR /app
COPY package*.json ./
RUN npm install
COPY . .
COPY --from=composer-builder /app/vendor ./vendor
RUN npm run build

# --- Stage 3: Production Runtime (FrankenPHP for Octane Speed) ---
FROM dunglas/frankenphp:1-php8.2-bookworm

WORKDIR /var/www/html

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    libicu-dev \
    libxml2-dev \
    zip \
    unzip \
    git \
    && rm -rf /var/lib/apt/lists/*

# Install helper for PHP extensions
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions

# Install required PHP extensions for Laravel and Octane
RUN install-php-extensions \
    pdo_mysql \
    bcmath \
    gd \
    zip \
    intl \
    xml \
    opcache \
    pcntl

# Copy custom opcache configuration
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# Copy codebase
COPY . /var/www/html
COPY --from=composer-builder /app/vendor /var/www/html/vendor
COPY --from=node-builder /app/public/build /var/www/html/public/build

# Prepare storage and bootstrap cache
RUN mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Port configuration for DigitalOcean
EXPOSE 8080
ENV PORT 8080

COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

CMD ["/entrypoint.sh"]
