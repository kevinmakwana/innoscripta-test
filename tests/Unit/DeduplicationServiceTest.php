<?php

namespace Tests\Unit;

use App\Models\Article;
use App\Services\DeduplicationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeduplicationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_detects_duplicate_by_external_id()
    {
        Article::factory()->create(['external_id' => 'ext-123', 'url' => 'https://example.com/1', 'title' => 'Hello']);

        $deduper = new DeduplicationService;

        $this->assertTrue($deduper->isDuplicate(['external_id' => 'ext-123', 'url' => 'https://example.com/1', 'title' => 'Hello']));
    }

    public function test_detects_duplicate_by_url_normalization()
    {
        Article::factory()->create(['external_id' => 'ext-999', 'url' => 'https://example.com/article?utm=1', 'title' => 'A']);

        $deduper = new DeduplicationService;

        $this->assertTrue($deduper->isDuplicate(['external_id' => 'other', 'url' => 'https://example.com/article', 'title' => 'A']));
    }

    public function test_detects_duplicate_by_similar_title()
    {
        Article::factory()->create(['external_id' => 'x1', 'url' => 'https://example.com/aa', 'title' => 'Breaking News: Fire in Market']);

        $deduper = new DeduplicationService;

        $this->assertTrue($deduper->isDuplicate(['external_id' => 'x2', 'url' => 'https://example.com/bb', 'title' => 'Breaking News - Fire at Market']));
    }
}
