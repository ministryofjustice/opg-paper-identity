FROM php:8.2-fpm-alpine AS app

RUN mkdir /app
WORKDIR /app
COPY index.php .

COPY --from=composer /usr/bin/composer /usr/bin/

CMD ["php", "-S", "0.0.0.0:8000", "index.php"]
