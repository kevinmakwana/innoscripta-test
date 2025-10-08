<?php
declare(strict_types=1);

namespace App\Services\Metrics;

use App\Contracts\MetricsClientInterface;
use Illuminate\Support\Facades\App as AppFacade;

class StatsDClient implements MetricsClientInterface
{
    /**
     * @var mixed|null
     */
    private $client;

    public function __construct()
    {
        // Attempt to resolve a container binding 'statsd' which may be provided
        // by a third-party package in production. If missing, keep null and
        // act as a harmless no-op implementation.
        $this->client = AppFacade::bound('statsd') ? AppFacade::make('statsd') : null;
    }

    public function incrementMetric(string $name, int $value = 1, array $tags = []): void
    {
        if (! $this->client) {
            return;
        }

        if (is_object($this->client) && method_exists($this->client, 'increment')) {
            // popular clients support tags as an associative array
            $this->client->increment($name, $value, 1.0, $tags);
            return;
        }

        if (is_object($this->client) && method_exists($this->client, 'count')) {
            $this->client->count($name, $value);
            return;
        }
    }

    public function incrementKey(string $key, int $value = 1, ?int $ttlSeconds = null): void
    {
        // StatsD does not support raw keys; no-op
    }

    public function setKey(string $key, string $value, ?int $ttlSeconds = null): void
    {
        // StatsD does not support raw key storage; no-op
    }
}
