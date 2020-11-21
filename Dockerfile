FROM php:7.4-cli

MAINTAINER Michele Orselli

RUN apt-get update && apt-get install -y git zip

RUN pecl install xdebug-2.9.5 \
	&& docker-php-ext-enable xdebug

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

WORKDIR /var/www
