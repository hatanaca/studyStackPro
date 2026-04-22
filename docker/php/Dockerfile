FROM php:8.4-fpm-alpine

# Actualiza pacotes base Alpine (musl, openssl, etc.) antes de instalar extensões.
RUN apk upgrade --no-cache

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

RUN apk upgrade --no-cache

COPY php.ini /usr/local/etc/php/conf.d/custom.ini
COPY www.conf /usr/local/etc/php-fpm.d/www.conf

WORKDIR /var/www/html

RUN chown -R www-data:www-data /var/www/html

USER www-data
