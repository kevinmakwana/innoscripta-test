<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Contracts\MessageBrokerInterface;
use App\Events\SourceFetchFailed;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * Listener that records source fetch failures into metrics and optionally
 * updates a lightweight failure counter on the Source model.
 */
class RecordSourceFetchFailureMetrics implements ShouldQueue
{
    public function handle(SourceFetchFailed $event): void
    {
        $source = $event->source;

        // Try to record metrics via a bound statsd/dogstatsd client if available
        try {
            if (app()->bound('statsd')) {
                $statsd = app('statsd');
                // Increment a per-source failure counter and a global counter
                $statsd->increment('source.fetch.failure', 1, 1.0, ['source' => $source->slug]);
                $statsd->increment('source.fetch.failure_total', 1);
            } else {
                Log::info('RecordSourceFetchFailureMetrics: statsd not bound, logging metric', ['source' => $source->slug, 'exhausted' => $event->exhausted]);
            }
        } catch (\Throwable $e) {
            Log::warning('RecordSourceFetchFailureMetrics: failed to emit metrics', ['error' => $e->getMessage()]);
        }

        // Update simple failure counters on the source model if the columns exist
        try {
            if (isset($source->consecutive_failures)) {
                $source->consecutive_failures = ($source->consecutive_failures ?? 0) + 1;
                $source->last_failed_at = now();
                $source->save();

                // Optionally disable after threshold
                $threshold = (int) config('news.auto_disable_after_failures', 0);
                if ($threshold > 0 && ($source->consecutive_failures ?? 0) >= $threshold && ($source->auto_disable ?? false)) {
                    $source->enabled = false;
                    $source->save();
                    Log::warning('RecordSourceFetchFailureMetrics: auto-disabled source due to consecutive failures', ['source' => $source->slug, 'consecutive_failures' => $source->consecutive_failures]);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('RecordSourceFetchFailureMetrics: failed to update source counters', ['error' => $e->getMessage()]);
        }

        // Publish a compact failure message to a broker for downstream systems
        try {
            $broker = app(MessageBrokerInterface::class);
            $broker->publish('source.fetch.failed', (string) json_encode([
                'source_id' => $source->id,
                'slug' => $source->slug,
                'exhausted' => $event->exhausted,
                'timestamp' => now()->toIso8601String(),
            ]));
        } catch (\Throwable $e) {
            Log::debug('RecordSourceFetchFailureMetrics: broker publish failed', ['error' => $e->getMessage()]);
        }
    }
}
