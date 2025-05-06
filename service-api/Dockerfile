FROM php:8.3-fpm-alpine AS app

RUN apk --no-cache add fcgi icu-dev libxml2-dev $PHPIZE_DEPS \
  && docker-php-ext-install opcache \
  && docker-php-ext-install soap \
  && docker-php-ext-enable sodium \
  && pecl install apcu && docker-php-ext-enable apcu \
  && pecl install opentelemetry-1.0.3 && docker-php-ext-enable opentelemetry

# Patch Vulnerabilities
RUN apk upgrade --no-cache openssl curl libcurl libxml2

# Get Latest CA Cert Bundle
ADD https://curl.se/ca/cacert.pem /etc/ssl/cert.pem
RUN chmod 0644 /etc/ssl/cert.pem

WORKDIR /var/www/

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY --chown=www-data:www-data --chmod=755 docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini
COPY --chown=www-data:www-data --chmod=755 docker/php/www.conf /usr/local/etc/php-fpm.d/www.conf
COPY --chown=www-data:www-data --chmod=755 docker/php/apcu.ini /usr/local/etc/php/conf.d/apcu.ini
COPY --chown=www-data:www-data --chmod=755 docker/php-entrypoint /usr/local/bin/opg-paper-identity-entrypoint
COPY --chown=www-data:www-data --chmod=755 docker/scripts /usr/local/bin/scripts

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
ENV OTEL_PROPAGATORS=tracecontext,xray

COPY --chown=www-data:www-data --chmod=755 --from=composer /usr/bin/composer /usr/bin/
COPY --chown=www-data:www-data --chmod=755 composer.json composer.json
COPY --chown=www-data:www-data --chmod=755 composer.lock composer.lock

RUN composer check-platform-reqs &&\
  composer install --prefer-dist --no-dev --no-interaction --no-scripts &&\
  composer dumpautoload -o

COPY --chown=www-data:www-data --chmod=755 public public
COPY --chown=www-data:www-data --chmod=755 config/autoload config/autoload
COPY --chown=www-data:www-data --chmod=755 config/*.php config/
COPY --chown=www-data:www-data --chmod=755 module module
STOPSIGNAL SIGQUIT

HEALTHCHECK --interval=5s --timeout=10s --start-period=5s --retries=5 \
  CMD SCRIPT_NAME=/ping SCRIPT_FILENAME=/ping REQUEST_METHOD=GET cgi-fcgi -bind -connect 127.0.0.1:9000

FROM app AS production

COPY docker/scripts/harden.sh /harden.sh
RUN /harden.sh www-data && rm /harden.sh
USER "www-data"

ENTRYPOINT ["/usr/local/bin/opg-paper-identity-entrypoint"]
CMD ["php-fpm"]

FROM app AS development
USER "root"
RUN apk add git
RUN pecl install pcov && docker-php-ext-enable pcov;

RUN composer install --prefer-dist --no-interaction --no-scripts &&\
  composer dumpautoload -o

USER "www-data"
COPY phpcs.xml phpcs.xml
COPY phpunit.xml phpunit.xml
COPY psalm.xml psalm.xml

