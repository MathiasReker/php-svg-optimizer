FROM php:8.3-fpm

LABEL org.opencontainers.image.description="php-svg-optimizer is a PHP library designed to optimize SVG files by applying various transformations and cleanup operations."

RUN apt-get update && apt-get install -y --no-install-recommends \
    libxml2-dev \
    libonig-dev \
    unzip \
    && docker-php-ext-install dom mbstring \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install --no-interaction --prefer-dist --optimize-autoloader

COPY . .
