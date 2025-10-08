<?php

declare(strict_types=1);

namespace App\Contracts;

interface MessageBrokerInterface
{
    /**
     * Push a message to the specified destination.
     */
    public function push(string $destination, string $message): void;

    /**
     * Pop a message from the specified destination.
     */
    public function pop(string $destination): ?string;

    /**
     * Acknowledge a message has been processed.
     */
    public function acknowledge(string $destination, string $message): void;

    /**
     * Get the length of the queue for the specified destination.
     */
    public function getQueueLength(string $destination): int;

    /**
     * Publish a message to the specified topic.
     */
    public function publishToTopic(string $topic, string $message): void;

    /**
     * Publish a message to a destination (convenience method).
     * Some brokers may differentiate between queue destinations and topics.
     */
    public function publish(string $destination, string $message): void;

    /**
     * Subscribe to the specified topic, providing a callback to handle incoming messages.
     */
    public function subscribeToTopic(string $topic, callable $callback): void;
}
