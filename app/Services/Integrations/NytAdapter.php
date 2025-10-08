<?php

declare(strict_types=1);

namespace App\Services\Integrations;

use App\Contracts\SourceAdapterInterface;
use Illuminate\Support\Collection;
/**
 * Adapter for New York Times APIs (topstories). Returns results as a
 * Collection of raw article objects.
 */
use Illuminate\Support\Facades\Http;

class NytAdapter implements SourceAdapterInterface
{
    protected string $baseUrl;

    protected ?string $apiKey;

    public function __construct()
    {
        $this->baseUrl = 'https://api.nytimes.com/svc';
        $this->apiKey = config('news.nyt.key');
    }

    /**
     * Fetch top stories from NYT topstories endpoint.
     *
     * @param  array<string,mixed>  $params  ['section' => 'home'|'world'|...]
     * @return \Illuminate\Support\Collection<int, array<string,mixed>>
     */
    public function fetchTopHeadlines(array $params = []): Collection
    {
        if (! $this->apiKey) {
            return collect();
        }

        // Example: /svc/topstories/v2/home.json
        $section = $params['section'] ?? 'home';

        $attempts = (int) config('news.nyt.retry_attempts', 3);

        $client = Http::withRetry($attempts, (int) config('news.nyt.timeout', 10));
        $response = $client->timeout((int) config('news.nyt.timeout', 10))
            ->get("{$this->baseUrl}/topstories/v2/{$section}.json", ['api-key' => $this->apiKey]);

        if (! $response->successful()) {
            \Illuminate\Support\Facades\Log::warning('NytAdapter non-success response', ['status' => $response->status(), 'url' => "{$this->baseUrl}/topstories/v2/{$section}.json", 'section' => $section]);

            return collect();
        }

        $payload = $response->json();

        /** @var \Illuminate\Support\Collection<int, array<string,mixed>> $results */
        $results = collect($payload['results'] ?? []);

        return $results;
    }
}
