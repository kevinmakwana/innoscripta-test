<?php
declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Str;
use App\DTOs\NormalizedArticle;

/**
 * Service responsible for normalizing provider-specific article payloads
 * into the application's canonical Article shape.
 *
 * Each `normalizeFrom*` method accepts the raw API response item and
 * returns an associative array with keys matching the Article fillable
 * attributes (external_id, title, excerpt, body, url, image_url,
 * published_at, raw_json).
 */
class ArticleNormalizationService
{
    /**
     * Normalize a NewsAPI article payload.
     *
    * @param array<string,mixed> $item Raw NewsAPI article array
    * @return \App\DTOs\NormalizedArticle Normalized article data
    */
    public function normalizeFromNewsApi(array $item): NormalizedArticle
    {
        $author = null;
        if (isset($item['author']) && $item['author'] !== '') {
            $author = $this->normalizeAuthor(['name' => (string) $item['author'], 'external_id' => null]);
        }

        $category = null;
        if (isset($item['category']) && $item['category'] !== '') {
            $category = $this->normalizeCategory(['name' => (string) $item['category'], 'slug' => \Illuminate\Support\Str::slug((string) $item['category'])]);
        }

        return new NormalizedArticle(
            $this->generateExternalId($item),
            $item['title'] ?? null,
            $item['description'] ?? null,
            $item['content'] ?? null,
            $item['url'] ?? null,
            $item['urlToImage'] ?? null,
            $item['publishedAt'] ?? null,
            $author,
            $category,
            $item,
        );
    }

    /**
     * Normalize a The Guardian article payload.
     *
    * @param array<string,mixed> $item Raw Guardian article array
    * @return \App\DTOs\NormalizedArticle Normalized article data
    */
    public function normalizeFromGuardian(array $item): NormalizedArticle
    {
        $author = null;
        if (isset($item['fields']['byline']) && $item['fields']['byline'] !== '') {
            $author = $this->normalizeAuthor(['name' => (string) $item['fields']['byline'], 'external_id' => (string) $item['fields']['byline']]);
        }

        $category = null;
        if (isset($item['sectionName']) && $item['sectionName'] !== '') {
            $category = $this->normalizeCategory(['name' => $item['sectionName'], 'slug' => \Illuminate\Support\Str::slug($item['sectionName'])]);
        }

        return new NormalizedArticle(
            $item['id'] ?? $this->generateExternalId($item),
            $item['webTitle'] ?? null,
            $item['fields']['trailText'] ?? null,
            $item['fields']['body'] ?? null,
            $item['webUrl'] ?? null,
            $item['fields']['thumbnail'] ?? null,
            $item['webPublicationDate'] ?? null,
            $author,
            $category,
            $item,
        );
    }

    /**
     * Normalize a New York Times article payload.
     *
    * @param array<string,mixed> $item Raw NYT article array
    * @return \App\DTOs\NormalizedArticle Normalized article data
    */
    public function normalizeFromNyt(array $item): NormalizedArticle
    {
        $author = null;
        if (isset($item['byline']) && $item['byline'] !== '') {
            $author = $this->normalizeAuthor(['name' => (string) $item['byline'], 'external_id' => null]);
        }

        $category = null;
        if (isset($item['section']) && $item['section'] !== '') {
            $category = $this->normalizeCategory(['name' => (string) $item['section'], 'slug' => \Illuminate\Support\Str::slug((string) $item['section'])]);
        }

        return new NormalizedArticle(
            $item['url'] ?? $this->generateExternalId($item),
            $item['title'] ?? null,
            $item['abstract'] ?? null,
            $item['abstract'] ?? null,
            $item['url'] ?? null,
            $this->extractNytImage($item),
            $item['published_date'] ?? $item['published_date'] ?? null,
            $author,
            $category,
            $item,
        );
    }

    /**
     * Normalize author payload into strict shape or null.
     *
     * @param array<string,mixed> $author
     * @return array{name:string,external_id:?string}|null
     */
    protected function normalizeAuthor(array $author): ?array
    {
        $name = isset($author['name']) ? trim((string) $author['name']) : '';
        if ($name === '') {
            return null;
        }

        $external = isset($author['external_id']) ? (string) $author['external_id'] : null;

        return ['name' => $name, 'external_id' => $external !== '' ? $external : null];
    }

    /**
     * Normalize category payload into strict shape or null.
     *
     * @param array<string,mixed> $category
     * @return array{name:string,slug:string}|null
     */
    protected function normalizeCategory(array $category): ?array
    {
        $name = isset($category['name']) ? trim((string) $category['name']) : '';
        if ($name === '') {
            return null;
        }

        $slug = isset($category['slug']) && $category['slug'] !== '' ? (string) $category['slug'] : \Illuminate\Support\Str::slug($name);

        return ['name' => $name, 'slug' => $slug];
    }

    /**
     * Extract the first image URL from an NYT multimedia block if present.
     *
    * @param array<string,mixed> $item Raw NYT article array
    * @return string|null
     */
    protected function extractNytImage(array $item): ?string
    {
        if (! empty($item['multimedia']) && is_array($item['multimedia'])) {
            return $item['multimedia'][0]['url'] ?? null;
        }

        return null;
    }

    /**
     * Generate a stable external_id for items that lack a provider id.
     * Uses source id + title when available or an MD5 of the filtered payload.
     *
    * @param array<string,mixed> $item
    * @return string
     */
    protected function generateExternalId(array $item): string
    {
        if (! empty($item['source']['id'])) {
            return (string) $item['source']['id'] . '::' . (($item['title'] ?? (string) Str::uuid()));
        }

        return md5((string) json_encode(array_filter($item)));
    }
}
