# opg-paper-id

OPG Paper Identity client application for staff to perform and record ID checks.

## Pre-requisites

- Docker
- `make`

## Setup

After cloning the repo, you can build and start it by running `make build up`. The service will then be available on `http://localhost:8080`.

## Tests

You can run `make api-test` and `make front-test` to run Psalm, PHPCS and unit tests in each service. There are more specific commands for each available, which you can find by running `make help`.
