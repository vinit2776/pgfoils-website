#!/usr/bin/env bash
# Builds everything the Vercel deploy needs: images, CSS/JS, and a read-only SQLite copy of the public content.
set -euo pipefail
cd "$(dirname "$0")/.."

php artisan images:build
npm run build
php artisan filament:assets
php artisan livewire:publish --assets >/dev/null 2>&1 || true

rm -f database/deploy.sqlite
touch database/deploy.sqlite
DB_CONNECTION=sqlite DB_DATABASE="$PWD/database/deploy.sqlite" php artisan migrate --seed --force
