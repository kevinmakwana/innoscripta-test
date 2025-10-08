<?php

declare(strict_types=1);

namespace App\Services\Messaging;

use App\Contracts\MessageBrokerInterface;
use Illuminate\Support\Facades\Redis;

class RedisMessageBroker implements MessageBrokerInterface
{
    /** @var array<string,array<int,callable>> */
    private array $subscribers = [];

    public function push(string $destination, string $message): void
    {
        Redis::lpush($destination, $message);
    }

    public function pop(string $destination): ?string
    {
        return Redis::rpop($destination);
    }

    public function acknowledge(string $destination, string $message): void
    {
        // Redis does not have a built-in ack mechanism; this is a placeholder for future extension.
    }

    public function getQueueLength(string $destination): int
    {
        return Redis::llen($destination);
    }

    public function publishToTopic(string $topic, string $message): void
    {
        if (isset($this->subscribers[$topic])) {
            foreach ($this->subscribers[$topic] as $callback) {
                $callback($message);
            }
        }
    }

    public function publish(string $destination, string $message): void
    {
        // For Redis-backed broker, treat publish as pushing a message
        Redis::rpush($destination, $message);
    }

    public function subscribeToTopic(string $topic, callable $callback): void
    {
        if (! isset($this->subscribers[$topic])) {
            $this->subscribers[$topic] = [];
        }
        $this->subscribers[$topic][] = $callback;
    }
}
