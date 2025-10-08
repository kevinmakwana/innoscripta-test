<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Category;
use App\Models\Source;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CategoryEndpointsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Source $source;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->source = Source::factory()->create();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_list_all_categories()
    {
        Category::factory(5)->create();

        $response = $this->getJson('/api/v1/categories');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'slug',
                        'articles_count',
                        'created_at',
                        'updated_at',
                    ],
                ],
            ]);

        $this->assertCount(5, $response->json('data'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_list_categories_with_pagination()
    {
        Category::factory(25)->create();

        $response = $this->getJson('/api/v1/categories?per_page=10');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'links',
                'meta' => [
                    'current_page',
                    'per_page',
                    'total',
                    'last_page',
                ],
            ]);

        $this->assertCount(10, $response->json('data'));
        $this->assertEquals(25, $response->json('meta.total'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_search_categories_by_name()
    {
        Category::factory()->create(['name' => 'Technology News']);
        Category::factory()->create(['name' => 'Sports Update']);
        Category::factory()->create(['name' => 'Tech Reviews']);

        $response = $this->getJson('/api/v1/categories?search=tech');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_show_single_category()
    {
        $category = Category::factory()->create([
            'name' => 'Technology',
            'slug' => 'technology',
        ]);

        $response = $this->getJson("/api/v1/categories/{$category->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'slug',
                    'articles_count',
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJson([
                'data' => [
                    'id' => $category->id,
                    'name' => 'Technology',
                    'slug' => 'technology',
                ],
            ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_404_for_non_existent_category()
    {
        $response = $this->getJson('/api/v1/categories/999');

        $response->assertStatus(404);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function authenticated_user_can_create_category()
    {
        Sanctum::actingAs($this->user);

        $categoryData = [
            'name' => 'New Technology',
        ];

        $response = $this->postJson('/api/v1/categories', $categoryData);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'name' => 'New Technology',
                    'slug' => 'new-technology',
                ],
            ]);

        $this->assertDatabaseHas('categories', [
            'name' => 'New Technology',
            'slug' => 'new-technology',
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_auto_generates_unique_slug_when_creating_category()
    {
        Sanctum::actingAs($this->user);

        // Create first category
        Category::create(['name' => 'Technology News', 'slug' => 'technology-news']);

        // Create second category with name that would generate same slug
        $response = $this->postJson('/api/v1/categories', [
            'name' => 'Technology-News!',  // This should generate 'technology-news' but will be made unique
        ]);

        $response->assertStatus(201);

        // Should create with incremented slug since technology-news already exists
        $createdCategory = Category::where('name', 'Technology-News!')->first();
        $this->assertEquals('technology-news-1', $createdCategory->slug);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_validates_category_creation_data()
    {
        Sanctum::actingAs($this->user);

        // Test missing name
        $response = $this->postJson('/api/v1/categories', []);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);

        // Test duplicate name
        Category::factory()->create(['name' => 'Existing Category']);
        $response = $this->postJson('/api/v1/categories', ['name' => 'Existing Category']);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function unauthenticated_user_cannot_create_category()
    {
        $response = $this->postJson('/api/v1/categories', ['name' => 'Test Category']);

        $response->assertStatus(401);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function authenticated_user_can_update_category()
    {
        Sanctum::actingAs($this->user);

        $category = Category::factory()->create([
            'name' => 'Old Name',
            'slug' => 'old-name',
        ]);

        $updateData = [
            'name' => 'New Name',
        ];

        $response = $this->putJson("/api/v1/categories/{$category->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'name' => 'New Name',
                    'slug' => 'new-name', // should auto-update slug
                ],
            ]);

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'New Name',
            'slug' => 'new-name',
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function authenticated_user_can_delete_empty_category()
    {
        Sanctum::actingAs($this->user);

        $category = Category::factory()->create();

        $response = $this->deleteJson("/api/v1/categories/{$category->id}");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Category deleted successfully',
            ]);

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function cannot_delete_category_with_articles()
    {
        Sanctum::actingAs($this->user);

        $category = Category::factory()->create();

        // Create articles in this category
        Article::factory(2)->create([
            'source_id' => $this->source->id,
            'category_id' => $category->id,
        ]);

        $response = $this->deleteJson("/api/v1/categories/{$category->id}");

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Cannot delete category that has associated articles',
                'data' => ['articles_count' => 2],
            ]);

        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_get_articles_for_category()
    {
        $category = Category::factory()->create();

        // Create articles in this category
        Article::factory(3)->create([
            'source_id' => $this->source->id,
            'category_id' => $category->id,
        ]);

        // Create articles in other categories
        Article::factory(2)->create([
            'source_id' => $this->source->id,
            'category_id' => Category::factory()->create()->id,
        ]);

        $response = $this->getJson("/api/v1/categories/{$category->id}/articles");

        $response->assertStatus(200);
        $this->assertCount(3, $response->json('data'));

        // Verify all articles belong to the correct category
        foreach ($response->json('data') as $article) {
            $this->assertEquals($category->id, $article['category']['id']);
        }
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_search_articles_within_category()
    {
        $category = Category::factory()->create();

        Article::factory()->create([
            'title' => 'Laravel Framework Update',
            'source_id' => $this->source->id,
            'category_id' => $category->id,
        ]);

        Article::factory()->create([
            'title' => 'React Development Guide',
            'source_id' => $this->source->id,
            'category_id' => $category->id,
        ]);

        $response = $this->getJson("/api/v1/categories/{$category->id}/articles?q=Laravel");

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertStringContainsString('Laravel', $response->json('data.0.title'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_filter_category_articles_by_date()
    {
        $category = Category::factory()->create();

        Article::factory()->create([
            'source_id' => $this->source->id,
            'category_id' => $category->id,
            'published_at' => '2024-01-15 10:00:00',
        ]);

        Article::factory()->create([
            'source_id' => $this->source->id,
            'category_id' => $category->id,
            'published_at' => '2024-03-15 10:00:00',
        ]);

        $response = $this->getJson("/api/v1/categories/{$category->id}/articles?from=2024-02-01&to=2024-12-31");

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function categories_are_ordered_by_name()
    {
        Category::create(['name' => 'Zebra Category', 'slug' => 'zebra-category']);
        Category::create(['name' => 'Alpha Category', 'slug' => 'alpha-category']);
        Category::create(['name' => 'Beta Category', 'slug' => 'beta-category']);

        $response = $this->getJson('/api/v1/categories');

        $response->assertStatus(200);
        $categories = $response->json('data');

        $this->assertEquals('Alpha Category', $categories[0]['name']);
        $this->assertEquals('Beta Category', $categories[1]['name']);
        $this->assertEquals('Zebra Category', $categories[2]['name']);
    }
}
