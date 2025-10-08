<?php
declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\ArticleNormalizationService;
use App\DTOs\NormalizedArticle;

class ArticleNormalizationEdgeCaseTest extends TestCase
{
    public function test_normalize_handles_missing_author_and_category()
    {
        $srv = new ArticleNormalizationService();

        $item = [
            'title' => 'Edge',
            'description' => null,
            'content' => null,
            'url' => 'https://example.com/edge',
            'urlToImage' => null,
            'publishedAt' => null,
            // author missing
            // category missing
        ];

        $out = $srv->normalizeFromNewsApi($item);
        if ($out instanceof NormalizedArticle) {
            $out = $out->toArray();
        }

        $this->assertIsArray($out);
        $this->assertArrayHasKey('author', $out);
        $this->assertNull($out['author']);
        $this->assertArrayHasKey('category', $out);
        $this->assertNull($out['category']);
    }

    public function test_normalize_nyt_handles_empty_multimedia()
    {
        $srv = new ArticleNormalizationService();

        $item = [
            'url' => 'https://nyt.example/empty',
            'title' => 'NYT Edge',
            'abstract' => null,
            'multimedia' => [],
            'published_date' => null,
            'byline' => '',
            'section' => '',
        ];

        $out = $srv->normalizeFromNyt($item);
        if ($out instanceof NormalizedArticle) {
            $out = $out->toArray();
        }

        $this->assertIsArray($out);
        $this->assertNull($out['image_url']);
        $this->assertNull($out['author']);
        $this->assertNull($out['category']);
    }
}
