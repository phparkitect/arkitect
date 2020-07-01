FROM php:7.3-cli

MAINTAINER Michele Orselli

RUN apt-get update && apt-get install -y git
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" && php composer-setup.php --install-dir=/usr/local/bin --filename=composer

WORKDIR /var/www



