<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Source;
use App\Jobs\FetchSourceJob;
use App\Models\Article;

class FetchSourceJobConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_concurrent_inserts_do_not_create_duplicates_and_merge()
    {
        $source = Source::factory()->create(['slug' => 'newsapi', 'name' => 'Concurrency Source']);

        $payload = [
            'source' => ['id' => 'concurrent-1'],
            'title' => 'Concurrent Article',
            'description' => 'desc',
            'content' => 'body',
            'url' => 'https://example.com/concurrent',
            'urlToImage' => 'https://example.com/image.jpg',
            'publishedAt' => '2025-10-06T14:00:00Z',
            'author' => 'Concurrent Author',
            'category' => 'Concurrency',
        ];

        $adapterA = $this->createMock(\App\Contracts\SourceAdapterInterface::class);
        $adapterA->method('fetchTopHeadlines')->willReturn(collect([$payload]));

        $adapterB = $this->createMock(\App\Contracts\SourceAdapterInterface::class);
        $adapterB->method('fetchTopHeadlines')->willReturn(collect([$payload]));

        // Simulate two workers processing the same incoming article payload.
        $job1 = new FetchSourceJob($source, $adapterA);
        $job2 = new FetchSourceJob($source, $adapterB);

        // Run job1 then job2 to simulate a race; with transaction + duplicate handling
        // we should end with a single article record.
        $job1->handle(
            app(\App\Services\ArticleNormalizationService::class),
            app(\App\Services\Integrations\AdapterResolver::class),
            app(\App\Services\DeduplicationService::class)
        );
        $job2->handle(
            app(\App\Services\ArticleNormalizationService::class),
            app(\App\Services\Integrations\AdapterResolver::class),
            app(\App\Services\DeduplicationService::class)
        );

        $articles = Article::where('source_id', $source->id)->where('external_id', 'like', '%concurrent-1%')->get();
        $this->assertCount(1, $articles, 'Expected exactly one article after concurrent insert attempts');

        $article = $articles->first();
        $this->assertEquals('Concurrent Article', $article->title);
        $this->assertNotNull($article->author_id);
    }
}
