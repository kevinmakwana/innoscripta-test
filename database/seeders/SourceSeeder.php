<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\Source;

class SourceSeeder extends Seeder
{
    public function run(): void
    {
        $sources = [
            ['name' => 'NewsAPI', 'slug' => 'newsapi', 'base_url' => 'https://newsapi.org', 'api_key_env' => 'NEWSAPI_KEY'],
            ['name' => 'The Guardian', 'slug' => 'theguardian', 'base_url' => 'https://content.guardianapis.com', 'api_key_env' => 'GUARDIAN_API_KEY'],
            ['name' => 'New York Times', 'slug' => 'nytimes', 'base_url' => 'https://api.nytimes.com', 'api_key_env' => 'NYT_API_KEY'],
        ];

        foreach ($sources as $s) {
            Source::updateOrCreate(['slug' => $s['slug']], $s);
        }
    }
}
