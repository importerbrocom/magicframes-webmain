#!/usr/bin/env bash
# Restore a backup made by `php artisan site:backup`.   Usage: ./scripts/restore.sh storage/app/backups/magicframes-YYYYMMDD-HHMMSS.zip
# WARNING: this REPLACES the current database contents and uploaded media.
set -euo pipefail
cd "$(dirname "$0")/.."

ZIP="${1:?Usage: $0 path/to/backup.zip}"
[ -f "$ZIP" ] || { echo "ERROR: $ZIP not found"; exit 1; }
PHP="${PHP:-php}"
MYSQL_BIN="${MYSQL_BIN:-mysql}"   # may include extra arguments, e.g. MYSQL_BIN="mysql --ssl-mode=DISABLED"
read -ra MYSQL_CMD <<< "$MYSQL_BIN"

env_get() { grep -E "^$1=" .env | head -1 | cut -d= -f2- | sed -e 's/^"//' -e 's/"$//' -e 's/[[:space:]]*#.*$//'; }

read -r -p "This will OVERWRITE the current database and media with $ZIP. Type YES to continue: " ok
[ "$ok" = "YES" ] || { echo "Aborted."; exit 1; }

WORK="$(mktemp -d)"; trap 'rm -rf "$WORK"' EXIT
unzip -q "$ZIP" -d "$WORK"

$PHP artisan down --retry=60 || true
trap '$PHP artisan up || true; rm -rf "$WORK"' EXIT

CONN="$(env_get DB_CONNECTION)"
if [ -f "$WORK/database.sql" ]; then
  [ "$CONN" = "mysql" ] || [ "$CONN" = "mariadb" ] || { echo "ERROR: backup is a MySQL dump but DB_CONNECTION=$CONN"; exit 1; }
  MYSQL_PWD="$(env_get DB_PASSWORD)" "${MYSQL_CMD[@]}" -h "$(env_get DB_HOST)" -P "$(env_get DB_PORT)" -u "$(env_get DB_USERNAME)" "$(env_get DB_DATABASE)" < "$WORK/database.sql"
  echo "Database restored."
elif [ -f "$WORK/database.sqlite" ]; then
  DB_PATH="$(env_get DB_DATABASE)"; DB_PATH="${DB_PATH:-database/database.sqlite}"
  cp "$WORK/database.sqlite" "$DB_PATH"
  echo "SQLite database restored."
fi

if [ -d "$WORK/media" ]; then
  mkdir -p storage/app/public
  cp -a "$WORK/media/." storage/app/public/
  echo "Media restored."
fi

$PHP artisan storage:link >/dev/null 2>&1 || true
$PHP artisan optimize:clear
$PHP artisan cache:clear
echo "Restore complete."
