#!/bin/bash
set -euo pipefail

gosu postgres pg_ctl -w start -o "-c listen_addresses=127.0.0.1" > /dev/null
trap 'gosu postgres pg_ctl -w stop -m fast > /dev/null' EXIT

php tests/bin/console messenger:consume async --no-interaction > /dev/null 2>&1 &

cd tests
symfony server:start --document-root=public --allow-all-ip --no-tls --port=8000 &
server=$!

# PID 1 does not get default signal handling; forward it to the server
trap 'kill -TERM "$server" 2> /dev/null' TERM INT
wait "$server" || wait "$server" || true
