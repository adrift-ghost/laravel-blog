@extends('blog::layouts.app')

@section('title', 'Dashboard')

@section('actions')
    @can('create', \Vitebox\LaravelBlog\Models\Post::class)
        <a href="{{ blog_route('posts.create') }}" class="btn btn-primary">+ New post</a>
    @endcan
@endsection

@section('content')
    <div class="grid grid-4">
        <div class="card"><div class="muted small">{{ $blog->can($blogUser, 'posts.view_any') ? 'All posts' : 'My posts' }}</div><div class="stat">{{ number_format($totals['posts']) }}</div></div>
        <div class="card"><div class="muted small">Published</div><div class="stat">{{ number_format($totals['published']) }}</div></div>
        <div class="card"><div class="muted small">Pending review</div><div class="stat">{{ number_format($counts['pending_review'] ?? 0) }}</div></div>
        <div class="card"><div class="muted small">Total views</div><div class="stat">{{ number_format($totals['views']) }}</div></div>
    </div>

    <div class="layout-side" style="margin-top:16px">
        <div>
            <div class="card">
                <h2>Continue writing</h2>
                @forelse($myWork as $post)
                    <div class="row" style="padding:8px 0;border-bottom:1px solid var(--border)">
                        <a href="{{ blog_route('posts.edit', $post) }}"><strong>{{ $post->title }}</strong></a>
                        @include('blog::partials.status', ['status' => $post->status])
                        <span class="spacer"></span>
                        <span class="muted small">{{ $post->updated_at->diffForHumans() }}</span>
                    </div>
                @empty
                    <p class="muted">Nothing in progress. @can('create', \Vitebox\LaravelBlog\Models\Post::class)<a href="{{ blog_route('posts.create') }}">Start a new post →</a>@endcan</p>
                @endforelse
            </div>

            <div class="card">
                <h2>Recent activity</h2>
                @include('blog::partials.activity', ['activities' => $activity, 'showPost' => true])
            </div>
        </div>

        <div>
            <div class="card">
                <h2>By status</h2>
                <div class="stack">
                    @foreach(\Vitebox\LaravelBlog\Enums\PostStatus::cases() as $status)
                        <a class="row" href="{{ blog_route('posts.index', ['status' => $status->value]) }}" style="color:inherit">
                            @include('blog::partials.status', ['status' => $status])
                            <span class="spacer"></span><strong>{{ $counts[$status->value] ?? 0 }}</strong>
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="card">
                <h2>By type</h2>
                <div class="stack">
                    @foreach($blog->types()->all() as $key => $type)
                        <a class="row" href="{{ blog_route('posts.index', ['type' => $key]) }}" style="color:inherit">
                            <span>{{ $type->icon() }} {{ $type->label() }}</span>
                            <span class="spacer"></span><strong>{{ $typeCounts[$key] ?? 0 }}</strong>
                        </a>
                    @endforeach
                </div>
            </div>

            @if($upcoming->isNotEmpty())
                <div class="card">
                    <h2>Scheduled</h2>
                    @foreach($upcoming as $post)
                        <div style="padding:6px 0">
                            <a href="{{ blog_route('posts.show', $post) }}">{{ $post->title }}</a>
                            <div class="muted small">{{ $post->published_at?->toDayDateTimeString() }}</div>
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="card">
                <h2>Your role: {{ $blogRole?->label() }}</h2>
                <p class="muted" style="margin:0">{{ $blogRole?->description() }}</p>
            </div>
        </div>
    </div>
@endsection
