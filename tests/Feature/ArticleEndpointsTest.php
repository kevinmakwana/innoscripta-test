<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Author;
use App\Models\Category;
use App\Models\Source;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticleEndpointsTest extends TestCase
{
    use RefreshDatabase;

    protected Source $source;

    protected Category $category;

    protected Author $author;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test data
        $this->source = Source::factory()->create([
            'name' => 'Test News',
            'slug' => 'test-news',
            'enabled' => true,
        ]);

        $this->category = Category::factory()->create([
            'name' => 'Technology',
            'slug' => 'technology',
        ]);

        $this->author = Author::factory()->create([
            'name' => 'John Doe',
        ]);

        $this->user = User::factory()->create();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_list_articles()
    {
        // Create test articles
        Article::factory(5)->create([
            'source_id' => $this->source->id,
            'category_id' => $this->category->id,
            'author_id' => $this->author->id,
        ]);

        $response = $this->getJson('/api/v1/articles');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'excerpt',
                        'url',
                        'image_url',
                        'published_at',
                        'source' => ['id', 'name', 'slug'],
                        'category' => ['id', 'name', 'slug'],
                        'author' => ['id', 'name'],
                    ],
                ],
                'links',
                'meta',
            ]);

        $this->assertCount(5, $response->json('data'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_show_single_article()
    {
        $article = Article::factory()->create([
            'source_id' => $this->source->id,
            'category_id' => $this->category->id,
            'author_id' => $this->author->id,
            'title' => 'Test Article Title',
        ]);

        $response = $this->getJson("/api/v1/articles/{$article->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'title',
                    'excerpt',
                    'url',
                    'image_url',
                    'published_at',
                    'source' => ['id', 'name', 'slug'],
                    'category' => ['id', 'name', 'slug'],
                    'author' => ['id', 'name'],
                ],
            ])
            ->assertJson([
                'data' => [
                    'id' => $article->id,
                    'title' => 'Test Article Title',
                ],
            ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_404_for_non_existent_article()
    {
        $response = $this->getJson('/api/v1/articles/999');

        $response->assertStatus(404);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_search_articles_by_query()
    {
        Article::factory()->create([
            'title' => 'Laravel Framework News',
            'source_id' => $this->source->id,
        ]);

        Article::factory()->create([
            'title' => 'React Development Update',
            'excerpt' => 'Contains Laravel mention in excerpt',
            'source_id' => $this->source->id,
        ]);

        Article::factory()->create([
            'title' => 'Vue.js Tutorial',
            'source_id' => $this->source->id,
        ]);

        $response = $this->getJson('/api/v1/articles?q=Laravel');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_filter_articles_by_source()
    {
        $anotherSource = Source::factory()->create(['slug' => 'another-source']);

        Article::factory(3)->create(['source_id' => $this->source->id]);
        Article::factory(2)->create(['source_id' => $anotherSource->id]);

        $response = $this->getJson('/api/v1/articles?source=test-news');

        $response->assertStatus(200);
        $this->assertCount(3, $response->json('data'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_filter_articles_by_category()
    {
        $anotherCategory = Category::factory()->create(['slug' => 'sports']);

        Article::factory(2)->create([
            'source_id' => $this->source->id,
            'category_id' => $this->category->id,
        ]);

        Article::factory(3)->create([
            'source_id' => $this->source->id,
            'category_id' => $anotherCategory->id,
        ]);

        $response = $this->getJson('/api/v1/articles?category=technology');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_filter_articles_by_author()
    {
        $anotherAuthor = Author::factory()->create(['name' => 'Jane Smith']);

        Article::factory(2)->create([
            'source_id' => $this->source->id,
            'author_id' => $this->author->id,
        ]);

        Article::factory(1)->create([
            'source_id' => $this->source->id,
            'author_id' => $anotherAuthor->id,
        ]);

        // Test filtering by author ID
        $response = $this->getJson("/api/v1/articles?author={$this->author->id}");
        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));

        // Test filtering by author name
        $response = $this->getJson('/api/v1/articles?author=John');
        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_filter_articles_by_date_range()
    {
        Article::factory()->create([
            'source_id' => $this->source->id,
            'published_at' => '2024-01-15 10:00:00',
        ]);

        Article::factory()->create([
            'source_id' => $this->source->id,
            'published_at' => '2024-02-15 10:00:00',
        ]);

        Article::factory()->create([
            'source_id' => $this->source->id,
            'published_at' => '2024-03-15 10:00:00',
        ]);

        // Filter by date range
        $response = $this->getJson('/api/v1/articles?from=2024-01-01&to=2024-02-28');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_paginate_articles()
    {
        Article::factory(25)->create(['source_id' => $this->source->id]);

        $response = $this->getJson('/api/v1/articles?per_page=10');

        $response->assertStatus(200);
        $this->assertCount(10, $response->json('data'));

        // Check pagination meta
        $response->assertJsonStructure([
            'meta' => [
                'current_page',
                'per_page',
                'total',
                'last_page',
            ],
        ]);

        $this->assertEquals(10, $response->json('meta.per_page'));
        $this->assertEquals(25, $response->json('meta.total'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_combine_multiple_filters()
    {
        $anotherSource = Source::factory()->create(['slug' => 'another-source']);

        // Create articles that match multiple criteria
        Article::factory()->create([
            'title' => 'Laravel News Update',
            'source_id' => $this->source->id,
            'category_id' => $this->category->id,
            'published_at' => '2024-02-15 10:00:00',
        ]);

        // Create articles that don't match all criteria
        Article::factory()->create([
            'title' => 'Vue.js Update',
            'source_id' => $anotherSource->id,
            'category_id' => $this->category->id,
            'published_at' => '2024-02-15 10:00:00',
        ]);

        $response = $this->getJson('/api/v1/articles?q=Laravel&source=test-news&category=technology&from=2024-01-01&to=2024-12-31');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('Laravel News Update', $response->json('data.0.title'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_orders_articles_by_published_date_desc_by_default()
    {
        $older = Article::factory()->create([
            'source_id' => $this->source->id,
            'published_at' => '2024-01-15 10:00:00',
            'title' => 'Older Article',
        ]);

        $newer = Article::factory()->create([
            'source_id' => $this->source->id,
            'published_at' => '2024-02-15 10:00:00',
            'title' => 'Newer Article',
        ]);

        $response = $this->getJson('/api/v1/articles');

        $response->assertStatus(200);
        $articles = $response->json('data');

        $this->assertEquals('Newer Article', $articles[0]['title']);
        $this->assertEquals('Older Article', $articles[1]['title']);
    }
}
