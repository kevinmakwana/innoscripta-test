<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Source;
use App\Jobs\FetchSourceJob;
use App\Models\Author;
use App\Models\Category;
use App\Models\Article;

class FetchSourceJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        putenv('MESSAGING_DRIVER=redis');
        config(['messaging.default' => 'redis']);
    }

    public function test_job_persists_author_category_and_article()
    {
        // create a source record
    $source = Source::factory()->create(['slug' => 'newsapi', 'name' => 'Test Source']);

        // mock adapter implementing the SourceAdapterInterface
        $fakeAdapter = $this->createMock(\App\Contracts\SourceAdapterInterface::class);
        $fakeAdapter->method('fetchTopHeadlines')->willReturn(collect([
            [
                'source' => ['id' => 'testsuite'],
                'title' => 'Job created article',
                'description' => 'desc',
                'content' => 'body',
                'url' => 'https://example.com/jobs/article',
                'urlToImage' => 'https://example.com/image.jpg',
                'publishedAt' => '2025-10-06T13:00:00Z',
                'author' => 'Fetch Author',
                'category' => 'Testing',
            ],
    ]));

        // run the job using the fake adapter
        $job = new FetchSourceJob($source, $fakeAdapter);
        $job->handle(
            app(\App\Services\ArticleNormalizationService::class),
            app(\App\Services\Integrations\AdapterResolver::class),
            app(\App\Services\DeduplicationService::class)
        );

        // assertions
        $this->assertDatabaseHas('authors', ['name' => 'Fetch Author']);
        $this->assertDatabaseHas('categories', ['slug' => 'testing']);

        $article = Article::where('url', 'https://example.com/jobs/article')->first();
        $this->assertNotNull($article);
        $this->assertNotNull($article->author_id);
        $this->assertNotNull($article->category_id);

        $this->assertDatabaseHas('articles', ['id' => $article->id, 'source_id' => $source->id]);
    }
}
