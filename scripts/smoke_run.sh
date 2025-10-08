#!/usr/bin/env bash
set -euo pipefail
# smoke_run.sh - small smoke script to start services, run migrations, fetch articles once, and check health
ROOT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT_DIR"

echo "Starting docker compose (build if necessary)..."
docker compose up -d --build

echo "Copy .env.example to .env if missing..."
docker compose exec -T app bash -lc "cp .env.example .env || true"

echo "Generating APP_KEY..."
docker compose exec -T app bash -lc "php artisan key:generate --ansi || true"

echo "Running migrations and seeders..."
docker compose exec -T app bash -lc "php artisan migrate --seed --no-interaction"

echo "Dispatching articles:fetch (synchronous)"
docker compose exec -T app bash -lc "php artisan articles:fetch"

echo "Processing queue once..."
docker compose exec -T app bash -lc "php artisan queue:work --once --tries=3"

echo "Health check..."
HEALTH=$(curl -sS http://localhost:8000/api/v1/health || true)
if [[ "$HEALTH" == "" ]]; then
  echo "Health endpoint empty or unreachable. Check docker compose port mapping or server binding."
  docker compose logs --tail=200 app
  exit 2
fi

echo "Health response:"
echo "$HEALTH"

echo "Smoke run complete."
