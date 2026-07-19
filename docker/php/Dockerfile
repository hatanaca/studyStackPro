FROM php:8.4-fpm-alpine

# Actualiza pacotes base Alpine (musl, openssl, etc.) antes de instalar extensões.
RUN apk upgrade --no-cache

RUN apk add --no-cache \
    libpq-dev \
    icu-dev \
    libzip-dev \
    oniguruma-dev \
    su-exec \
    shadow \
    $PHPIZE_DEPS

# Sincroniza UID do www-data com o container CLI (Debian usa uid 33).
# Evita "Permission denied" em storage/logs quando múltiplos containers
# (php-fpm Alpine uid 82, horizon/scheduler Debian uid 33) compartilham volume.
RUN usermod -u 33 www-data && groupmod -g 33 www-data

RUN docker-php-ext-install \
    pdo_pgsql \
    intl \
    zip \
    bcmath \
    opcache

RUN pecl install redis && docker-php-ext-enable redis

RUN apk upgrade --no-cache

COPY php.ini.dev /usr/local/etc/php/conf.d/custom.ini
COPY www.conf /usr/local/etc/php-fpm.d/www.conf
COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

WORKDIR /var/www/html

RUN chown -R www-data:www-data /var/www/html

ENTRYPOINT ["entrypoint.sh"]
