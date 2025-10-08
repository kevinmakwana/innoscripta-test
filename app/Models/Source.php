<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Source model.
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $base_url
 * @property string|null $api_key_env
 * @property bool $enabled
 *
 * @use \Illuminate\Database\Eloquent\Factories\HasFactory<\Database\Factories\SourceFactory>
 * @method \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Article, \App\Models\Source> articles()
 */
class Source extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'base_url', 'api_key_env', 'enabled', 'adapter_class',
    ];

    /**
     * @var array<string,string>
     */
    protected $casts = [
        'enabled' => 'boolean',
        'failure_count' => 'integer',
        'last_failed_at' => 'datetime',
        'disabled_at' => 'datetime',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function articles(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Article::class);
    }

    /**
     * Increment failure_count and set last_failed_at timestamp.
     */
    public function markFailure(): void
    {
        $this->failure_count = ($this->failure_count ?? 0) + 1;
        $this->last_failed_at = now();
        $this->save();
    }

    /**
     * Reset failure_count after a successful run.
     */
    public function resetFailures(): void
    {
        $this->failure_count = 0;
        $this->last_failed_at = null;
        $this->disabled_at = null;
        $this->save();
    }

    /**
     * Disable the source and mark disabled_at.
     */
    public function disable(string $reason = ''): void
    {
        $this->enabled = false;
        $this->disabled_at = now();
        $this->save();
    }

    /**
     * Whether source is disabled (either explicit flag or disabled_at set).
     */
    public function isDisabled(): bool
    {
        return ! $this->enabled || $this->disabled_at !== null;
    }
}
