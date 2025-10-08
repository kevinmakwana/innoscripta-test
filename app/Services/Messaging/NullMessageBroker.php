<?php
declare(strict_types=1);

namespace App\Services\Messaging;

use App\Contracts\MessageBrokerInterface;
use Illuminate\Support\Facades\Log;

class NullMessageBroker implements MessageBrokerInterface
{
    public function push(string $destination, string $message): void
    {
        Log::debug('NullMessageBroker: push', ['destination' => $destination, 'message' => $message]);
    }

    public function pop(string $destination): ?string
    {
        Log::debug('NullMessageBroker: pop', ['destination' => $destination]);
        return null;
    }

    public function acknowledge(string $destination, string $message): void
    {
        Log::debug('NullMessageBroker: acknowledge', ['destination' => $destination, 'message' => $message]);
    }

    public function getQueueLength(string $destination): int
    {
        Log::debug('NullMessageBroker: getQueueLength', ['destination' => $destination]);
        return 0;
    }

    public function publishToTopic(string $topic, string $message): void
    {
        Log::debug('NullMessageBroker: publishToTopic', ['topic' => $topic, 'message' => $message]);
    }
    
    public function publish(string $destination, string $message): void
    {
        Log::debug('NullMessageBroker: publish', ['destination' => $destination, 'message' => $message]);
    }

    public function subscribeToTopic(string $topic, callable $callback): void
    {
        Log::debug('NullMessageBroker: subscribeToTopic', ['topic' => $topic]);
    }
}
