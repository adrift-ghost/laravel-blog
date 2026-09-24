<?php

namespace Vitebox\LaravelBlog\Http\Controllers\Api;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Vitebox\LaravelBlog\Facades\Blog;
use Vitebox\LaravelBlog\Http\Resources\PostResource;
use Vitebox\LaravelBlog\Models\Post;

/**
 * Public, read-only endpoints. Only published posts are ever exposed.
 */
class PostApiController extends Controller
{
    public function types(): JsonResponse
    {
        return response()->json([
            'data' => array_values(array_map(fn ($t) => $t->toArray(), Blog::types()->all())),
        ]);
    }

    /**
     * GET /posts?type=event,news&category=slug&tag=slug&q=term&featured=1&sort=latest|popular|oldest&per_page=12
     */
    public function index(Request $request)
    {
        $query = $this->base()
            ->when($request->filled('type'), fn ($q) => $q->ofType(explode(',', (string) $request->input('type'))))
            ->when($request->filled('category'), fn ($q) => $q->inCategory((string) $request->input('category')))
            ->when($request->filled('tag'), fn ($q) => $q->withTag((string) $request->input('tag')))
            ->when($request->filled('author'), fn ($q) => $q->where('author_id', $request->input('author')))
            ->when($request->boolean('featured'), fn ($q) => $q->featured())
            ->search($request->input('q'));

        match ($request->input('sort')) {
            'popular' => $query->orderByDesc('views'),
            'oldest' => $query->orderBy('published_at'),
            default => $query->latestFirst(),
        };

        return PostResource::collection($query->paginate($this->perPage($request))->withQueryString());
    }

    public function show(Request $request, string $slug)
    {
        [$post, $redirected] = Post::findBySlugWithRedirect($slug, fn ($q) => $q->published());

        abort_unless($post, 404);

        if ($redirected) {
            return response()->json([
                'redirect' => true,
                'slug' => $post->slug,
                'url' => route(config('blog.api.route_name', 'blog.api.').'posts.show', $post->slug),
            ], 301, ['Location' => route(config('blog.api.route_name', 'blog.api.').'posts.show', $post->slug)]);
        }

        if (config('blog.api.count_views', true)) {
            $post->newQuery()->whereKey($post->id)->increment('views');
            $post->views++;
        }

        $post->load(['author', 'categories', 'tags']);

        $related = $this->base()
            ->whereKeyNot($post->id)
            ->where(fn ($q) => $q
                ->whereHas('categories', fn ($c) => $c->whereIn($c->getModel()->getTable().'.id', $post->categories->pluck('id')))
                ->orWhereHas('tags', fn ($t) => $t->whereIn($t->getModel()->getTable().'.id', $post->tags->pluck('id'))))
            ->latestFirst()
            ->limit(4)
            ->get();

        return (new PostResource($post))->full()->additional([
            'related' => PostResource::collection($related),
        ]);
    }

    public function upcomingEvents(Request $request)
    {
        return PostResource::collection(
            $this->base()->upcomingEvents()->paginate($this->perPage($request))
        );
    }

    public function activeAnnouncements(Request $request)
    {
        return PostResource::collection(
            $this->base()->activeAnnouncements()->paginate($this->perPage($request))
        );
    }

    protected function base(): Builder
    {
        return Post::query()->published()->with(['author', 'categories', 'tags']);
    }

    protected function perPage(Request $request): int
    {
        return max(1, min((int) $request->input('per_page', config('blog.api.per_page', 12)), (int) config('blog.api.max_per_page', 50)));
    }
}
