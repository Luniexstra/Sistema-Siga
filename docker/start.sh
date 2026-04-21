#!/usr/bin/env sh
set -eu

mkdir -p storage/framework/views
mkdir -p storage/framework/cache
mkdir -p storage/framework/sessions
mkdir -p storage/logs
mkdir -p bootstrap/cache

php artisan migrate --force
php artisan config:cache

exec php -S 0.0.0.0:${PORT:-10000} -t public
