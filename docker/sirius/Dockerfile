FROM outofcoffee/imposter:4.6.7

COPY . /opt/imposter/config

HEALTHCHECK --interval=5s --timeout=10s --start-period=2s --retries=5 \
    CMD ["imposter", "list", "-x" ]

