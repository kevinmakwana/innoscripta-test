<?php
declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\ArticleNormalizationService;
use App\DTOs\NormalizedArticle;

class ArticleNormalizationStrictTest extends TestCase
{
    public function test_normalize_from_newsapi_returns_expected_shape()
    {
        $service = new ArticleNormalizationService();

        $item = [
            'source' => ['id' => 'test-source'],
            'title' => 'Test Title',
            'description' => 'Desc',
            'content' => 'Body',
            'url' => 'https://example.com/article',
            'urlToImage' => 'https://example.com/img.jpg',
            'publishedAt' => '2025-10-06T12:00:00Z',
            'author' => 'Jane Doe',
            'category' => 'Tech',
        ];

        $out = $service->normalizeFromNewsApi($item);
        if ($out instanceof NormalizedArticle) {
            $out = $out->toArray();
        }

        $this->assertIsArray($out);
        $this->assertArrayHasKey('external_id', $out);
        $this->assertIsString($out['external_id']);
        $this->assertSame('Test Title', $out['title']);
        $this->assertSame('Desc', $out['excerpt']);
        $this->assertSame('Body', $out['body']);
        $this->assertSame('https://example.com/article', $out['url']);
        $this->assertSame('https://example.com/img.jpg', $out['image_url']);
        $this->assertSame('2025-10-06T12:00:00Z', $out['published_at']);
        $this->assertIsArray($out['author']);
        $this->assertSame('Jane Doe', $out['author']['name']);
        $this->assertArrayHasKey('external_id', $out['author']);
        $this->assertIsString($out['author']['external_id'] ?? '');
        $this->assertIsArray($out['category']);
        $this->assertSame('Tech', $out['category']['name']);
        $this->assertIsString($out['category']['slug']);
    }

    public function test_normalize_from_guardian_returns_expected_shape()
    {
        $service = new ArticleNormalizationService();

        $item = [
            'id' => 'guardian-1',
            'webTitle' => 'G Title',
            'fields' => [
                'trailText' => 'G Desc',
                'body' => 'G Body',
                'thumbnail' => 'https://example.com/g.jpg',
                'byline' => 'Guardian Author',
            ],
            'webUrl' => 'https://guardian.example/article',
            'webPublicationDate' => '2025-10-06T11:00:00Z',
            'sectionName' => 'Science',
        ];

        $out = $service->normalizeFromGuardian($item);
        if ($out instanceof NormalizedArticle) {
            $out = $out->toArray();
        }

        $this->assertIsArray($out);
        $this->assertSame('guardian-1', $out['external_id']);
        $this->assertSame('G Title', $out['title']);
        $this->assertSame('G Desc', $out['excerpt']);
        $this->assertSame('G Body', $out['body']);
        $this->assertSame('https://guardian.example/article', $out['url']);
        $this->assertSame('https://example.com/g.jpg', $out['image_url']);
        $this->assertSame('2025-10-06T11:00:00Z', $out['published_at']);
        $this->assertIsArray($out['author']);
        $this->assertSame('Guardian Author', $out['author']['name']);
        $this->assertIsString($out['author']['external_id']);
        $this->assertIsArray($out['category']);
        $this->assertSame('Science', $out['category']['name']);
        $this->assertIsString($out['category']['slug']);
    }

    public function test_normalize_from_nyt_returns_expected_shape()
    {
        $service = new ArticleNormalizationService();

        $item = [
            'url' => 'https://nyt.example/article',
            'title' => 'NYT Title',
            'abstract' => 'NYT Abstract',
            'multimedia' => [['url' => 'https://example.com/ny.jpg']],
            'published_date' => '2025-10-06T10:00:00Z',
            'byline' => 'NYT Author',
            'section' => 'Opinion',
        ];

        $out = $service->normalizeFromNyt($item);
        if ($out instanceof NormalizedArticle) {
            $out = $out->toArray();
        }

        $this->assertIsArray($out);
        $this->assertSame('https://nyt.example/article', $out['external_id']);
        $this->assertSame('NYT Title', $out['title']);
        $this->assertSame('NYT Abstract', $out['excerpt']);
        $this->assertSame('NYT Abstract', $out['body']);
        $this->assertSame('https://nyt.example/article', $out['url']);
        $this->assertSame('https://example.com/ny.jpg', $out['image_url']);
        $this->assertSame('2025-10-06T10:00:00Z', $out['published_at']);
        $this->assertIsArray($out['author']);
        $this->assertSame('NYT Author', $out['author']['name']);
        $this->assertIsArray($out['category']);
        $this->assertSame('Opinion', $out['category']['name']);
        $this->assertIsString($out['category']['slug']);
    }
}
