<?php

namespace App\Listeners;

use App\Contracts\MetricsClientInterface;
use App\Events\HttpRetryEvent;
use Illuminate\Support\Facades\Log;

class RecordHttpRetryMetrics
{
    private MetricsClientInterface $metrics;

    public function __construct(MetricsClientInterface $metrics)
    {
        $this->metrics = $metrics;
    }

    /**
     * Handle the event.
     */
    public function handle(HttpRetryEvent $event): void
    {
        try {
            // Increment a logical metric name. Implementations decide how to
            // record this (StatsD, Redis keys, or no-op).
            $this->metrics->incrementMetric('http_retry.attempts', 1, [
                'uri' => (string) $event->uri,
                'exhausted' => $event->exhausted ? '1' : '0',
            ]);

            // Also increment a per-uri key and set last seen time when Redis is used
            $uriKey = 'metrics:http_retry:uri:'.md5((string) $event->uri);
            $global = 'metrics:http_retry:global';

            $this->metrics->incrementKey($uriKey, 1, 60 * 60 * 24);
            $this->metrics->incrementKey($global, 1, 60 * 60 * 24 * 7);
            $this->metrics->setKey('metrics:http_retry:last:'.md5((string) $event->uri), now()->toIso8601String(), 60 * 60 * 24);
        } catch (\Throwable $e) {
            // Metrics must never impact mainline flow; degrade to a log.
            Log::info(sprintf('Metric(http_retry.attempts) uri=%s attempt=%d exhausted=%s elapsed_ms=%d error=%s', $event->uri, $event->attempt, $event->exhausted ? '1' : '0', $event->elapsedMs, $e->getMessage()));
        }
    }
}
