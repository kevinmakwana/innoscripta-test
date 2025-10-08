<?php

namespace Tests\Unit;

use App\Events\HttpRetryEvent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HttpRetryMiddlewarePublicContractTest extends TestCase
{
    public function test_http_with_retry_retries_and_dispatches_events()
    {
        Event::fake();

        // Simulate transient failures: 500, 500, then 200
        Http::fakeSequence()
            ->pushStatus(500)
            ->pushStatus(500)
            ->pushStatus(200, [], 'OK');

        // Use the macro that wires retry behavior (AppServiceProvider::withRetry)
        $client = Http::withRetry(3, 1);

        $response = $client->get('https://example.test/endpoint');

        $this->assertEquals(200, $response->status());

        // Expect at least one HttpRetryEvent dispatched (for attempts and final success)
        Event::assertDispatched(HttpRetryEvent::class, function (HttpRetryEvent $e) {
            return $e->uri === 'https://example.test/endpoint' && $e->attempt >= 1;
        });
    }
}
