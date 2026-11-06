#!/bin/sh
# Start the website for E2E tests: fresh SQLite DB, deterministic seed, built-in PHP server.
# Usage: sh e2e/serve.sh [port]   (run from anywhere)
set -e
PORT="${1:-8001}"
cd "$(dirname "$0")/.."

DB="$(pwd)/database/e2e.sqlite"
rm -f "$DB"
touch "$DB"

export APP_ENV=testing
export APP_DEBUG=false
export APP_URL="http://127.0.0.1:${PORT}"
export APP_KEY="${APP_KEY:-base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=}"
export DB_CONNECTION=sqlite
export DB_DATABASE="$DB"
export SESSION_DRIVER=file
export CACHE_STORE=array
export QUEUE_CONNECTION=sync
export LOG_CHANNEL=stderr

php artisan migrate:fresh --force --seeder='Database\Seeders\E2ESeeder' --seed
exec php artisan serve --host=127.0.0.1 --port="$PORT" --no-reload
