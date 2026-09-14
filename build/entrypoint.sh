#!/bin/sh
set -eu

# Artisan commands remain usable for setup before the web service is started.
if [ "${1:-}" = "/usr/bin/supervisord" ]; then
    if [ -z "${APP_KEY:-}" ]; then
        echo "APP_KEY must be supplied in the runtime environment." >&2
        exit 1
    fi

    case "${QUEUE_WORKER_ENABLED:-false}" in
        true|false) ;;
        *) echo "QUEUE_WORKER_ENABLED must be true or false." >&2; exit 1 ;;
    esac

    mkdir -p storage/app/public storage/app/private storage/framework/cache/data \
        storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
    chown -R www-data:www-data storage bootstrap/cache
fi

exec "$@"
