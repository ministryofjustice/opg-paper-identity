# 5. Store external service availability in Parameter Store

Date: 2024-10-09

## Status

Accepted

## Context

We need to know the status of our external integrations for at least two reasons:

1. To provide meaningful health check pages
2. To modify which routes are available to a user (for example, blocking the Passport route if the HMPO API is unavailable)

We cannot reliably monitor our integrations automatically, as they don't all provide health check endpoints, and we also may need to disable integrations even if their health check responds positively. Therefore we need a way to manage the status manually.

## Decision

We will create a parameter in AWS Parameter Store that contains a JSON blob with the status of each external integration in it.

The parameter will be fetched at runtime by the API to be used in health check pages and to mark which routes are currently available.

## Consequences

This will allow us to better control how the service works, but adds responsibility to the digital team to accurately reflect the status of integrations in the parameter.

We will use Parameter Store as it is convenient to integrate and there is prior art of using it in Sirius. However, it could be replaced by any other document store in the future.
