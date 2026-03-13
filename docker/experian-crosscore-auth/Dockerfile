FROM node:lts-alpine3.23@sha256:7fddd9ddeae8196abf4a3ef2de34e11f7b1a722119f91f28ddf1e99dcafdf114

WORKDIR  /usr/src/prism

COPY ./openapi.yml /usr/src/prism/openapi.yml

COPY ./package.json /usr/src/prism/package.json
COPY ./package-lock.json /usr/src/prism/package-lock.json

RUN npm ci --ignore-scripts

RUN npm uninstall -g npm

RUN apk upgrade --no-cache zlib

HEALTHCHECK --interval=5s --timeout=10s --start-period=2s --retries=10 \
    CMD wget -O /dev/null -S 'http://0.0.0.0:8080/api/v1/healthcheck' 2>&1 | grep 'HTTP/1.1 200 OK' || exit 1

RUN chown -R node:node /usr/src/prism 

USER node

CMD [ "node", "./node_modules/@stoplight/prism-cli/dist/index.js", "mock", "-h", "0.0.0.0", "-p", "8080", "-d", "/usr/src/prism/openapi.yml" ]
