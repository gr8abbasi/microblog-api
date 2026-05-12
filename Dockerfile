# Stage 1 — Install dependencies
FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install \
    --no-scripts \
    --no-autoloader \
    --no-interaction \
    --prefer-dist

COPY . .

RUN composer dump-autoload --optimize

# Stage 2 — Runtime
FROM php:8.4-fpm-alpine

# Install SQLite and composer
RUN apk add --no-cache \
    sqlite \
    sqlite-dev \
    && docker-php-ext-install pdo pdo_sqlite

# Copy composer binary into runtime stage so we can run it via exec
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Copy application + vendor from build stage
COPY --from=vendor /app /var/www

# Prepare application
RUN cp .env.example .env \
    && php artisan key:generate \
    && touch database/database.sqlite \
    && chown -R www-data:www-data storage database

EXPOSE 8000