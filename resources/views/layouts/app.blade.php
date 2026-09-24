<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') · {{ config('blog.admin.brand', 'Blog Manager') }}</title>
    @include('blog::partials.styles')
    @if(config('blog.content.editor') === 'trix')
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/trix@2.1.8/dist/trix.css">
        <script src="https://cdn.jsdelivr.net/npm/trix@2.1.8/dist/trix.umd.min.js"></script>
    @endif
    @stack('blog_head')
</head>
<body>
<div class="shell">
    <aside class="sidebar">
        <a href="{{ blog_route('dashboard') }}" class="brand">
            <span class="brand-mark">B</span>
            <span>{{ config('blog.admin.brand', 'Blog Manager') }}</span>
        </a>

        <nav class="nav">
            <a href="{{ blog_route('dashboard') }}" @class(['active' => blog_route_is('dashboard')])>Dashboard</a>
            @can('viewAny', \Vitebox\LaravelBlog\Models\Post::class)
                <a href="{{ blog_route('posts.index') }}" @class(['active' => blog_route_is('posts.index', 'posts.edit', 'posts.show')])>Posts</a>
            @endcan
            @can('create', \Vitebox\LaravelBlog\Models\Post::class)
                <a href="{{ blog_route('posts.create') }}" @class(['active' => blog_route_is('posts.create')])>New post</a>
            @endcan
            @if($blog->can($blogUser, 'posts.review') || $blog->can($blogUser, 'posts.publish'))
                <a href="{{ blog_route('review.index') }}" @class(['active' => blog_route_is('review.*')])>
                    Review queue
                    @php($pendingCount = \Vitebox\LaravelBlog\Models\Post::query()->whereIn('status', ['pending_review', 'approved'])->count())
                    @if($pendingCount)<span class="pill">{{ $pendingCount }}</span>@endif
                </a>
            @endif
            @can('blog.categories.manage')
                <a href="{{ blog_route('categories.index') }}" @class(['active' => blog_route_is('categories.*')])>Categories</a>
            @endcan
            @can('blog.tags.manage')
                <a href="{{ blog_route('tags.index') }}" @class(['active' => blog_route_is('tags.*')])>Tags</a>
            @endcan
            @can('blog.team.manage')
                <a href="{{ blog_route('team.index') }}" @class(['active' => blog_route_is('team.*')])>Team &amp; roles</a>
            @endcan
        </nav>

        <div class="whoami">
            <div class="avatar">{{ strtoupper(mb_substr($blog->userName($blogUser), 0, 1)) }}</div>
            <div>
                <div class="whoami-name">{{ $blog->userName($blogUser) }}</div>
                <div class="muted small">{{ $blogRole?->label() }}</div>
            </div>
        </div>
        <a href="{{ url('/') }}" class="muted small back-link">← Back to website</a>
    </aside>

    <main class="main">
        <header class="page-head">
            <div>
                @hasSection('breadcrumb')<div class="muted small">@yield('breadcrumb')</div>@endif
                <h1>@yield('title', 'Dashboard')</h1>
            </div>
            <div class="actions">@yield('actions')</div>
        </header>

        @include('blog::partials.flash')

        @yield('content')
    </main>
</div>

@include('blog::partials.scripts')
@stack('blog_scripts')
</body>
</html>
