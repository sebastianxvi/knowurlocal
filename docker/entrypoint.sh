#!/bin/sh

set -eu

if [ -n "${AIVEN_CA_CERT:-}" ]; then
    printf '%s\n' "$AIVEN_CA_CERT" > /app/certs/aiven-ca.pem
    chmod 644 /app/certs/aiven-ca.pem
fi

if [ -f /app/certs/aiven-ca.pem ]; then
    export MYSQL_ATTR_SSL_CA=/app/certs/aiven-ca.pem
fi

exec "$@"