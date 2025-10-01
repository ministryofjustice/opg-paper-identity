FROM node:21.4.0-alpine3.17 AS asset-env

WORKDIR /app

RUN apk add --no-cache git

COPY package.json .
COPY yarn.lock .
RUN yarn install --frozen-lockfile --ignore-scripts

COPY web web
RUN yarn build
