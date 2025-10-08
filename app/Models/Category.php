<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Category model.
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 *
 * @use \Illuminate\Database\Eloquent\Factories\HasFactory<\Database\Factories\CategoryFactory>
 *
 * @method \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Article, \App\Models\Category> articles()
 */
class Category extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug'];

    public function articles(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Article::class);
    }
}
