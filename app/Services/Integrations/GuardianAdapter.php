<?php

declare(strict_types=1);

namespace App\Services\Integrations;

use App\Contracts\SourceAdapterInterface;
use Illuminate\Support\Collection;
/**
 * Adapter for The Guardian content API. Returns search results as a Collection
 * of raw article result objects.
 */
use Illuminate\Support\Facades\Http;

class GuardianAdapter implements SourceAdapterInterface
{
    protected string $baseUrl;

    protected ?string $apiKey;

    public function __construct()
    {
        $this->baseUrl = 'https://content.guardianapis.com';
        $this->apiKey = config('news.guardian.key');
    }

    /**
     * Fetch headlines/results from The Guardian search endpoint.
     *
     * @param  array<string,mixed>  $params  Query params (q, section, pageSize, etc.)
     * @return \Illuminate\Support\Collection<int, array<string,mixed>>
     */
    public function fetchTopHeadlines(array $params = []): Collection
    {
        if (! $this->apiKey) {
            return collect();
        }

        $query = array_merge(['api-key' => $this->apiKey, 'show-fields' => 'headline,trailText,thumbnail,body'], $params);

        $attempts = (int) config('news.guardian.retry_attempts', 3);

        $client = Http::withRetry($attempts, (int) config('news.guardian.timeout', 10));
        $response = $client->timeout((int) config('news.guardian.timeout', 10))
            ->get("{$this->baseUrl}/search", $query);

        if (! $response->successful()) {
            \Illuminate\Support\Facades\Log::warning('GuardianAdapter non-success response', ['status' => $response->status(), 'url' => "{$this->baseUrl}/search", 'query' => $query]);

            return collect();
        }

        $payload = $response->json();

        /** @var \Illuminate\Support\Collection<int, array<string,mixed>> $results */
        $results = collect($payload['response']['results'] ?? []);

        return $results;
    }
}
