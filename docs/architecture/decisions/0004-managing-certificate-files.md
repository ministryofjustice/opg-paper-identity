# 4. Managing certificate files

Date: 2024-08-08

## Status

Accepted

## Context

Some external API integrations require a signed PEM file to be provided over an SSL connection. Due to how PHP's SOAP client works, this must be present on the file system for the duration of the requests to the API. This means it cannot be passed as a string variable, or through a temporary file.

## Decision

We store the PEM certificate as a `.pem` file on disk. It will be created as a startup script on the container, rather than during runtime.

The file will be in a private directory, which only the `www-data` user can write to or read from.

The contents of the PEM file will be retrieved from secrets manager and written to disk.

## Consequences

Rotating the certificate will require updating secrets manager, then restarting each API container to regenerate the PEM file.
