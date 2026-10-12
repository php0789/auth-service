FROM php:8.3-apache AS php-base

RUN apt-get update \
    && apt-get install -y \
        git \
        unzip \
        curl \
        libicu-dev \
        libzip-dev \
        libonig-dev \
    && docker-php-ext-install \
        intl \
        mbstring \
        pdo_mysql \
        pcntl \
        zip \
        opcache \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

# Laravel public directory
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

RUN sed -ri \
    -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
    /etc/apache2/sites-available/000-default.conf \
    /etc/apache2/apache2.conf

RUN printf '<Directory /var/www/html/public>\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>\n' \
    > /etc/apache2/conf-available/laravel.conf \
    && a2enconf laravel

FROM php-base AS dependencies

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

COPY composer.json composer.lock ./

RUN composer install \
    --prefer-dist \
    --no-interaction \
    --no-progress \
    --no-scripts \
    --no-autoloader

COPY . .

RUN composer dump-autoload \
        --optimize \
        --no-scripts \
    && php artisan package:discover --ansi \
    && composer check-platform-reqs

FROM php-base AS runtime

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

COPY --from=dependencies /var/www/html /var/www/html

COPY build/php.ini \
    /usr/local/etc/php/conf.d/zz-app.ini

COPY --chmod=755 build/entrypoint.sh \
    /usr/local/bin/app-entrypoint

COPY --chmod=755 build/healthcheck.sh \
    /usr/local/bin/app-healthcheck

RUN mkdir -p \
        storage/app/public \
        storage/app/private \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwX storage bootstrap/cache

EXPOSE 80

HEALTHCHECK \
    --interval=30s \
    --timeout=5s \
    --start-period=20s \
    --retries=3 \
    CMD app-healthcheck

ENTRYPOINT ["app-entrypoint"]

CMD ["apache2-foreground"]
