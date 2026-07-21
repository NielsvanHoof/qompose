#!/usr/bin/env sh

set -eu

php artisan optimize

exec "$@"
