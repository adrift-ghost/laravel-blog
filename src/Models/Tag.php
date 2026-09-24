<?php

namespace Vitebox\LaravelBlog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Vitebox\LaravelBlog\Concerns\HasSlug;
use Vitebox\LaravelBlog\Concerns\UsesBlogTable;
use Vitebox\LaravelBlog\Facades\Blog;

class Tag extends Model
{
    use HasSlug;
    use UsesBlogTable;

    protected string $blogTable = 'tags';

    protected $fillable = ['name', 'slug', 'description'];

    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class, Blog::table('post_tag'), 'tag_id', 'post_id');
    }

    /**
     * Resolve a list of tag names (or a comma separated string) into ids,
     * creating missing tags when $create is true.
     *
     * @param  string|array<int, string|int>  $input
     * @return Collection<int, int>
     */
    public static function resolveIds(string|array $input, bool $create = true): Collection
    {
        $items = is_array($input) ? $input : explode(',', $input);

        return collect($items)
            ->map(fn ($v) => is_string($v) ? trim($v) : $v)
            ->filter(fn ($v) => $v !== '' && $v !== null)
            ->unique(fn ($v) => is_string($v) ? Str::lower($v) : $v)
            ->map(function ($value) use ($create) {
                if (is_int($value) || ctype_digit((string) $value)) {
                    return static::query()->whereKey((int) $value)->value('id');
                }

                $existing = static::query()
                    ->whereRaw('LOWER(name) = ?', [Str::lower($value)])
                    ->orWhere('slug', Str::slug($value))
                    ->first();

                if ($existing) {
                    return $existing->id;
                }

                return $create ? static::query()->create(['name' => Str::limit($value, 60, '')])->id : null;
            })
            ->filter()
            ->values();
    }
}
