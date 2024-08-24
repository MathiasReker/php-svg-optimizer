# Use a specific version of Composer to ensure consistency
FROM composer:latest AS composer

# Use a specific PHP version for predictability
FROM php:8.3-fpm

# Copy Composer from the composer image
COPY --from=composer /usr/bin/composer /usr/bin/composer

# Set label for the image
LABEL org.opencontainers.image.description="php-svg-optimizer is a PHP library designed to optimize SVG files by applying various transformations and cleanup operations."

# Install system dependencies and clean up
RUN apt-get update && \
    apt-get -y upgrade && \
    apt-get -y install --no-install-recommends \
    && apt-get clean

# Set working directory
WORKDIR /app

# Copy application code to the container
COPY . .

# Install PHP dependencies
RUN composer install --optimize-autoloader
