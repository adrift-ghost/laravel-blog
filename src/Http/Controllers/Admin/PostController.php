<?php

namespace Vitebox\LaravelBlog\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Vitebox\LaravelBlog\Enums\PostStatus;
use Vitebox\LaravelBlog\Exceptions\InvalidTransition;
use Vitebox\LaravelBlog\Facades\Blog;
use Vitebox\LaravelBlog\Http\Controllers\Controller;
use Vitebox\LaravelBlog\Http\Requests\PostRequest;
use Vitebox\LaravelBlog\Models\Category;
use Vitebox\LaravelBlog\Models\Post;
use Vitebox\LaravelBlog\Models\Tag;
use Vitebox\LaravelBlog\Services\PostService;
use Vitebox\LaravelBlog\Services\PostWorkflow;

class PostController extends Controller
{
    public function __construct(
        protected PostService $posts,
        protected PostWorkflow $workflow,
    ) {
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', Post::class);
        $user = $request->user();

        $base = Post::query()
            ->when(! Blog::can($user, 'posts.view_any') || $request->boolean('mine'),
                fn ($q) => $q->where('author_id', $user->getAuthIdentifier()));

        $counts = (clone $base)->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        $posts = (clone $base)
            ->with(['author', 'categories'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->input('type')))
            ->when($request->filled('category'), fn ($q) => $q->inCategory((int) $request->input('category')))
            ->when($request->filled('tag'), fn ($q) => $q->withTag($request->input('tag')))
            ->when($request->boolean('trashed') && Blog::can($user, 'posts.delete_any'), fn ($q) => $q->onlyTrashed())
            ->search($request->input('q'))
            ->orderByDesc('updated_at')
            ->paginate(config('blog.admin.per_page', 20))
            ->withQueryString();

        return view('blog::posts.index', [
            'posts' => $posts,
            'counts' => $counts,
            'categories' => Category::tree(),
            'filters' => $request->only(['status', 'type', 'category', 'q', 'mine', 'tag']),
        ]);
    }

    /** Review queue: pending review, approved (ready to publish) & scheduled. */
    public function queue(Request $request)
    {
        $user = $request->user();
        abort_unless(Blog::can($user, 'posts.review') || Blog::can($user, 'posts.publish'), 403);

        $load = fn (PostStatus $s) => Post::query()->with('author')->status($s)->orderBy('submitted_at')->get();

        return view('blog::review.index', [
            'pending' => $load(PostStatus::PendingReview),
            'approved' => $load(PostStatus::Approved),
            'scheduled' => Post::query()->with('author')->status(PostStatus::Scheduled)->orderBy('published_at')->get(),
        ]);
    }

    public function create(Request $request)
    {
        $this->authorize('create', Post::class);

        $post = new Post([
            'type' => Blog::types()->has($request->query('type')) ? $request->query('type') : config('blog.default_post_type'),
        ]);

        return view('blog::posts.form', $this->formData($post));
    }

    public function store(PostRequest $request)
    {
        $this->authorize('create', Post::class);

        $post = $this->posts->save(new Post, $this->payload($request), $request->user());

        return $this->afterSave($request, $post, 'Post created.');
    }

    public function show(Post $post)
    {
        $this->authorize('view', $post);

        return view('blog::posts.show', ['post' => $post->load(['author', 'categories', 'tags'])]);
    }

    public function edit(Request $request, Post $post)
    {
        $this->authorize('view', $post);

        if (Gate::denies('update', $post)) {
            return redirect($this->route('posts.show', $post))
                ->with('blog_warning', 'This post is "'.$post->status->label().'" — you can view it but not edit it right now.');
        }

        return view('blog::posts.form', $this->formData($post->load(['categories', 'tags', 'activities.user'])));
    }

    public function update(PostRequest $request, Post $post)
    {
        $this->authorize('update', $post);

        $post = $this->posts->save($post, $this->payload($request), $request->user());

        return $this->afterSave($request, $post, 'Post saved.');
    }

    public function destroy(Request $request, Post $post)
    {
        $this->authorize('delete', $post);

        $this->posts->delete($post);

        return redirect($this->route('posts.index'))->with('blog_success', 'Post moved to trash.');
    }

    /** Only publishers may set featured / pinned flags. */
    protected function payload(PostRequest $request): array
    {
        $data = $request->validated();
        $user = $request->user();

        foreach (['cover_image', 'video_file'] as $file) {
            if ($request->hasFile($file)) {
                $data[$file] = $request->file($file);
            }
        }

        if (! Blog::can($user, 'posts.publish')) {
            unset($data['is_featured'], $data['is_pinned']);
        }

        $data['_can_create_tags'] = Blog::can($user, 'tags.create');
        unset($data['intent'], $data['publish_at']);

        return $data;
    }

    protected function afterSave(PostRequest $request, Post $post, string $message)
    {
        $user = $request->user();
        $edit = redirect($this->route('posts.edit', $post));

        try {
            switch ($request->input('intent')) {
                case 'submit':
                    $this->authorize('submit', $post);
                    $this->workflow->submit($post, $user, $request->input('revision_note'));
                    $message .= config('blog.workflow.require_review', true) ? ' Submitted for review.' : ' Ready to publish.';

                    return redirect($this->route('posts.show', $post))->with('blog_success', $message);

                case 'publish':
                    $this->authorize('publish', $post);
                    $this->workflow->publish($post, $user, $request->input('publish_at'));
                    $message .= $post->status === PostStatus::Scheduled ? ' Scheduled.' : ' Published.';
                    break;
            }
        } catch (InvalidTransition $e) {
            return $edit->with('blog_error', $e->getMessage());
        }

        return $edit->with('blog_success', $message);
    }

    protected function formData(Post $post): array
    {
        return [
            'post' => $post,
            'types' => Blog::types()->all(),
            'categories' => Category::tree(),
            'allTags' => Tag::query()->orderBy('name')->pluck('name'),
            'selectedCategories' => $post->exists ? $post->categories->pluck('id')->all() : [],
            'selectedTags' => $post->exists ? $post->tags->pluck('name')->implode(', ') : '',
        ];
    }
}
