FROM php:8.2-apache

# Install dependencies and extensions
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    && docker-php-ext-install pdo pdo_mysql zip

# Enable Apache Mod Rewrite
RUN a2enmod rewrite

# Set the working directory
WORKDIR /var/www/html
