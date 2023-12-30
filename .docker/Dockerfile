FROM composer:latest as composer
FROM php:7.4-cli

RUN apt update && apt install git wget zip unzip libcurl4-openssl-dev pkg-config libssl-dev -y

RUN pecl install phalcon-5.4.0 mongodb && \
    docker-php-ext-enable phalcon mongodb

# Composer
COPY --from=composer /usr/bin/composer /usr/local/bin/composer
