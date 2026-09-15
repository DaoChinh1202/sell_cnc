FROM php:8.4-fpm

WORKDIR /var/www/html


RUN apt-get update && apt-get install -y \
    git \
    unzip \
    curl \
    libzip-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libwebp-dev \
    fonts-dejavu-core \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install \
        gd \
        pdo_mysql \
        zip


COPY --from=composer:latest /usr/bin/composer /usr/bin/composer


RUN pecl install redis \
    && docker-php-ext-enable redis
