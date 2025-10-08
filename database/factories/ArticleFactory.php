<?php

namespace Database\Factories;

use App\Models\Article;
use App\Models\Source;
use Illuminate\Database\Eloquent\Factories\Factory;

class ArticleFactory extends Factory
{
    protected $model = Article::class;

    public function definition(): array
    {
        return [
            'source_id' => Source::factory(),
            'external_id' => $this->faker->uuid(),
            'title' => $this->faker->sentence(),
            'excerpt' => $this->faker->paragraph(),
            'body' => $this->faker->text(200),
            'url' => $this->faker->url(),
            'image_url' => $this->faker->imageUrl(),
            'published_at' => now(),
            'raw_json' => [],
        ];
    }
}
