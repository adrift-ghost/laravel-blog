<?php

namespace Vitebox\LaravelBlog\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Vitebox\LaravelBlog\Http\Resources\PostResource;
use Vitebox\LaravelBlog\Http\Resources\TagResource;
use Vitebox\LaravelBlog\Models\Post;
use Vitebox\LaravelBlog\Models\Tag;

class TagApiController extends Controller
{
    /** Tags that have at least one published post, most used first. */
    public function index(Request $request)
    {
        $tags = Tag::query()
            ->withCount(['posts' => fn ($q) => $q->published()])
            ->whereHas('posts', fn ($q) => $q->published())
            ->orderByDesc('posts_count')
            ->orderBy('name')
            ->limit(min(200, max(1, (int) $request->input('limit', 100))))
            ->get();

        return TagResource::collection($tags);
    }

    public function show(Request $request, string $slug)
    {
        [$tag, $redirected] = Tag::findBySlugWithRedirect($slug);
        abort_unless($tag, 404);

        if ($redirected) {
            return response()->json(['redirect' => true, 'slug' => $tag->slug], 301, [
                'Location' => route(config('blog.api.route_name', 'blog.api.').'tags.show', $tag->slug),
            ]);
        }

        $perPage = max(1, min((int) $request->input('per_page', config('blog.api.per_page', 12)), (int) config('blog.api.max_per_page', 50)));

        return PostResource::collection(
            Post::query()->published()->withTag($tag)->with(['author', 'categories', 'tags'])->latestFirst()->paginate($perPage)->withQueryString()
        )->additional(['tag' => new TagResource($tag)]);
    }
}
