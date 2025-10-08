<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserPreferenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_crud_preferences()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $headers = ['Authorization' => 'Bearer '.$token];

        // Create
        $payload = ['sources' => [1, 2], 'categories' => ['tech'], 'authors' => ['john']];
        $res = $this->withHeaders($headers)->postJson('/api/v1/preferences', $payload);
        $res->assertStatus(201)->assertJsonFragment(['sources' => [1, 2]]);

        // Read
        $res = $this->withHeaders($headers)->getJson('/api/v1/preferences');
        $res->assertStatus(200)->assertJsonFragment(['categories' => ['tech']]);

        // Update
        $res = $this->withHeaders($headers)->putJson('/api/v1/preferences', ['sources' => [3]]);
        $res->assertStatus(200)->assertJsonFragment(['sources' => [3]]);

        // Delete
        $res = $this->withHeaders($headers)->deleteJson('/api/v1/preferences');
        $res->assertStatus(204);
    }
}
