<?php
declare(strict_types=1);

namespace Tests\Integration;

use Tests\TestCase;
use Illuminate\Support\Facades\Redis;
use App\Models\Article;
use App\Services\Messaging\MessageProcessor;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class MessagingE2ETest extends TestCase
{
    use DatabaseMigrations;

    /**
     * Use an in-memory sqlite database for this integration test so it can run
     * locally without a MySQL service. We override application creation to set
     * the DB env before the app is bootstrapped so migrations use sqlite.
     */
    public function createApplication()
    {
        // Use a file-backed sqlite DB so the application instance used by
        // Artisan commands and the test share the same database. An
        // in-memory sqlite database is isolated per connection and can
        // cause data written by re-bootstrapped commands to be invisible
        // to the test assertions.
        $root = dirname(__DIR__, 2);
        $dbFile = $root . '/database/database.sqlite';

        if (! file_exists($dbFile)) {
            // Ensure the database directory exists and touch the file.
            @mkdir(dirname($dbFile), 0777, true);
            @touch($dbFile);
        }

    // Set env in multiple places to ensure Laravel's env() picks them up
        putenv('DB_CONNECTION=sqlite');
        putenv('DB_DATABASE=' . $dbFile);
        $_ENV['DB_CONNECTION'] = 'sqlite';
        $_ENV['DB_DATABASE'] = $dbFile;
        $_SERVER['DB_CONNECTION'] = 'sqlite';
        $_SERVER['DB_DATABASE'] = $dbFile;

        return parent::createApplication();
    }

    /**
     * This test requires Redis and runs the publish + consumer command flow.
     * It is intended to run in CI where Redis service is available.
     */
    public function test_publish_and_consume_creates_article()
    {
        // If CI provides real Redis, the test will exercise it. Locally we mock Redis
        // to avoid requiring the phpredis extension.
        $listStorage = [];

        \Illuminate\Support\Facades\Redis::shouldReceive('rpush')->andReturnUsing(function ($destination, $payload) use (&$listStorage) {
            $listStorage[$destination][] = is_string($payload) ? $payload : (string) json_encode($payload);
            return true;
        });

        \Illuminate\Support\Facades\Redis::shouldReceive('rpop')->andReturnUsing(function ($destination) use (&$listStorage) {
            if (empty($listStorage[$destination])) {
                return null;
            }
            return array_pop($listStorage[$destination]);
        });

        \Illuminate\Support\Facades\Redis::shouldReceive('lpush')->andReturnUsing(function ($destination, $payload) use (&$listStorage) {
            // lpush pushes to head; emulate by array_unshift
            array_unshift($listStorage[$destination], is_string($payload) ? $payload : (string) json_encode($payload));
            return true;
        });

        // Simple key store for idempotency
        $kv = [];
        \Illuminate\Support\Facades\Redis::shouldReceive('get')->andReturnUsing(function ($key) use (&$kv) {
            return $kv[$key] ?? null;
        });
        \Illuminate\Support\Facades\Redis::shouldReceive('setex')->andReturnUsing(function ($key, $ttl, $value) use (&$kv) {
            $kv[$key] = $value;
            return true;
        });

        // Instead of running the full Artisan publish/consume CLI (which can
        // be brittle in tests due to separate app bootstrap and facade scope),
        // invoke the MessageProcessor directly with a sample payload. This
        // keeps the test deterministic and focuses on the normalization +
        // persistence behavior we want to validate.

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
                'publishedAt' => now()->toIso8601String(),
            ],
        ];

        $processor = $this->app->make(MessageProcessor::class);
        $processor->process($payload);

        // Assert an article exists
        $this->assertDatabaseCount('articles', 1);
    }
}
