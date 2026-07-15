#!/bin/sh
set -e
cd /app

# Charger les secrets Docker en variables d'environnement.
load_secret() {
    var_name="$1"
    secret_file="$2"
    if [ -f "$secret_file" ]; then
        export "$var_name"="$(cat "$secret_file")"
    fi
}

load_secret "APP_KEY"               "/run/secrets/kennelo_app_key"
load_secret "JWT_SECRET"            "/run/secrets/kennelo_jwt_secret"
load_secret "REVERB_APP_SECRET"     "/run/secrets/kennelo_reverb_app_secret"
load_secret "DB_PASSWORD"           "/run/secrets/kennelo_postgres_password"
load_secret "REDIS_PASSWORD"        "/run/secrets/kennelo_redis_password"
load_secret "AWS_ACCESS_KEY_ID"     "/run/secrets/kennelo_minio_root_user"
load_secret "AWS_SECRET_ACCESS_KEY" "/run/secrets/kennelo_minio_root_password"
load_secret "GOOGLE_CLIENT_SECRET"  "/run/secrets/kennelo_google_client_secret"
load_secret "RESEND_KEY"            "/run/secrets/kennelo_resend_api_key"
load_secret "STRIPE_SECRET"         "/run/secrets/kennelo_stripe_secret_key"
load_secret "STRIPE_WEBHOOK_SECRET" "/run/secrets/kennelo_stripe_webhook_secret"

echo "Caching Laravel config, routes and views..."
php artisan config:cache || true
php artisan route:cache  || true
php artisan view:cache   || true

exec "$@"
