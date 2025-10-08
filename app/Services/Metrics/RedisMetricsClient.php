<?php
declare(strict_types=1);

namespace App\Services\Metrics;

use App\Contracts\MetricsClientInterface;
use Illuminate\Support\Facades\Redis;

class RedisMetricsClient implements MetricsClientInterface
{
    public function incrementMetric(string $name, int $value = 1, array $tags = []): void
    {
        // Map metric name to a redis key; tags are ignored for Redis backend
        $key = 'metrics:'.$this->sanitizeKey($name);
        try {
            Redis::incrby($key, $value);
            $ttl = $this->defaultTtl();
            Redis::expire($key, $ttl);
        } catch (\Throwable $e) {
            // swallow errors - metrics should not break application flow
        }
    }

    public function incrementKey(string $key, int $value = 1, ?int $ttlSeconds = null): void
    {
        try {
            Redis::incrby($key, $value);
            if (! is_null($ttlSeconds)) {
                Redis::expire($key, $ttlSeconds);
            }
        } catch (\Throwable $e) {
            // swallow
        }
    }

    public function setKey(string $key, string $value, ?int $ttlSeconds = null): void
    {
        try {
            Redis::set($key, $value);
            if (! is_null($ttlSeconds)) {
                Redis::expire($key, $ttlSeconds);
            }
        } catch (\Throwable $e) {
            // swallow
        }
    }

    private function sanitizeKey(string $name): string
    {
        return preg_replace('/[^a-zA-Z0-9_:\-]/', '_', $name) ?: $name;
    }

    private function defaultTtl(): int
    {
        return 60 * 60 * 24; // 24 hours
    }
}
