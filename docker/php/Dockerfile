# syntax=docker/dockerfile:1

###############################################################################
# base — PHP 8.4 FPM + extensions communes à tous les environnements
###############################################################################
FROM php:8.4-fpm-alpine AS base

# install-php-extensions gère les dépendances système de chaque extension
COPY --from=mlocati/php-extension-installer:latest \
     /usr/bin/install-php-extensions /usr/local/bin/

RUN apk add --no-cache git unzip icu-data-full \
 && install-php-extensions \
        pdo_mysql \
        intl \
        opcache \
        zip \
        gd \
        exif \
        apcu

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_MEMORY_LIMIT=-1

WORKDIR /app

###############################################################################
# dev — sources montées en bind mount, dépendances installées au démarrage
###############################################################################
FROM base AS dev

ENV APP_ENV=dev \
    APP_DEBUG=1

COPY docker/php/conf.d/app.ini      /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/php/conf.d/app.dev.ini  /usr/local/etc/php/conf.d/zz-app-dev.ini

COPY docker/php/docker-entrypoint.sh /usr/local/bin/docker-entrypoint
RUN chmod +x /usr/local/bin/docker-entrypoint

ENTRYPOINT ["docker-entrypoint"]
CMD ["php-fpm"]

###############################################################################
# prod — sources et dépendances figées dans l'image
###############################################################################
FROM base AS prod

ENV APP_ENV=prod \
    APP_DEBUG=0

COPY docker/php/conf.d/app.ini      /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/php/conf.d/app.prod.ini /usr/local/etc/php/conf.d/zz-app-prod.ini

# Couche dépendances séparée : rejouée uniquement si composer.* change
COPY backend/composer.json backend/composer.lock backend/symfony.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist \
 && composer clear-cache

COPY backend/ ./

RUN composer dump-autoload --classmap-authoritative --no-dev \
 && composer run-script --no-dev post-install-cmd \
 && mkdir -p var/cache var/log \
 && chown -R www-data:www-data var

USER www-data

CMD ["php-fpm"]
