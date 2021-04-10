ARG PHP_VERSION=7.1

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
