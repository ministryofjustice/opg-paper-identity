FROM php:8.2-fpm-alpine AS app

RUN mkdir /app
WORKDIR /app
COPY index.php .

RUN apk add icu-dev
RUN docker-php-ext-install mysqli pdo pdo_mysql
RUN docker-php-ext-configure intl && docker-php-ext-install intl

COPY --from=composer /usr/bin/composer /usr/bin/

CMD ["php", "-S", "0.0.0.0:8000", "index.php"]
