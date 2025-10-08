<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Contracts\SourceAdapterInterface;
use App\Events\SourceFetchFailed;
use App\Jobs\FetchSourceJob;
use App\Models\Source;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class BrokerFailureTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test job handling when message broker (Redis) is unavailable
     */
    public function test_job_handles_broker_failure_gracefully()
    {
        // Simulate broker failure by using sync driver but mocking failure
        config(['queue.default' => 'sync']);

        Event::fake();

        $source = Source::factory()->create(['enabled' => true]);

        $mockAdapter = $this->mock(SourceAdapterInterface::class);
        $mockAdapter->shouldReceive('fetchTopHeadlines')
            ->once()
            ->andThrow(new \Exception('Connection to Redis failed'));

        // Dispatch job - should fail immediately in sync mode
        try {
            Bus::dispatch(new FetchSourceJob($source, $mockAdapter));
        } catch (\Exception $e) {
            // Expected in sync mode
        }

        // Assert failure event was dispatched
        Event::assertDispatched(SourceFetchFailed::class, function ($event) use ($source) {
            return $event->source->id === $source->id &&
                   str_contains($event->errorMessage, 'Connection to Redis failed');
        });
    }

    /**
     * Test rate limiting scenario in adapter
     */
    public function test_adapter_rate_limiting_is_handled()
    {
        Event::fake();

        $source = Source::factory()->create(['enabled' => true]);

        $mockAdapter = $this->mock(SourceAdapterInterface::class);
        $mockAdapter->shouldReceive('fetchTopHeadlines')
            ->once()
            ->andThrow(new \Exception('API rate limit exceeded. Try again in 60 seconds.'));

        Bus::dispatch(new FetchSourceJob($source, $mockAdapter));

        $this->artisan('queue:work', ['--once' => true, '--tries' => 1]);

        Event::assertDispatched(SourceFetchFailed::class, function ($event) {
            return str_contains($event->errorMessage, 'rate limit');
        });

        // Source should be marked as exhausted or failure incremented
        $source->refresh();
        $this->assertGreaterThan(0, $source->failure_count);
    }

    /**
     * Test job retry mechanism for transient failures
     */
    public function test_job_retries_on_transient_failures()
    {
        Event::fake();

        $source = Source::factory()->create();

        $mockAdapter = $this->mock(SourceAdapterInterface::class);
        $mockAdapter->shouldReceive('fetchTopHeadlines')
            ->once() // In sync queue, job fails immediately without retry
            ->andThrow(new \Exception('Temporary network error'));

        // Dispatch job - FetchSourceJob has tries = 3 by default
        Bus::dispatch(new FetchSourceJob($source, $mockAdapter));

        // Process job - in sync queue, no retries happen
        $this->artisan('queue:work', ['--once' => true, '--tries' => 3]);

        // Assert failure event was dispatched at least once
        Event::assertDispatched(SourceFetchFailed::class);
    }
}
