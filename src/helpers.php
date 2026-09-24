<?php

if (! function_exists('blog_route')) {
    /**
     * URL of a blog admin route, honouring config('blog.admin.route_name').
     */
    function blog_route(string $name, mixed $parameters = [], bool $absolute = true): string
    {
        return route(config('blog.admin.route_name', 'blog.admin.').$name, $parameters, $absolute);
    }
}

if (! function_exists('blog_route_is')) {
    function blog_route_is(string ...$patterns): bool
    {
        $prefix = config('blog.admin.route_name', 'blog.admin.');

        return request()->routeIs(...array_map(fn ($p) => $prefix.$p, $patterns));
    }
}
