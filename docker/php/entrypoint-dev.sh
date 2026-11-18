#!/bin/sh
set -e
cd /var/www/html

# Only the main `app` container prepares the project (queue/scheduler wait for it to be healthy).
if [ "${PREPARE_PROJECT:-false}" = "true" ]; then
    if [ ! -f .env ]; then
        echo "ERROR: .env is missing. Run: cp .env.example .env" >&2
        exit 1
    fi

    composer install --no-interaction --prefer-dist

    if ! grep -Eq '^APP_KEY=.+' .env; then
        php artisan key:generate --force
    fi

    # Create/update the tables first: the queue worker, the session and the cache all use the database.
    php artisan migrate --force

    # Dev only: let PHP workers (www-data) write on a bind mount owned by the host user.
    chmod -R a+rwX storage bootstrap/cache || true
fi

exec "$@"
