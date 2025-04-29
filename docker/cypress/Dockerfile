FROM cypress/included:14.3.2

WORKDIR /root

COPY e2e-tests/package.json .
COPY e2e-tests/package-lock.json .

RUN npm install --ignore-scripts

ENV CYPRESS_VIDEO=false
ENV CYPRESS_baseUrl=http://front-web

COPY e2e-tests/cypress.config.ts .
COPY e2e-tests/tsconfig.json .
COPY e2e-tests/cypress cypress
