@extends('blog::layouts.app')

@section('title', $post->exists ? 'Edit post' : 'New post')
@section('breadcrumb')<a href="{{ blog_route('posts.index') }}">Posts</a> / {{ $post->exists ? \Illuminate\Support\Str::limit($post->title, 50) : 'New' }}@endsection

@section('actions')
    @if($post->exists)
        <a href="{{ blog_route('posts.show', $post) }}" class="btn">Preview</a>
    @endif
@endsection

@section('content')
@php
    $canPublish = $blog->can($blogUser, 'posts.publish');
    $canSubmit = $post->exists ? $blogUser->can('submit', $post) : true;
    $selectedType = old('type', $post->type);
    $typeMeta = collect($types)->map->toArray();
    $editor = config('blog.content.editor', 'textarea');
@endphp

<form method="POST" enctype="multipart/form-data"
      action="{{ $post->exists ? blog_route('posts.update', $post) : blog_route('posts.store') }}">
    @csrf
    @if($post->exists) @method('PUT') @endif
    <script type="application/json" id="blog-type-meta">@json($typeMeta)</script>

    <div class="layout-side">
        <div>
            {{-- Post type --}}
            <div class="card">
                <h2>Post type</h2>
                <div class="type-grid">
                    @foreach($types as $key => $type)
                        <label class="type-card">
                            <input type="radio" name="type" value="{{ $key }}" @checked($selectedType === $key)>
                            <span>
                                <strong>{{ $type->icon() }} {{ $type->label() }}</strong>
                                <span class="muted small">{{ $type->description() }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
                @error('type')<div class="error">{{ $message }}</div>@enderror
            </div>

            {{-- Main content --}}
            <div class="card">
                <div class="field">
                    <label for="title">Title</label>
                    <input type="text" id="title" name="title" value="{{ old('title', $post->title) }}" required maxlength="255" @class(['invalid' => $errors->has('title')])>
                    @error('title')<div class="error">{{ $message }}</div>@enderror
                </div>

                <div class="field">
                    <div class="row" style="justify-content:space-between">
                        <label for="slug" style="margin:0">Slug</label>
                        <label class="check small" style="margin:0">
                            <input type="checkbox" id="slug-auto" @checked(!old('slug') && !$post->exists)> Generate from title
                        </label>
                    </div>
                    <div class="slug-row" style="margin-top:6px">
                        <input type="text" id="slug" name="slug" value="{{ old('slug', $post->slug) }}"
                               data-slug-model="post" data-slug-source="#title" data-slug-auto="#slug-auto" data-slug-hint="#slug-hint"
                               @if($post->exists) data-slug-ignore="{{ $post->id }}" @endif
                               placeholder="auto-generated-from-title" @class(['invalid' => $errors->has('slug')])>
                    </div>
                    <div class="help" id="slug-hint"></div>
                    <div class="help">Leave empty for an automatic slug. Custom slugs are normalised and made unique.@if($post->exists && $post->published_at) Changing the slug of a published post keeps the old URL redirecting.@endif</div>
                    @error('slug')<div class="error">{{ $message }}</div>@enderror
                </div>

                <div class="field">
                    <label for="excerpt">Excerpt <span class="muted small">(optional — generated from content when empty)</span></label>
                    <textarea id="excerpt" name="excerpt" rows="3" maxlength="1000">{{ old('excerpt', $post->excerpt) }}</textarea>
                </div>

                <div class="field">
                    <label for="content">Content <span id="content-required" class="muted small">(required)</span></label>
                    @if($editor === 'trix')
                        <input id="content" type="hidden" name="content" value="{{ old('content', $post->content) }}">
                        <trix-editor input="content"></trix-editor>
                    @else
                        <textarea id="content" name="content" class="content" @class(['invalid' => $errors->has('content')])>{{ old('content', $post->content) }}</textarea>
                        <div class="help">HTML is supported (headings, lists, links, images, YouTube/Vimeo iframes). Unsafe markup is removed automatically.</div>
                    @endif
                    @error('content')<div class="error">{{ $message }}</div>@enderror
                </div>
            </div>

            {{-- Cover image --}}
            <div class="card" id="cover-section">
                <h2>Cover image <span id="cover-required" class="muted small">(required for this type)</span></h2>
                <img id="cover-preview" class="cover-preview" src="{{ $post->cover_image_url }}" alt="" @if(!$post->cover_image_url) hidden @endif>
                <div class="grid grid-2">
                    <div class="field">
                        <label for="cover_image">Upload image</label>
                        <input type="file" id="cover_image" name="cover_image" accept="image/*" data-preview="#cover-preview">
                        <div class="help">{{ strtoupper(implode(', ', config('blog.media.image_mimes'))) }} · max {{ round(config('blog.media.max_image_kb') / 1024, 1) }} MB</div>
                        @error('cover_image')<div class="error">{{ $message }}</div>@enderror
                    </div>
                    <div class="field">
                        <label for="cover_image_alt">Alt text</label>
                        <input type="text" id="cover_image_alt" name="cover_image_alt" value="{{ old('cover_image_alt', $post->cover_image_alt) }}" placeholder="Describe the image">
                    </div>
                </div>
                @if($post->cover_image)
                    <label class="check"><input type="checkbox" name="remove_cover_image" value="1"> Remove current image</label>
                @endif
            </div>

            {{-- Video --}}
            <div class="card" id="video-section">
                <h2>Video</h2>
                <div class="grid grid-2">
                    <div class="field">
                        <label for="video_url">Video URL</label>
                        <input type="url" id="video_url" name="video_url" value="{{ old('video_url', $post->video_url) }}" placeholder="https://www.youtube.com/watch?v=…">
                        <div class="help">YouTube, Vimeo or a direct .mp4 link.</div>
                        @error('video_url')<div class="error">{{ $message }}</div>@enderror
                    </div>
                    <div class="field">
                        <label for="video_file">…or upload a file</label>
                        <input type="file" id="video_file" name="video_file" accept="video/*">
                        <div class="help">{{ strtoupper(implode(', ', config('blog.media.video_mimes'))) }} · max {{ round(config('blog.media.max_video_kb') / 1024) }} MB</div>
                        @error('video_file')<div class="error">{{ $message }}</div>@enderror
                    </div>
                </div>
                @if($post->video_path)
                    <div class="row"><span class="muted small">Uploaded: {{ basename($post->video_path) }}</span>
                        <label class="check"><input type="checkbox" name="remove_video" value="1"> Remove uploaded video</label></div>
                @endif
            </div>

            {{-- Type specific fields --}}
            @foreach($types as $key => $type)
                @if(count($type->fields()))
                    <div class="card" data-type-fields="{{ $key }}" @if($selectedType !== $key) hidden @endif>
                        <h2>{{ $type->icon() }} {{ $type->label() }} details</h2>
                        <div class="grid grid-2">
                            @foreach($type->fields() as $field)
                                @include('blog::partials.field', ['field' => $field, 'post' => $post, 'active' => $selectedType === $key])
                            @endforeach
                        </div>
                    </div>
                @endif
            @endforeach

            {{-- SEO --}}
            <div class="card">
                <details @if($errors->hasAny(['meta_title', 'meta_description', 'canonical_url'])) open @endif>
                    <summary>SEO</summary>
                    <div style="margin-top:12px">
                        <div class="field">
                            <label for="meta_title">Meta title</label>
                            <input type="text" id="meta_title" name="meta_title" value="{{ old('meta_title', $post->meta_title) }}" maxlength="255" placeholder="Defaults to the post title">
                        </div>
                        <div class="field">
                            <label for="meta_description">Meta description</label>
                            <textarea id="meta_description" name="meta_description" rows="2" maxlength="500" placeholder="Defaults to the excerpt">{{ old('meta_description', $post->meta_description) }}</textarea>
                        </div>
                        <div class="field">
                            <label for="canonical_url">Canonical URL</label>
                            <input type="url" id="canonical_url" name="canonical_url" value="{{ old('canonical_url', $post->canonical_url) }}">
                        </div>
                    </div>
                </details>
            </div>
        </div>

        {{-- Sidebar --}}
        <div>
            <div class="card">
                <div class="row" style="margin-bottom:12px">
                    <strong>Status</strong><span class="spacer"></span>
                    @include('blog::partials.status', ['status' => $post->status ?? \Vitebox\LaravelBlog\Enums\PostStatus::Draft])
                </div>

                @if($post->exists)
                    <div class="field">
                        <label for="revision_note" class="small">Note for reviewers <span class="muted">(optional)</span></label>
                        <input type="text" id="revision_note" name="revision_note" maxlength="500" placeholder="What changed?">
                    </div>
                @endif

                <div class="stack">
                    <button class="btn btn-block" name="intent" value="save">Save {{ $post->exists ? 'changes' : 'draft' }}</button>

                    @if($canSubmit && in_array(($post->status ?? \Vitebox\LaravelBlog\Enums\PostStatus::Draft)->value, ['draft', 'changes_requested']))
                        <button class="btn btn-primary btn-block" name="intent" value="submit">
                            Save &amp; {{ config('blog.workflow.require_review', true) ? 'submit for review' : 'mark ready' }}
                        </button>
                    @endif

                    @if($canPublish && ($post->status === null || in_array($post->status, app(\Vitebox\LaravelBlog\Services\PostWorkflow::class)->publishableStates(), true)))
                        <div class="card" style="padding:12px;background:var(--surface-2);box-shadow:none">
                            <label for="publish_at" class="small">Publish at <span class="muted">(empty = now)</span></label>
                            <input type="datetime-local" id="publish_at" name="publish_at" value="{{ old('publish_at') }}">
                            <button class="btn btn-ok btn-block" style="margin-top:8px" name="intent" value="publish">Save &amp; publish</button>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card">
                <h3>Categories</h3>
                @if($categories->isEmpty())
                    <p class="muted small">No categories yet.@can('blog.categories.manage') <a href="{{ blog_route('categories.index') }}">Create one</a>@endcan</p>
                @else
                    <div class="cat-list">
                        @php($checked = old('categories', $selectedCategories))
                        @foreach($categories as $cat)
                            <label class="check" style="padding-left: {{ $cat->depth * 16 }}px">
                                <input type="checkbox" name="categories[]" value="{{ $cat->id }}" @checked(in_array($cat->id, array_map('intval', (array) $checked), true))>
                                {{ $cat->name }} @unless($cat->is_active)<span class="muted small">(hidden)</span>@endunless
                            </label>
                        @endforeach
                    </div>
                @endif
                <input type="hidden" name="categories[]" value="">
            </div>

            <div class="card">
                <h3>Tags</h3>
                <input type="text" name="tags" list="blog-tags" value="{{ old('tags', $selectedTags) }}" placeholder="laravel, php, tutorials">
                <datalist id="blog-tags">
                    @foreach($allTags as $tag)<option value="{{ $tag }}">@endforeach
                </datalist>
                <div class="help">Comma separated. @if($blog->can($blogUser, 'tags.create'))New tags are created automatically.@else Only existing tags are kept.@endif</div>
            </div>

            @if($canPublish)
                <div class="card">
                    <h3>Visibility</h3>
                    <input type="hidden" name="is_featured" value="0">
                    <label class="check"><input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $post->is_featured))> Featured</label>
                    <input type="hidden" name="is_pinned" value="0">
                    <label class="check"><input type="checkbox" name="is_pinned" value="1" @checked(old('is_pinned', $post->is_pinned))> Pinned to top</label>
                </div>
            @endif

            @if($post->exists)
                <div class="card">
                    <h3>History</h3>
                    @include('blog::partials.activity', ['activities' => $post->activities->take(10)])
                </div>
                @can('delete', $post)
                    <div style="margin-top:12px;text-align:right">
                        <button form="delete-post" class="link-btn danger small">Move to trash</button>
                    </div>
                @endcan
            @endif
        </div>
    </div>
</form>

@if($post->exists)
    @can('delete', $post)
        <form id="delete-post" method="POST" action="{{ blog_route('posts.destroy', $post) }}" data-confirm="Move this post to trash?">
            @csrf @method('DELETE')
        </form>
    @endcan
@endif
@endsection
