FROM php:8.2-apache

# Install necessary extensions
RUN docker-php-ext-install pdo pdo_mysql mysqli
RUN pecl install redis \
    && docker-php-ext-enable redis

RUN apt-get update && apt-get install -y \
    redis-tools \
    unzip \
    git \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy source code into container
COPY src/ /var/www/html/

# Open port 80
EXPOSE 80
