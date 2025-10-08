# Architectural Decisions

This document outlines key architectural decisions made during the design and implementation of the news aggregator backend. It explains the rationale behind choices, trade-offs considered, and alternatives evaluated.

## Messaging Broker: Redis over Kafka/RabbitMQ

### Decision
Use Redis as the default message broker for event publishing and queueing, with an abstraction layer (`MessageBrokerInterface`) to allow switching to other brokers.

### Rationale
- **Simplicity and Speed**: Redis provides low-latency, in-memory messaging suitable for the expected throughput (article fetches every few hours). It's simpler to set up and operate than Kafka or RabbitMQ, reducing operational overhead.
- **Development Velocity**: For a proof-of-concept or initial deployment, Redis allows faster iteration without the complexity of distributed brokers.
- **Laravel Integration**: Laravel's queue system natively supports Redis, making it a natural fit.
- **Cost-Effectiveness**: Redis is lightweight and doesn't require additional infrastructure like ZooKeeper (Kafka) or Erlang runtime (RabbitMQ).

### Trade-offs and Alternatives Considered
- **Kafka**: Better for high-volume, distributed systems with guaranteed ordering and replayability. Considered for future scaling if article volumes exceed Redis' capacity (e.g., millions of articles/day). Trade-off: Higher complexity and resource requirements.
- **RabbitMQ**: Excellent for reliable messaging with advanced routing. Preferred if complex message patterns (e.g., topic exchanges) are needed. Trade-off: Slower setup and higher memory usage.
- **Abstraction Benefits**: The `MessageBrokerInterface` ensures we can migrate to Kafka/RabbitMQ without code changes, as demonstrated by the `RedisMessageBroker` and placeholder for others.

### When to Reconsider
- If message volume grows >10k messages/hour or requires guaranteed delivery across regions, evaluate Kafka.
- For production, consider RabbitMQ if Redis persistence becomes a bottleneck.

## Deduplication Strategy

### Decision
Implement deduplication at the application level using a `DeduplicationService` that merges articles based on `external_id` (preferred) or content hashing, with database-level constraints for final safety.

### Rationale
- **Data Integrity**: Prevents duplicate articles from cluttering the database and API responses, ensuring clean data for users.
- **Performance**: Application-level deduplication allows intelligent merging (e.g., updating existing articles with new data) rather than rejecting duplicates.
- **Flexibility**: Content hashing handles sources without unique IDs, while `external_id` ensures accuracy for sources that provide them.
- **Transactional Safety**: Wrapped in database transactions to handle concurrent inserts.

### Implementation Details
- **Primary Key**: `external_id` from source (e.g., NewsAPI article ID).
- **Fallback**: MD5 hash of title + URL for uniqueness.
- **Merge Logic**: Updates existing articles with newer data (e.g., fresher published_at), preserving the earliest fetch.
- **Database Constraints**: Unique index on `source_id + external_id` prevents duplicates at DB level.

### Trade-offs and Alternatives Considered
- **Database-Only Deduplication**: Using unique constraints alone would reject duplicates but not merge them. Trade-off: Less intelligent, potential data loss.
- **External Tools**: Redis-based deduplication or Bloom filters for high-speed checks. Considered for extreme scale but overkill for current needs.
- **No Deduplication**: Simplest but leads to bloated data and poor UX.

### When to Reconsider
- If deduplication becomes a bottleneck (>1000 articles/min), consider Redis-based pre-checks.
- For multi-tenant setups, add tenant-scoped deduplication.

## Data Normalization with DTOs

### Decision
Use `NormalizedArticle` DTO for standardizing data from diverse sources, with source-specific normalization methods in `ArticleNormalizationService`.

### Rationale
- **Consistency**: Ensures all articles have uniform fields (title, body, etc.) regardless of source API variations.
- **Type Safety**: Immutable DTO with readonly properties prevents accidental mutations.
- **Testability**: Easy to mock and assert in tests.
- **Maintainability**: Centralizes normalization logic, making it easy to add new sources.

### Trade-offs and Alternatives Considered
- **Direct Model Hydration**: Simpler but couples to source formats, leading to messy controllers.
- **Eloquent Casting**: Could handle normalization but less explicit and harder to test.

## Asynchronous Processing with Jobs

### Decision
Use Laravel Jobs (`FetchSourceJob`) for article fetching, with batching and retries.

### Rationale
- **Scalability**: Offloads heavy I/O (API calls) to background workers, preventing web request timeouts.
- **Reliability**: Built-in retries and failure handling via Laravel's queue system.
- **Concurrency**: Multiple workers can process jobs in parallel.
- **Observability**: Job failures trigger events for metrics/logging.

### Trade-offs and Alternatives Considered
- **Synchronous Fetching**: Simpler for small-scale but blocks users and risks timeouts.
- **Cron Jobs**: Less flexible than queue-based; harder to scale.

## Database Choice: MySQL for Local, MySQL for Production

### Decision
Use MySQL for local development/testing, MySQL for production, with Eloquent migrations ensuring compatibility.

### Rationale
- **Development Simplicity**: MySQL requires no setup, speeds up onboarding.
- **Production Readiness**: MySQL handles concurrency and scaling better.
- **Migration Portability**: Eloquent abstracts differences.


## Error Handling and Resilience

### Decision
Use events/listeners for failures, with source disable thresholds and retry logic.

### Rationale
- **Decoupling**: Events allow flexible handling (logging, alerts) without coupling jobs to handlers.
- **Resilience**: Automatic retries and source disabling prevent cascade failures.
- **Observability**: Failure events enable monitoring dashboards.

### Alternatives
- Exceptions alone; less structured for async processing.

## Security Considerations

### Decision
Store API keys in `.env`, use Laravel Sanctum for auth, throttle API endpoints.

### Rationale
- **Standard Practices**: Follows Laravel conventions for security.
- **Minimal Exposure**: Keys not in code; throttling prevents abuse.

## Future Considerations
- **Microservices Migration**: Current modular design (adapters, services) facilitates splitting into services.
- **Caching**: Add Redis caching for frequent queries.
- **Monitoring**: Integrate with tools like New Relic for performance tracking.</content>
<parameter name="filePath">/Volumes/Personal/innoscripta-test/docs/ARCHITECTURAL_DECISIONS.md