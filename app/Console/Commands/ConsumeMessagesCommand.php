<?php
declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Services\Messaging\MessageProcessor;
use App\Contracts\MessageBrokerInterface;

class ConsumeMessagesCommand extends Command
{
    protected $signature = 'messaging:consume {destination=source.fetch.failed} {--limit=0}';
    protected $description = 'Consume messages from a Redis list destination and process them (simple example)';

    private MessageBrokerInterface $broker;

    public function __construct(MessageBrokerInterface $broker)
    {
        parent::__construct();
        $this->broker = $broker;
    }

    public function handle(): int
    {
        $arg = $this->argument('destination');
        if (is_array($arg)) {
            $destination = implode(' ', $arg);
        } else {
            $destination = (string) $arg;
        }
        $limit = (int) $this->option('limit');
        $count = 0;

        $processor = new MessageProcessor();

        while (true) {
            $message = $this->broker->pop($destination);
            if ($message === null) {
                $this->info('No more messages');
                break;
            }

            $payload = json_decode($message, true);

            if (! is_array($payload) || ! $processor->validate($payload)) {
                Log::warning('ConsumeMessagesCommand: invalid message schema, pushing to DLQ', ['destination' => $destination, 'message' => $message]);
                $dlq = config('messaging.dlq_prefix') . $destination;
                $this->broker->push($dlq, $message);
                continue;
            }

            // Idempotency check
            try {
                if ($processor->alreadyProcessed($payload)) {
                    Log::info('ConsumeMessagesCommand: skipping already-processed message', ['idempotency_key' => $processor->idempotencyKey($payload)]);
                    continue;
                }
            } catch (\Throwable $e) {
                Log::warning('ConsumeMessagesCommand: idempotency check failed', ['error' => $e->getMessage()]);
                // proceed to processing to avoid dropping messages silently
            }

            // Process message and handle errors by sending to DLQ
            try {
                $processor->process($payload);
                $this->line('Processed message: ' . (string) json_encode($payload));
            } catch (\Throwable $e) {
                Log::error('ConsumeMessagesCommand: processing failed, pushing to DLQ', ['error' => $e->getMessage(), 'message' => $payload]);
                $dlq = config('messaging.dlq_prefix') . $destination;
                $this->broker->push($dlq, (string) json_encode($payload));
            }

            $count++;
            if ($limit > 0 && $count >= $limit) {
                $this->info('Limit reached');
                break;
            }
        }

        return 0;
    }
}
