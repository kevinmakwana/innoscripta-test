# Innoscripta Test - News Aggregator API

![Coverage Status](https://codecov.io/gh/<your-org-or-username>/innoscripta-test/branch/main/graph/badge.svg)

A Laravel-based backend service for aggregating, normalizing, and deduplicating news articles from multiple external sources (NewsAPI, Guardian, NYT, etc.). Designed for scalability, reliability, and clean architecture.

## Features

- **Modular Architecture**: Clean separation with adapters, services, DTOs, and jobs
- **Asynchronous Processing**: Queue-based article fetching with Redis/RabbitMQ support
- **Data Normalization**: Standardized article data from diverse APIs
- **Deduplication**: Intelligent merging of duplicate articles
- **RESTful API**: Paginated endpoints with authentication and personalization
- **Docker Ready**: Complete containerized development environment
- **Comprehensive Testing**: Unit, feature, and integration tests with coverage reporting

## Quick Start

### Prerequisites
- Docker and Docker Compose
- Git

### Setup
1. Clone the repository:
   ```bash
   git clone <repository-url>
   cd innoscripta-test
   ```

2. Copy environment file:
   ```bash
   cp .env.example .env
   ```

3. Start the development environment:
   ```bash
   docker compose up -d --build
   ```

4. Run migrations and seeders:
   ```bash
   docker compose exec -T app bash -lc "php artisan migrate --seed --no-interaction"
   ```

5. Generate application key:
   ```bash
   docker compose exec -T app bash -lc "php artisan key:generate"
   ```

6. Run tests to verify setup:
   ```bash
   docker compose exec -T app bash -lc "./vendor/bin/phpunit --testdox"
   ```

The API will be available at `http://localhost:8000/api/v1`

### API Documentation
- Interactive Swagger UI: `/api/v1/docs`
- OpenAPI Specification: `/api/v1/openapi.json`

## Architecture Overview

```
[External Sources] --> [Adapters] --> [Jobs] --> [Normalization] --> [Deduplication] --> [Persistence] --> [API]
                          |            |            |                  |                  |
                          v            v            v                  v                  v
                    [SourceAdapterInterface] [FetchSourceJob] [ArticleNormalizationService] [DeduplicationService] [Models/Controllers]
```

### Key Components
- **Adapters**: Implement `SourceAdapterInterface` for fetching from specific APIs
- **Jobs**: `FetchSourceJob` handles async processing with error resilience
- **Services**: Business logic for normalization and deduplication
- **DTOs**: `NormalizedArticle` for standardized data transfer
- **Events/Listeners**: Failure handling and metrics collection

### Additional Documentation
- [Architectural Decisions](docs/ARCHITECTURAL_DECISIONS.md): Rationale behind key design choices (e.g., Redis, deduplication).
- [Scaling and Performance](docs/SCALING_PERFORMANCE.md): Performance considerations and scaling guides.
- [Messaging](docs/MESSAGING.md): Broker integrations and event handling.
- [Operations](docs/OPERATION.md): Operational notes and best practices.
- [Test Coverage](docs/COVERAGE.md): Coverage requirements, badge, and how to improve coverage.

## API Examples

### Health Check
```bash
curl http://localhost:8000/api/v1/health
```

### List Articles
```bash
curl "http://localhost:8000/api/v1/articles?per_page=10"
```

### Authenticate and Get Personalized Articles
```bash
# Register
curl -X POST http://localhost:8000/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -d '{"name":"Test User","email":"test@example.com","password":"password"}'

# Login
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"password"}'

# Get personalized articles (requires Bearer token)
curl -H "Authorization: Bearer YOUR_TOKEN" \
  http://localhost:8000/api/v1/articles/personalized
```

### Manage User Preferences
```bash
# Get preferences
curl -H "Authorization: Bearer YOUR_TOKEN" \
  http://localhost:8000/api/v1/preferences

# Update preferences
curl -X POST -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"sources":[1,2],"categories":["technology","sports"]}' \
  http://localhost:8000/api/v1/preferences
```

## Development

### Running Tests
```bash
# All tests
docker compose exec app ./vendor/bin/phpunit

# With coverage
docker compose exec app ./vendor/bin/phpunit --coverage-html build/coverage

# Specific test suite
docker compose exec app ./vendor/bin/phpunit --testsuite Unit
```

### Code Quality
```bash
# Static analysis
docker compose exec app ./vendor/bin/phpstan analyse

# Code style
docker compose exec app ./vendor/bin/pint --test
```

### Scheduler
The application includes a scheduled command that fetches articles hourly:
```bash
docker compose exec app php artisan articles:fetch
```

## Troubleshooting

### Common Issues

**Database Connection Failed**
- Ensure MySQL container is running: `docker compose ps`
- Check environment variables in `.env`
- Run migrations: `php artisan migrate`

**Queue Jobs Not Processing**
- Start queue worker: `php artisan queue:work`
- Check Redis connection in `.env`
- Verify job failures: `php artisan queue:failed`

**API Authentication Issues**
- Ensure `APP_KEY` is set: `php artisan key:generate`
- Check Sanctum token in Authorization header
- Verify user exists in database

**External API Errors**
- Check API keys in `.env` (NEWSAPI_KEY, GUARDIAN_KEY, NYT_KEY)
- Verify rate limits and API status
- Review logs for adapter failures

**Docker Build Issues**
- Clear Docker cache: `docker system prune`
- Rebuild: `docker compose up --build`
- Check disk space and Docker version

**Test Failures**
- Ensure database is seeded: `php artisan migrate --seed`
- Check environment: `APP_ENV=testing`
- Run with verbose output: `phpunit -v`

### Logs and Debugging
```bash
# View application logs
docker compose logs app

# Access container shell
docker compose exec app bash

# Run Tinker for debugging
docker compose exec app php artisan tinker
```

### Performance Issues
- Monitor queue backlog: `php artisan queue:status`
- Check database indexes on articles table
- Review job failure rates in logs

## Environment Configuration

Key environment variables (see `.env.example`):

- `APP_KEY`: Application encryption key
- `DB_*`: Database connection settings
- `QUEUE_CONNECTION`: Queue driver (sync/redis/database)
- `NEWSAPI_KEY`, `GUARDIAN_KEY`, `NYT_KEY`: External API keys
- `MESSAGING_DRIVER`: Message broker (redis/rabbitmq)

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make changes with tests
4. Run full test suite
5. Submit pull request

## License

This project is licensed under the MIT License.

## Generating local PHPDocs

This repo includes a tiny PHPDoc extractor that generates a simple HTML
summary of docblocks under `app/`.

Run locally inside the project (we recommend using the container):

```sh
make build
docker compose run --rm app php tools/generate_phpdoc.php
open docs/phpdoc/index.html # macOS
```

This is a lightweight alternative to phpDocumentor to avoid adding heavy
dependencies to the project. For a full-site doc generation you can still
try installing `phpDocumentor` in a separate environment.

## Documentation

- [Architecture Overview](docs/ARCHITECTURE.md) - System design and data flow.
- [Environment Setup](docs/ENVIRONMENT.md) - Secrets and configuration guidance.
- [Contributing Guidelines](.github/CONTRIBUTING.md) - How to contribute.
- [OpenAPI Spec](docs/openapi.json) - API specification.
- [API Documentation UI](/api/v1/docs) - Interactive Swagger UI for exploring the API.

## Postman collection & smoke script

Quick helpers for reviewers and QA:

- Postman collection: `docs/postman_collection.json` (import into Postman). Set an environment variable `base_url` to `http://localhost:8000/api/v1` and `auth_token` after logging in.
- Smoke script: `scripts/smoke_run.sh` runs compose, migrates, dispatches fetch jobs, processes the queue once, and performs a health check.

To import collection in Postman:

1. Open Postman → Import → File → select `docs/postman_collection.json` from the repo.
2. Create an environment with `base_url = http://localhost:8000/api/v1`.
3. Use the `Login` request to obtain a token and set `auth_token` in the environment.

To run smoke script locally:

```bash
chmod +x scripts/smoke_run.sh
./scripts/smoke_run.sh
```

Or via Makefile:

```bash
make smoke
```

If the health check fails, inspect logs with:

```bash
docker compose logs --tail=200 app
```


## Scheduler / Regular updates

This application ships with a scheduled artisan command that dispatches
fetch jobs for enabled sources. The scheduler entry is configured in
`app/Console/Kernel.php` and runs the `articles:fetch` command hourly.

How it works:

- `app/Console/Commands/FetchArticlesCommand.php` loads enabled `Source`
	rows and dispatches `FetchSourceJob` for each.
- `app/Console/Kernel.php` schedules `articles:fetch` to run hourly. It
	uses `withoutOverlapping()` and `onOneServer()` so it is safe to run in
	clustered environments.

Run the scheduler locally (dev):

```sh
# run the scheduler in the foreground (helpful for testing)
php artisan schedule:work

# or trigger scheduled commands once (useful in CI / simple runs)
php artisan schedule:run
```

Run the scheduler in Docker (example):

```sh
# exec into the app container and run the scheduler
docker compose exec app php artisan schedule:work

# or add a cron entry on the host that executes inside the container
# every minute to evaluate scheduled tasks:
# * * * * * docker compose exec app php /var/www/html/artisan schedule:run >> /dev/null 2>&1
```

Notes:

- The scheduled command only dispatches jobs to the queue. Make sure a
	queue worker is running (`php artisan queue:work` or using a process
	manager inside Docker) to perform the actual fetch and persistence.
- For production scale, ensure your queue worker pool and rate limits are
	configured to handle the number of sources and batch sizes you use.

## Author & Category persistence

When articles are fetched from external providers (NewsAPI, The Guardian, NYT), the ingestion pipeline will attempt to persist lightweight Author and Category records when that information is available in the provider payload. The pipeline behavior:

- The `ArticleNormalizationService` returns optional `author` and `category` candidate objects in the normalized payload.
- The `FetchSourceJob` will call `Author::firstOrCreate(...)` and `Category::firstOrCreate(...)` to persist these candidates and associate them with the created or merged `Article` via `author_id` and `category_id`.
- When a duplicate article is detected, the job will merge incoming data into the existing Article record and set `author_id`/`category_id` if the existing record lacks them.

This enables filtering articles by author or category using the API endpoints described below.

## API: filtering by source, category, and author

The Articles API supports filtering and pagination. All endpoints are under the API prefix (see `routes/api.php`). Key query parameters for `GET /api/articles`:

- `q` — full-text search against `title` and `excerpt`.
- `source` — source slug (for example `newsapi`, `theguardian`, `nytimes`).
- `category` — category slug persisted by the ingestion pipeline (for example `technology`, `world`).
- `author` — author name or id depending on your client usage. The API currently filters `author` by slug/name via the related `author` model.
- `from`, `to` — date range for `published_at` (ISO 8601 strings).
- `per_page` — pagination size (default: 15).

Example: list the latest technology articles from The Guardian

```sh
curl "http://localhost:8000/api/articles?source=theguardian&category=technology&per_page=10"
```

Expected JSON (truncated) - paginated resource envelope:

{
	"data": [
		{
			"id": 123,
			"title": "Example article title",
			"excerpt": "Short summary...",
			"url": "https://...",
			"image_url": "https://...",
			"published_at": "2025-10-06T11:00:00Z",
			"source": {"id": 2, "slug": "theguardian", "name": "The Guardian"},
			"category": {"id": 5, "slug": "technology", "name": "Technology"},
			"author": {"id": 7, "name": "Jane Doe"}
		}
	],
	"links": {"first": "...", "last": "..."},
	"meta": {"current_page":1, "per_page":10, "total": 42}
}

Example: search by author name (case insensitive match on author name)

```sh
curl "http://localhost:8000/api/articles?author=Jane%20Doe"
```

Notes and caveats
- Author and category extraction is best-effort and depends on provider payload fields. Not every article will have these relations.
- Category slugs are generated using Laravel's `Str::slug()` from the provider's category/section name. If your UI relies on specific canonical slugs, normalize them before querying.

## Running tests and generating a test summary

We've added a convenience script and composer shortcut that runs the test suite inside the `app` container, generates a JUnit report and a human-friendly Markdown summary, and copies the summary to `tmp/test-summary.md` on the host.

Prerequisites: Docker and Docker Compose available locally.

Run via Composer (recommended):

```sh
composer test:summary
```

Or run the wrapper directly from the project root:

```sh
./tools/run_tests_and_summary.sh
```

The script will:
- Run PHPUnit inside the `app` container (writes `/tmp/junit.xml` inside the container).
- Run `tools/generate_test_summary.php` to produce `/tmp/test-summary.md` inside the container.
- Copy the generated summary to `tmp/test-summary.md` on the host and print it to your terminal.

Use this summary to quickly identify slow tests and prioritize optimizations.

## Quickstart (10-minute) 🚀

Follow these steps to get the project running locally using Docker Compose. These commands assume you're in the project root.

1. Build and start services (detached):

```sh
docker compose up -d --build
```

2. Install PHP dependencies inside the `app` container (recommended):

```sh
docker compose exec -T app bash -lc "composer install --no-interaction --prefer-dist"
```

3. Generate app key, run migrations and seed sample data:

```sh
docker compose exec -T app bash -lc "php artisan key:generate --ansi"
docker compose exec -T app bash -lc "php artisan migrate --seed --no-interaction"
```

4. Start the scheduler and a queue worker in separate terminals (or as background processes):

```sh
docker compose exec app bash -lc "php artisan schedule:work"
docker compose exec app bash -lc "php artisan queue:work --tries=3"
```

5. Open the app (if a webserver or port is mapped):

```sh
# If using the provided dev nginx container and port mapping
open http://localhost:8000
```

Notes:
- Use `docker compose logs -f app` to follow application logs.
- For running tests inside the container use: `docker compose exec -T app bash -lc "./vendor/bin/phpunit --testdox"`.

Makefile shortcuts

This repository includes a convenient `Makefile` with common targets. From the project root you can run:

```sh
make up                      # docker compose up -d --build
make composer-install-in-container  # run composer install inside app container
make migrate                 # run migrations inside app container
make seed                    # run seeders inside app container
make test                    # run phpunit inside app container
make logs                    # tail application logs
make build-prod              # build the production image
```

