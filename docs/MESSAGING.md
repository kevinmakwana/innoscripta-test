# Messaging & External Integrations

This project includes a simple messaging abstraction to help integrate with
external message brokers (Redis, Kafka, RabbitMQ) without coupling the
application code to a specific broker.

What was added
- `App\Contracts\MessageBrokerInterface` — small contract with `publish(string $destination, array $payload): void`.
- `App\Services\Messaging\RedisMessageBroker` — publishes to Redis (list or pub/sub).
- `App\Services\Messaging\NullMessageBroker` — no-op, useful for local dev and tests.
- `config/messaging.php` — driver + redis mode configuration.
- `App\Providers\AppServiceProvider` binds the `MessageBrokerInterface` to an implementation based on `config('messaging.driver')`.
- `RecordSourceFetchFailureMetrics` now publishes a compact failure message to `source.fetch.failed` after metrics are recorded.

How to use
- Inject `App\Contracts\MessageBrokerInterface` or call `app(App\Contracts\MessageBrokerInterface::class)` and call `publish()`.

Switching to other brokers (Kafka / RabbitMQ)
- Implement `App\Contracts\MessageBrokerInterface` in a class under `App\Services\Messaging` (for example `KafkaMessageBroker`).
- Bind your implementation in a service provider (for example in `AppServiceProvider` or a dedicated `MessagingServiceProvider`) to override the default binding. Example:

```php
$this->app->bind(\App\Contracts\MessageBrokerInterface::class, function ($app) {
    return new \App\Services\Messaging\KafkaMessageBroker(/* config */);
});
```

Operational notes
- For heavy throughput use a dedicated broker (Kafka/Rabbit/SQS) and run dedicated consumers. The `RedisMessageBroker` includes a `redis_mode` option to choose between `list` (queue-like) and `pubsub` (event stream).
- Ensure message payloads are compact and versioned; consumers should tolerate missing fields.
- Consider adding retries or dead-letter handling in consumers.

Consumer example
- A simple consumer command is provided: `php artisan messaging:consume` which reads from a Redis list (default `source.fetch.failed`). It is intended as a simple demonstration; production consumers should be robust, idempotent, and run as separate long-lived processes or in containers.

Example consumer (dev):

```sh
php artisan messaging:consume source.fetch.failed --limit=100
```
