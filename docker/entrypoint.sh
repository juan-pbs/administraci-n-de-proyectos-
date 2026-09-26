#!/usr/bin/env sh
set -eu

if [ -z "${APP_KEY:-}" ] && [ -f .env ]; then
    APP_KEY="$(grep '^APP_KEY=' .env 2>/dev/null | cut -d= -f2- || true)"
    export APP_KEY
fi

if [ -z "${APP_KEY:-}" ]; then
    mkdir -p storage/app/private
    APP_KEY="$(php -r '$stream = fopen("storage/app/private/.encryption-key", "c+"); flock($stream, LOCK_EX); $key = trim(stream_get_contents($stream)); if (! $key) { $key = "base64:".base64_encode(random_bytes(32)); fwrite($stream, $key); chmod("storage/app/private/.encryption-key", 0600); } flock($stream, LOCK_UN); fclose($stream); echo $key;')"
    export APP_KEY
fi

cat > .env <<EOF
APP_NAME="${APP_NAME:-Laravel}"
APP_ENV=${APP_ENV:-local}
APP_KEY=${APP_KEY:-}
APP_DEBUG=${APP_DEBUG:-false}
APP_URL=${APP_URL:-http://localhost}
APP_LOCALE=${APP_LOCALE:-es}
APP_FALLBACK_LOCALE=${APP_FALLBACK_LOCALE:-es}
APP_FAKER_LOCALE=${APP_FAKER_LOCALE:-es_MX}
APP_TIMEZONE=${APP_TIMEZONE:-America/Mexico_City}
BCRYPT_ROUNDS=${BCRYPT_ROUNDS:-12}
LOG_CHANNEL=${LOG_CHANNEL:-stack}
LOG_STACK=${LOG_STACK:-single}
DB_CONNECTION=${DB_CONNECTION:-sqlite}
DB_HOST=${DB_HOST:-127.0.0.1}
DB_PORT=${DB_PORT:-3306}
DB_DATABASE=${DB_DATABASE:-database/database.sqlite}
DB_USERNAME=${DB_USERNAME:-root}
DB_PASSWORD=${DB_PASSWORD:-}
SESSION_DRIVER=${SESSION_DRIVER:-database}
CACHE_STORE=${CACHE_STORE:-database}
QUEUE_CONNECTION=${QUEUE_CONNECTION:-database}
MAIL_MAILER=${MAIL_MAILER:-log}
FILESYSTEM_DISK=${FILESYSTEM_DISK:-local}
VITE_APP_NAME="${APP_NAME:-Laravel}"
EOF

if [ "${DB_CONNECTION:-sqlite}" = "mysql" ] || [ "${DB_CONNECTION:-sqlite}" = "mariadb" ]; then
    echo "Waiting for database at ${DB_HOST}:${DB_PORT:-3306}..."
    until php -r "new PDO('mysql:host='.getenv('DB_HOST').';port='.(getenv('DB_PORT') ?: '3306').';dbname='.getenv('DB_DATABASE'), getenv('DB_USERNAME'), getenv('DB_PASSWORD'));" >/dev/null 2>&1; do
        sleep 2
    done
fi

if [ "${SKIP_BOOTSTRAP:-false}" != "true" ] && [ "${APP_ENV:-local}" != "testing" ]; then
php artisan migrate --force
php artisan storage:link >/dev/null 2>&1 || true

if [ "${AUTO_SEED:-false}" = "true" ]; then
    SHOULD_SEED="$(php -r "require 'vendor/autoload.php'; \$app = require 'bootstrap/app.php'; \$app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap(); echo Illuminate\\Support\\Facades\\DB::table('usuarios')->count() === 0 ? 'yes' : 'no';")"

    if [ "$SHOULD_SEED" = "yes" ]; then
        php artisan db:seed --force
    fi
fi
fi

exec "$@"
