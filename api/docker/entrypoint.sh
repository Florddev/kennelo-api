#!/bin/sh
set -e

cd /app

echo "Caching Laravel config, routes and views..."
php artisan config:cache || true
php artisan route:cache  || true
php artisan view:cache   || true

exec "$@"