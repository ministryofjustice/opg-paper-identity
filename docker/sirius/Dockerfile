FROM outofcoffee/imposter:4.5.7

COPY . /opt/imposter/config

HEALTHCHECK --interval=15s --timeout=10s --start-period=5s --retries=3 \
    CMD ["imposter", "list", "-x" ]

