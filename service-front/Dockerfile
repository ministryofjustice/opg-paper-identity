FROM node:21.4.0-alpine3.17 AS asset-env

WORKDIR /app

RUN apk add --no-cache git

RUN mkdir -p public

COPY package.json .
COPY yarn.lock .
RUN yarn

COPY web web
RUN yarn build

RUN wget -O /app/yoti-supported-documents.json https://api.yoti.com/idverify/v1/supported-documents

FROM php:8.3-fpm-alpine AS app

RUN mkdir /app
WORKDIR /var/www

RUN apk --no-cache add fcgi icu-dev autoconf libffi-dev linux-headers $PHPIZE_DEPS \
    && docker-php-ext-install ffi intl opcache \
    && docker-php-ext-enable sodium

# Patch Vulnerabilities
RUN apk upgrade --no-cache openssl

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY docker/php/memory_limit.ini /usr/local/etc/php/conf.d/memory-limit.ini
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/www.conf

RUN mkdir /tmp/cache &&\
    chown -R www-data /tmp/cache &&\
    chown -R www-data /var/www/

ENV PHP_FPM_MAX_CHILDREN "8"
ENV PHP_FPM_MAX_START_CHILDREN "4"
ENV PHP_FPM_MIN_SPARE_SERVERS "2"
ENV PHP_FPM_MAX_SPARE_SERVERS "4"
ENV PHP_FPM_MEMORY_LIMIT "256M"

USER "www-data"
VOLUME ["/var/www/public"]

COPY --from=composer /usr/bin/composer /usr/bin/
COPY composer.json composer.json
COPY composer.lock composer.lock

RUN composer check-platform-reqs &&\
    composer install --prefer-dist --no-dev --no-interaction --no-scripts &&\
    composer dumpautoload -o

COPY public public
COPY --from=asset-env /app/public public
COPY config config
COPY module module

COPY --from=asset-env /app/yoti-supported-documents.json module/Application/config/yoti-supported-documents.json

STOPSIGNAL SIGQUIT

FROM app AS development
USER "root"
RUN pecl install pcov && docker-php-ext-enable pcov;

USER "www-data"
RUN composer install --prefer-dist --no-interaction --no-scripts &&\
    composer dumpautoload -o

COPY phpcs.xml phpcs.xml
COPY phpunit.xml phpunit.xml
COPY psalm.xml psalm.xml

FROM app AS production
