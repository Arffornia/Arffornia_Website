ARG HTML_ENDPOINT="/var/www/html/"

# Composer dependencies
FROM composer:2.8.6 AS composer-builder

ARG HTML_ENDPOINT

WORKDIR ${HTML_ENDPOINT}

COPY composer.json composer.lock ${HTML_ENDPOINT}

# Composer require that composer.json#auload#classmap dirs exist (e.g.: factories/, seeders/)
# RUN mkdir -p ${HTML_ENDPOINT}database/{factories,seeds}

# See composer install doc: https://getcomposer.org/doc/03-cli.md#install-i
RUN composer install \
    --prefer-dist \
    --no-scripts \
    --no-autoloader \
    --no-progress \
    --ignore-platform-reqs

# NPM dependencies
FROM node:18-alpine3.20 AS npm-builder

ARG HTML_ENDPOINT

WORKDIR ${HTML_ENDPOINT}

COPY package.json package-lock.json vite.config.js ${HTML_ENDPOINT}

# Frontend
COPY resources ${HTML_ENDPOINT}resources/
COPY public ${HTML_ENDPOINT}public/

RUN npm ci && npm run build

FROM php:8.3-fpm-alpine AS runner

ARG HTML_ENDPOINT

WORKDIR ${HTML_ENDPOINT}

RUN apk add --no-cache \
    libzip-dev \
    zip \
    unzip \
    mysql-client \
    bash \
    && docker-php-ext-install zip pdo pdo_mysql opcache \
    && mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

COPY .docker/php/opcache.ini $PHP_INI_DIR/conf.d/

COPY --from=composer-builder /usr/bin/composer /usr/local/bin/composer

COPY --from=composer-builder ${HTML_ENDPOINT}/vendor/ ${HTML_ENDPOINT}/vendor/
COPY --from=npm-builder ${HTML_ENDPOINT}/public/ ${HTML_ENDPOINT}/public/
COPY --chown=www-data:www-data . ${HTML_ENDPOINT}

RUN composer dump -o \
    && composer check-platform-reqs \
    && rm -rf /usr/local/bin/composer
