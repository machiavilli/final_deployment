#!/bin/bash
set -euo pipefail

cd /app

# Railway MySQL plugin exposes MYSQL_URL; Symfony expects DATABASE_URL
if [ -z "${DATABASE_URL:-}" ] && [ -n "${MYSQL_URL:-}" ]; then
    export DATABASE_URL="${MYSQL_URL}"
fi
if [ -z "${DATABASE_URL:-}" ] && [ -n "${MYSQL_PUBLIC_URL:-}" ]; then
    export DATABASE_URL="${MYSQL_PUBLIC_URL}"
fi

if [ ! -f .env ]; then
    if [ -n "${RAILWAY_ENVIRONMENT:-}${RAILWAY_PUBLIC_DOMAIN:-}" ]; then
        # Railway: do not bake in local Docker DB credentials
        # Minimal .env — all secrets/URLs must come from Railway service variables
        cat > .env <<'EOF'
APP_ENV=prod
APP_DEBUG=0
TRUSTED_PROXIES=REMOTE_ADDR
MESSENGER_TRANSPORT_DSN=doctrine://default?auto_setup=0
EOF
    elif [ -f docker/env.docker.dist ]; then
        cp docker/env.docker.dist .env
    else
        touch .env
    fi
fi

# Real env vars must win over .env (remove local DATABASE_URL when Railway provides one)
if [ -n "${DATABASE_URL:-}" ] && grep -q '^DATABASE_URL=' .env 2>/dev/null; then
    sed -i '/^DATABASE_URL=/d' .env
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

    if [ -z "${DATABASE_URL:-}" ]; then
        echo "[bootstrap] DATABASE_URL is not set — skip migrations (set Railway MySQL → DATABASE_URL or MYSQL_URL)"
        return 0
    fi

    echo "[bootstrap] Waiting for database..."
    db_ready=0
    for _ in $(seq 1 45); do
        if php bin/console doctrine:query:sql "SELECT 1" >/dev/null 2>&1; then
            db_ready=1
            break
        fi
        sleep 2
    done

    if [ "$db_ready" -ne 1 ]; then
        echo "[bootstrap] Database not reachable — check DATABASE_URL / Railway MySQL credentials"
        return 0
    fi

    echo "[bootstrap] Running migrations..."
    php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration || true

    if [ -n "${INITIAL_ADMIN_EMAIL:-}" ] && [ -n "${INITIAL_ADMIN_PASSWORD:-}" ]; then
        echo "[bootstrap] Creating or updating admin user from INITIAL_ADMIN_* ..."
        php bin/console app:upsert-admin \
            --email="${INITIAL_ADMIN_EMAIL}" \
            --password="${INITIAL_ADMIN_PASSWORD}" \
            --username="${INITIAL_ADMIN_USERNAME:-admin}" \
            --name="${INITIAL_ADMIN_NAME:-Admin}" \
            --no-interaction || true
    fi

    echo "[bootstrap] Installing bundle assets..."
    php bin/console assets:install public --env="${APP_ENV}" --no-interaction || true

    echo "[bootstrap] Warming cache (${APP_ENV})..."
    php bin/console cache:clear --env="${APP_ENV}" --no-warmup || true
    php bin/console cache:warmup --env="${APP_ENV}" || true

    chown -R www-data:www-data var 2>/dev/null || true
    echo "[bootstrap] Finished."

    if [ "${RUN_MESSENGER_WORKER:-1}" = "1" ]; then
        echo "[bootstrap] Starting async messenger worker (emails, notifications)..."
        php bin/console messenger:consume async --time-limit=3600 --memory-limit=128M --no-interaction &
    fi
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
