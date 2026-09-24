@php
    $status = $post->status->value;
    $workflow = app(\Vitebox\LaravelBlog\Services\PostWorkflow::class);
    $canPublishNow = $blogUser->can('publish', $post) && in_array($post->status, $workflow->publishableStates(), true);
@endphp

<div class="card">
    <div class="row" style="margin-bottom:12px">
        <strong>Workflow</strong><span class="spacer"></span>
        @include('blog::partials.status', ['status' => $post->status])
    </div>

    <div class="stack">
        @if($status === 'changes_requested')
            @php($lastReview = $post->activities()->where('action', 'changes_requested')->first())
            @if($lastReview?->comment)
                <div class="alert alert-error" style="margin:0"><strong>Reviewer:</strong> {{ $lastReview->comment }}</div>
            @endif
        @endif

        @can('submit', $post)
            <form method="POST" action="{{ blog_route('posts.submit', $post) }}">
                @csrf
                <button class="btn btn-primary btn-block">{{ config('blog.workflow.require_review', true) ? 'Submit for review' : 'Mark ready to publish' }}</button>
            </form>
        @endcan

        @can('review', $post)
            @if($status === 'pending_review')
                <form method="POST" action="{{ blog_route('posts.approve', $post) }}" class="stack">
                    @csrf
                    <input type="text" name="comment" placeholder="Optional note">
                    <button class="btn btn-ok btn-block">Approve</button>
                </form>
            @endif
            <form method="POST" action="{{ blog_route('posts.request-changes', $post) }}" class="stack">
                @csrf
                <textarea name="comment" rows="3" placeholder="What needs to change? (required)" required></textarea>
                <button class="btn btn-danger btn-block">Request changes</button>
            </form>
        @endcan

        @if($canPublishNow)
            <form method="POST" action="{{ blog_route('posts.publish', $post) }}" class="stack">
                @csrf
                <label class="small" style="margin:0">Publish at <span class="muted">(empty = now)</span></label>
                <input type="datetime-local" name="publish_at">
                <button class="btn btn-ok btn-block">{{ $status === 'scheduled' ? 'Publish now / reschedule' : 'Publish' }}</button>
            </form>
        @endif

        @can('publish', $post)
            @if(in_array($status, ['published', 'scheduled']))
                <form method="POST" action="{{ blog_route('posts.unpublish', $post) }}" data-confirm="Take this post offline and move it back to draft?">
                    @csrf <button class="btn btn-block">Unpublish</button>
                </form>
            @endif
            <div class="row">
                <form method="POST" action="{{ blog_route('posts.toggle', [$post, 'featured']) }}">@csrf
                    <button class="btn btn-sm">{{ $post->is_featured ? '★ Unfeature' : '☆ Feature' }}</button></form>
                <form method="POST" action="{{ blog_route('posts.toggle', [$post, 'pinned']) }}">@csrf
                    <button class="btn btn-sm">{{ $post->is_pinned ? 'Unpin' : 'Pin' }}</button></form>
                <span class="spacer"></span>
                @if($status === 'archived')
                    <form method="POST" action="{{ blog_route('posts.restore', $post) }}">@csrf <button class="btn btn-sm">Restore</button></form>
                @else
                    <form method="POST" action="{{ blog_route('posts.archive', $post) }}" data-confirm="Archive this post?">@csrf <button class="btn btn-sm btn-danger">Archive</button></form>
                @endif
            </div>
        @endcan

        @if(! $blogUser->can('submit', $post) && ! $blogUser->can('review', $post) && ! $blogUser->can('publish', $post))
            <p class="muted small" style="margin:0">
                @switch($status)
                    @case('pending_review') Waiting for a reviewer. @break
                    @case('approved') Approved — waiting for a publisher. @break
                    @case('scheduled') Scheduled for {{ $post->published_at?->toDayDateTimeString() }}. @break
                    @case('published') Live on the website. @break
                    @default No actions available for your role.
                @endswitch
            </p>
        @endif
    </div>
</div>
