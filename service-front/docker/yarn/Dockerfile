FROM node:21.1.0-alpine3.17 as asset-env

WORKDIR /app

RUN apk add --no-cache git

COPY package.json .
COPY yarn.lock .
RUN yarn install

COPY web web
RUN yarn build