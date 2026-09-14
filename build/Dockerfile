# syntax=docker/dockerfile:1
FROM php:8.3-fpm-alpine AS php-base

RUN apk add --no-cache supervisor fcgi icu-libs libzip oniguruma \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS icu-dev libzip-dev oniguruma-dev \
    && docker-php-ext-install -j"$(nproc)" intl mbstring pdo_mysql pcntl zip opcache \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps

WORKDIR /var/www/html

FROM php-base AS dependencies
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress --no-scripts --no-autoloader
COPY . .
RUN composer dump-autoload --no-dev --optimize --no-scripts \
    && php artisan package:discover --ansi \
    && composer check-platform-reqs --no-dev

FROM php-base AS runtime
ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    QUEUE_WORKER_ENABLED=false

COPY --from=dependencies /var/www/html /var/www/html
COPY build/supervisord.conf /etc/supervisord.conf
COPY build/php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY build/php-fpm.conf /usr/local/etc/php-fpm.d/zz-app.conf
COPY --chmod=755 build/entrypoint.sh /usr/local/bin/app-entrypoint
COPY --chmod=755 build/healthcheck.sh /usr/local/bin/app-healthcheck

RUN mkdir -p storage/app/public storage/app/private storage/framework/cache/data \
        storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwX storage bootstrap/cache

EXPOSE 9000
STOPSIGNAL SIGTERM
HEALTHCHECK --interval=30s --timeout=5s --start-period=20s --retries=3 \
    CMD app-healthcheck
ENTRYPOINT ["app-entrypoint"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
