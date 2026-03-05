FROM outofcoffee/imposter:4.9.0@sha256:d28ed1a752392454aad04443a72161fd28c36f271e04379b2a185798e8cbf10d

HEALTHCHECK --interval=5s --timeout=10s --start-period=2s --retries=10 \
    CMD ["imposter", "list", "-x" ]

COPY . /opt/imposter/config
