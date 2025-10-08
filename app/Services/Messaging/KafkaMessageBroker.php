<?php

declare(strict_types=1);

namespace App\Services\Messaging;

use App\Contracts\MessageBrokerInterface;
use Illuminate\Support\Facades\Log;

/**
 * Skeleton Kafka broker example. To use in production you must install
 * ext-rdkafka or a userland Kafka client and wire configuration.
 */
class KafkaMessageBroker implements MessageBrokerInterface
{
    /**
     * @param  array<string,mixed>  $config
     */
    public function __construct(array $config = [])
    {
        // Keep the $config in the log context so phpstan doesn't flag it as unused.
        Log::warning('KafkaMessageBroker: constructed as skeleton; implement client initialization', ['config_keys' => array_keys($config)]);
    }

    public function publish(string $destination, string $message): void
    {
        // TODO: implement publishing via rdkafka producer
        Log::warning('KafkaMessageBroker: publish called (no-op skeleton)', ['destination' => $destination, 'message' => $message]);
    }

    // Implement the rest of the MessageBrokerInterface as no-op stubs for now
    public function push(string $destination, string $message): void
    {
        Log::warning('KafkaMessageBroker: push called (no-op skeleton)', ['destination' => $destination]);
    }

    public function pop(string $destination): ?string
    {
        return null;
    }

    public function acknowledge(string $destination, string $message): void
    {
        // no-op
    }

    public function getQueueLength(string $destination): int
    {
        return 0;
    }

    public function publishToTopic(string $topic, string $message): void
    {
        $this->publish($topic, $message);
    }

    public function subscribeToTopic(string $topic, callable $callback): void
    {
        // no-op
    }
}
