FROM public.ecr.aws/docker/library/php:8.5-fpm-alpine

RUN apk update && \
    apk upgrade && \
    apk add --no-cache \
    perl \
    wget \
    icu-dev \
    zlib-dev \
    libzip-dev \
    libpng-dev \
    freetype-dev \
    libjpeg-turbo-dev \
    git \
    autoconf \
    g++ \
    make

RUN docker-php-ext-configure gd --with-freetype --with-jpeg

RUN docker-php-ext-install \
    intl \
    zip \
    pdo_mysql \
    mysqli \
    gd

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .
