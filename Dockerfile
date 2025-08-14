# ---------- 1) Front build ----------
FROM node:18-alpine AS frontend
WORKDIR /app
COPY package*.json ./
RUN npm ci --silent
COPY vite.config.* ./
COPY resources ./resources
COPY public ./public
RUN npm run build

# ---------- 2) Composer deps ----------
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer config platform.php 8.3.0 \
 && composer install --no-dev --no-interaction --no-progress --prefer-dist --optimize-autoloader --no-scripts

# ---------- 3) PHP + Apache ----------
FROM php:8.3-apache AS app
WORKDIR /var/www/html

RUN apt-get update && apt-get install -y \
    libpng-dev libonig-dev libxml2-dev zip unzip git curl libzip-dev \
    libfreetype6-dev libjpeg62-turbo-dev \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip \
 && a2enmod rewrite \
 && rm -rf /var/lib/apt/lists/*

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri 's#DocumentRoot /var/www/html#DocumentRoot ${APACHE_DOCUMENT_ROOT}#g' /etc/apache2/sites-available/000-default.conf \
 && sed -ri 's#<Directory /var/www/>#<Directory ${APACHE_DOCUMENT_ROOT}>#g' /etc/apache2/apache2.conf \
 && sed -ri 's#AllowOverride None#AllowOverride All#g' /etc/apache2/apache2.conf

# код приложения
COPY . .

# кладём зависимости и фронтовый билд
COPY --from=vendor /app/vendor ./vendor
COPY --from=frontend /app/public/build ./public/build

# подчистим возможные закоммиченные кэши лары
RUN rm -f bootstrap/cache/*.php

# права на runtime‑каталоги
RUN chown -R www-data:www-data storage bootstrap/cache

# entrypoint
COPY docker/entrypoint.sh /usr/local/bin/app-entrypoint
RUN chmod +x /usr/local/bin/app-entrypoint

EXPOSE 80
CMD ["app-entrypoint"]
