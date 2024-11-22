FROM nginx:alpine

COPY default.conf.template /etc/nginx/templates/default.conf.template
COPY nginx.conf /etc/nginx/nginx.conf

HEALTHCHECK --interval=15s --timeout=10s --start-period=30s --retries=3 \
    CMD curl -f http://localhost/nginx-health || exit 1

ENV TIMEOUT=20
RUN mkdir -p /var/www/public
VOLUME ["/var/www/public"]
