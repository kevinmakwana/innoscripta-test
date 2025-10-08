<?php

namespace Tests\Feature;

use App\Models\Author;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorEndpointsTest extends TestCase
{
    use RefreshDatabase;

    public function test_authors_index_and_show()
    {
        $a = Author::factory()->create(['name' => 'Alice']);
        $b = Author::factory()->create(['name' => 'Bob']);

        $resp = $this->getJson('/api/v1/authors');
        $resp->assertStatus(200)->assertJsonPath('data.0.name', 'Alice');

        $resp2 = $this->getJson('/api/v1/authors/'.$b->id);
        $resp2->assertStatus(200)->assertJsonPath('data.name', 'Bob');
    }
}
