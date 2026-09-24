<?php

namespace Vitebox\LaravelBlog\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Vitebox\LaravelBlog\Concerns\HasSlug;
use Vitebox\LaravelBlog\Concerns\UsesBlogTable;
use Vitebox\LaravelBlog\Enums\PostStatus;
use Vitebox\LaravelBlog\Facades\Blog;
use Vitebox\LaravelBlog\PostTypes\PostType;
use Vitebox\LaravelBlog\Support\VideoEmbed;

/**
 * @property int $id
 * @property string $type
 * @property string $title
 * @property string $slug
 * @property ?string $excerpt
 * @property ?string $content
 * @property PostStatus $status
 * @property ?\Illuminate\Support\Carbon $published_at
 * @property ?\Illuminate\Support\Carbon $starts_at
 * @property ?\Illuminate\Support\Carbon $ends_at
 * @property ?\Illuminate\Support\Carbon $expires_at
 * @property array|null $type_data
 */
class Post extends Model
{
    use HasSlug;
    use SoftDeletes;
    use UsesBlogTable;

    protected string $blogTable = 'posts';

    protected $guarded = ['id', 'views', 'status', 'reviewer_id', 'publisher_id', 'submitted_at', 'reviewed_at'];

    protected $attributes = [
        'status' => 'draft',
        'is_featured' => false,
        'is_pinned' => false,
        'views' => 0,
    ];

    protected $casts = [
        'status' => PostStatus::class,
        'type_data' => 'array',
        'published_at' => 'datetime',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_featured' => 'boolean',
        'is_pinned' => 'boolean',
        'views' => 'integer',
        'reading_time' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (Post $post) {
            $words = str_word_count(strip_tags((string) $post->content));
            $post->reading_time = max(1, (int) ceil($words / max(1, (int) config('blog.content.words_per_minute', 200))));
        });
    }

    public function slugSourceColumn(): string
    {
        return 'title';
    }

    public function shouldKeepSlugRedirect(): bool
    {
        // Only public URLs need to keep working.
        return $this->getOriginal('published_at') !== null;
    }

    /* ----------------------------------------------------------------- */
    /* Relations                                                          */
    /* ----------------------------------------------------------------- */

    public function author(): BelongsTo
    {
        return $this->belongsTo(Blog::userModel(), 'author_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(Blog::userModel(), 'reviewer_id');
    }

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(Blog::userModel(), 'publisher_id');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, Blog::table('category_post'), 'post_id', 'category_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, Blog::table('post_tag'), 'post_id', 'tag_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(PostActivity::class, 'post_id')->latest('id');
    }

    /* ----------------------------------------------------------------- */
    /* Scopes                                                             */
    /* ----------------------------------------------------------------- */

    /** Live on the website: published and publish date reached. */
    public function scopePublished(Builder $q): Builder
    {
        return $q->where('status', PostStatus::Published->value)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function scopeStatus(Builder $q, PostStatus|string $status): Builder
    {
        return $q->where('status', $status instanceof PostStatus ? $status->value : $status);
    }

    public function scopeOfType(Builder $q, string|array $type): Builder
    {
        return $q->whereIn('type', (array) $type);
    }

    public function scopeInCategory(Builder $q, Category|string|int $category, bool $includeChildren = true): Builder
    {
        $cat = $category instanceof Category ? $category
            : Category::query()->where(is_numeric($category) ? 'id' : 'slug', $category)->first();

        if (! $cat) {
            return $q->whereRaw('1 = 0');
        }

        $ids = $includeChildren ? $cat->descendantIds() : [$cat->id];

        return $q->whereHas('categories', fn ($c) => $c->whereIn($c->getModel()->getTable().'.id', $ids));
    }

    public function scopeWithTag(Builder $q, Tag|string $tag): Builder
    {
        $slug = $tag instanceof Tag ? $tag->slug : $tag;

        return $q->whereHas('tags', fn ($t) => $t->where('slug', $slug));
    }

    public function scopeSearch(Builder $q, ?string $term): Builder
    {
        $term = trim((string) $term);
        if ($term === '') {
            return $q;
        }

        return $q->where(fn ($w) => $w->where('title', 'like', "%{$term}%")
            ->orWhere('excerpt', 'like', "%{$term}%")
            ->orWhere('content', 'like', "%{$term}%"));
    }

    public function scopeFeatured(Builder $q): Builder
    {
        return $q->where('is_featured', true);
    }

    /** Pinned first, then newest. */
    public function scopeLatestFirst(Builder $q): Builder
    {
        return $q->orderByDesc('is_pinned')->orderByDesc('published_at')->orderByDesc('id');
    }

    public function scopeUpcomingEvents(Builder $q): Builder
    {
        return $q->where('type', 'event')
            ->where(fn ($w) => $w->where('starts_at', '>=', now())->orWhere('ends_at', '>=', now()))
            ->orderBy('starts_at');
    }

    public function scopePastEvents(Builder $q): Builder
    {
        return $q->where('type', 'event')
            ->where('starts_at', '<', now())
            ->where(fn ($w) => $w->whereNull('ends_at')->orWhere('ends_at', '<', now()))
            ->orderByDesc('starts_at');
    }

    public function scopeActiveAnnouncements(Builder $q): Builder
    {
        return $q->where('type', 'announcement')
            ->where(fn ($w) => $w->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }

    /* ----------------------------------------------------------------- */
    /* Helpers                                                            */
    /* ----------------------------------------------------------------- */

    public function postType(): PostType
    {
        $types = Blog::types();

        return $types->has($this->type) ? $types->get($this->type) : $types->get(config('blog.default_post_type', 'standard'));
    }

    /** Value of a type-specific field (column-backed or JSON). */
    public function field(string $name, mixed $default = null): mixed
    {
        foreach ($this->postType()->fields() as $f) {
            if ($f->name === $name && $f->column) {
                return $this->getAttribute($f->column) ?? $default;
            }
        }

        return data_get($this->type_data, $name, $default);
    }

    public function isPublished(): bool
    {
        return $this->status === PostStatus::Published && $this->published_at && $this->published_at->lte(now());
    }

    public function isOwnedBy(mixed $user): bool
    {
        return $user && (string) $this->author_id === (string) $user->getAuthIdentifier();
    }

    public function getCoverImageUrlAttribute(): ?string
    {
        return static::mediaUrl($this->cover_image);
    }

    public function getSummaryAttribute(): string
    {
        if (filled($this->excerpt)) {
            return (string) $this->excerpt;
        }

        $text = trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags((string) $this->content))) ?? '');

        return Str::limit($text, (int) config('blog.content.excerpt_length', 200));
    }

    /** @return array{provider:string,id:?string,url:string,embed_url:?string,thumbnail:?string}|null */
    public function getVideoAttribute(): ?array
    {
        if ($this->video_path) {
            return ['provider' => 'upload', 'id' => null, 'url' => static::mediaUrl($this->video_path), 'embed_url' => null, 'thumbnail' => null];
        }

        return VideoEmbed::parse($this->video_url);
    }

    public static function mediaUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (preg_match('~^https?://~i', $path)) {
            return $path;
        }

        return Storage::disk(config('blog.media.disk', 'public'))->url($path);
    }
}
