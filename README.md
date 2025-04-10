# OPG-paper-id

The OPG-Paper-Identity application allows staff to help users perform offline ID-checks for their LPAs and record the results in Sirius. The application provides a number of different routes by which an ID-check can be performed. The preferred option is over the telephone, by which details of an ID document are validated against another government-department before a fraud-check is performed and Knowledge-Based-Verification (KBV) questions are answered. Alternatively users can choose a counter-service route, provided by the Post-Office, where they provide documents in-person. If neither of these options are appropriate someone can vouch for the users identity, this vouching-person will go through the same ID-check process. If none of these options are possible then we provide the option to go through the Court of Protection (CoP).

### External Services

Paper-ID connects to a number of different external services to validate Identities:

- **HMPO**: To validate that passport details provided match a real passport [NOT YET DEVELOPED].
- **DVLA**: To validate that driving-licence details provided match a real driving-licence [NOT YET DEVELOPED].
- **DWP**: To validate that national-insurance-number details provided match a real national-insurance-number.
- **YOTI**: To create a counter-service session to validate ID via the Post-Office (see [documentation](https://developers.yoti.com/identity-verification-api)).
- **Experian**: To perform a fraud-check against the identity-provided and generate KBV questions.

### Architecture

The architecture of the application is shown in [this diagram](docs/architecture/diagrams/architecture.drawio.svg).

## Setup

### Pre-requisites
To build the service locally you will need to have the following installed

- [Docker](https://www.docker.com/)
- `make`
- [composer](https://getcomposer.org/)

### Quick Start

After cloning the repo, you will need to install the php dependencies in the api and front services before building the application.
1. `cd` into `service-api` and run `composer install`.
2. `cd` into `service-front` and run `composer install`.
3. build and start the application by running `make build up`.
4. the service will then be available on `http://localhost:8080`.

## Entrypoint

You can start an ID check by directly accessing the landing page URL, for example:

- Donor: `http://localhost:8080/start?personType=donor&lpas[]=M-XYXY-YAGA-35G3`
- Certificate provider: `http://localhost:8080/start?personType=certificateProvider&lpas[]=M-XYXY-YAGA-0000`
- Voucher: `http://localhost:8080/start?personType=voucher&lpas[]=M-XYXY-YAGA-35G3`

In local development the LPA UID(s) you use will impact the behaviour of the application (see[Mocks](#mocks) below for more information).

## Tests

A number of `make` commands exist to simplify the running of tests and static-code-analysis. `make api-test` and `make front-test` can be used to dun unit and integration tests, alongside Psalm and PHPCS. There are more specific commands for each available, which you can find by running `make help`. Reports from these tests, including code-coverage can be found in the `build/` dir for each service.

End-to-end tests for the serivce have been written with cypress and live in `e2e-tests/cypress/`, these can be run with `make cypress`. If you wish to run an individual e2e-test, this can be achieved by running `npm install` followed by `npm test` from within the `e2e-tests/` folder.

## Mocks

For local development and in some ephemeral environments we mock several external services. The mock behaviour is explained below:

### Sirius

When requesting an LPA from Sirius, only certain LPA numbers are supported by the mock service. This is to ensure that the user data returned is consistent with the other services presently mocked by the system, eg Experian.

The LPAs are as follows:

| LPA UID        | Experian FraudScore Response |
|----------------|------------------------------|
|M-XYXY-YAGA-35G3| donor that will pass with an `ACCEPT` response |
|M-XYXY-YAGA-35G4| donor that will fail with a `NODECISION` response (ie person cannot be identified) |
|M-XYXY-YAGA-35G0| donor that will fail with a `STOP` response (ie person has been identified and is high risk) |
| | |
|M-XYXY-YAGA-0000| certificate provider that will pass with an `ACCEPT` response |
|M-XYXY-YAGA-0001| certificate provider that will fail with a `NODECISION` response (ie person cannot be identified) |
|M-XYXY-YAGA-0002| certificate provider that will fail with a `STOP` response (ie person has been identified and is high risk) |

When sending a completed ID check or document to Sirius, it will always return a 2xx response as long as the request shape matches the API specification.

#### Vouching Add Donor

In the vouching route there is the option to add additional donors to be vouched for. To test the potential iterations of statuses/ID-matches a number of LPAs have been mocked locally. Here the uIDs and json-files with their data are given, you can look in the json files to find the names/dobs/addresses which will cause ID-match warnings/errors.

- M-VOUC-HFOR-1001 -> voucherAddDonor.json
- M-VOUC-HFOR-1002 -> voucherAddDonorLinked.json
- M-VOUC-HFOR-2001 -> voucherAddAnotherDonor.json
- M-VOUC-HFOR-3001 -> voucherAddDonorComplete.json
- M-VOUC-HFOR-3002 -> voucherAddDonorDraft.json
- M-VOUC-HFOR-3003 -> voucherAddDonorStatus.json
- M-VOUC-HFOR-3004 -> voucherAddDonorLinkedStatus.json

1001 and 1002 are linked, so both will be returned if either are searched for.
All uIds starting with a 3 have a status which cannot be vouched for.

### Passport, Driving Licence and National Insurance Number lookup

For passport and driving-licence:

- a number ending in 8 or 9 will lead to an "Unable to verify..." message.
  - i.e. passport: `123456789` OR driving-licence: `MORGA657054SM9I8`
- all other numbers in the valid format will pass the document check.


National Insurance Number:

- only the number `NP 11 22 33 C` will pass the document check.
- all other numbers in the valid format will give an "Unable to verify..." message.

### FraudScore

The mock fraud score API returns different decisions based on the name being used.

- **Manthrope** (the donor on M-XYXY-YAGA-35G3 and CP on M-XYXY-YAGA-0000) gives an ACCEPT response.
- **Nodec** (the donor on M-XYXY-YAGA-35G4 and CP on M-XYXY-YAGA-0001) gives an NODECISION response.
- **Nohope** (the donor on M-XYXY-YAGA-35G0 and CP on M-XYXY-YAGA-0002) gives a STOP response.

If using the vouching route you test these journeys by inputting these names as part of the flow.

### Knowledge Based Verification (KBV) Questions

The mock KBV service will return a random selection of the following questions. Each question has a single correct answer which is marked with a (✓) :

- Who is your electricity supplier?
- How much was your last phone bill?
- What is your mother’s maiden name?
- What are the last two characters of your car number plate?
- Name one of your current account providers
- In what month did you move into your current house?
- Which company provides your car insurance?
- What colour is your front door?

### Counter Service

When requesting the nearest Post Office to a postcode there are 2 sets of 3 Post Offices returned.

- the postcode `SW1A 1AA` will return one set of post-offices
- any other valid postcode will return the other set of post-offices.


When creating a session with the Post Office, the mock will always return a 2xx response as long as the request shape matches the API specification.
