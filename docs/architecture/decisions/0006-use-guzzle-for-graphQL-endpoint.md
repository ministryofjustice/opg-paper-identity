# 6. Use guzzle to send graphQL queries

Date: 2025-07-23

## Status

Accepted

## Context

We need to send queries to a graphQL endpoint to integrate with some external APIs.

## Decision

Since our use-case means all calls to this API will use the same graphQL query, we have no specific need for a graphQL client/library. Instead we will use guzzle to create a http request, passing the query and relevant variables.

## Consequences

This will simplify and standardise the way Paper ID integrates with external services.

This can be revisited in the future if there is a need to generate more and complex graphQL queries, where a library may help simplify this.