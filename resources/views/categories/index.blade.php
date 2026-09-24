@extends('blog::layouts.app')

@section('title', 'Categories')

@section('content')
<div class="layout-side">
    <div class="card flush">
        <div class="table-wrap">
            <table>
                <thead><tr><th>Name</th><th>Slug</th><th>Posts</th><th>Status</th><th></th></tr></thead>
                <tbody>
                @forelse($categories as $cat)
                    <tr>
                        <td style="padding-left: {{ 12 + $cat->depth * 22 }}px">
                            @if($cat->color)<span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:{{ str_starts_with($cat->color, '#') ? $cat->color : '#'.$cat->color }};margin-right:6px"></span>@endif
                            @if($cat->depth)<span class="muted">↳</span>@endif
                            <strong>{{ $cat->name }}</strong>
                        </td>
                        <td class="small muted">{{ $cat->slug }}</td>
                        <td><a href="{{ blog_route('posts.index', ['category' => $cat->id]) }}">{{ $cat->posts_count }}</a></td>
                        <td>@if($cat->is_active)<span class="badge badge-green">Visible</span>@else<span class="badge badge-gray">Hidden</span>@endif</td>
                        <td style="text-align:right;white-space:nowrap">
                            <a href="{{ blog_route('categories.edit', $cat) }}" class="btn btn-sm">Edit</a>
                            <form method="POST" action="{{ blog_route('categories.destroy', $cat) }}" style="display:inline" data-confirm="Delete “{{ $cat->name }}”? Sub-categories move up one level; posts are kept.">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="muted" style="text-align:center;padding:40px">No categories yet — add your first one.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <form method="POST" action="{{ blog_route('categories.store') }}" class="card">
        @csrf
        <h2>Add category</h2>
        @include('blog::categories._form')
        <button class="btn btn-primary btn-block">Create category</button>
    </form>
</div>
@endsection
