#!/bin/sh
set -e

# No secret is committed: if APP_KEY isn't supplied, generate one for this container.
# (Nothing encrypted is shared between containers, so a per-container key is fine locally.)
if [ -z "$APP_KEY" ]; then
  APP_KEY="base64:$(head -c 32 /dev/urandom | base64)"
  export APP_KEY
fi

if [ "$RUN_MIGRATIONS" = "true" ]; then
  php artisan migrate --force --no-interaction
fi

exec "$@"
