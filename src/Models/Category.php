<?php

namespace Vitebox\LaravelBlog\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Vitebox\LaravelBlog\Concerns\HasSlug;
use Vitebox\LaravelBlog\Concerns\UsesBlogTable;
use Vitebox\LaravelBlog\Facades\Blog;

class Category extends Model
{
    use HasSlug;
    use UsesBlogTable;

    protected string $blogTable = 'categories';

    protected $fillable = [
        'parent_id', 'name', 'slug', 'description', 'color', 'cover_image',
        'meta_title', 'meta_description', 'sort_order', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(static::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(static::class, 'parent_id')->orderBy('sort_order')->orderBy('name');
    }

    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class, Blog::table('category_post'), 'category_id', 'post_id');
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }

    public function scopeOrdered(Builder $q): Builder
    {
        return $q->orderBy('sort_order')->orderBy('name');
    }

    public function scopeRoots(Builder $q): Builder
    {
        return $q->whereNull('parent_id');
    }

    /** @return array<int, int> this category and every nested child id */
    public function descendantIds(): array
    {
        $all = static::query()->get(['id', 'parent_id'])->groupBy('parent_id');
        $ids = [$this->id];
        $stack = [$this->id];

        while ($stack) {
            $id = array_pop($stack);
            foreach ($all->get($id, collect()) as $child) {
                if (! in_array($child->id, $ids, true)) {
                    $ids[] = $child->id;
                    $stack[] = $child->id;
                }
            }
        }

        return $ids;
    }

    /** Would setting $parentId as parent create a cycle? */
    public function wouldCreateCycle(?int $parentId): bool
    {
        return $parentId !== null && $this->exists && in_array($parentId, $this->descendantIds(), true);
    }

    /**
     * Flat list ordered as a tree, each item with a "depth" attribute —
     * handy for <select> options and indented tables.
     *
     * @return Collection<int, static>
     */
    public static function tree(): Collection
    {
        $all = static::query()->withCount('posts')->ordered()->get();
        $byParent = $all->groupBy(fn ($c) => $c->parent_id ?? 0);
        $out = collect();

        $walk = function ($parentId, $depth) use (&$walk, $byParent, $out) {
            foreach ($byParent->get($parentId, collect()) as $cat) {
                $cat->setAttribute('depth', $depth);
                $out->push($cat);
                $walk($cat->id, $depth + 1);
            }
        };
        $walk(0, 0);

        return $out;
    }

    public function getPathAttribute(): string
    {
        $names = [$this->name];
        $node = $this;
        $guard = 0;
        while ($node->parent && $guard++ < 20) {
            $node = $node->parent;
            array_unshift($names, $node->name);
        }

        return implode(' › ', $names);
    }
}
