<?php

namespace Vitebox\LaravelBlog\Services;

use DateTimeInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Vitebox\LaravelBlog\Enums\PostStatus;
use Vitebox\LaravelBlog\Events;
use Vitebox\LaravelBlog\Exceptions\InvalidTransition;
use Vitebox\LaravelBlog\Models\Post;
use Vitebox\LaravelBlog\Models\PostActivity;

/**
 * Editorial state machine.
 *
 *   draft ──submit──▶ pending_review ──approve──▶ approved ──publish──▶ published
 *     ▲                   │                          │  └──schedule──▶ scheduled ──(cron)──▶ published
 *     │                   └──request changes──▶ changes_requested ──submit──┘
 *     └──────────── restore ◀── archived ◀── archive (any state)
 *
 * Authorisation (who may do what) lives in PostPolicy; this class only
 * guards *valid* transitions and records the audit trail + events.
 */
class PostWorkflow
{
    public function submit(Post $post, mixed $user, ?string $note = null): Post
    {
        $this->guard($post, 'submitted for review', [PostStatus::Draft, PostStatus::ChangesRequested]);

        $to = config('blog.workflow.require_review', true) ? PostStatus::PendingReview : PostStatus::Approved;

        return $this->transition($post, $to, 'submitted', $user, $note, [
            'submitted_at' => now(),
        ], Events\PostSubmittedForReview::class);
    }

    public function approve(Post $post, mixed $user, ?string $comment = null): Post
    {
        $this->guard($post, 'approved', [PostStatus::PendingReview]);

        return $this->transition($post, PostStatus::Approved, 'approved', $user, $comment, [
            'reviewer_id' => $user?->getAuthIdentifier(),
            'reviewed_at' => now(),
        ], Events\PostApproved::class);
    }

    public function requestChanges(Post $post, mixed $user, string $comment): Post
    {
        $this->guard($post, 'sent back for changes', [PostStatus::PendingReview, PostStatus::Approved, PostStatus::Scheduled]);

        return $this->transition($post, PostStatus::ChangesRequested, 'changes_requested', $user, $comment, [
            'reviewer_id' => $user?->getAuthIdentifier(),
            'reviewed_at' => now(),
        ], Events\PostChangesRequested::class);
    }

    /**
     * Publish now, or schedule when $at is in the future.
     */
    public function publish(Post $post, mixed $user, DateTimeInterface|string|null $at = null): Post
    {
        $this->guard($post, 'published', $this->publishableStates());

        $at = $at ? Carbon::parse($at) : null;

        if ($at && $at->isFuture()) {
            return $this->transition($post, PostStatus::Scheduled, 'scheduled', $user, 'For '.$at->toDayDateTimeString(), [
                'published_at' => $at,
                'publisher_id' => $user?->getAuthIdentifier(),
            ], Events\PostScheduled::class);
        }

        return $this->transition($post, PostStatus::Published, 'published', $user, null, [
            'published_at' => $at ?? $post->published_at ?? now(),
            'publisher_id' => $user?->getAuthIdentifier(),
        ], Events\PostPublished::class);
    }

    public function unpublish(Post $post, mixed $user, ?string $reason = null): Post
    {
        $this->guard($post, 'unpublished', [PostStatus::Published, PostStatus::Scheduled]);

        return $this->transition($post, PostStatus::Draft, 'unpublished', $user, $reason, [], Events\PostUnpublished::class);
    }

    public function archive(Post $post, mixed $user, ?string $reason = null): Post
    {
        if ($post->status === PostStatus::Archived) {
            throw InvalidTransition::make('archived', $post->status);
        }

        return $this->transition($post, PostStatus::Archived, 'archived', $user, $reason, [], Events\PostArchived::class);
    }

    public function restore(Post $post, mixed $user): Post
    {
        $this->guard($post, 'restored', [PostStatus::Archived]);

        return $this->transition($post, PostStatus::Draft, 'restored', $user);
    }

    /**
     * Promote scheduled posts whose time has come. Returns the number published.
     */
    public function publishDue(): int
    {
        $count = 0;

        Post::query()
            ->where('status', PostStatus::Scheduled->value)
            ->where('published_at', '<=', now())
            ->orderBy('published_at')
            ->each(function (Post $post) use (&$count) {
                $this->transition($post, PostStatus::Published, 'published', null, 'Published automatically (scheduled).', [], Events\PostPublished::class);
                $count++;
            });

        return $count;
    }

    /** @return array<int, PostStatus> */
    public function publishableStates(): array
    {
        $states = [PostStatus::Approved, PostStatus::Scheduled];

        if (config('blog.workflow.direct_publish', true)) {
            array_push($states, PostStatus::Draft, PostStatus::PendingReview, PostStatus::ChangesRequested);
        }

        return $states;
    }

    public function log(Post $post, mixed $user, string $action, ?PostStatus $from = null, ?PostStatus $to = null, ?string $comment = null): PostActivity
    {
        return PostActivity::query()->create([
            'post_id' => $post->getKey(),
            'user_id' => $user?->getAuthIdentifier(),
            'action' => $action,
            'from_status' => $from,
            'to_status' => $to,
            'comment' => $comment,
        ]);
    }

    /** @param array<int, PostStatus> $allowed */
    protected function guard(Post $post, string $action, array $allowed): void
    {
        if (! in_array($post->status, $allowed, true)) {
            throw InvalidTransition::make($action, $post->status);
        }
    }

    /**
     * @param  class-string<Events\PostEvent>|null  $event
     */
    protected function transition(Post $post, PostStatus $to, string $action, mixed $user, ?string $comment = null, array $attributes = [], ?string $event = null): Post
    {
        $from = $post->status;

        DB::connection($post->getConnectionName())->transaction(function () use ($post, $to, $action, $user, $comment, $attributes, $from) {
            $post->forceFill(array_merge($attributes, ['status' => $to]))->save();
            $this->log($post, $user, $action, $from, $to, $comment);
        });

        event(new Events\PostStatusChanged($post, $from, $to, $user, $comment));

        if ($event) {
            event(new $event($post, $from, $to, $user, $comment));
        }

        return $post;
    }
}
