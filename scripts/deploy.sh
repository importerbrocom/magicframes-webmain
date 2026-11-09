#!/usr/bin/env bash
# Deploy / update Magic Frames Studio on the server.   Usage: ./scripts/deploy.sh
# Safe to run repeatedly. Run it from the project root as the web user (or with sudo -u www-data).
set -euo pipefail
cd "$(dirname "$0")/.."

PHP="${PHP:-php}"
COMPOSER="${COMPOSER:-composer}"

[ -f .env ] || { echo "ERROR: .env is missing. Copy .env.production.example to .env and fill it in first."; exit 1; }
grep -q '^APP_KEY=.\+' .env || { echo "ERROR: APP_KEY is empty. Run: $PHP artisan key:generate --force"; exit 1; }
if grep -q '^APP_DEBUG=true' .env && grep -q '^APP_ENV=production' .env; then
  echo "ERROR: APP_DEBUG=true in production. Set APP_DEBUG=false."; exit 1
fi

echo "==> Maintenance mode on"
$PHP artisan down --retry=60 --refresh=15 || true
trap '$PHP artisan up || true' EXIT

echo "==> Installing PHP dependencies (no dev packages)"
$COMPOSER install --no-dev --prefer-dist --optimize-autoloader --no-interaction

echo "==> Backing up the database before migrating"
$PHP artisan site:backup || echo "(backup skipped — continue only if this is the very first deploy)"

echo "==> Database migrations"
$PHP artisan migrate --force

echo "==> Seeding structure (idempotent: roles, categories, pages, menus; first admin if ADMIN_* set)"
$PHP artisan db:seed --force

echo "==> Public storage link"
$PHP artisan storage:link >/dev/null 2>&1 || true

echo "==> Rebuilding caches"
$PHP artisan optimize:clear
$PHP artisan optimize
$PHP artisan filament:optimize
$PHP artisan cache:clear

echo "==> Done. The site is live again."
