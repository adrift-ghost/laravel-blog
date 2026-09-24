<?php

namespace Vitebox\LaravelBlog\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Vitebox\LaravelBlog\Enums\PostStatus;
use Vitebox\LaravelBlog\Facades\Blog;
use Vitebox\LaravelBlog\Http\Controllers\Controller;
use Vitebox\LaravelBlog\Models\Category;
use Vitebox\LaravelBlog\Models\Post;
use Vitebox\LaravelBlog\Models\PostActivity;
use Vitebox\LaravelBlog\Models\Tag;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();
        $seeAll = Blog::can($user, 'posts.view_any');
        $scope = fn ($q) => $seeAll ? $q : $q->where('author_id', $user->getAuthIdentifier());

        $counts = $scope(Post::query())->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        $myWork = Post::query()
            ->where('author_id', $user->getAuthIdentifier())
            ->whereIn('status', [PostStatus::Draft->value, PostStatus::ChangesRequested->value])
            ->latest('updated_at')->limit(6)->get();

        $activity = PostActivity::query()
            ->with(['post' => fn ($q) => $q->withTrashed(), 'user'])
            ->when(! $seeAll, fn ($q) => $q->whereHas('post', fn ($p) => $p->where('author_id', $user->getAuthIdentifier())))
            ->latest('id')->limit(12)->get();

        return view('blog::dashboard', [
            'counts' => $counts,
            'totals' => [
                'posts' => $counts->sum(),
                'published' => (int) ($counts[PostStatus::Published->value] ?? 0),
                'categories' => Category::query()->count(),
                'tags' => Tag::query()->count(),
                'views' => (int) $scope(Post::query())->sum('views'),
            ],
            'myWork' => $myWork,
            'activity' => $activity,
            'typeCounts' => $scope(Post::query())->selectRaw('type, count(*) as total')->groupBy('type')->pluck('total', 'type'),
            'upcoming' => Post::query()->status(PostStatus::Scheduled)->orderBy('published_at')->limit(5)->get(),
        ]);
    }
}
