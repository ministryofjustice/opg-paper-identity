FROM php:8.2-fpm-alpine AS app

RUN mkdir /app
WORKDIR /app
COPY index.php .

CMD ["php", "-S", "0.0.0.0:8000", "index.php"]
