<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Support\Facades\Http;
use App\Services\Integrations\NytAdapter;

class NytAdapterTest extends TestCase
{
    public function test_fetch_top_headlines_returns_collection()
    {
        Http::fake([
            'https://api.nytimes.com/*' => Http::response(['results' => [
                ['title' => 'NYT Test']
            ]], 200)
        ]);

    config()->set('news.nyt.key', 'test');
    $adapter = new NytAdapter();
        $collection = $adapter->fetchTopHeadlines();

        $this->assertCount(1, $collection);
        $this->assertEquals('NYT Test', $collection->first()['title']);
    }
}
