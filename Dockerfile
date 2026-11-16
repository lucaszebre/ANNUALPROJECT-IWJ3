FROM php:8.5-cli-bookworm AS php-base

RUN apt-get update \
    && apt-get install -y --no-install-recommends libonig-dev libpq-dev libzip-dev unzip \
    && docker-php-ext-install bcmath mbstring pdo_pgsql zip \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

FROM php-base AS dependencies

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-scripts \
    --prefer-dist \
    --no-progress \
    --no-interaction
COPY . .
RUN mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache \
    && composer dump-autoload --no-dev --optimize --no-interaction \
    && php artisan package:discover --ansi

FROM node:24-bookworm-slim AS assets

WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY . .
RUN npm run build

FROM php-base AS application

COPY --from=dependencies /var/www/html /var/www/html
COPY --from=assets /app/public/build /var/www/html/public/build

EXPOSE 8000

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
