<?php
declare(strict_types=1);

namespace App\DTOs;

final class NormalizedArticle
{
    public readonly ?string $external_id;
    public readonly ?string $title;
    public readonly ?string $excerpt;
    public readonly ?string $body;
    public readonly ?string $url;
    public readonly ?string $image_url;
    public readonly ?string $published_at;
    /** @var array<string,mixed>|null */
    public readonly ?array $author;
    /** @var array<string,mixed>|null */
    public readonly ?array $category;
    /** @var array<string,mixed> */
    public readonly array $raw_json;

    /**
     * @param array<string,mixed>|null $author
     * @param array<string,mixed>|null $category
     * @param array<string,mixed> $raw_json
     */
    public function __construct(?string $external_id, ?string $title, ?string $excerpt, ?string $body, ?string $url, ?string $image_url, ?string $published_at, ?array $author = null, ?array $category = null, array $raw_json = [])
    {
        $this->external_id = $external_id;
        $this->title = $title;
        $this->excerpt = $excerpt;
        $this->body = $body;
        $this->url = $url;
        $this->image_url = $image_url;
        $this->published_at = $published_at;
        $this->author = $author;
        $this->category = $category;
        $this->raw_json = $raw_json;
    }

    /**
     * Convert DTO to array matching previous normalized shape.
     *
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return [
            'external_id' => $this->external_id,
            'title' => $this->title,
            'excerpt' => $this->excerpt,
            'body' => $this->body,
            'url' => $this->url,
            'image_url' => $this->image_url,
            'published_at' => $this->published_at,
            'author' => $this->author,
            'category' => $this->category,
            'raw_json' => $this->raw_json,
        ];
    }
}
