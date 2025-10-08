<?php

namespace Tests\Unit;

use App\Models\Article;
use App\Models\Source;
use App\Services\DeduplicationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FetchMergeTest extends TestCase
{
    use RefreshDatabase;

    public function test_merge_updates_existing_article()
    {
        $source = Source::factory()->create(['slug' => 'newsapi']);

        $existing = Article::factory()->create([
            'source_id' => $source->id,
            'external_id' => 'ext-1',
            'title' => 'Old Title',
            'url' => 'https://example.com/old',
            'published_at' => now()->subDay(),
            'raw_json' => ['a' => 1],
        ]);

        // Simulate adapter returning a normalized item with updated fields
        $normalized = [
            'external_id' => 'ext-1',
            'title' => 'New Title',
            'excerpt' => 'New excerpt',
            'body' => 'New body',
            'url' => 'https://example.com/old',
            'image_url' => 'https://img.example/new.jpg',
            'published_at' => now()->toDateTimeString(),
            'raw_json' => ['b' => 2],
        ];

        $deduper = new DeduplicationService;

        $candidate = $normalized;
        $candidate['source_id'] = $source->id;

        $match = $deduper->findDuplicate($candidate);
        $this->assertNotNull($match);

        // Simulate the merge logic used by the job
        $match->title = $candidate['title'] ?? $match->title;
        $match->excerpt = $candidate['excerpt'] ?? $match->excerpt;
        $match->body = $candidate['body'] ?? $match->body;
        $match->url = $candidate['url'] ?? $match->url;
        $match->image_url = $candidate['image_url'] ?? $match->image_url;

        if (! empty($candidate['published_at'])) {
            $candidateTs = strtotime($candidate['published_at']);
            $existingTs = $match->published_at ? $match->published_at->getTimestamp() : 0;
            if ($candidateTs > $existingTs) {
                $match->published_at = $candidate['published_at'];
            }
        }

        $match->raw_json = array_merge((array) $match->raw_json, (array) ($candidate['raw_json'] ?? []));
        $match->save();

        $fresh = Article::find($existing->id);

        $this->assertEquals('New Title', $fresh->title);
        $this->assertEquals('New excerpt', $fresh->excerpt);
        $this->assertEquals('New body', $fresh->body);
        $this->assertEquals('https://img.example/new.jpg', $fresh->image_url);
        $this->assertArrayHasKey('a', $fresh->raw_json);
        $this->assertArrayHasKey('b', $fresh->raw_json);
    }
}
