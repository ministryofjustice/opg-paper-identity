FROM outofcoffee/imposter:4.8.0

HEALTHCHECK --interval=5s --timeout=10s --start-period=2s --retries=10 \
    CMD ["imposter", "list", "-x" ]

COPY . /opt/imposter/config
