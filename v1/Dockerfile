FROM php:8.2-apache

# Install necessary extensions
RUN docker-php-ext-install pdo pdo_mysql mysqli
RUN pecl install redis \
    && docker-php-ext-enable redis

# Copy source code into container
COPY src/ /var/www/html/

# Open port 80
EXPOSE 80
