# 3. Handover from Sirius

Date: 2024-03-14

## Status

Accepted

## Context

Users will access the Paper ID service from Sirius, starting from an LPA's details page. They will select which person is being ID-checked, which LPAs the check applies to and then be taken to the Paper ID service.

We need a way to pass the person and case data between services.

## Decision

Sirius will generate a URL of the Paper ID service including the type of person being ID-checked and LPAs that the check should impact. It will then redirect the user to that link.

The structure of the URL will be `/start?personType={type}&lpas[]={uid}`. `{type}` will be either "donor" or "certificate-provider". `{uid}` will be the LPA's UID.

Where multiple LPAs are being provided, they should be sent as separate fields with array syntax: `/start?personType={type}&lpas[]={uid1}&lpas[]={uid2}`

The Paper ID service will handle that URL and:

1. Validate the `{type}` and `{uid}` parameters
   - If they're invalid, show the user an error message
2. Fetch the data of each LPA based on its `{uid}` from the Sirius API
3. Retrieve the relevant person details from the LPA data
4. Verify that the person details are identical on each LPA
   - If not, show the user an error message
5. Create a case record in the API containing the person details and LPA UIDs
6. Redirect the user to the "What ID document will the caller use" page for that case

## Consequences

The Paper ID service will require Sirius to be running to start a case, but that's reasonable since the journey should start from there and we currently require Sirius to be running for auth anyway.
