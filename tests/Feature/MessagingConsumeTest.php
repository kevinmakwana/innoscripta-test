<?php
declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Redis;

class MessagingConsumeTest extends TestCase
{
    public function test_consume_command_processes_messages_from_redis()
    {
        $source = \App\Models\Source::factory()->create(['id' => 1, 'slug' => 'test-source']);
    $validMessage = (string) json_encode([
            'version' => 1,
            'type' => 'source.fetch.failed',
            'data' => [
                'source_id' => 1,
                'error_message' => 'Network timeout',
                'exhausted' => false
            ]
        ]);
        Redis::shouldReceive('rpop')->once()->andReturn($validMessage);
        Redis::shouldReceive('rpop')->once()->andReturnNull();
        Redis::shouldReceive('get')->andReturnNull(); // Idempotency check
        Redis::shouldReceive('setex')->andReturnNull(); // Mark as processed
        Redis::shouldReceive('lpush')->andReturnNull(); // For any DLQ pushes

        $this->artisan('messaging:consume source.fetch.failed --limit=10')
            ->expectsOutput('Processed message: {"version":1,"type":"source.fetch.failed","data":{"source_id":1,"error_message":"Network timeout","exhausted":false}}')
            ->assertExitCode(0);
    }
}
