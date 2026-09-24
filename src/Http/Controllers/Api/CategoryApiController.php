<?php

namespace Vitebox\LaravelBlog\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Vitebox\LaravelBlog\Http\Resources\CategoryResource;
use Vitebox\LaravelBlog\Http\Resources\PostResource;
use Vitebox\LaravelBlog\Models\Category;
use Vitebox\LaravelBlog\Models\Post;

class CategoryApiController extends Controller
{
    /** Nested tree of active categories with published post counts. */
    public function index()
    {
        $count = ['posts' => fn ($q) => $q->published()];

        $children = function ($q) use (&$children, $count) {
            $q->active()->ordered()->withCount($count)->with(['children' => $children]);
        };

        return CategoryResource::collection(
            Category::query()->active()->roots()->ordered()->withCount($count)->with(['children' => $children])->get()
        );
    }

    public function show(Request $request, string $slug)
    {
        [$category, $redirected] = Category::findBySlugWithRedirect($slug, fn ($q) => $q->active());
        abort_unless($category, 404);

        if ($redirected) {
            return response()->json(['redirect' => true, 'slug' => $category->slug], 301, [
                'Location' => route(config('blog.api.route_name', 'blog.api.').'categories.show', $category->slug),
            ]);
        }

        $perPage = max(1, min((int) $request->input('per_page', config('blog.api.per_page', 12)), (int) config('blog.api.max_per_page', 50)));

        $posts = Post::query()->published()->inCategory($category)
            ->with(['author', 'categories', 'tags'])
            ->latestFirst()
            ->paginate($perPage)
            ->withQueryString();

        return PostResource::collection($posts)->additional([
            'category' => new CategoryResource($category->load('children')),
        ]);
    }
}
