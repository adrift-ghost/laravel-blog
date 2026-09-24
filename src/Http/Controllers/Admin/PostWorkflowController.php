<?php

namespace Vitebox\LaravelBlog\Http\Controllers\Admin;

use Closure;
use Illuminate\Http\Request;
use Vitebox\LaravelBlog\Exceptions\InvalidTransition;
use Vitebox\LaravelBlog\Http\Controllers\Controller;
use Vitebox\LaravelBlog\Models\Post;
use Vitebox\LaravelBlog\Services\PostWorkflow;

class PostWorkflowController extends Controller
{
    public function __construct(protected PostWorkflow $workflow)
    {
    }

    public function submit(Request $request, Post $post)
    {
        $this->authorize('submit', $post);

        return $this->run(fn () => $this->workflow->submit($post, $request->user(), $request->input('comment')),
            config('blog.workflow.require_review', true) ? 'Submitted for review.' : 'Ready to publish.', $post);
    }

    public function approve(Request $request, Post $post)
    {
        $this->authorize('review', $post);

        return $this->run(fn () => $this->workflow->approve($post, $request->user(), $request->input('comment')), 'Post approved.', $post);
    }

    public function requestChanges(Request $request, Post $post)
    {
        $this->authorize('review', $post);
        $data = $request->validate(['comment' => ['required', 'string', 'max:5000']]);

        return $this->run(fn () => $this->workflow->requestChanges($post, $request->user(), $data['comment']), 'Changes requested — the author has been notified.', $post);
    }

    public function publish(Request $request, Post $post)
    {
        $this->authorize('publish', $post);
        $data = $request->validate(['publish_at' => ['nullable', 'date']]);

        return $this->run(function () use ($post, $request, $data) {
            $this->workflow->publish($post, $request->user(), $data['publish_at'] ?? null);
        }, fn () => $post->status->value === 'scheduled' ? 'Post scheduled for '.$post->published_at->toDayDateTimeString().'.' : 'Post published.', $post);
    }

    public function unpublish(Request $request, Post $post)
    {
        $this->authorize('publish', $post);

        return $this->run(fn () => $this->workflow->unpublish($post, $request->user(), $request->input('comment')), 'Post unpublished and moved back to draft.', $post);
    }

    public function archive(Request $request, Post $post)
    {
        $this->authorize('publish', $post);

        return $this->run(fn () => $this->workflow->archive($post, $request->user(), $request->input('comment')), 'Post archived.', $post);
    }

    public function restore(Request $request, Post $post)
    {
        $this->authorize('publish', $post);

        return $this->run(fn () => $this->workflow->restore($post, $request->user()), 'Post restored to draft.', $post);
    }

    public function toggle(Request $request, Post $post, string $flag)
    {
        $this->authorize('publish', $post);

        $column = 'is_'.$flag;
        $post->forceFill([$column => ! $post->{$column}])->save();
        $this->workflow->log($post, $request->user(), ($post->{$column} ? '' : 'un').$flag);

        return back()->with('blog_success', 'Post '.($post->{$column} ? '' : 'un').$flag.'.');
    }

    protected function run(Closure $action, string|Closure $message, Post $post)
    {
        try {
            $action();
        } catch (InvalidTransition $e) {
            return back()->with('blog_error', $e->getMessage());
        }

        return redirect($this->route('posts.show', $post))
            ->with('blog_success', $message instanceof Closure ? $message() : $message);
    }
}
