ARG PHP_VERSION=8.0

FROM php:${PHP_VERSION}-cli-alpine AS php_build

COPY --from=composer:2.0 /usr/bin/composer /usr/bin/composer

WORKDIR /arkitect

COPY bin-stub ./bin-stub
COPY src ./src
COPY composer.json ./composer.json
COPY box.json ./box.json
COPY phpunit.xml ./phpunit.xml
COPY psalm.xml ./psalm.xml

RUN  composer install --no-dev --optimize-autoloader --prefer-dist

RUN apk add zip git bash make icu-dev

ENV PATH="/arkitect/bin-stub:${PATH}"

ENTRYPOINT [ "phparkitect"]

FROM php_build AS with_xdebug

# compatibility chart: https://xdebug.org/docs/compat
ARG XDEBUG_VERSION="xdebug-3.4.0"
RUN apk add autoconf g++ linux-headers

RUN pecl install ${XDEBUG_VERSION} \
    && echo "zend_extension=$(find /usr/local/lib/php/extensions/ -name xdebug.so)" > /usr/local/etc/php/conf.d/xdebug.ini \
    && echo "xdebug.remote_enable=on" >> /usr/local/etc/php/conf.d/xdebug.ini \
    && echo "xdebug.remote_autostart=off" >> /usr/local/etc/php/conf.d/xdebug.ini
