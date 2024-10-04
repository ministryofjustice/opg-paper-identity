# opg-paper-id

OPG Paper Identity client application for staff to perform and record ID checks.

## Pre-requisites

- Docker
- `make`

## Setup

After cloning the repo, you can build and start it by running `make build up`. The service will then be available on `http://localhost:8080`.

## Entrypoint

You can start an ID check by directly accessing the landing page URL, for example:

- Donor: `http://localhost:8080/start?personType=donor&lpas[]=M-1234-1234-1234`
- Certificate provider: `http://localhost:8080/start?personType=certificateProvider&lpas[]=M-1234-1234-1234`

The LPA UID you use doesn't matter as the data is randomly generated (see [Mocks](#mocks) below)

## Tests

You can run `make api-test` and `make front-test` to run Psalm, PHPCS and unit tests in each service. There are more specific commands for each available, which you can find by running `make help`.

## Mocks

For local development we mock several external services. The mock behaviour is explained below:

### Sirius

When requesting an LPA from Sirius, the mock generates a random record based on Sirius's API response structure. This changes on each request, so using the same LPA UID won't return the same data if you start the ID check again.

When sending a completed ID check or document to Sirius, it will always return a 2xx response as long as the request shape matches the API specification.

### Passport, driving license and national insurance number lookup

When checking if the caller's details match the **passport** or **driving license** number given, the mock will return "not enough details" if the number ends with 9, "no match" if it ends with 8 and "pass" for all other numbers.

When checking if the caller's details match the national insurance number given, the mock will return "not enough details" if the national insurance number ends with "D", "no match" if it ends with "C" and "pass" for all other values.

### FraudScore

The mock fraud score API will always return "ACCEPT" and a score of 999.

### Knowledge Based Verification (KBV) Questions

The mock KBV service will return a random selection of the following questions. Each question has a single correct answer, which is included below:

- Who is your electricity supplier?
  - VoltWave
- How much was your last phone bill?
  - £5.99
- What is your mother’s maiden name?
  - Germanotta
- What are the last two characters of your car number plate?
  - IF
- Name one of your current account providers
  - Liberty Trust Bank
- In what month did you move into your current house?
  - July
- Which company provides your car insurance?
  - SafeDrive Insurance
- What colour is your front door?
  - Pink

### Counter Service

When requesting the nearest Post Office to a postcode, the mock will always return the same three results.

When creating a session with the Post Office, the mock will always return a 2xx response as long as the request shape matches the API specification.
