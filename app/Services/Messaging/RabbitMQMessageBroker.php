<?php

declare(strict_types=1);

namespace App\Services\Messaging;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use App\Contracts\MessageBrokerInterface;

class RabbitMQMessageBroker implements MessageBrokerInterface
{
    private AMQPStreamConnection $connection;
    private string $exchange;

    public function __construct(string $host, int $port, string $user, string $password, string $exchange)
    {
        $this->connection = new AMQPStreamConnection($host, $port, $user, $password);
        $this->exchange = $exchange;
    }

    public function push(string $destination, string $message): void
    {
        $channel = $this->connection->channel();
        $channel->exchange_declare($this->exchange, 'direct', false, true, false);
        $msg = new AMQPMessage($message);
        $channel->basic_publish($msg, $this->exchange, $destination);
        $channel->close();
    }

    public function pop(string $destination): ?string
    {
        $channel = $this->connection->channel();
        $channel->queue_declare($destination, false, true, false, false);
        $message = $channel->basic_get($destination);

        if ($message) {
            $channel->basic_ack($message->delivery_info['delivery_tag']);
            $channel->close();
            return $message->body;
        }

        $channel->close();
        return null;
    }

    public function acknowledge(string $destination, string $message): void
    {
        // RabbitMQ handles acknowledgments automatically in the pop method.
    }

    public function getQueueLength(string $destination): int
    {
        $channel = $this->connection->channel();
        list(, $messageCount) = $channel->queue_declare($destination, true);
        $channel->close();
        return $messageCount;
    }

    public function publishToTopic(string $topic, string $message): void
    {
        $channel = $this->connection->channel();
        $channel->exchange_declare($this->exchange, 'topic', false, true, false);
        $msg = new AMQPMessage($message);
        $channel->basic_publish($msg, $this->exchange, $topic);
        $channel->close();
    }

    public function subscribeToTopic(string $topic, callable $callback): void
    {
        $channel = $this->connection->channel();
        $queueName = $channel->queue_declare('', false, true, true, false)[0];
        $channel->queue_bind($queueName, $this->exchange, $topic);

        $channel->basic_consume($queueName, '', false, true, false, false, function ($message) use ($callback) {
            $callback($message->body);
        });

        while ($channel->is_consuming()) {
            $channel->wait();
        }

        $channel->close();
    }

    public function publish(string $destination, string $message): void
    {
        // Publish to a queue (treated as direct routing key)
        $this->push($destination, $message);
    }
}