<?php

namespace Vitebox\LaravelBlog\Tests\Fixtures;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Vitebox\LaravelBlog\Concerns\HasBlogRole;

class User extends Authenticatable
{
    use HasBlogRole;

    protected $table = 'users';

    protected $guarded = [];
}
