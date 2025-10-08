<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class OpenApiSpecTest extends TestCase
{
    #[\PHPUnit\Framework\Attributes\Test]
    public function openapi_json_is_valid_json()
    {
        $path = base_path('docs/openapi.json');
        $this->assertTrue(File::exists($path), 'docs/openapi.json should exist');

        $json = File::get($path);
        json_decode($json);
        $this->assertEquals(JSON_ERROR_NONE, json_last_error(), 'openapi.json must be valid JSON: '.json_last_error_msg());
    }
}
