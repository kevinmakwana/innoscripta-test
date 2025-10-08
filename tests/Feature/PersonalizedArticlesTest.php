<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\UserPreference;
use App\Models\Article;
use App\Models\Source;
use App\Models\Category;
use App\Models\Author;
use Laravel\Sanctum\Sanctum;

class PersonalizedArticlesTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Source $source1;
    protected Source $source2;
    protected Category $category1;
    protected Category $category2;
    protected Author $author1;
    protected Author $author2;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        
        $this->source1 = Source::factory()->create(['slug' => 'tech-news']);
        $this->source2 = Source::factory()->create(['slug' => 'sports-news']);
        
        $this->category1 = Category::factory()->create(['slug' => 'technology']);
        $this->category2 = Category::factory()->create(['slug' => 'sports']);
        
        $this->author1 = Author::factory()->create(['name' => 'Tech Writer']);
        $this->author2 = Author::factory()->create(['name' => 'Sports Writer']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function unauthenticated_user_cannot_access_personalized_articles()
    {
        $response = $this->getJson('/api/v1/articles/personalized');
        
        $response->assertStatus(401);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function authenticated_user_without_preferences_gets_all_articles()
    {
        Sanctum::actingAs($this->user);
        
        // Create some articles
        Article::factory(3)->create(['source_id' => $this->source1->id]);
        Article::factory(2)->create(['source_id' => $this->source2->id]);

        $response = $this->getJson('/api/v1/articles/personalized');

        $response->assertStatus(200);
        $this->assertCount(5, $response->json('data'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_filters_articles_by_preferred_sources()
    {
        Sanctum::actingAs($this->user);
        
        // Set user preferences for specific sources
        UserPreference::create([
            'user_id' => $this->user->id,
            'sources' => ['tech-news'],
            'categories' => [],
            'authors' => []
        ]);
        
        // Create articles from different sources
        Article::factory(3)->create(['source_id' => $this->source1->id]); // tech-news
        Article::factory(2)->create(['source_id' => $this->source2->id]); // sports-news

        $response = $this->getJson('/api/v1/articles/personalized');

        $response->assertStatus(200);
        $this->assertCount(3, $response->json('data'));
        
        // Verify all returned articles are from preferred source
        foreach ($response->json('data') as $article) {
            $this->assertEquals('tech-news', $article['source']['slug']);
        }
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_filters_articles_by_preferred_categories()
    {
        Sanctum::actingAs($this->user);
        
        // Set user preferences for specific categories
        UserPreference::create([
            'user_id' => $this->user->id,
            'sources' => [],
            'categories' => ['technology'],
            'authors' => []
        ]);
        
        // Create articles with different categories
        Article::factory(2)->create([
            'source_id' => $this->source1->id,
            'category_id' => $this->category1->id
        ]); // technology
        
        Article::factory(3)->create([
            'source_id' => $this->source1->id,
            'category_id' => $this->category2->id
        ]); // sports

        $response = $this->getJson('/api/v1/articles/personalized');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
        
        // Verify all returned articles are from preferred category
        foreach ($response->json('data') as $article) {
            $this->assertEquals('technology', $article['category']['slug']);
        }
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_filters_articles_by_preferred_authors()
    {
        Sanctum::actingAs($this->user);
        
        // Set user preferences for specific authors
        UserPreference::create([
            'user_id' => $this->user->id,
            'sources' => [],
            'categories' => [],
            'authors' => [$this->author1->id]
        ]);
        
        // Create articles by different authors
        Article::factory(2)->create([
            'source_id' => $this->source1->id,
            'author_id' => $this->author1->id
        ]); // Tech Writer
        
        Article::factory(3)->create([
            'source_id' => $this->source1->id,
            'author_id' => $this->author2->id
        ]); // Sports Writer

        $response = $this->getJson('/api/v1/articles/personalized');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
        
        // Verify all returned articles are by preferred author
        foreach ($response->json('data') as $article) {
            $this->assertEquals($this->author1->id, $article['author']['id']);
        }
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_combines_multiple_preference_types()
    {
        Sanctum::actingAs($this->user);
        
        // Set user preferences for sources, categories, and authors
        UserPreference::create([
            'user_id' => $this->user->id,
            'sources' => ['tech-news'],
            'categories' => ['sports'], // Different from source preference
            'authors' => [$this->author1->id]
        ]);
        
        // Create articles matching different preference criteria
        $matchingSource = Article::factory()->create([
            'source_id' => $this->source1->id, // tech-news (matches source preference)
            'category_id' => $this->category1->id,
            'author_id' => $this->author2->id
        ]);
        
        $matchingCategory = Article::factory()->create([
            'source_id' => $this->source2->id,
            'category_id' => $this->category2->id, // sports (matches category preference)
            'author_id' => $this->author2->id
        ]);
        
        $matchingAuthor = Article::factory()->create([
            'source_id' => $this->source2->id,
            'category_id' => $this->category1->id,
            'author_id' => $this->author1->id // matches author preference
        ]);
        
        // Create article matching none
        $notMatching = Article::factory()->create([
            'source_id' => $this->source2->id,
            'category_id' => $this->category1->id,
            'author_id' => $this->author2->id
        ]);

        $response = $this->getJson('/api/v1/articles/personalized');

        $response->assertStatus(200);
        $this->assertCount(3, $response->json('data')); // Should get articles matching any preference
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_allows_additional_filtering_on_personalized_results()
    {
        Sanctum::actingAs($this->user);
        
        // Set user preferences
        UserPreference::create([
            'user_id' => $this->user->id,
            'sources' => ['tech-news'],
            'categories' => [],
            'authors' => []
        ]);
        
        // Create articles with different titles
        Article::factory()->create([
            'title' => 'Laravel Framework Update',
            'source_id' => $this->source1->id,
            'published_at' => '2024-01-15 10:00:00'
        ]);
        
        Article::factory()->create([
            'title' => 'React Development Guide',
            'source_id' => $this->source1->id,
            'published_at' => '2024-02-15 10:00:00'
        ]);

        // Test search filtering on personalized results
        $response = $this->getJson('/api/v1/articles/personalized?q=Laravel');
        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        
        // Test date filtering on personalized results
        $response = $this->getJson('/api/v1/articles/personalized?from=2024-02-01');
        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_paginates_personalized_articles()
    {
        Sanctum::actingAs($this->user);
        
        UserPreference::create([
            'user_id' => $this->user->id,
            'sources' => ['tech-news'],
            'categories' => [],
            'authors' => []
        ]);
        
        // Create many articles
        Article::factory(25)->create(['source_id' => $this->source1->id]);

        $response = $this->getJson('/api/v1/articles/personalized?per_page=10');

        $response->assertStatus(200);
        $this->assertCount(10, $response->json('data'));
        
        // Check pagination meta
        $response->assertJsonStructure([
            'meta' => [
                'current_page',
                'per_page',
                'total',
                'last_page'
            ]
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_orders_personalized_articles_by_published_date_desc()
    {
        Sanctum::actingAs($this->user);
        
        UserPreference::create([
            'user_id' => $this->user->id,
            'sources' => ['tech-news'],
            'categories' => [],
            'authors' => []
        ]);
        
        $older = Article::factory()->create([
            'source_id' => $this->source1->id,
            'published_at' => '2024-01-15 10:00:00',
            'title' => 'Older Article'
        ]);

        $newer = Article::factory()->create([
            'source_id' => $this->source1->id,
            'published_at' => '2024-02-15 10:00:00',
            'title' => 'Newer Article'
        ]);

        $response = $this->getJson('/api/v1/articles/personalized');

        $response->assertStatus(200);
        $articles = $response->json('data');
        
        $this->assertEquals('Newer Article', $articles[0]['title']);
        $this->assertEquals('Older Article', $articles[1]['title']);
    }
}