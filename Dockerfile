FROM php:7.4.12-cli-alpine

MAINTAINER Michele Orselli

RUN apk add zip git bash make icu-dev

RUN docker-php-ext-configure intl && docker-php-ext-install intl

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

WORKDIR /var/www
