<?php

namespace Tests\Unit;

use App\Services\Integrations\NewsApiAdapter;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NewsApiAdapterTest extends TestCase
{
    public function test_fetch_top_headlines_returns_collection()
    {
        Http::fake([
            'https://newsapi.org/*' => Http::response(['articles' => [
                ['title' => 'Test', 'description' => 'desc', 'source' => ['id' => 'test']],
            ]], 200),
        ]);

        config()->set('news.newsapi.key', 'test');
        $adapter = new NewsApiAdapter;
        $collection = $adapter->fetchTopHeadlines(['q' => 'test']);

        $this->assertCount(1, $collection);
        $this->assertEquals('Test', $collection->first()['title']);
    }
}
