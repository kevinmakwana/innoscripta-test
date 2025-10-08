# Environment Setup & Secrets Management

This guide covers setting up environment variables and managing secrets securely.

## Required Environment Variables

See `.env.example` for a complete template. Key variables include:

- **APP_KEY**: Generate with `php artisan key:generate`.
- **DB_***: Database connection details.
- **NEWSAPI_KEY, GUARDIAN_KEY, NYT_KEY**: API keys for news sources (obtain from providers).
- **MESSAGING_DRIVER**: Choose 'redis' or 'rabbitmq' for message brokers.

## Secrets Handling

- **Local Development**: Use `.env` file (ignored by Git).
- **Production**: Use server environment variables or secure managers (e.g., Laravel Envoy, AWS Systems Manager).
- **Best Practices**:
  - Never commit `.env` files.
  - Use strong, unique keys.
  - Rotate secrets periodically.
  - Limit access to secrets.

## Docker Environment

In Docker Compose, override via `environment` in `docker-compose.yml` or use `.env` files.

Example:
```yaml
services:
  app:
    environment:
      - APP_ENV=production
      - NEWSAPI_KEY=${NEWSAPI_KEY}
```

## Troubleshooting

- Missing keys: Adapters may fail with auth errors.
- Wrong DB config: Run `php artisan migrate` to verify.
- Broker issues: Check logs for connection errors.

- The repository provides sensible defaults for local dev (MySQL credentials used by `docker-compose.yml`). Do not use these defaults in production.

In CI
- CI should inject secrets via repository secrets or environment variables (do not commit .env with real secrets).

Production
- Use a secrets manager (AWS Secrets Manager, Azure Key Vault, HashiCorp Vault) or your cloud provider's environment variable features.
- Ensure `APP_KEY` is set and kept secret. Rotate keys only with a migration plan.

Required environment variables
- `APP_KEY` - application encryption key (required)
- `DB_*` - database connection details
- `QUEUE_CONNECTION` - recommended to use Redis or SQS in production
- `NEWS_API_KEY`, `GUARDIAN_API_KEY`, `NYT_API_KEY` - provider API keys
 - `MESSAGING_DRIVER` - messaging driver to use (null, redis). Default: null
 - `MESSAGING_REDIS_MODE` - when using Redis, choose `list` or `pubsub` (default: list)

Security best practices
- Never commit credentials or production `.env` files to the repository.
- Use least-privilege credentials for production DB users.
- Ensure backups and monitoring are in place for secrets stores.

Docker and composer cache
- `docker-compose.yml` mounts a `composer_cache` volume at `/home/developer/.composer/cache` to speed up `composer install` in dev. The CI pipeline caches composer separately.

Additional notes
- If you introduce provider credentials into CI, make sure to set them as repository secrets and avoid printing them in logs.