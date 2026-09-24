@extends('blog::layouts.app')

@section('title', 'Edit tag')
@section('breadcrumb')<a href="{{ blog_route('tags.index') }}">Tags</a> / {{ $tag->name }}@endsection

@section('content')
<form method="POST" action="{{ blog_route('tags.update', $tag) }}" class="card" style="max-width:560px">
    @csrf @method('PUT')
    <div class="field">
        <label for="tag-name">Name</label>
        <input type="text" id="tag-name" name="name" required maxlength="60" value="{{ old('name', $tag->name) }}">
    </div>
    <div class="field">
        <div class="row" style="justify-content:space-between">
            <label for="tag-slug" style="margin:0">Slug</label>
            <label class="check small" style="margin:0"><input type="checkbox" id="tag-slug-auto"> Auto</label>
        </div>
        <input type="text" id="tag-slug" name="slug" value="{{ old('slug', $tag->slug) }}" style="margin-top:6px"
               data-slug-model="tag" data-slug-source="#tag-name" data-slug-auto="#tag-slug-auto" data-slug-hint="#tag-slug-hint" data-slug-ignore="{{ $tag->id }}">
        <div class="help" id="tag-slug-hint"></div>
    </div>
    <div class="field">
        <label for="tag-desc">Description</label>
        <textarea id="tag-desc" name="description" rows="3">{{ old('description', $tag->description) }}</textarea>
    </div>
    <div class="row">
        <button class="btn btn-primary">Save</button>
        <a href="{{ blog_route('tags.index') }}" class="btn">Cancel</a>
    </div>
</form>
@endsection
