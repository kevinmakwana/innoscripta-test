<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Article;
use App\Services\Messaging\MessageProcessor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class MessagingProcessorPersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_processor_persists_article_and_idempotency_skips_duplicate()
    {
        // Mock Redis facade to avoid external dependency in tests
        Redis::shouldReceive('get')->andReturnNull();
        Redis::shouldReceive('setex')->andReturnTrue();
        Redis::shouldReceive('rpush')->andReturnTrue();

        // Prepare payload shaped like a NewsAPI item
        $payload = [
            'version' => 1,
            'type' => 'newsapi.sample',
            'meta' => [],
            'data' => [
                'source' => ['id' => 'sample-source'],
                'title' => 'Test Title',
                'description' => 'An excerpt',
                'content' => 'Full body',
                'url' => 'https://example.com/article/1',
                'urlToImage' => null,
                'publishedAt' => now()->toISOString(),
            ],
        ];

        $processor = $this->app->make(MessageProcessor::class);

        // First processing should create an article
        $processor->process($payload);
        $this->assertDatabaseCount('articles', 1);

        // Re-processing the same payload should be skipped due to idempotency
        $processor->process($payload);
        $this->assertDatabaseCount('articles', 1);
    }
}
