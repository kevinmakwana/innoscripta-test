<?php
declare(strict_types=1);

namespace Tests\Integration;

use Tests\TestCase;
use App\Models\Source;
use App\Models\Article;
use App\Jobs\FetchSourceJob;
use App\Contracts\SourceAdapterInterface;
use Illuminate\Support\Facades\Bus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use App\Events\SourceFetchFailed;

class FetchSourceJobE2ETest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test full workflow: dispatch FetchSourceJob and verify articles are persisted
     */
    public function test_job_dispatches_and_persists_articles()
    {
        // Create a test source
        $source = Source::factory()->create([
            'name' => 'Test Source',
            'slug' => 'test-source-' . uniqid(),
            'adapter_class' => 'App\Services\Integrations\NewsApiAdapter',
            'enabled' => true,
        ]);

        // Mock the adapter to return test data
        $mockAdapter = $this->mock(SourceAdapterInterface::class);
        $mockAdapter->shouldReceive('fetchTopHeadlines')
            ->once()
            ->andReturn(collect([
                [
                    'title' => 'Breaking News from Politics',
                    'description' => 'Test description 1',
                    'url' => 'https://example.com/1',
                    'publishedAt' => now()->toISOString(),
                    'source' => ['name' => 'Test Source'],
                    'author' => 'Test Author',
                ],
                [
                    'title' => 'Sports Update Today',
                    'description' => 'Test description 2',
                    'url' => 'https://example.com/2',
                    'publishedAt' => now()->subDay()->toISOString(),
                    'source' => ['name' => 'Test Source'],
                    'author' => 'Test Author 2',
                ],
            ]));

        // Dispatch the job
        Bus::dispatch(new FetchSourceJob($source, $mockAdapter));

        // Process the job
        $this->artisan('queue:work', [
            '--once' => true,
            '--tries' => 1,
        ]);

        // Assert articles were created
        $this->assertDatabaseCount('articles', 2);
        $this->assertDatabaseHas('articles', [
            'title' => 'Breaking News from Politics',
            'source_id' => $source->id,
        ]);
        $this->assertDatabaseHas('articles', [
            'title' => 'Sports Update Today',
            'source_id' => $source->id,
        ]);

        // Assert related models were created
        $this->assertDatabaseCount('authors', 2);
        $this->assertDatabaseCount('categories', 0); // No categories in test data
    }

    /**
     * Test job failure handling when adapter throws exception
     */
    public function test_job_handles_adapter_failure()
    {
        Event::fake();

        // Create a test source
        $source = Source::factory()->create([
            'enabled' => true,
        ]);

        // Mock adapter to throw exception
        $mockAdapter = $this->mock(SourceAdapterInterface::class);
        $mockAdapter->shouldReceive('fetchTopHeadlines')
            ->once()
            ->andThrow(new \Exception('API rate limit exceeded'));

        // Dispatch the job
        Bus::dispatch(new FetchSourceJob($source, $mockAdapter));

        // Process the job
        $this->artisan('queue:work', [
            '--once' => true,
            '--tries' => 1,
        ]);

        // Assert failure event was dispatched
        Event::assertDispatched(SourceFetchFailed::class, function ($event) use ($source) {
            return $event->source->id === $source->id &&
                   str_contains($event->errorMessage, 'API rate limit exceeded');
        });

        // Assert source failure counter was incremented
        $source->refresh();
        $this->assertGreaterThan(0, $source->failure_count);
    }

    /**
     * Test deduplication: same article fetched twice should not create duplicate
     */
    public function test_deduplication_prevents_duplicates()
    {
        $source = Source::factory()->create();

        $articleData = [
            'title' => 'Unique Article',
            'description' => 'Unique description',
            'url' => 'https://example.com/unique',
            'publishedAt' => now()->toISOString(),
            'source' => ['name' => $source->name],
        ];

        $mockAdapter = $this->mock(SourceAdapterInterface::class);
        $mockAdapter->shouldReceive('fetchTopHeadlines')
            ->twice()
            ->andReturn(collect([$articleData]));

        // First job
        Bus::dispatch(new FetchSourceJob($source, $mockAdapter));
        $this->artisan('queue:work', ['--once' => true]);

        $this->assertDatabaseCount('articles', 1);

        // Second job with same data
        Bus::dispatch(new FetchSourceJob($source, $mockAdapter));
        $this->artisan('queue:work', ['--once' => true]);

        // Should still be only 1 article
        $this->assertDatabaseCount('articles', 1);
    }

    /**
     * Test concurrent job processing doesn't create duplicates
     */
    public function test_concurrent_jobs_handle_duplicates_safely()
    {
        $source = Source::factory()->create();

        $articleData = [
            'title' => 'Concurrent Article',
            'description' => 'Test concurrent processing',
            'url' => 'https://example.com/concurrent',
            'publishedAt' => now()->toISOString(),
            'source' => ['name' => $source->name],
        ];

        $mockAdapter = $this->mock(SourceAdapterInterface::class);
        $mockAdapter->shouldReceive('fetchTopHeadlines')
            ->twice()
            ->andReturn(collect([$articleData]));

        // Dispatch two jobs simultaneously
        Bus::dispatch(new FetchSourceJob($source, $mockAdapter));
        Bus::dispatch(new FetchSourceJob($source, $mockAdapter));

        // Process both jobs
        $this->artisan('queue:work', ['--once' => true]);
        $this->artisan('queue:work', ['--once' => true]);

        // Should have only 1 article despite concurrent processing
        $this->assertDatabaseCount('articles', 1);
    }
}