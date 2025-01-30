FROM php:8.3-fpm-alpine AS app

RUN apk --no-cache add fcgi icu-dev libxml2-dev $PHPIZE_DEPS \
  && docker-php-ext-install opcache \
  && docker-php-ext-install soap \
  && docker-php-ext-enable sodium \
  && pecl install apcu && docker-php-ext-enable apcu \
  && pecl install opentelemetry-1.0.3 && docker-php-ext-enable opentelemetry

ARG ENABLE_API_XDEBUG="false"
# We don't enable xdebug by default
RUN if [ "$ENABLE_API_XDEBUG" = "true" ] ; then \
  apk add --update linux-headers && \
  pecl install xdebug && \
  docker-php-ext-enable xdebug && \
  apk del linux-headers; \
  fi

# Patch Vulnerabilities
RUN apk upgrade --no-cache openssl curl libcurl

# Get Latest CA Cert Bundle
ADD https://curl.se/ca/cacert.pem /etc/ssl/cert.pem
RUN chmod 0644 /etc/ssl/cert.pem

WORKDIR /var/www/

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY docker/php/apcu.ini /usr/local/etc/php/conf.d/apcu.ini
COPY docker/php/memory_limit.ini /usr/local/etc/php/conf.d/memory-limit.ini
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/www.conf
COPY docker/php/xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini

COPY docker/php-entrypoint /usr/local/bin/opg-paper-identity-entrypoint
COPY docker/scripts /usr/local/bin/scripts

RUN chmod +x /usr/local/bin/opg-paper-identity-entrypoint

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

ENV OTEL_PHP_AUTOLOAD_ENABLED=true
ENV OTEL_SERVICE_NAME=api.lpa-identity-check.local
ENV OTEL_TRACES_EXPORTER=none
ENV OTEL_METRICS_EXPORTER=none
ENV OTEL_LOGS_EXPORTER=none
ENV OTEL_PROPAGATORS=xray

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

HEALTHCHECK --interval=15s --timeout=10s --start-period=10s --retries=3 \
  CMD SCRIPT_NAME=/ping SCRIPT_FILENAME=/ping REQUEST_METHOD=GET cgi-fcgi -bind -connect 127.0.0.1:9000

ENTRYPOINT ["/usr/local/bin/opg-paper-identity-entrypoint"]
CMD ["php-fpm"]

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
