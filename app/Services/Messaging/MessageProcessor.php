<?php
declare(strict_types=1);

namespace App\Services\Messaging;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use App\Services\ArticleNormalizationService;
use App\Models\Article;
use App\Models\Source;
use Illuminate\Support\Arr;

class MessageProcessor
{
    protected string $idPrefix = 'messaging:processed:';

    /**
     * Validate a message payload against minimal schema expectations.
     * Returns true when valid.
     */
    /**
     * @param array<string,mixed> $payload
     */
    public function validate(array $payload): bool
    {
        if (! isset($payload['version']) || ! isset($payload['type']) || ! isset($payload['data'])) {
            return false;
        }

        // Basic type check
        if (! is_int($payload['version']) || ! is_string($payload['type']) || ! is_array($payload['data'])) {
            return false;
        }

        return true;
    }

    /**
     * Compute an idempotency key for a message. Uses type + a stable fingerprint of data.
     */
    /**
     * @param array<string,mixed> $payload
     */
    public function idempotencyKey(array $payload): string
    {
    $dataHash = md5((string) json_encode($payload['data'] ?? []));
        return $this->idPrefix . ($payload['type'] ?? 'unknown') . ':' . $dataHash;
    }

    /**
     * Check-and-mark idempotency. Returns true if this message was already processed.
     */
    /**
     * @param array<string,mixed> $payload
     */
    public function alreadyProcessed(array $payload): bool
    {
        $key = $this->idempotencyKey($payload);
        $exists = Redis::get($key);
        if ($exists) {
            return true;
        }

        $ttl = config('messaging.idempotency_ttl', 86400);
        Redis::setex($key, (int) $ttl, '1');
        return false;
    }

    /**
     * Process the message payload (business logic placeholder).
     */
    /**
     * @param array<string,mixed> $payload
     */
    public function process(array $payload): void
    {
        // Basic validation guard
        if (! $this->validate($payload)) {
            Log::warning('MessageProcessor: invalid payload, sending to DLQ', ['payload' => $payload]);
            // push to DLQ
            $dlq = config('messaging.dlq_prefix', 'messaging:dlq:') . ($payload['type'] ?? 'unknown');
            Redis::rpush($dlq, (string) json_encode($payload));
            return;
        }

        // Already processed check (idempotency)
        if ($this->alreadyProcessed($payload)) {
            Log::info('MessageProcessor: skipping already processed message', ['key' => $this->idempotencyKey($payload)]);
            return;
        }

        $type = $payload['type'];
        $data = $payload['data'];

        // Handle different message types
        if ($type === 'source.fetch.failed') {
            // Fire event for source fetch failure
            $source = Source::find($data['source_id']);
            if (! $source) {
                throw new \RuntimeException('Source not found for fetch failure event');
            }
            event(new \App\Events\SourceFetchFailed($source, $data['error_message'], $data['exhausted'] ?? false));
            Log::info('MessageProcessor: fired SourceFetchFailed event', ['data' => $data]);
            return;
        }

        // Default: assume article normalization
        try {
            // Normalize according to type if we have a mapping
            $normalizer = app(ArticleNormalizationService::class);

            // Expect data to be a provider-specific item and type to hint at source
            // Map common types to normalization methods
            $normalized = null;
            if (str_contains($type, 'newsapi')) {
                $normalized = $normalizer->normalizeFromNewsApi(Arr::wrap($data)[0] ?? $data);
            } elseif (str_contains($type, 'guardian')) {
                $normalized = $normalizer->normalizeFromGuardian(Arr::wrap($data)[0] ?? $data);
            } elseif (str_contains($type, 'nyt') || str_contains($type, 'newyorktimes')) {
                $normalized = $normalizer->normalizeFromNyt(Arr::wrap($data)[0] ?? $data);
            } else {
                // Fallback: assume NewsAPI-like shape
                $normalized = $normalizer->normalizeFromNewsApi(Arr::wrap($data)[0] ?? $data);
            }

            // Normalizer methods are declared to return NormalizedArticle; no runtime type check required.

            // Persist author & category (if present) and article
            $source = null;
            if (isset($payload['meta']['source_id'])) {
                $source = Source::find($payload['meta']['source_id']);
            }

            // Attempt to resolve source from normalized raw_json if possible
            if (! $source) {
                $sourceExternal = $normalized->raw_json['source']['id'] ?? null;
                if ($sourceExternal) {
                    $slug = is_string($sourceExternal) ? $sourceExternal : (string) $sourceExternal;
                    $source = Source::firstOrCreate([
                        'slug' => $slug,
                    ], [
                        'name' => ucfirst(str_replace(['-', '_'], ' ', $slug)),
                        'enabled' => true,
                    ]);
                }
            }

            // If still no source found, try a default source record to satisfy FK
            if (! $source) {
                $source = Source::firstOrCreate([
                    'slug' => 'unknown',
                ], [
                    'name' => 'Unknown Source',
                    'enabled' => true,
                ]);
            }

            // Create or update by external_id + source
            $attributes = [
                'external_id' => $normalized->external_id,
                'title' => $normalized->title,
                'excerpt' => $normalized->excerpt,
                'body' => $normalized->body,
                'url' => $normalized->url,
                'image_url' => $normalized->image_url,
                'published_at' => $normalized->published_at,
                'raw_json' => $normalized->raw_json,
            ];

            $query = Article::query();
            if ($source) {
                $attributes['source_id'] = $source->id;
                $existing = $query->where('source_id', $source->id)->where('external_id', $normalized->external_id)->first();
            } else {
                $existing = $query->where('external_id', $normalized->external_id)->first();
            }

            if ($existing) {
                $existing->update($attributes);
            } else {
                Article::create($attributes);
            }

            Log::info('MessageProcessor: processed and persisted article', ['external_id' => $normalized->external_id]);
        } catch (\Throwable $e) {
            Log::error('MessageProcessor: processing failed, sending to DLQ', ['error' => $e->getMessage(), 'payload' => $payload]);
            $dlq = config('messaging.dlq_prefix', 'messaging:dlq:') . ($payload['type'] ?? 'unknown');
            Redis::rpush($dlq, (string) json_encode(['payload' => $payload, 'error' => $e->getMessage()]));
            // Let the idempotency key expire naturally; do not mark as processed
        }
    }
}
