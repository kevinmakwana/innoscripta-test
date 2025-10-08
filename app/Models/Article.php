<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Article model representing a normalized article stored locally.
 *
 * @property int $id
 * @property int $source_id
 * @property string|null $external_id
 * @property string $title
 * @property string|null $excerpt
 * @property string|null $body
 * @property string|null $url
 * @property string|null $image_url
 * @property \Illuminate\Support\Carbon|null $published_at
 * @property array<string,mixed>|null $raw_json
 *
 * @use \Illuminate\Database\Eloquent\Factories\HasFactory<\Database\Factories\ArticleFactory>
 * @method static \Illuminate\Database\Eloquent\Builder<\App\Models\Article> search(?string $q)
 * @mixin \Illuminate\Database\Eloquent\Builder<\App\Models\Article>
 *
 * Note: callers may use Article::search($q) (static invocation) rather than
 * Article::query()->search($q) to help static analyzers (phpstan) resolve
 * the scope method and avoid false-positive "method not found" warnings.
 */
class Article extends Model
{
    use HasFactory;

    protected $fillable = [
        'source_id', 'external_id', 'title', 'excerpt', 'body', 'url', 'image_url', 'published_at', 'category_id', 'author_id', 'raw_json'
    ];

    /**
     * @var array<string,string>
     */
    protected $casts = [
        'published_at' => 'datetime',
        'raw_json' => 'array',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function source(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Source::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function category(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function author(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Author::class);
    }

    /**
     * Scope a query to search title and excerpt using LIKE. This centralizes
     * current behavior and makes switching to full-text later easier.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string|null $q
     * @return \Illuminate\Database\Eloquent\Builder
     */
    /**
     * @param \Illuminate\Database\Eloquent\Builder<\App\Models\Article> $query
     * @param string|null $q
     * @return \Illuminate\Database\Eloquent\Builder<\App\Models\Article>
     */
    public function scopeSearch(\Illuminate\Database\Eloquent\Builder $query, ?string $q): \Illuminate\Database\Eloquent\Builder
    {
        if ($q === null || $q === '') {
            return $query;
        }

        $like = '%'. $q . '%';
        return $query->where(function ($sub) use ($like) {
            $sub->where('title', 'like', $like)
                ->orWhere('excerpt', 'like', $like);
        });
    }
}
