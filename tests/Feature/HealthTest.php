<?php

namespace Tests\Feature;

use Tests\TestCase;

class HealthTest extends TestCase
{
    public function test_health_endpoint_returns_ok()
    {
        $response = $this->get('/api/v1/health');
        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'OK',
            'data' => ['status' => 'ok'],
        ]);
    }
}
