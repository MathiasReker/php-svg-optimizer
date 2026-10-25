# syntax=docker/dockerfile:1

ARG PHP_VERSION=8.3

FROM php:${PHP_VERSION}-cli

LABEL org.opencontainers.image.title="php-svg-optimizer development environment" \
      org.opencontainers.image.description="PHP CLI with Composer and Xdebug for developing php-svg-optimizer." \
      org.opencontainers.image.source="https://github.com/MathiasReker/php-svg-optimizer" \
      org.opencontainers.image.licenses="MIT"

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip \
    && apt-get install -y --no-install-recommends $PHPIZE_DEPS \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && apt-get purge -y --auto-remove $PHPIZE_DEPS \
    && rm -rf /var/lib/apt/lists/* /tmp/pear \
    && printf 'memory_limit=-1\n' > "$PHP_INI_DIR/conf.d/zz-development.ini"

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

ENV COMPOSER_HOME=/tmp/composer \
    COMPOSER_ROOT_VERSION=dev-develop \
    XDEBUG_MODE=off

WORKDIR /app
