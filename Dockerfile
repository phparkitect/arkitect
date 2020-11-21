FROM php:7.3-cli

MAINTAINER Michele Orselli

RUN apt-get update && apt-get install -y git zip

RUN curl -L https://cs.symfony.com/download/php-cs-fixer-v2.phar -o php-cs-fixer

RUN pecl install xdebug-2.9.5 \
	&& docker-php-ext-enable xdebug

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

WORKDIR /var/www
