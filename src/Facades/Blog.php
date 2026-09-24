<?php

namespace Vitebox\LaravelBlog\Facades;

use Illuminate\Support\Facades\Facade;
use Vitebox\LaravelBlog\BlogManager;

/**
 * @method static \Vitebox\LaravelBlog\PostTypes\PostTypeRegistry types()
 * @method static string table(string $name)
 * @method static string userModel()
 * @method static string userName(mixed $user)
 * @method static \Vitebox\LaravelBlog\Enums\BlogRole|null roleOf(?\Illuminate\Contracts\Auth\Authenticatable $user)
 * @method static bool hasAccess(?\Illuminate\Contracts\Auth\Authenticatable $user)
 * @method static bool can(?\Illuminate\Contracts\Auth\Authenticatable $user, string $permission)
 * @method static \Vitebox\LaravelBlog\Models\TeamMember assignRole($user, \Vitebox\LaravelBlog\Enums\BlogRole|string $role)
 * @method static void removeRole($user)
 *
 * @see BlogManager
 */
class Blog extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return BlogManager::class;
    }
}
