<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class MessagingDLQTest extends TestCase
{
    public function test_invalid_message_is_pushed_to_dlq()
    {
        $invalid = (string) json_encode(['bad' => 'payload']);

        Redis::shouldReceive('rpop')->once()->andReturn($invalid);
        Redis::shouldReceive('lpush')->once()->with('dlq.source.fetch.failed', $invalid);
        Redis::shouldReceive('rpop')->once()->andReturnNull();

        $this->artisan('messaging:consume source.fetch.failed --limit=1')
            ->expectsOutput('No more messages')
            ->assertExitCode(0);
    }

    public function test_idempotent_message_is_skipped()
    {
        $message = (string) json_encode(['version' => 1, 'type' => 'source.fetch.failed', 'data' => ['id' => 1]]);

        // Simulate message popped
        Redis::shouldReceive('rpop')->once()->andReturn($message);
        // Simulate idempotency key existing
        Redis::shouldReceive('get')->andReturn('1');
        // After skipping, rpop returns null
        Redis::shouldReceive('rpop')->once()->andReturnNull();

        $this->artisan('messaging:consume source.fetch.failed --limit=1')
            ->expectsOutput('No more messages')
            ->assertExitCode(0);
    }
}
