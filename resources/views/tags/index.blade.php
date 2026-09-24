@extends('blog::layouts.app')

@section('title', 'Tags')

@section('content')
<div class="layout-side">
    <div>
        <form method="GET" class="filters">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Search tags…">
            <button class="btn">Search</button>
        </form>

        <div class="card flush">
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Name</th><th>Slug</th><th>Posts</th><th></th></tr></thead>
                    <tbody>
                    @forelse($tags as $tag)
                        <tr>
                            <td><strong>{{ $tag->name }}</strong></td>
                            <td class="small muted">{{ $tag->slug }}</td>
                            <td><a href="{{ blog_route('posts.index', ['tag' => $tag->slug]) }}">{{ $tag->posts_count }}</a></td>
                            <td style="text-align:right;white-space:nowrap">
                                <a href="{{ blog_route('tags.edit', $tag) }}" class="btn btn-sm">Edit</a>
                                <form method="POST" action="{{ blog_route('tags.destroy', $tag) }}" style="display:inline" data-confirm="Delete tag “{{ $tag->name }}”?">
                                    @csrf @method('DELETE') <button class="btn btn-sm btn-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="muted" style="text-align:center;padding:40px">No tags yet. Tags are also created while writing posts.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="pagination">{{ $tags->links('blog::partials.pagination') }}</div>
    </div>

    <div>
        <form method="POST" action="{{ blog_route('tags.store') }}" class="card">
            @csrf
            <h2>Add tag</h2>
            <div class="field">
                <label for="tag-name">Name</label>
                <input type="text" id="tag-name" name="name" required maxlength="60" value="{{ old('name') }}">
            </div>
            <div class="field">
                <div class="row" style="justify-content:space-between">
                    <label for="tag-slug" style="margin:0">Slug</label>
                    <label class="check small" style="margin:0"><input type="checkbox" id="tag-slug-auto" checked> Auto</label>
                </div>
                <input type="text" id="tag-slug" name="slug" style="margin-top:6px" data-slug-model="tag" data-slug-source="#tag-name" data-slug-auto="#tag-slug-auto" data-slug-hint="#tag-slug-hint">
                <div class="help" id="tag-slug-hint"></div>
            </div>
            <button class="btn btn-primary btn-block">Create tag</button>
        </form>

        @if($allTags->count() > 1)
            <form method="POST" action="{{ blog_route('tags.merge') }}" class="card" data-confirm="Merge the selected tags? This cannot be undone.">
                @csrf
                <h2>Merge tags</h2>
                <p class="muted small" style="margin-top:0">Posts tagged with any of the selected tags are re-tagged with the target, then the selected tags are deleted.</p>
                <div class="field">
                    <label>Tags to merge</label>
                    <select name="sources[]" multiple size="6">
                        @foreach($allTags as $t)<option value="{{ $t->id }}">{{ $t->name }}</option>@endforeach
                    </select>
                </div>
                <div class="field">
                    <label>Into</label>
                    <select name="target">
                        @foreach($allTags as $t)<option value="{{ $t->id }}">{{ $t->name }}</option>@endforeach
                    </select>
                </div>
                <button class="btn btn-block">Merge</button>
            </form>
        @endif
    </div>
</div>
@endsection
