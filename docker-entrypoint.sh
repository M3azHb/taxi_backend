#!/bin/bash
set -e
cd /var/www/html

# .env from example only if missing — never overwrite an existing one
if [ ! -f .env ] && [ -f .env.example ]; then
    cp .env.example .env
    echo "[entrypoint] created .env from .env.example"
fi

# Generate APP_KEY if empty
if ! grep -qE '^APP_KEY=base64:.+' .env 2>/dev/null; then
    echo "[entrypoint] APP_KEY missing — generating"
    php artisan key:generate --force
fi

chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
chmod -R 775 storage bootstrap/cache 2>/dev/null || true

# Ensure public/storage -> storage/app/public symlink exists (re-created on
# every boot because the image's COPY of public/ does not preserve the link).
# Without it, FILESYSTEM_DISK=public uploads are stored fine but are not
# reachable via /storage/<path> URLs from the browser.
if [ ! -L public/storage ]; then
    php artisan storage:link || true
fi

if [ "$APP_ENV" = "production" ]; then
    php artisan config:cache || true
    php artisan route:cache || true
    php artisan view:cache || true
fi

if [ "$RUN_MIGRATIONS" = "true" ]; then
    php artisan migrate --force || true
fi

exec "$@"
