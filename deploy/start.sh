#!/bin/sh
set -eu

# Never migrate from an HTTP instance: scale-out must not race schema changes.
php artisan config:cache
php artisan deploy:check --configuration-only
php artisan route:cache
php artisan view:cache
exec frankenphp run --config /etc/caddy/Caddyfile --adapter caddyfile
