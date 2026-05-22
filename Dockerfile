# syntax=docker/dockerfile:1

# --- Composer dependencies (for Encore + Symfony) ---
FROM composer:2 AS vendor
WORKDIR /app

ARG INSTALL_DEV_DEPS=0

COPY composer.json composer.lock symfony.lock ./
RUN if [ "$INSTALL_DEV_DEPS" = "1" ]; then \
      composer install --no-interaction --prefer-dist --no-scripts --no-autoloader; \
    else \
      composer install --no-interaction --prefer-dist --no-dev --no-scripts --no-autoloader; \
    fi

# --- Frontend assets (Webpack Encore) ---
FROM node:20-bookworm AS assets
WORKDIR /app

COPY package.json package-lock.json webpack.config.js assets/controllers.json ./
COPY assets ./assets
COPY config/packages/webpack_encore.yaml ./config/packages/
COPY --from=vendor /app/vendor ./vendor

RUN npm ci && npm run build

# --- PHP application image (nginx + PHP-FPM) ---
FROM php:8.3-fpm-bookworm

ARG INSTALL_DEV_DEPS=0

RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    unzip \
    nginx \
    gettext-base \
    curl \
    libicu-dev \
    libzip-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql intl zip opcache gd \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock symfony.lock ./
COPY --from=vendor /app/vendor ./vendor

COPY . .

COPY --from=assets /app/public/build ./public/build

RUN if [ "$INSTALL_DEV_DEPS" = "1" ]; then \
      composer install --no-interaction --prefer-dist; \
    else \
      composer install --no-interaction --prefer-dist --no-dev; \
    fi \
    && composer dump-autoload --optimize --classmap-authoritative \
    && test -f vendor/autoload_runtime.php

RUN mkdir -p var/cache var/log public/uploads/images public/uploads/products \
    && chown -R www-data:www-data var public/uploads

COPY docker/nginx-main.conf /etc/nginx/nginx.conf
COPY docker/default.conf.template /etc/nginx/templates/default.conf.template
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

COPY docker/env.docker.dist /app/docker/env.docker.dist

EXPOSE 8080

ENV PORT=8080
ENV APP_ENV=prod

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
