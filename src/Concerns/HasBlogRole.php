<?php

namespace Vitebox\LaravelBlog\Concerns;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Vitebox\LaravelBlog\Enums\BlogRole;
use Vitebox\LaravelBlog\Facades\Blog;
use Vitebox\LaravelBlog\Models\Post;
use Vitebox\LaravelBlog\Models\TeamMember;

/**
 * OPTIONAL convenience trait for your User model:
 *
 *   class User extends Authenticatable { use \Vitebox\LaravelBlog\Concerns\HasBlogRole; }
 *
 *   $user->blogRole();            // BlogRole|null
 *   $user->hasBlogPermission('posts.publish');
 *   $user->blogPosts()->published()->get();
 */
trait HasBlogRole
{
    public function blogMembership(): HasOne
    {
        return $this->hasOne(TeamMember::class, 'user_id');
    }

    public function blogPosts(): HasMany
    {
        return $this->hasMany(Post::class, 'author_id');
    }

    public function blogRole(): ?BlogRole
    {
        return Blog::roleOf($this);
    }

    public function hasBlogRole(BlogRole|string $role): bool
    {
        $role = $role instanceof BlogRole ? $role : BlogRole::tryFrom($role);

        return $role !== null && $this->blogRole() === $role;
    }

    public function hasBlogPermission(string $permission): bool
    {
        return Blog::can($this, $permission);
    }

    public function assignBlogRole(BlogRole|string $role): TeamMember
    {
        return Blog::assignRole($this, $role);
    }
}
