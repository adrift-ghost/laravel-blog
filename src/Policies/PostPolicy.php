<?php

namespace Vitebox\LaravelBlog\Policies;

use Vitebox\LaravelBlog\Enums\PostStatus;
use Vitebox\LaravelBlog\Facades\Blog;
use Vitebox\LaravelBlog\Models\Post;

/**
 * Role based access for posts.
 *
 *  Writer    – create; edit/submit/delete own posts while draft or changes requested
 *  Reviewer  – everything a writer can + see all posts, edit & review posts pending review
 *  Publisher – see/edit all posts, publish / schedule / unpublish / archive / feature
 *  Admin     – everything
 */
class PostPolicy
{
    public function viewAny($user): bool
    {
        return Blog::can($user, 'posts.create') || Blog::can($user, 'posts.view_any');
    }

    public function view($user, Post $post): bool
    {
        return Blog::can($user, 'posts.view_any') || ($post->isOwnedBy($user) && Blog::hasAccess($user));
    }

    public function create($user): bool
    {
        return Blog::can($user, 'posts.create');
    }

    public function update($user, Post $post): bool
    {
        if (Blog::can($user, 'posts.update_any')) {
            return true;
        }

        if ($post->isOwnedBy($user) && Blog::can($user, 'posts.create') && $post->status->isEditableByAuthor()) {
            return true;
        }

        // Reviewers may polish copy while reviewing.
        return Blog::can($user, 'posts.review') && $post->status === PostStatus::PendingReview;
    }

    public function delete($user, Post $post): bool
    {
        if (Blog::can($user, 'posts.delete_any')) {
            return true;
        }

        return $post->isOwnedBy($user)
            && Blog::can($user, 'posts.create')
            && in_array($post->status, [PostStatus::Draft, PostStatus::ChangesRequested], true)
            && $post->published_at === null;
    }

    public function submit($user, Post $post): bool
    {
        return in_array($post->status, [PostStatus::Draft, PostStatus::ChangesRequested], true)
            && ($post->isOwnedBy($user) || Blog::can($user, 'posts.update_any'))
            && Blog::can($user, 'posts.create');
    }

    public function review($user, Post $post): bool
    {
        if (! Blog::can($user, 'posts.review')) {
            return false;
        }

        if (! config('blog.workflow.allow_self_review', false) && $post->isOwnedBy($user)) {
            return false;
        }

        return in_array($post->status, [PostStatus::PendingReview, PostStatus::Approved, PostStatus::Scheduled], true);
    }

    public function publish($user, Post $post): bool
    {
        return Blog::can($user, 'posts.publish');
    }
}
