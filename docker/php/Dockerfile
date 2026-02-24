FROM php:8.3-fpm-alpine

RUN apk add --no-cache \
    libpq-dev \
    icu-dev \
    libzip-dev \
    oniguruma-dev \
    $PHPIZE_DEPS

RUN docker-php-ext-install \
    pdo_pgsql \
    intl \
    zip \
    bcmath \
    opcache

RUN pecl install redis && docker-php-ext-enable redis

COPY php.ini /usr/local/etc/php/conf.d/custom.ini

WORKDIR /var/www/html

RUN chown -R www-data:www-data /var/www/html
