<?php

namespace Tests\Unit;

use App\Services\Integrations\NewsApiAdapter;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NewsApiAdapterRetryCountTest extends TestCase
{
    public function test_retry_attempts_are_made_on_transient_failures()
    {
        // Prepare a fake that returns 500 twice then 200
        $sequence = Http::fakeSequence();
        $sequence->push('', 500)->push('', 500)->push(['articles' => [['title' => 'OK']]], 200);

        config()->set('news.newsapi.key', 'test_key');
        config()->set('news.newsapi.retry_attempts', 3);
        config()->set('news.newsapi.retry_sleep_ms', 1);
        config()->set('news.newsapi.retry_max_sleep_ms', 10);

        $adapter = new NewsApiAdapter;
        $result = $adapter->fetchTopHeadlines(['q' => 'test']);

        $this->assertIsIterable($result);
        $this->assertCount(1, $result);

        // Verify that the fake received 3 calls by checking the sequence internal pointer indirectly
        // Http::fakeSequence does not expose call count, but we can assert that response arrived
        $this->assertEquals('OK', $result->first()['title']);
    }
}
