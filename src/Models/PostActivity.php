<?php

namespace Vitebox\LaravelBlog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Vitebox\LaravelBlog\Concerns\UsesBlogTable;
use Vitebox\LaravelBlog\Enums\PostStatus;
use Vitebox\LaravelBlog\Facades\Blog;

/**
 * Audit trail of a post: created, edited, submitted, reviewed, published...
 */
class PostActivity extends Model
{
    use UsesBlogTable;

    public const UPDATED_AT = null;

    protected string $blogTable = 'post_activities';

    protected $fillable = ['post_id', 'user_id', 'action', 'from_status', 'to_status', 'comment'];

    protected $casts = [
        'from_status' => PostStatus::class,
        'to_status' => PostStatus::class,
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'post_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(Blog::userModel(), 'user_id');
    }

    public function getLabelAttribute(): string
    {
        return match ($this->action) {
            'created' => 'created the post',
            'updated' => 'edited the post',
            'submitted' => 'submitted for review',
            'approved' => 'approved the post',
            'changes_requested' => 'requested changes',
            'published' => 'published the post',
            'scheduled' => 'scheduled the post',
            'unpublished' => 'unpublished the post',
            'archived' => 'archived the post',
            'restored' => 'restored the post to draft',
            'featured' => 'featured the post',
            'unfeatured' => 'removed from featured',
            'pinned' => 'pinned the post',
            'unpinned' => 'unpinned the post',
            default => str_replace('_', ' ', $this->action),
        };
    }
}
