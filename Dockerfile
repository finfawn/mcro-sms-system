FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --no-progress --optimize-autoloader
COPY . .
RUN composer install --no-dev --no-interaction --prefer-dist --no-progress --optimize-autoloader

FROM node:18-alpine AS assets
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY . .
RUN npm run build

FROM php:8.2-cli
RUN apt-get update && apt-get install -y unzip git curl libzip-dev zip && docker-php-ext-install pdo pdo_mysql zip
WORKDIR /var/www
COPY . .
COPY --from=vendor /app/vendor ./vendor
COPY --from=vendor /app/composer.lock ./composer.lock
COPY --from=assets /app/public/build ./public/build
ENV APP_ENV=production
EXPOSE 10000
CMD sh -lc "php artisan config:cache && php artisan route:cache && php artisan view:cache; php artisan migrate --force || true; php -S 0.0.0.0:${PORT:-10000} -t public"
