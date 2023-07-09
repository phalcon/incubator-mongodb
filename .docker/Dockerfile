FROM composer:latest as composer
FROM php:7.4-cli

RUN pecl install phalcon mongodb && \
    docker-php-ext-enable phalcon mongodb

# Composer
COPY --from=composer /usr/bin/composer /usr/local/bin/composer
