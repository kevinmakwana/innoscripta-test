<?php

declare(strict_types=1);

namespace App\Contracts;

interface MetricsClientInterface
{
    /**
     * Increment a named metric. Implementations may map this to StatsD counters or Redis keys.
     *
     * @param  array<string,string>  $tags
     */
    public function incrementMetric(string $name, int $value = 1, array $tags = []): void;

    /**
     * Increment a raw key (used by Redis-style implementations).
     */
    public function incrementKey(string $key, int $value = 1, ?int $ttlSeconds = null): void;

    /**
     * Set a raw key value (used to store last-seen timestamps etc).
     */
    public function setKey(string $key, string $value, ?int $ttlSeconds = null): void;
}
