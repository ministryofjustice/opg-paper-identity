FROM php:8.3-fpm-alpine AS app

RUN apk --no-cache add fcgi icu-dev libxml2-dev $PHPIZE_DEPS \
  && docker-php-ext-install opcache \
  && docker-php-ext-install soap \
  && docker-php-ext-enable sodium
RUN pecl install apcu && docker-php-ext-enable apcu

# Patch Vulnerabilities
RUN apk upgrade --no-cache openssl

WORKDIR /var/www/

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY docker/php/memory_limit.ini /usr/local/etc/php/conf.d/memory-limit.ini
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/www.conf
COPY docker/php/apcu.ini /usr/local/etc/php/conf.d/apcu.ini

COPY docker/php-entrypoint /usr/local/bin/opg-paper-identity-entrypoint
COPY docker/scripts /usr/local/bin/scripts

RUN chmod +x /usr/local/bin/opg-paper-identity-entrypoint
ENTRYPOINT ["/usr/local/bin/opg-paper-identity-entrypoint"]
CMD ["php-fpm"]

RUN mkdir /tmp/cache &&\
  chown -R www-data /tmp/cache &&\
  mkdir -m 700 /opg-private &&\
  chown -R www-data /opg-private &&\
  chown -R www-data /var/www/

ENV PHP_FPM_MAX_CHILDREN "8"
ENV PHP_FPM_MAX_START_CHILDREN "4"
ENV PHP_FPM_MIN_SPARE_SERVERS "2"
ENV PHP_FPM_MAX_SPARE_SERVERS "4"
ENV PHP_FPM_MEMORY_LIMIT "256M"

USER "www-data"

COPY --from=composer /usr/bin/composer /usr/bin/
COPY composer.json composer.json
COPY composer.lock composer.lock

RUN composer check-platform-reqs &&\
  composer install --prefer-dist --no-dev --no-interaction --no-scripts &&\
  composer dumpautoload -o

COPY public public
COPY config config
COPY module module
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
