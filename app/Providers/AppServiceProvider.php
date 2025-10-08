<?php
declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use App\Events\HttpRetryEvent;
use App\Contracts\MessageBrokerInterface;
use App\Services\Messaging\RedisMessageBroker;
use App\Services\Integrations\AdapterResolver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind source adapters so they can be type-hinted and mocked in tests
        $this->app->bind(\App\Contracts\SourceAdapterInterface::class, function ($app) {
            // Default adapter when resolving the interface directly.
            return $app->make(\App\Services\Integrations\NewsApiAdapter::class);
        });

        // Also bind concrete adapters for direct resolution if needed
        $this->app->bind(\App\Services\Integrations\NewsApiAdapter::class);
        $this->app->bind(\App\Services\Integrations\GuardianAdapter::class);
        $this->app->bind(\App\Services\Integrations\NytAdapter::class);

        $this->app->singleton(AdapterResolver::class);

        // Bind MessageBrokerInterface
        $this->app->singleton(\App\Contracts\MessageBrokerInterface::class, function ($app) {
            return new \App\Services\Messaging\RedisMessageBroker();
        });

        // Bind a MetricsClientInterface. Prefer a StatsD-backed client when
        // a 'statsd' binding exists in the container, otherwise use Redis.
        $this->app->bind(\App\Contracts\MetricsClientInterface::class, function ($app) {
            if ($app->bound('statsd')) {
                return new \App\Services\Metrics\StatsDClient();
            }

            return new \App\Services\Metrics\RedisMetricsClient();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        
        // Register a macro that returns a small client wrapper which performs
        // manual retries using the Http facade. We do manual retries so that
        // Http::fakeSequence works deterministically in tests and so we can
        // dispatch HttpRetryEvent for observability.
        Http::macro('withRetry', function (?int $attempts = null, ?int $baseSleepMs = null, ?int $maxSleepMs = null) {
            $attempts = $attempts ?? (int) config('news.newsapi.retry_attempts', 3);
            $baseSleepMs = $baseSleepMs ?? (int) config('news.newsapi.retry_sleep_ms', 100);
            $maxSleepMs = $maxSleepMs ?? (int) config('news.newsapi.retry_max_sleep_ms', 2000);

            return new class($attempts, $baseSleepMs, $maxSleepMs) {
                protected int $attempts;
                protected int $baseSleepMs;
                protected int $maxSleepMs;
                /** @var array<string,string> */
                protected array $headers = [];
                protected ?int $timeout = null;

                public function __construct(int $attempts, int $baseSleepMs, int $maxSleepMs)
                {
                    $this->attempts = $attempts;
                    $this->baseSleepMs = $baseSleepMs;
                    $this->maxSleepMs = $maxSleepMs;
                }

                public function timeout(int $seconds): self
                {
                    $this->timeout = $seconds;
                    return $this;
                }

                /**
                 * @param array<string,string> $headers
                 */
                public function withHeaders(array $headers): self
                {
                    $this->headers = array_merge($this->headers, $headers);
                    return $this;
                }

                /**
                 * Build a PendingRequest with configured headers and timeout.
                 *
                 * @return \Illuminate\Http\Client\PendingRequest
                 */
                protected function buildRequest(): \Illuminate\Http\Client\PendingRequest
                {
                    $req = Http::withHeaders($this->headers);
                    if (! is_null($this->timeout)) {
                        $req = $req->timeout($this->timeout);
                    }
                    return $req;
                }

                /**
                 * @param string $url
                 * @param array<string,mixed> $query
                 * @return \Illuminate\Http\Client\Response|null
                 */
                public function get(string $url, array $query = []): ?\Illuminate\Http\Client\Response
                {
                    $start = microtime(true);
                    $lastException = null;

                    for ($attempt = 1; $attempt <= $this->attempts; $attempt++) {
                        try {
                            $req = $this->buildRequest();
                            $response = $req->get($url, $query);

                            $status = $response->status();
                            $elapsedMs = (int) ((microtime(true) - $start) * 1000);

                            Event::dispatch(new HttpRetryEvent($url, $attempt, $status, null, $elapsedMs, false));

                            if ($status >= 500 && $attempt < $this->attempts) {
                                $sleepMs = min($this->maxSleepMs, $this->baseSleepMs * (2 ** ($attempt - 1)) + random_int(0, $this->baseSleepMs));
                                usleep($sleepMs * 1000);
                                continue;
                            }

                            return $response;
                        } catch (\Throwable $e) {
                            $lastException = $e;
                            $elapsedMs = (int) ((microtime(true) - $start) * 1000);
                            Event::dispatch(new HttpRetryEvent($url, $attempt, null, $e->getMessage(), $elapsedMs, $attempt >= $this->attempts));

                            if ($attempt >= $this->attempts) {
                                throw $e;
                            }

                            $sleepMs = min($this->maxSleepMs, $this->baseSleepMs * (2 ** ($attempt - 1)) + random_int(0, $this->baseSleepMs));
                            usleep($sleepMs * 1000);
                        }
                    }

                    if ($lastException) {
                        throw $lastException;
                    }

                    return null;
                }

                /**
                 * Forward unknown calls to the Http facade.
                 *
                 * @param string $name
                 * @param array<mixed> $arguments
                 * @return mixed
                 */
                public function __call($name, $arguments)
                {
                    // Fallback: if a chainable method is invoked we try to store it
                    if ($name === 'timeout' && isset($arguments[0])) {
                        return $this->timeout((int) $arguments[0]);
                    }
                    if ($name === 'withHeaders' && isset($arguments[0]) && is_array($arguments[0])) {
                        return $this->withHeaders($arguments[0]);
                    }

                    // Otherwise forward to Http facade (single-shot)
                    return Http::{$name}(...$arguments);
                }
            };
        });
    }
}
