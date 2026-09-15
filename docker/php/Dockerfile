FROM php:8.4-fpm

WORKDIR /var/www/html


RUN apt-get update && apt-get install -y \
    git \
    unzip \
    curl \
    libzip-dev \
    && docker-php-ext-install \
        pdo_mysql \
        zip


COPY --from=composer:latest /usr/bin/composer /usr/bin/composer


RUN pecl install redis \
    && docker-php-ext-enable redis
