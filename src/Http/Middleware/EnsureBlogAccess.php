<?php

namespace Vitebox\LaravelBlog\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Vitebox\LaravelBlog\Facades\Blog;

/**
 * Only users holding a blog role (or listed as super admins) may enter the
 * admin panel. Everything finer-grained is handled by policies / gates.
 */
class EnsureBlogAccess
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (! $user) {
            $login = config('blog.admin.login_route', 'login');

            return $request->expectsJson() || ! Route::has($login)
                ? abort(401)
                : redirect()->guest(route($login));
        }

        if (! Blog::hasAccess($user)) {
            abort(403, 'You do not have a role in the blog team. Ask a blog admin to invite you.');
        }

        return $next($request);
    }
}
