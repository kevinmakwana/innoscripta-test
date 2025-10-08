<?php
declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ArticleResource extends JsonResource
{
    /**
     * @return array<string,mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'excerpt' => $this->excerpt,
            'url' => $this->url,
            'image_url' => $this->image_url,
            'published_at' => $this->published_at?->toIso8601String(),
            'source' => $this->source?->only(['id','name','slug']),
            'category' => $this->category?->only(['id','name','slug']),
            'author' => $this->author?->only(['id','name']),
        ];
    }
}
