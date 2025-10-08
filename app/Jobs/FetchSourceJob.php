<?php

declare(strict_types=1);

namespace App\Jobs;

use App\DTOs\NormalizedArticle;
use App\Events\SourceFetchFailed;
use App\Models\Article;
use App\Models\Author;
use App\Models\Category;
use App\Models\Source;
use App\Services\ArticleNormalizationService;
use App\Services\DeduplicationService;
use App\Services\Integrations\AdapterFactory;
use App\Services\Integrations\AdapterResolver;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\QueryException;
use Illuminate\Queue\Attributes\WithoutRelations;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
/**
 * Job responsible for fetching articles from a given Source and storing
 * them in the database. The job is intentionally small and delegates:
 *  - fetching to the source-specific adapter (AdapterFactory)
 *  - normalization to ArticleNormalizationService
 *  - duplication detection to DeduplicationService
 *
 * The job will merge incoming data into existing articles when duplicates
 * are detected to avoid storing redundant entries.
 */

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class FetchSourceJob implements ShouldQueue
{
    use Batchable, InteractsWithQueue, Queueable, SerializesModels {
        __serialize as traitSerialize;
        __unserialize as traitUnserialize;
    }

    protected Source $source;

    #[WithoutRelations]
    protected mixed $adapter = null;

    protected string $idempotencyKey;

    /**
     * Number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * Registry for runtime-only adapters to survive in-process serialization.
     * Keys are generated using spl_object_id to uniquely identify instances.
     *
     * @var array<string, mixed>
     */
    private static array $runtimeAdapters = [];

    public function __construct(Source $source, mixed $adapter = null, ?string $idempotencyKey = null)
    {
        $this->source = $source;
        $this->adapter = $adapter;
        // Use a deterministic key for idempotency: source + date hour
        $this->idempotencyKey = $idempotencyKey ?: 'fetchjob:'.$source->id.':'.now()->format('YmdH');
    }

    public function handle(ArticleNormalizationService $normalizer, AdapterResolver $adapterResolver, DeduplicationService $deduper): void
    {
        // Idempotency: prevent duplicate job execution for the same source/hour
        // Skip idempotency in testing environment to allow multiple job runs
        if (! app()->environment('testing') && Cache::has($this->idempotencyKey)) {
            Log::info('FetchSourceJob: duplicate execution prevented', [
                'source_id' => $this->source->id,
                'idempotency_key' => $this->idempotencyKey,
            ]);

            return;
        }
        if (! app()->environment('testing')) {
            Cache::put($this->idempotencyKey, true, now()->addHour());
        }

        $adapter = $this->adapter ?? $adapterResolver->resolveForSource($this->source);

        if (! $adapter) {
            Log::error('FetchSourceJob: adapter not found', [
                'source_id' => $this->source->id,
                'source_slug' => $this->source->slug,
            ]);

            return;
        }

        // Circuit breaker: if source is disabled, skip fetch
        if ($this->source->disabled_at) {
            Log::warning('FetchSourceJob: source is disabled, skipping fetch', [
                'source_id' => $this->source->id,
                'source_slug' => $this->source->slug,
            ]);

            return;
        }

        // Adapter-level fetch with observability on failure
        try {
            $articles = $adapter->fetchTopHeadlines([
                'sources' => $this->source->slug,
                'pageSize' => (int) config('news.batch_size', 50),
            ]);
            Log::info('FetchSourceJob: fetched articles', [
                'source_id' => $this->source->id,
                'source_slug' => $this->source->slug,
                'count' => is_countable($articles) ? count($articles) : 0,
            ]);
        } catch (\Throwable $e) {
            Log::error('FetchSourceJob: adapter fetch failed', [
                'source_id' => $this->source->id,
                'source_slug' => $this->source->slug,
                'error' => $e->getMessage(),
                'exception_class' => get_class($e),
            ]);

            // Mark failure on the Source and potentially disable after threshold
            try {
                $this->source->markFailure();
                $threshold = (int) config('news.failure_threshold', 3);
                if (($this->source->failure_count ?? 0) >= $threshold) {
                    $this->source->disable('failure_threshold_reached');
                    Log::alert('FetchSourceJob: circuit breaker tripped, source disabled', [
                        'source_id' => $this->source->id,
                        'source_slug' => $this->source->slug,
                        'failure_count' => $this->source->failure_count,
                    ]);
                }
            } catch (\Throwable $_) {
                // don't let model failures bubble up
            }

            try {
                Event::dispatch(new SourceFetchFailed($this->source, $e->getMessage(), true));
            } catch (\Throwable $_) {
                // swallow event errors
            }

            // Metrics: log failure for monitoring
            Log::notice('FetchSourceJob: metrics - fetch failure', [
                'source_id' => $this->source->id,
                'source_slug' => $this->source->slug,
                'event' => 'fetch_failed',
            ]);

            return;
        }

        $deduper = new DeduplicationService;

        $processed = 0;
        foreach ($articles as $item) {
            try {
                // Select normalization strategy based on adapter/source
                if ($this->source->slug === 'newsapi') {
                    $dto = $normalizer->normalizeFromNewsApi($item);
                } elseif ($this->source->slug === 'theguardian') {
                    $dto = $normalizer->normalizeFromGuardian($item);
                } elseif ($this->source->slug === 'nytimes') {
                    $dto = $normalizer->normalizeFromNyt($item);
                } else {
                    // Generic normalization for test or unknown sources
                    $author = null;
                    if (isset($item['author']) && is_string($item['author']) && trim($item['author']) !== '') {
                        $author = ['name' => trim($item['author']), 'external_id' => null];
                    }

                    $category = null;
                    if (isset($item['category']) && is_string($item['category']) && trim($item['category']) !== '') {
                        $category = ['name' => trim($item['category']), 'slug' => Str::slug($item['category'])];
                    }

                    $dto = new NormalizedArticle(
                        md5((string) json_encode($item)),
                        $item['webTitle'] ?? $item['title'] ?? null,
                        $item['fields']['trailText'] ?? $item['description'] ?? null,
                        $item['fields']['body'] ?? $item['content'] ?? null,
                        $item['webUrl'] ?? $item['url'] ?? null,
                        $item['fields']['thumbnail'] ?? $item['multimedia'][0]['url'] ?? $item['image'] ?? null,
                        $item['webPublicationDate'] ?? $item['publishedAt'] ?? null,
                        $author,
                        $category,
                        $item,
                    );
                }

                // Convert DTO to array for downstream processing
                $data = $dto->toArray();
                $data['source_id'] = $this->source->id;

                // Wrap per-item persistence in a transaction so that concurrent
                // upserts / duplicate key handling can be isolated per item.
                DB::transaction(function () use ($data, $deduper) {
                    // Persist or find author/category if present in the normalized payload
                    $authorId = null;
                    if (! empty($data['author']) && is_array($data['author'])) {
                        $authorName = isset($data['author']['name']) ? (string) $data['author']['name'] : null;
                        $authorExternal = isset($data['author']['external_id']) ? (string) $data['author']['external_id'] : null;

                        $authorKey = [];
                        $authorAttrs = ['name' => $authorName];

                        if (! empty($authorExternal)) {
                            $authorKey = ['external_id' => $authorExternal];
                        } else {
                            $authorKey = ['name' => $authorName];
                        }

                        $author = Author::firstOrCreate($authorKey, $authorAttrs);
                        $authorId = $author->id;
                    }

                    $categoryId = null;
                    if (! empty($data['category']) && is_array($data['category'])) {
                        $categoryName = isset($data['category']['name']) ? (string) $data['category']['name'] : null;

                        // Prefer an explicit slug from the normalized payload when present and non-empty,
                        // otherwise generate one from the category name. Use isset checks to satisfy static analyzers.
                        if (isset($data['category']['slug']) && $data['category']['slug'] !== '') {
                            $slug = (string) $data['category']['slug'];
                        } else {
                            // Casting null to string yields an empty string; avoid null-coalescing to satisfy static analysis
                            $slug = Str::slug((string) $categoryName);
                        }

                        $category = Category::firstOrCreate(['slug' => $slug], ['name' => $categoryName, 'slug' => $slug]);
                        $categoryId = $category->id;
                    }

                    if ($authorId) {
                        $data['author_id'] = $authorId;
                    }

                    if ($categoryId) {
                        $data['category_id'] = $categoryId;
                    }

                    $existing = $deduper->findDuplicate($data);

                    if ($existing) {
                        $deduper->mergeIntoExisting($existing, $data);
                    } else {
                        // Only allow Article fillable attributes to be mass assigned.
                        $articleAttrs = \Illuminate\Support\Arr::only($data, [
                            'source_id', 'external_id', 'title', 'excerpt', 'body', 'url', 'image_url',
                            'published_at', 'author_id', 'category_id', 'raw_json',
                        ]);

                        try {
                            Article::create($articleAttrs);
                        } catch (QueryException $qe) {
                            // Handle duplicate-key race: someone else inserted the article
                            // between our duplicate check and create. In that case re-query
                            // the article by source_id+external_id and merge changes.
                            $sqlState = $qe->getCode();
                            // Accept common SQLSTATE codes for unique violations (MySQL/Postgres)
                            if (in_array($sqlState, ['23000', '23505'], true)) {
                                $re = Article::where('source_id', $data['source_id'])
                                    ->where('external_id', $data['external_id'])
                                    ->first();

                                if ($re) {
                                    $deduper->mergeIntoExisting($re, $data);
                                    $re->save();
                                }
                            } else {
                                // unknown query error; rethrow to be handled by outer catch
                                throw $qe;
                            }
                        }
                    }
                });
                $processed++;
            } catch (\Throwable $e) {
                // Log per-item normalization/processing errors and continue with remaining items
                Log::warning('FetchSourceJob: item processing failed', [
                    'source_id' => $this->source->id,
                    'source_slug' => $this->source->slug,
                    'error' => $e->getMessage(),
                    'exception_class' => get_class($e),
                    'item' => $item,
                ]);

                try {
                    Event::dispatch(new SourceFetchFailed($this->source, $e->getMessage(), false));
                    // record per-item failure on Source
                    $this->source->markFailure();
                    $threshold = (int) config('news.failure_threshold', 3);
                    if (($this->source->failure_count ?? 0) >= $threshold) {
                        $this->source->disable('failure_threshold_reached');
                        Log::alert('FetchSourceJob: circuit breaker tripped, source disabled (item)', [
                            'source_id' => $this->source->id,
                            'source_slug' => $this->source->slug,
                            'failure_count' => $this->source->failure_count,
                        ]);
                    }
                } catch (\Throwable $_) {
                    // swallow
                }

                // Metrics: log per-item failure
                Log::notice('FetchSourceJob: metrics - item failure', [
                    'source_id' => $this->source->id,
                    'source_slug' => $this->source->slug,
                    'event' => 'item_failed',
                ]);

                // continue with next item
                continue;
            }
        }

        // Metrics: log job completion
        Log::info('FetchSourceJob: completed', [
            'source_id' => $this->source->id,
            'source_slug' => $this->source->slug,
            'processed' => $processed,
            'idempotency_key' => $this->idempotencyKey,
        ]);

        // If we processed items and reached here without adapter exception, reset failure counter
        try {
            $this->source->resetFailures();
        } catch (\Throwable $_) {
            // ignore
        }
    }

    /**
     * Ensure adapter isn't serialized with the job payload (mocks/closures
     * and internal reflection objects are not serializable). We alias the
     * trait methods and call them after clearing the adapter.
     *
     * @return array
     */
    public function __serialize()
    {
        // If adapter is an object (mock or runtime instance) we cannot safely
        // serialize it (mocks contain closures/reflection). Store it in a
        // static registry keyed by spl_object_id and replace with a small
        // placeholder that can be resolved during unserialization in the
        // same process. If the job is serialized across processes the
        // registry won't have the instance and the adapter will be resolved
        // in handle() via AdapterResolver.
        if (is_object($this->adapter)) {
            $key = 'ra_'.spl_object_id($this->adapter);
            self::$runtimeAdapters[$key] = $this->adapter;
            $this->adapter = ['__runtime_adapter_id' => $key];
        }

        return $this->traitSerialize();
    }

    /**
     * Restore properties after unserialization. Ensure adapter remains null.
     *
     * @param  array<string,mixed>  $values
     */
    public function __unserialize(array $values): void
    {
        $this->traitUnserialize($values);

        // If adapter was replaced with a runtime registry placeholder,
        // restore the actual object if it exists in the registry. Otherwise
        // keep adapter null so handle() will resolve it via AdapterResolver.
        if (is_array($this->adapter) && isset($this->adapter['__runtime_adapter_id'])) {
            $key = $this->adapter['__runtime_adapter_id'];
            if (isset(self::$runtimeAdapters[$key])) {
                $this->adapter = self::$runtimeAdapters[$key];
            } else {
                $this->adapter = null;
            }
        }
    }
}
