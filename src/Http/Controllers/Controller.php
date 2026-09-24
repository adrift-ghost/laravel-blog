<?php

namespace Vitebox\LaravelBlog\Http\Controllers;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Gate;

abstract class Controller extends BaseController
{
    protected function authorize(string $ability, mixed $arguments = []): void
    {
        Gate::authorize($ability, $arguments);
    }

    protected function route(string $name, mixed $params = []): string
    {
        return route(config('blog.admin.route_name', 'blog.admin.').$name, $params);
    }
}
