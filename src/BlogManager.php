<?php

namespace Vitebox\LaravelBlog;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Vitebox\LaravelBlog\Enums\BlogRole;
use Vitebox\LaravelBlog\Models\TeamMember;
use Vitebox\LaravelBlog\PostTypes\PostTypeRegistry;

class BlogManager
{
    /** @var array<string, BlogRole|null> */
    protected array $roleCache = [];

    public function __construct(protected PostTypeRegistry $types)
    {
    }

    public function types(): PostTypeRegistry
    {
        return $this->types;
    }

    public function table(string $name): string
    {
        return config('blog.table_prefix', 'blog_').$name;
    }

    /** @return class-string<Model> */
    public function userModel(): string
    {
        return config('blog.user_model', 'App\\Models\\User');
    }

    public function userName(mixed $user): string
    {
        if (! $user) {
            return 'Unknown';
        }

        return (string) (data_get($user, config('blog.user_name_column', 'name'))
            ?? data_get($user, config('blog.user_email_column', 'email'))
            ?? '#'.$user->getKey());
    }

    public function findUserByEmail(string $email): ?Model
    {
        $model = $this->userModel();

        return $model::query()->where(config('blog.user_email_column', 'email'), $email)->first();
    }

    public function isSuperAdmin(?Authenticatable $user): bool
    {
        if (! $user) {
            return false;
        }

        $email = data_get($user, config('blog.user_email_column', 'email'));
        $list = array_map('strtolower', (array) config('blog.super_admins', []));

        return $email && in_array(strtolower((string) $email), $list, true);
    }

    public function roleOf(?Authenticatable $user): ?BlogRole
    {
        if (! $user) {
            return null;
        }

        $key = (string) $user->getAuthIdentifier();

        if (array_key_exists($key, $this->roleCache)) {
            return $this->roleCache[$key];
        }

        if ($this->isSuperAdmin($user)) {
            return $this->roleCache[$key] = BlogRole::Admin;
        }

        $member = TeamMember::query()
            ->where('user_id', $user->getAuthIdentifier())
            ->where('is_active', true)
            ->first();

        return $this->roleCache[$key] = $member?->role;
    }

    public function hasAccess(?Authenticatable $user): bool
    {
        return $this->roleOf($user) !== null;
    }

    public function can(?Authenticatable $user, string $permission): bool
    {
        return (bool) $this->roleOf($user)?->allows($permission);
    }

    public function assignRole(Model|Authenticatable $user, BlogRole|string $role): TeamMember
    {
        $role = $role instanceof BlogRole ? $role : BlogRole::from($role);

        $member = TeamMember::query()->updateOrCreate(
            ['user_id' => $user->getKey()],
            ['role' => $role, 'is_active' => true],
        );

        $this->flushRoleCache();

        return $member;
    }

    public function removeRole(Model|Authenticatable $user): void
    {
        TeamMember::query()->where('user_id', $user->getKey())->delete();
        $this->flushRoleCache();
    }

    public function flushRoleCache(): void
    {
        $this->roleCache = [];
    }
}
