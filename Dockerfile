FROM php:8.2-apache

# Install necessary extensions
RUN docker-php-ext-install pdo pdo_mysql mysqli
RUN pecl install redis \
    && docker-php-ext-enable redis

RUN apt-get update && apt-get install -y redis-tools

# Copy source code into container
COPY src/ /var/www/html/

# Open port 80
EXPOSE 80
