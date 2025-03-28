FROM stoplight/prism:5.12.1

COPY ./openapi.yml /usr/src/prism/openapi.yml

HEALTHCHECK --interval=5s --timeout=10s --start-period=2s --retries=5 \
    CMD wget -O /dev/null -S 'http://0.0.0.0:8080/api/v1/healthcheck' 2>&1 | grep 'HTTP/1.1 200 OK' || exit 1

CMD [ "mock", "-h", "0.0.0.0", "-p", "8080", "-d", "/usr/src/prism/openapi.yml" ]
