#!/usr/bin/env sh
set -eu

php artisan migrate --force
php artisan config:cache
php artisan view:cache

exec php -S 0.0.0.0:${PORT:-10000} -t public
