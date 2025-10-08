<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Source;

class SourceFactory extends Factory
{
    protected $model = Source::class;

    public function definition(): array
    {
        $name = $this->faker->company();
        $slug = Str::slug($name) ?: 'source-' . Str::random(6);

        return [
            'name' => $name,
            'slug' => $slug,
            'base_url' => 'https://example.com',
            'api_key_env' => null,
            'enabled' => true,
        ];
    }
}
