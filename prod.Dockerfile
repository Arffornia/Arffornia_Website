ARG HTML_ENDPOINT="/var/www/html/"

# Composer dependencies
FROM composer:2.10.3 AS composer-builder

ARG HTML_ENDPOINT

WORKDIR ${HTML_ENDPOINT}

COPY composer.json composer.lock ${HTML_ENDPOINT}

# Composer require that composer.json#auload#classmap dirs exist (e.g.: factories/, seeders/)
# RUN mkdir -p ${HTML_ENDPOINT}database/{factories,seeds}

# See composer install doc: https://getcomposer.org/doc/03-cli.md#install-i
# Production image: no dev packages. The PHP version is pinned by
# composer.json `config.platform.php`, and the runner stage re-checks the real
# runtime with `composer check-platform-reqs`.
RUN composer install \
    --no-dev \
    --prefer-dist \
    --no-scripts \
    --no-autoloader \
    --no-progress

# NPM dependencies
FROM node:22-alpine AS npm-builder

ARG HTML_ENDPOINT

WORKDIR ${HTML_ENDPOINT}

COPY package.json package-lock.json vite.config.js ${HTML_ENDPOINT}

# Frontend
COPY resources ${HTML_ENDPOINT}resources/
COPY public ${HTML_ENDPOINT}public/

RUN npm ci && npm run build

FROM php:8.5-fpm-alpine AS runner

ARG HTML_ENDPOINT

WORKDIR ${HTML_ENDPOINT}

# OPcache is always compiled in since PHP 8.5: only its settings are added (opcache.ini).
RUN apk add --no-cache \
    libzip-dev \
    zip \
    unzip \
    postgresql-client \
    libpq-dev \
    bash \
    rsync \
    && docker-php-ext-install zip pdo pdo_pgsql pgsql \
    && mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

COPY .docker/php/opcache.ini $PHP_INI_DIR/conf.d/

COPY --from=composer-builder /usr/bin/composer /usr/local/bin/composer

COPY --from=composer-builder ${HTML_ENDPOINT}/vendor/ ${HTML_ENDPOINT}/vendor/
COPY --from=npm-builder ${HTML_ENDPOINT}/public/ ${HTML_ENDPOINT}/public/
COPY --chown=www-data:www-data . ${HTML_ENDPOINT}

RUN composer dump-autoload -o --no-dev \
    && composer check-platform-reqs --no-dev \
    && rm -rf /usr/local/bin/composer
