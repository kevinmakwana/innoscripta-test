<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Contracts\MessageBrokerInterface;
use Illuminate\Console\Command;

class PublishSampleMessageCommand extends Command
{
    protected $signature = 'messaging:publish-sample {destination=source.fetch.failed}';

    protected $description = 'Publish a small sample message to the configured broker (for smoke tests)';

    public function handle(MessageBrokerInterface $broker): int
    {
        $arg = $this->argument('destination');
        $destination = is_array($arg) ? implode(' ', $arg) : (string) $arg;

        // Publish a NewsAPI-like article payload so consumers can normalize/persist it
        $payload = [
            'version' => 1,
            'type' => 'newsapi.sample',
            'data' => [
                'source' => ['id' => 'sample-source'],
                'author' => 'CI Smoke',
                'title' => 'Smoke Test Article',
                'description' => 'Smoke test excerpt',
                'content' => 'Smoke test body content',
                'url' => 'https://example.com/smoke-article',
                'urlToImage' => null,
                'publishedAt' => now()->toIso8601String(),
            ],
            'meta' => [
                'source_id' => null,
            ],
        ];

        // Broker API expects string messages; encode the payload to JSON
        $broker->publish($destination, (string) json_encode($payload));

        $this->info('Published sample message to '.$destination);

        return 0;
    }
}
