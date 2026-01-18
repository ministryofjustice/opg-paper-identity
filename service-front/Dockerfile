FROM node:25.3.0-alpine3.23@sha256:e80397b81fa93888b5f855e8bef37d9b18d3c5eb38b8731fc23d6d878647340f AS asset-env

WORKDIR /app

RUN apk add --no-cache git

RUN mkdir -p public

COPY package.json .
COPY package-lock.json .
RUN npm ci --ignore-scripts

COPY web web
RUN npm run build

RUN wget -O /app/yoti-supported-documents.json https://api.yoti.com/idverify/v1/supported-documents

FROM php:8.3-fpm-alpine3.23@sha256:42d49317f2cbeaaad51e9953d845604c08d802d0f9a8fd16624b02809823bbc8 AS app

RUN apk --no-cache add fcgi icu-dev autoconf libffi-dev linux-headers $PHPIZE_DEPS \
    && docker-php-ext-install ffi intl opcache \
    && docker-php-ext-enable sodium \
    && pecl install opentelemetry-1.0.3 && docker-php-ext-enable opentelemetry

# Patch Vulnerabilities
RUN apk upgrade --no-cache openssl curl libcurl libxml2

WORKDIR /var/www

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY --chown=www-data:www-data --chmod=755  docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini
COPY --chown=www-data:www-data --chmod=755  docker/php/www.conf /usr/local/etc/php-fpm.d/www.conf

RUN mkdir /tmp/cache &&\
    chown -R www-data /tmp/cache &&\
    chown -R www-data /var/www/

ENV PHP_FPM_MAX_CHILDREN "8"
ENV PHP_FPM_MAX_START_CHILDREN "4"
ENV PHP_FPM_MIN_SPARE_SERVERS "2"
ENV PHP_FPM_MAX_SPARE_SERVERS "4"
ENV PHP_FPM_MEMORY_LIMIT "256M"

ENV OTEL_PHP_AUTOLOAD_ENABLED=true
ENV OTEL_SERVICE_NAME=front.lpa-identity-check.local
ENV OTEL_TRACES_EXPORTER=none
ENV OTEL_METRICS_EXPORTER=none
ENV OTEL_LOGS_EXPORTER=none
ENV OTEL_PROPAGATORS=tracecontext,xray

COPY --chown=www-data:www-data --chmod=755  --from=composer /usr/bin/composer /usr/bin/
COPY --chown=www-data:www-data --chmod=755  composer.json composer.json
COPY --chown=www-data:www-data --chmod=755  composer.lock composer.lock

RUN composer check-platform-reqs &&\
    composer install --prefer-dist --no-dev --no-interaction --no-scripts &&\
    composer dumpautoload -o

VOLUME ["/var/www/public"]
COPY --chown=www-data:www-data --chmod=755 public public
COPY --chown=www-data:www-data --chmod=755 --from=asset-env /app/public public
COPY --chown=www-data:www-data --chmod=755 config/autoload config/autoload
COPY --chown=www-data:www-data --chmod=755 config/*.php config/
COPY --chown=www-data:www-data --chmod=755 module module

COPY --from=asset-env /app/yoti-supported-documents.json module/Application/config/yoti-supported-documents.json

FROM app AS production
COPY docker/scripts/harden.sh /harden.sh
RUN /harden.sh www-data && rm /harden.sh
USER "www-data"

HEALTHCHECK --interval=15s --timeout=10s --start-period=30s --retries=3 \
    CMD SCRIPT_NAME=/ping SCRIPT_FILENAME=/ping REQUEST_METHOD=GET cgi-fcgi -bind -connect 127.0.0.1:9000

STOPSIGNAL SIGQUIT

FROM app AS development
USER "root"
RUN apk add git

# Update vulnerable packages
RUN apk upgrade --no-cache sqlite-libs

RUN pecl install pcov && docker-php-ext-enable pcov;

RUN composer install --prefer-dist --no-interaction --no-scripts &&\
    composer dumpautoload -o

USER "www-data"
COPY phpcs.xml phpcs.xml
COPY phpunit.xml phpunit.xml
COPY psalm.xml psalm.xml

