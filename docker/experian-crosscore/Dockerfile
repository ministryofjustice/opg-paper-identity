FROM outofcoffee/imposter:4.6.3

HEALTHCHECK --interval=5s --timeout=10s --start-period=2s --retries=5 \
    CMD ["imposter", "list", "-x" ]

COPY . /opt/imposter/config
