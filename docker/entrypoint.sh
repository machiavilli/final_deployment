#!/bin/bash
set -euo pipefail

cd /app

if [ ! -f .env ]; then
    if [ -f docker/env.docker.dist ]; then
        cp docker/env.docker.dist .env
    else
        touch .env
    fi
fi

# Railway public URL for emails, OAuth, and absolute links
if [ -n "${RAILWAY_PUBLIC_DOMAIN:-}" ]; then
    export APP_URL="${APP_URL:-https://${RAILWAY_PUBLIC_DOMAIN}}"
    export DEFAULT_URI="${DEFAULT_URI:-${APP_URL}}"
fi

export PORT="${PORT:-8080}"
export APP_ENV="${APP_ENV:-prod}"

echo "Nginx binding 0.0.0.0:${PORT} — set Railway Public Networking to port ${PORT}"

# Render nginx site config from template (supports Railway PORT)
envsubst '${PORT}' < /etc/nginx/templates/default.conf.template > /etc/nginx/conf.d/default.conf

mkdir -p var/cache var/log public/uploads/images public/uploads/products
chown -R www-data:www-data var public/uploads 2>/dev/null || true
chmod -R ug+rwX var public/uploads 2>/dev/null || true

run_bootstrap() {
    if [ ! -f vendor/autoload_runtime.php ]; then
        echo "[bootstrap] Missing vendor/autoload_runtime.php — skipping console tasks"
        return 0
    fi

    echo "[bootstrap] Waiting for database..."
    for _ in $(seq 1 45); do
        if php bin/console doctrine:query:sql "SELECT 1" >/dev/null 2>&1; then
            break
        fi
        sleep 2
    done

    echo "[bootstrap] Running migrations..."
    php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration || true

    echo "[bootstrap] Installing bundle assets..."
    php bin/console assets:install public --env="${APP_ENV}" --no-interaction || true

    echo "[bootstrap] Warming cache (${APP_ENV})..."
    php bin/console cache:clear --env="${APP_ENV}" --no-warmup || true
    php bin/console cache:warmup --env="${APP_ENV}" || true

    chown -R www-data:www-data var 2>/dev/null || true
    echo "[bootstrap] Finished."
}

# Start nginx quickly; run slow tasks in background on Railway/production
if [ "${APP_ENV}" = "prod" ]; then
    run_bootstrap &
else
    run_bootstrap
fi

nginx -t
php-fpm -D

echo "Starting nginx on 0.0.0.0:${PORT}..."
exec nginx -g "daemon off;"
