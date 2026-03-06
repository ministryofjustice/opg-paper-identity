FROM outofcoffee/imposter-distroless:4.9.0@sha256:28cf00475da841086c16281b2230d6f464c9962d745b5b740fb24ab089fffdec

HEALTHCHECK --interval=5s --timeout=15s --start-period=2s --retries=10 \
    CMD ["imposter", "list", "-x" ]

COPY . /opt/imposter/config
