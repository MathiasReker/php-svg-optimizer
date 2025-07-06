# Use a specific PHP version
FROM php:8.4-fpm

# Install system dependencies and tools (including Xdebug)
RUN apt update && \
    apt install -y git unzip curl libzip-dev && \
    docker-php-ext-install zip && \
    pecl install xdebug && \
    docker-php-ext-enable xdebug

# Configure Xdebug for code coverage
RUN echo "zend_extension=xdebug.so" >> /usr/local/etc/php/php.ini && \
    echo "xdebug.mode=coverage" >> /usr/local/etc/php/php.ini && \
    echo "xdebug.start_with_request=yes" >> /usr/local/etc/php/php.ini

# Copy Composer from the official image
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /app

# Copy application code
COPY . .

# Install PHP dependencies
RUN composer install --optimize-autoloader
