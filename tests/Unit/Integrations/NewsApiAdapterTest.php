<?php

namespace Tests\Unit\Integrations;

use App\Services\Integrations\NewsApiAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NewsApiAdapterTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_fetch_top_headlines_returns_collection_when_api_ok()
    {
        Http::fake([
            'https://newsapi.org/*' => Http::response(['articles' => [
                ['title' => 'Test', 'description' => 'Desc'],
            ]], 200),
        ]);

        config()->set('news.newsapi.key', 'test_key');

        $adapter = new NewsApiAdapter;
        $result = $adapter->fetchTopHeadlines(['q' => 'test']);

        $this->assertIsIterable($result);
        $this->assertCount(1, $result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_fetch_top_headlines_returns_empty_when_no_key()
    {
        // Ensure config key is empty for this test
        config()->set('news.newsapi.key', null);

        $adapter = new NewsApiAdapter;
        $result = $adapter->fetchTopHeadlines(['q' => 'test']);

        $this->assertIsIterable($result);
        $this->assertCount(0, $result);
    }
}
