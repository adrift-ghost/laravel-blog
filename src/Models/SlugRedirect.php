<?php

namespace Vitebox\LaravelBlog\Models;

use Illuminate\Database\Eloquent\Model;
use Vitebox\LaravelBlog\Concerns\UsesBlogTable;

class SlugRedirect extends Model
{
    use UsesBlogTable;

    protected string $blogTable = 'slug_redirects';

    protected $fillable = ['model_type', 'model_id', 'old_slug'];

    public static function remember(string $type, mixed $id, string $oldSlug): void
    {
        static::query()->updateOrCreate(
            ['model_type' => $type, 'old_slug' => $oldSlug],
            ['model_id' => $id],
        );
    }
}
