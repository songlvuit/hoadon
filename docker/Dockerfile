# Một container chạy Nginx + PHP-FPM (qua supervisord) cho app Laravel/Filament.
FROM php:8.3-fpm-alpine

RUN apk add --no-cache nginx supervisor postgresql-client icu-libs libzip libpng freetype libjpeg-turbo tzdata \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS icu-dev libzip-dev postgresql-dev libpng-dev freetype-dev libjpeg-turbo-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo_pgsql intl zip bcmath gd opcache exif \
    && apk del .build-deps \
    && cp /usr/share/zoneinfo/Asia/Ho_Chi_Minh /etc/localtime && echo "Asia/Ho_Chi_Minh" > /etc/timezone

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Cài thư viện trước để tận dụng cache khi chỉ sửa code
COPY composer.json composer.lock* ./
RUN composer install --no-dev --no-scripts --no-autoloader --no-interaction --prefer-dist

COPY . .
RUN composer dump-autoload --optimize --no-dev \
    && php artisan package:discover --ansi \
    && php artisan filament:assets \
    && mkdir -p storage/framework/{cache,sessions,views} storage/logs storage/app/private bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["supervisord", "-c", "/etc/supervisord.conf"]
