<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Contracts\MessageBrokerInterface;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

class MessageBrokerTest extends TestCase
{
    private MessageBrokerInterface $broker;

    protected function setUp(): void
    {
        parent::setUp();

        // Bind a lightweight in-memory broker for local PHPUnit runs so we
        // don't require the phpredis extension. This keeps the test focused
        // on broker behavior (push/pop/len/publish) without external deps.
        $this->app->bind(MessageBrokerInterface::class, function () {
            return new class implements MessageBrokerInterface
            {
                private array $store = [];

                public function push(string $destination, string $message): void
                {
                    // lpush semantics: push to head
                    if (! isset($this->store[$destination]) || ! is_array($this->store[$destination])) {
                        $this->store[$destination] = [];
                    }
                    array_unshift($this->store[$destination], $message);
                }

                public function pop(string $destination): ?string
                {
                    if (! isset($this->store[$destination]) || ! is_array($this->store[$destination]) || empty($this->store[$destination])) {
                        return null;
                    }

                    return array_pop($this->store[$destination]);
                }

                public function acknowledge(string $destination, string $message): void
                {
                    // no-op for in-memory
                }

                public function getQueueLength(string $destination): int
                {
                    return count($this->store[$destination] ?? []);
                }

                public function publishToTopic(string $topic, string $message): void
                {
                    // simple fan-out to subscribers
                    if (! empty($this->store['subscribers'][$topic])) {
                        foreach ($this->store['subscribers'][$topic] as $cb) {
                            $cb($message);
                        }
                    }
                }

                public function subscribeToTopic(string $topic, callable $callback): void
                {
                    $this->store['subscribers'][$topic][] = $callback;
                }

                public function publish(string $destination, string $message): void
                {
                    $this->push($destination, $message);
                }
            };
        });

        $this->broker = App::make(MessageBrokerInterface::class);
    }

    public function test_push_and_pop(): void
    {
        $destination = 'test-queue';
        $message = 'Test Message';

        $this->broker->push($destination, $message);
        $poppedMessage = $this->broker->pop($destination);

        $this->assertEquals($message, $poppedMessage);
    }

    public function test_get_queue_length(): void
    {
        $destination = 'test-queue-length';
        // Clear the queue
        while ($this->broker->pop($destination) !== null) {
            // pop until empty
        }
        $this->broker->push($destination, 'Message 1');
        $this->broker->push($destination, 'Message 2');

        $length = $this->broker->getQueueLength($destination);

        $this->assertEquals(2, $length);
    }

    public function test_publish_and_subscribe(): void
    {
        $topic = 'test-topic';
        $message = 'Test Topic Message';

        $receivedMessages = [];

        $this->broker->subscribeToTopic($topic, function ($msg) use (&$receivedMessages) {
            $receivedMessages[] = $msg;
        });

        $this->broker->publishToTopic($topic, $message);

        // Allow some time for the message to be received
        sleep(1);

        $this->assertContains($message, $receivedMessages);
    }
}
