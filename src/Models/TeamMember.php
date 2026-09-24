<?php

namespace Vitebox\LaravelBlog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Vitebox\LaravelBlog\Concerns\UsesBlogTable;
use Vitebox\LaravelBlog\Enums\BlogRole;
use Vitebox\LaravelBlog\Facades\Blog;

/**
 * Maps a host-application user to a blog role.
 *
 * @property BlogRole $role
 */
class TeamMember extends Model
{
    use UsesBlogTable;

    protected string $blogTable = 'team_members';

    protected $fillable = ['user_id', 'role', 'is_active', 'display_name', 'bio', 'avatar'];

    protected $casts = [
        'role' => BlogRole::class,
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(Blog::userModel(), 'user_id');
    }
}
