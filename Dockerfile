# syntax=docker/dockerfile:1

# Keep these in sync with ADR-0001 / composer.json / .nvmrc.
ARG PHP_VERSION=8.5
ARG NODE_VERSION=24
ARG CADDY_VERSION=2.10

# ---------------------------------------------------------------------------
# php-base: PHP-FPM + the extensions the app needs (shared by every PHP stage)
# ---------------------------------------------------------------------------
FROM php:${PHP_VERSION}-fpm-alpine AS php-base
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
# Add gd / exif here later if image processing is needed.
RUN install-php-extensions pdo_pgsql pcntl intl zip bcmath opcache \
    && apk add --no-cache fcgi
COPY docker/php/app.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/php/fpm-pool.conf /usr/local/etc/php-fpm.d/zz-app.conf
WORKDIR /var/www/html

# ---------------------------------------------------------------------------
# Stage 1: Composer dependencies (production only)
# ---------------------------------------------------------------------------
FROM php-base AS vendor
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --prefer-dist \
    --no-scripts --no-autoloader
COPY . .
RUN composer dump-autoload --no-dev --optimize

# ---------------------------------------------------------------------------
# Stage 2: Vite assets build (needs app/ and resources/ for Tailwind scanning,
# and vendor/ in case a package ships CSS/JS used by the build)
# ---------------------------------------------------------------------------
FROM node:${NODE_VERSION}-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
COPY --from=vendor /app/vendor ./vendor
RUN npm run build

# ---------------------------------------------------------------------------
# Stage 3a (production): PHP-FPM runtime with code baked in
# Used by app, queue and scheduler containers.
# ---------------------------------------------------------------------------
FROM php-base AS app
ENV APP_ENV=production \
    APP_DEBUG=false
RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY docker/php/prod.ini /usr/local/etc/php/conf.d/zz-prod.ini
COPY --chown=www-data:www-data . .
COPY --from=vendor --chown=www-data:www-data /app/vendor ./vendor
COPY --from=assets --chown=www-data:www-data /app/public/build ./public/build
COPY --chmod=755 docker/php/entrypoint.sh /usr/local/bin/entrypoint
RUN mkdir -p storage/framework/cache storage/framework/sessions \
        storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache
USER www-data
ENTRYPOINT ["entrypoint"]
CMD ["php-fpm"]

# ---------------------------------------------------------------------------
# Stage 3b (production): Caddy
# ---------------------------------------------------------------------------
FROM caddy:${CADDY_VERSION}-alpine AS web
COPY docker/caddy/Caddyfile /etc/caddy/Caddyfile
COPY --from=app /var/www/html/public /var/www/html/public

# ---------------------------------------------------------------------------
# Development image: no code baked in (bind-mounted), Composer available,
# dependencies installed at container start into a named volume.
# ---------------------------------------------------------------------------
FROM php-base AS dev
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1
RUN cp "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"
COPY --chmod=755 docker/php/entrypoint-dev.sh /usr/local/bin/entrypoint
ENTRYPOINT ["entrypoint"]
CMD ["php-fpm"]
