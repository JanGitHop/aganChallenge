FROM dunglas/frankenphp:1-php8.3 AS frankenphp_base

# Install PHP extensions
RUN install-php-extensions \
    pdo_pgsql \
    intl \
    zip \
    opcache

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Prevent Composer from running as superuser
ENV COMPOSER_ALLOW_SUPERUSER=1

# Development stage
FROM frankenphp_base AS frankenphp_dev

# Enable Xdebug in development
RUN install-php-extensions xdebug

# Development PHP configuration
COPY docker/frankenphp/conf.d/app.dev.ini $PHP_INI_DIR/conf.d/
COPY docker/frankenphp/Caddyfile /etc/caddy/Caddyfile

# Disable worker mode by default
ENV FRANKENPHP_CONFIG=""

# Application files will be mounted as volume
VOLUME /app/var

# Production stage
FROM frankenphp_base AS frankenphp_prod

# Production PHP configuration
COPY docker/frankenphp/conf.d/app.prod.ini $PHP_INI_DIR/conf.d/
COPY docker/frankenphp/Caddyfile /etc/caddy/Caddyfile

# Copy application files
COPY . /app

RUN set -eux; \
    mkdir -p var/cache var/log; \
    composer install --prefer-dist --no-dev --no-autoloader --no-scripts --no-progress; \
    composer clear-cache; \
    composer dump-autoload --classmap-authoritative --no-dev; \
    chmod +x bin/console; sync;
