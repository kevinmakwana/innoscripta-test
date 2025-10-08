#!/usr/bin/env bash
set -euo pipefail

# Runs PHPUnit inside the app container, generates the markdown summary, and prints it locally.
# Usage: ./tools/run_tests_and_summary.sh

BASE_DIR="$(cd "$(dirname "$0")/.." && pwd)"

echo "Running PHPUnit inside the app container..."
docker compose exec -T app bash -lc "./vendor/bin/phpunit --testdox --log-junit /tmp/junit.xml"

echo "Generating markdown summary..."
docker compose exec -T app bash -lc "php /var/www/html/tools/generate_test_summary.php /tmp/junit.xml /tmp/test-summary.md"

echo "Copying summary to host and printing..."
# copy the file from the container to host path so user can open it locally
docker compose exec -T app bash -lc "cat /tmp/test-summary.md" > "$BASE_DIR/tmp/test-summary.md"
cat "$BASE_DIR/tmp/test-summary.md"

echo "Done. The summary was copied to $BASE_DIR/tmp/test-summary.md and printed above."
