# --- Stage 1: Install Composer Dependencies ---
FROM composer:2 AS composer-builder
WORKDIR /app
COPY composer.* ./
# Install without scripts first to be faster and avoid errors
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist
COPY . .
RUN composer dump-autoload --optimize

# --- Stage 2: Build Assets (requires vendor from Stage 1) ---
FROM node:22-alpine AS node-builder
WORKDIR /app
COPY package*.json ./
RUN npm install
# Copy the codebase and the vendor folder from the previous stage
COPY . .
COPY --from=composer-builder /app/vendor ./vendor
RUN npm run build

# --- Stage 3: Production Runtime ---
FROM php:8.2-apache

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

# Configure and Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        bcmath \
        gd \
        zip \
        intl \
        xml \
        opcache

# Enable Apache modules
RUN a2enmod rewrite

# Set Document Root to /public
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Set working directory
WORKDIR /var/www/html

# Copy all files first
COPY . /var/www/html

# Copy vendor from composer stages and build from node stage
COPY --from=composer-builder /app/vendor /var/www/html/vendor
COPY --from=node-builder /app/public/build /var/www/html/public/build

# Build Optimization
RUN php artisan filament:optimize && \
    php artisan config:cache && \
    php artisan route:cache && \
    php artisan view:cache

# Set permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Port configuration
EXPOSE 8080
RUN sed -i 's/80/8080/g' /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf

CMD ["apache2-foreground"]
