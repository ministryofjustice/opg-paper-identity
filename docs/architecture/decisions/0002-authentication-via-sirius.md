# 1. Authentication via Sirius

Date: 2024-03-13

## Status

Accepted

## Context

Users need to authenticate to access this service, using the same authentication requirements as they do for [Sirius](https://github.com/ministryofjustice/opg-sirius/). Authentication should be transparent: by clicking through to the Paper ID service from Sirius, they will immediately be logged in. If not authenticated with Sirius, or their authentication expires, they will not be able to access the Paper ID service.

## Decision

The Paper ID service will be hosted on the same domain as Sirius, so will have access to the authentication cookie that Sirius sets. The Paper ID service will use that cookie to make a request to the Sirius API to check that it returns a 200 response.

## Consequences

This puts a strict dependency on the Sirius API in order to access Paper ID. It will also result in lots of calls to the Sirius API: if this becomes a high load we could cache a successful authentication in session so it's confirmed less frequently.
