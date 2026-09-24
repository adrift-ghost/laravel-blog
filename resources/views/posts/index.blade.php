@extends('blog::layouts.app')

@section('title', 'Posts')

@section('actions')
    @can('create', \Vitebox\LaravelBlog\Models\Post::class)
        <a href="{{ blog_route('posts.create') }}" class="btn btn-primary">+ New post</a>
    @endcan
@endsection

@section('content')
    @php($statuses = \Vitebox\LaravelBlog\Enums\PostStatus::cases())
    <div class="tabs">
        <a href="{{ blog_route('posts.index', array_filter(array_merge($filters, ['status' => null]))) }}" @class(['active' => empty($filters['status'])])>
            All <span class="muted">{{ $counts->sum() }}</span>
        </a>
        @foreach($statuses as $status)
            <a href="{{ blog_route('posts.index', array_merge($filters, ['status' => $status->value])) }}" @class(['active' => ($filters['status'] ?? null) === $status->value])>
                {{ $status->label() }} <span class="muted">{{ $counts[$status->value] ?? 0 }}</span>
            </a>
        @endforeach
    </div>

    <form method="GET" class="filters">
        @if(!empty($filters['status']))<input type="hidden" name="status" value="{{ $filters['status'] }}">@endif
        <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search title or content…" style="min-width:240px">
        <select name="type">
            <option value="">All types</option>
            @foreach($blog->types()->all() as $key => $type)
                <option value="{{ $key }}" @selected(($filters['type'] ?? '') === $key)>{{ $type->icon() }} {{ $type->label() }}</option>
            @endforeach
        </select>
        <select name="category">
            <option value="">All categories</option>
            @foreach($categories as $cat)
                <option value="{{ $cat->id }}" @selected((string) ($filters['category'] ?? '') === (string) $cat->id)>{{ str_repeat('— ', $cat->depth) }}{{ $cat->name }}</option>
            @endforeach
        </select>
        @if($blog->can($blogUser, 'posts.view_any'))
            <label class="check" style="margin:0"><input type="checkbox" name="mine" value="1" @checked(!empty($filters['mine']))> Only mine</label>
        @endif
        <button class="btn">Filter</button>
        @if(array_filter($filters))<a class="btn" href="{{ blog_route('posts.index') }}">Reset</a>@endif
    </form>

    <div class="card flush">
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th style="width:64px"></th>
                    <th>Title</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Author</th>
                    <th>Updated</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse($posts as $post)
                    <tr>
                        <td>
                            @if($post->cover_image_url)
                                <img src="{{ $post->cover_image_url }}" alt="" class="thumb" loading="lazy">
                            @else
                                <div class="thumb" style="display:grid;place-items:center">{{ $post->postType()->icon() }}</div>
                            @endif
                        </td>
                        <td>
                            <a href="{{ blog_route('posts.show', $post) }}"><strong>{{ $post->title }}</strong></a>
                            @if($post->is_featured)<span class="badge badge-violet">Featured</span>@endif
                            @if($post->is_pinned)<span class="badge badge-blue">Pinned</span>@endif
                            <div class="muted small">/{{ $post->slug }}
                                @if($post->categories->isNotEmpty()) · {{ $post->categories->pluck('name')->implode(', ') }}@endif
                            </div>
                        </td>
                        <td class="small">{{ $post->postType()->label() }}</td>
                        <td>
                            @include('blog::partials.status', ['status' => $post->status])
                            @if($post->status->value === 'scheduled')<div class="muted small">{{ $post->published_at?->toDayDateTimeString() }}</div>@endif
                        </td>
                        <td class="small">{{ $blog->userName($post->author) }}</td>
                        <td class="small muted">{{ $post->updated_at->diffForHumans() }}</td>
                        <td class="small" style="white-space:nowrap">
                            @can('update', $post)<a href="{{ blog_route('posts.edit', $post) }}">Edit</a>@else<a href="{{ blog_route('posts.show', $post) }}">View</a>@endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="muted" style="text-align:center;padding:40px">No posts found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="pagination">{{ $posts->links('blog::partials.pagination') }}</div>
@endsection
