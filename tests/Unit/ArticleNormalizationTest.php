<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\DTOs\NormalizedArticle;
use App\Services\ArticleNormalizationService;
use Tests\TestCase;

class ArticleNormalizationTest extends TestCase
{
    public function test_normalize_from_newsapi_includes_author_and_category_candidates(): void
    {
        $service = new ArticleNormalizationService;

        $sample = [
            'source' => ['id' => 'newsapi'],
            'title' => 'Test Title',
            'description' => 'desc',
            'content' => 'body',
            'url' => 'https://example.com/article',
            'urlToImage' => 'https://example.com/image.jpg',
            'publishedAt' => '2025-10-06T12:00:00Z',
            'author' => 'Jane Doe',
            'category' => 'Technology',
        ];

        $out = $service->normalizeFromNewsApi($sample);
        if ($out instanceof NormalizedArticle) {
            $out = $out->toArray();
        }

        $this->assertArrayHasKey('author', $out);
        $this->assertNotNull($out['author']);
        $this->assertArrayHasKey('name', $out['author']);

        $this->assertArrayHasKey('category', $out);
        $this->assertNotNull($out['category']);
        $this->assertArrayHasKey('slug', $out['category']);
    }

    public function test_normalize_from_guardian_includes_author_and_category_candidates(): void
    {
        $service = new ArticleNormalizationService;

        $sample = [
            'id' => 'guardian-1',
            'webTitle' => 'Guardian Title',
            'fields' => ['trailText' => 'trail', 'body' => 'body', 'byline' => 'Guardian Author', 'thumbnail' => 'https://example.com/thumb.jpg'],
            'webUrl' => 'https://guardian.example/article',
            'webPublicationDate' => '2025-10-06T11:00:00Z',
            'sectionName' => 'World',
        ];

        $out = $service->normalizeFromGuardian($sample);
        if ($out instanceof NormalizedArticle) {
            $out = $out->toArray();
        }

        $this->assertArrayHasKey('author', $out);
        $this->assertNotNull($out['author']);
        $this->assertArrayHasKey('name', $out['author']);

        $this->assertArrayHasKey('category', $out);
        $this->assertNotNull($out['category']);
        $this->assertArrayHasKey('slug', $out['category']);
    }

    public function test_normalize_from_nyt_includes_author_and_category_candidates(): void
    {
        $service = new ArticleNormalizationService;

        $sample = [
            'url' => 'https://nyt.example/article',
            'title' => 'NYT Title',
            'abstract' => 'abstract',
            'byline' => 'NYT Author',
            'section' => 'U.S.',
            'multimedia' => [['url' => 'https://example.com/nyt.jpg']],
            'published_date' => '2025-10-06',
        ];

        $out = $service->normalizeFromNyt($sample);
        if ($out instanceof NormalizedArticle) {
            $out = $out->toArray();
        }

        $this->assertArrayHasKey('author', $out);
        $this->assertNotNull($out['author']);
        $this->assertArrayHasKey('name', $out['author']);

        $this->assertArrayHasKey('category', $out);
        $this->assertNotNull($out['category']);
        $this->assertArrayHasKey('slug', $out['category']);
    }
}
