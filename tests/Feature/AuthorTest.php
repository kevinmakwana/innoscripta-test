<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Author;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AuthorTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_paginated_authors()
    {
        Author::factory()->count(30)->create();

        $response = $this->getJson('/api/v1/authors');

        $response->assertStatus(200);
        $response->assertJsonStructure(['data', 'links', 'meta']);
    }

    public function test_show_returns_author()
    {
        $author = Author::factory()->create();

        $response = $this->getJson("/api/v1/authors/{$author->id}");

        $response->assertStatus(200);
        // Resource is returned wrapped under `data` by Laravel's JsonResource
        $response->assertJson(['data' => ['id' => $author->id, 'name' => $author->name]]);
    }
}
