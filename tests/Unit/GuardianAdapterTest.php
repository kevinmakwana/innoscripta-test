<?php

namespace Tests\Unit;

use App\Services\Integrations\GuardianAdapter;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GuardianAdapterTest extends TestCase
{
    public function test_fetch_top_headlines_returns_collection()
    {
        Http::fake([
            'https://content.guardianapis.com/*' => Http::response(['response' => ['results' => [
                ['webTitle' => 'G Test', 'fields' => ['trailText' => 'desc']],
            ]]], 200),
        ]);

        config()->set('news.guardian.key', 'test');
        $adapter = new GuardianAdapter;
        $collection = $adapter->fetchTopHeadlines(['q' => 'test']);

        $this->assertCount(1, $collection);
        $this->assertEquals('G Test', $collection->first()['webTitle']);
    }
}
