#!/bin/sh
set -e

# Production: build Laravel's config/route/view/event caches from the runtime environment.
if [ "${APP_ENV}" = "production" ] && [ "${RUN_OPTIMIZE:-true}" = "true" ]; then
    php artisan optimize || echo "WARNING: php artisan optimize failed, continuing" >&2
fi

exec "$@"
