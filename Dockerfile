FROM php:8.3-fpm-alpine AS app_php

# Persistent dependencies
RUN apk add --no-cache \
    acl \
    fcgi \
    file \
    gettext \
    git \
    postgresql-dev \
    icu-dev \
    libzip-dev \
    zlib-dev

# PHP extensions
RUN docker-php-ext-install -j$(nproc) \
    intl \
    pdo_pgsql \
    zip \
    opcache

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Configure PHP for development
RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

COPY docker/php/conf.d/app.ini $PHP_INI_DIR/conf.d/
COPY docker/php/conf.d/app.prod.ini $PHP_INI_DIR/conf.d/

WORKDIR /app

# Prevent Composer from running as superuser
ENV COMPOSER_ALLOW_SUPERUSER=1

# Copy application files
COPY . /app

RUN set -eux; \
    mkdir -p var/cache var/log; \
    composer install --prefer-dist --no-autoloader --no-scripts --no-progress; \
    composer clear-cache; \
    composer dump-autoload --classmap-authoritative; \
    chmod +x bin/console; sync;

VOLUME /app/var

COPY docker/php/docker-entrypoint.sh /usr/local/bin/docker-entrypoint
RUN chmod +x /usr/local/bin/docker-entrypoint

ENTRYPOINT ["docker-entrypoint"]
CMD ["php-fpm"]
