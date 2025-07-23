FROM php:8.3-fpm

LABEL org.opencontainers.image.description="php-svg-optimizer is a PHP library designed to optimize SVG files by applying various transformations and cleanup operations."

RUN apt update && \
    apt -y upgrade && \
    apt -y install --no-install-recommends && \
    apt clean

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY . .

RUN composer install --optimize-autoloader
