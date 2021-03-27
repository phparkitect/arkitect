ARG PHP_VERSION=7.4

FROM php:${PHP_VERSION}-cli-alpine AS php_build

COPY --from=composer:2.0 /usr/bin/composer /usr/bin/composer

COPY ./docker/php/docker-entrypoint.sh /usr/local/bin/docker-entrypoint

RUN chmod +x /usr/local/bin/docker-entrypoint

WORKDIR /arkitect

COPY . .

RUN  composer install --no-dev --optimize-autoloader --prefer-dist

RUN apk add zip git bash make icu-dev

ENV PATH="/arkitect/bin-stub:${PATH}"

ENTRYPOINT [ "phparkitect"]
