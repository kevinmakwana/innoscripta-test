<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Article;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Small service that detects duplicates among Article records.
 *
 * Detection strategies (in order):
 *  - exact match by source_id + external_id
 *  - normalized URL match
 *  - fuzzy title match (levenshtein threshold)
 */
class DeduplicationService
{
    /**
     * Return the matching Article model if a duplicate is found, or null.
     *
     * @param  array<string,mixed>  $candidate  Candidate article data (must include url/title/external_id/source_id as available)
     */
    public function findDuplicate(array $candidate): ?Article
    {
        // Check by source_id + external_id
        if (! empty($candidate['source_id']) && ! empty($candidate['external_id'])) {
            $match = Article::where('source_id', $candidate['source_id'])->where('external_id', $candidate['external_id'])->first();
            if ($match) {
                return $match;
            }
        }
        // Prefer a Postgres trigram similarity check when using pgsql and pg_trgm is available.
        // This offloads fuzzy comparisons to the DB and scales far better.
        $driver = DB::getDriverName();

        if ($driver === 'pgsql' && ! empty($candidate['title'])) {
            // similarity threshold (0..1). 0.4 is a reasonable starting point for titles.
            $threshold = 0.4;
            $title = trim($candidate['title']);

            try {
                // Use pg_trgm's similarity function; ensure the extension exists in production.
                $match = Article::select('id')
                    ->whereRaw('similarity(title, ?) > ?', [$title, $threshold])
                    ->orderByRaw('similarity(title, ?) DESC', [$title])
                    ->first();

                // $match may be an Article model or null; guard for safety
                if ($match instanceof Article) {
                    return Article::find($match->id);
                }
            } catch (\Throwable $e) {
                // If the DB doesn't support pg_trgm or the query fails, fall back to recent-window method
            }
        }

        // Fallback: Limit fuzzy checks to a recent window to avoid loading the whole table
        $recentWindowDays = 90;
        $recentSince = now()->subDays($recentWindowDays);

        // Check by normalized URL against recent articles (in-memory comparison)
        if (! empty($candidate['url'])) {
            $candidateNorm = $this->normalizeUrl($candidate['url']);
            $recent = Article::select('id', 'url', 'title', 'published_at')
                ->where('published_at', '>=', $recentSince)
                ->whereNotNull('url')
                ->get();

            foreach ($recent as $r) {
                if ($this->normalizeUrl($r->url) === $candidateNorm) {
                    $found = Article::find($r->id);
                    if ($found instanceof Article) {
                        return $found;
                    }
                }
            }
        }

        // Fuzzy title comparison (simple levenshtein threshold) on recent articles only
        if (! empty($candidate['title'])) {
            $title = Str::lower(trim($candidate['title']));

            $recentTitles = Article::select('id', 'title')
                ->whereNotNull('title')
                ->where('published_at', '>=', $recentSince)
                ->get();

            foreach ($recentTitles as $row) {
                $ex = Str::lower(trim($row->title));
                $dist = levenshtein($title, $ex);
                $len = max(strlen($title), strlen($ex));
                if ($len > 0 && ($dist / $len) < 0.2) { // 20% difference
                    $found = Article::find($row->id);
                    if ($found instanceof Article) {
                        return $found;
                    }
                }
            }
        }

        return null;
    }

    // quoteBindingPlaceholder removed — orderByRaw now uses explicit placeholders directly.

    /**
     * Convenience boolean wrapper for findDuplicate().
     *
     * @param  array<string,mixed>  $candidate
     */
    public function isDuplicate(array $candidate): bool
    {
        return (bool) $this->findDuplicate($candidate);
    }

    /**
     * Normalize a URL for basic comparison (strip scheme & trailing slash).
     */
    protected function normalizeUrl(string $url): string
    {
        // Parse URL and rebuild as host + path (strip scheme and query params)
        $parts = parse_url(trim($url));
        if ($parts === false) {
            return rtrim(preg_replace('#^https?://#', '', $url), '/');
        }

        $host = $parts['host'] ?? '';
        $path = $parts['path'] ?? '';

        $norm = strtolower(rtrim($host.$path, '/'));

        return $norm;
    }

    /**
     * Merge incoming data into an existing article.
     *
     * @param  array<string,mixed>  $data
     */
    public function mergeIntoExisting(Article $existing, array $data): void
    {
        $existing->title = $data['title'] ?? $existing->title;
        $existing->excerpt = $data['excerpt'] ?? $existing->excerpt;
        $existing->body = $data['body'] ?? $existing->body;
        $existing->url = $data['url'] ?? $existing->url;
        $existing->image_url = $data['image_url'] ?? $existing->image_url;

        if (! empty($data['published_at'])) {
            $candidateTs = strtotime($data['published_at']);
            $existingTs = $existing->published_at ? $existing->published_at->getTimestamp() : 0;
            if ($candidateTs > $existingTs) {
                $existing->published_at = $data['published_at'];
            }
        }

        $existingRaw = $existing->raw_json ?? [];
        $existing->raw_json = array_merge((array) $existingRaw, (array) ($data['raw_json'] ?? []));

        if (empty($existing->external_id) && ! empty($data['external_id'])) {
            $existing->external_id = $data['external_id'];
        }

        if (! empty($data['author_id']) && empty($existing->author_id)) {
            $existing->author_id = $data['author_id'];
        }

        if (! empty($data['category_id']) && empty($existing->category_id)) {
            $existing->category_id = $data['category_id'];
        }

        $existing->save();
    }
}
