## Operations notes

Messaging schema and DLQ
- Define a small `version` field in message payloads so consumers can evolve: `{ "version": 1, "type": "source.fetch.failed", "data": { ... } }`.
- Consumers should validate the version and have a migration strategy for newer versions.
- Implement a dead-letter queue (DLQ) by pushing failed messages to a separate list `dlq.{destination}` and alerting on DLQ growth.

Retries
- Consumers should implement exponential backoff and limit retries before moving messages to DLQ.

Credentials and secrets
- Add broker credentials to `.env` and reference them in `docs/ENVIRONMENT.md`. Use secrets manager in CI and production.
