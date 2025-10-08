<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use App\Models\Source;
use App\Jobs\FetchSourceJob;
use App\Events\SourceFetchFailed;
use App\Models\Article;

class FetchSourceJobFailureTest extends TestCase
{
    use RefreshDatabase;

    public function test_adapter_exception_dispatches_source_fetch_failed_event()
    {
        Event::fake();

        $source = Source::factory()->create(['slug' => 'newsapi', 'name' => 'Throwing Source']);

        $throwingAdapter = $this->createMock(\App\Contracts\SourceAdapterInterface::class);
    $throwingAdapter->method('fetchTopHeadlines')->will($this->throwException(new \RuntimeException('adapter down')));

        $job = new FetchSourceJob($source, $throwingAdapter);
        $job->handle(
            app(\App\Services\ArticleNormalizationService::class),
            app(\App\Services\Integrations\AdapterResolver::class),
            app(\App\Services\DeduplicationService::class)
        );

        Event::assertDispatched(SourceFetchFailed::class, function ($event) use ($source) {
            return $event->source->id === $source->id && $event->exhausted === true;
        });
    }

    public function test_per_item_exception_dispatches_and_continues_processing()
    {
        Event::fake();

        $source = Source::factory()->create(['slug' => 'newsapi', 'name' => 'Partial Failure Source']);

        // Adapter returns two items; first will cause normalization to throw, second is good
        $fakeAdapter = $this->createMock(\App\Contracts\SourceAdapterInterface::class);
        $fakeAdapter->method('fetchTopHeadlines')->willReturn(collect([
            null,
            [
                'source' => ['id' => 'ok'],
                'title' => 'Survivor',
                'description' => 'desc',
                'content' => 'body',
                'url' => 'https://example.com/survivor',
                'urlToImage' => 'https://example.com/image.jpg',
                'publishedAt' => '2025-10-06T13:00:00Z',
                'author' => 'Good Author',
                'category' => 'Testing',
            ],
        ]));

        $job = new FetchSourceJob($source, $fakeAdapter);
        $job->handle(
            app(\App\Services\ArticleNormalizationService::class),
            app(\App\Services\Integrations\AdapterResolver::class),
            app(\App\Services\DeduplicationService::class)
        );

        // We expect a SourceFetchFailed event for the bad item, exhausted=false
        Event::assertDispatched(SourceFetchFailed::class, function ($event) use ($source) {
            return $event->source->id === $source->id && $event->exhausted === false;
        });

        // And the second (good) article should still be persisted
        $this->assertDatabaseHas('articles', ['url' => 'https://example.com/survivor', 'source_id' => $source->id]);
    }
}
