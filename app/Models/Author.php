<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Author model.
 *
 * @property int $id
 * @property string $name
 * @property string|null $external_id
 *
 * @use \Illuminate\Database\Eloquent\Factories\HasFactory<\Database\Factories\AuthorFactory>
 *
 * @method \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Article, \App\Models\Author> articles()
 */
class Author extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'external_id'];

    public function articles(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Article::class);
    }
}
