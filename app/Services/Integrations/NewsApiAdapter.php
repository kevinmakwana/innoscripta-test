<?php
declare(strict_types=1);

namespace App\Services\Integrations;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Collection;

/**
 * Adapter for the NewsAPI.org service. Responsible for calling the
 * top-headlines endpoint and returning a Collection of raw article items.
 */
use App\Contracts\SourceAdapterInterface;

class NewsApiAdapter implements SourceAdapterInterface
{
    protected string $baseUrl;
    protected ?string $apiKey;

    public function __construct()
    {
        $this->baseUrl = 'https://newsapi.org/v2';
        // Read API key from config; config/news.php provides defaults and env fallbacks.
    $this->apiKey = config('news.newsapi.key');
    }

    /**
     * Fetch top headlines from NewsAPI.
     *
     * @param array<string,mixed> $params Query parameters (country, category, q, sources, pageSize, etc.)
     * @return \Illuminate\Support\Collection<int, array<string,mixed>>
     */
    public function fetchTopHeadlines(array $params = []): Collection
    {
        if (! $this->apiKey) {
            return collect();
        }

    $attempts = (int) config('news.newsapi.retry_attempts', 3);
    $sleep = (int) config('news.newsapi.retry_sleep_ms', 100);
    $maxSleep = (int) config('news.newsapi.retry_max_sleep_ms', 2000);

    $client = Http::withRetry($attempts, $sleep, $maxSleep);
    $response = $client->timeout((int) config('news.newsapi.timeout', 10))
        ->withHeaders(['X-Api-Key' => $this->apiKey])
            ->get("{$this->baseUrl}/top-headlines", $params);

        if (! $response->successful()) {
            \Illuminate\Support\Facades\Log::warning('NewsApiAdapter non-success response', ['status' => $response->status(), 'url' => "{$this->baseUrl}/top-headlines", 'params' => $params]);
            return collect();
        }

        $payload = $response->json();

        /** @var \Illuminate\Support\Collection<int, array<string,mixed>> $results */
        $results = collect($payload['articles'] ?? []);

        return $results;
    }
}
