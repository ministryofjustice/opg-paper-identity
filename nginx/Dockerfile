FROM nginx:alpine

COPY default.conf.template /etc/nginx/templates/default.conf.template
COPY nginx.conf /etc/nginx/nginx.conf

ENV TIMEOUT=20
RUN mkdir -p /var/www/public
VOLUME ["/var/www/public"]
