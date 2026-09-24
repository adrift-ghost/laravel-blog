@extends('blog::layouts.app')

@section('title', $post->title)
@section('breadcrumb')<a href="{{ blog_route('posts.index') }}">Posts</a> / Preview @endsection

@section('actions')
    @can('update', $post)
        <a href="{{ blog_route('posts.edit', $post) }}" class="btn btn-primary">Edit</a>
    @endcan
@endsection

@section('content')
@php
    $type = $post->postType();
    $video = $type->video() !== 'none' ? $post->video : null;
@endphp
<div class="layout-side">
    <article class="card">
        <div class="row" style="margin-bottom:10px">
            <span class="chip">{{ $type->icon() }} {{ $type->label() }}</span>
            @foreach($post->categories as $cat)<span class="chip">{{ $cat->name }}</span>@endforeach
            <span class="spacer"></span>
            <span class="muted small">{{ $post->reading_time }} min read · {{ number_format($post->views) }} views</span>
        </div>

        @if($post->cover_image_url)
            <img src="{{ $post->cover_image_url }}" alt="{{ $post->cover_image_alt }}" style="width:100%;max-height:380px;object-fit:cover;border-radius:10px;margin-bottom:16px">
        @endif

        @if($post->field('subtitle'))<p class="muted" style="font-size:18px;margin-top:0">{{ $post->field('subtitle') }}</p>@endif

        @if($video)
            <div style="margin-bottom:16px">
                @if($video['embed_url'])
                    <iframe src="{{ $video['embed_url'] }}" style="width:100%;aspect-ratio:16/9;border:0;border-radius:10px" allowfullscreen title="{{ $post->title }}"></iframe>
                @else
                    <video src="{{ $video['url'] }}" controls style="width:100%;border-radius:10px"></video>
                @endif
            </div>
        @endif

        @php($details = collect($type->present($post))->except(['video', 'transcript', 'references', 'subtitle'])->filter(fn ($v) => $v !== null && $v !== '' && $v !== false))
        @if($details->isNotEmpty())
            <div class="card" style="background:var(--surface-2);box-shadow:none;margin-bottom:16px">
                <div class="grid grid-2">
                    @foreach($details as $key => $value)
                        <div><div class="muted small">{{ \Illuminate\Support\Str::headline($key) }}</div>
                            <div>
                                @if($value === true) Yes
                                @elseif(is_string($value) && preg_match('~^https?://~', $value)) <a href="{{ $value }}" target="_blank" rel="noopener">{{ \Illuminate\Support\Str::limit($value, 48) }}</a>
                                @elseif(is_string($value) && preg_match('~^\d{4}-\d\d-\d\dT~', $value)) {{ \Illuminate\Support\Carbon::parse($value)->toDayDateTimeString() }}
                                @else {{ is_scalar($value) ? $value : json_encode($value) }}
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @if($post->excerpt)<p style="font-size:17px"><em>{{ $post->excerpt }}</em></p>@endif

        <div class="prose">{!! $post->content !!}</div>

        @if($post->field('references'))
            <h3 style="margin-top:24px">References</h3>
            <div class="comment">{{ $post->field('references') }}</div>
        @endif

        @if($post->tags->isNotEmpty())
            <div class="chips" style="margin-top:20px">@foreach($post->tags as $tag)<span class="chip">#{{ $tag->name }}</span>@endforeach</div>
        @endif
    </article>

    <div>
        @include('blog::partials.workflow', ['post' => $post])

        <div class="card">
            <h3>Details</h3>
            <table class="small">
                <tr><td class="muted">Author</td><td>{{ $blog->userName($post->author) }}</td></tr>
                <tr><td class="muted">Slug</td><td>/{{ $post->slug }}</td></tr>
                @if($post->reviewer_id)<tr><td class="muted">Reviewed by</td><td>{{ $blog->userName($post->reviewer) }}</td></tr>@endif
                @if($post->published_at)<tr><td class="muted">{{ $post->status->value === 'scheduled' ? 'Scheduled for' : 'Published' }}</td><td>{{ $post->published_at->toDayDateTimeString() }}</td></tr>@endif
                <tr><td class="muted">Created</td><td>{{ $post->created_at->toDayDateTimeString() }}</td></tr>
                <tr><td class="muted">Updated</td><td>{{ $post->updated_at->diffForHumans() }}</td></tr>
            </table>
        </div>

        <div class="card">
            <h3>History &amp; review comments</h3>
            @include('blog::partials.activity', ['activities' => $post->activities()->with('user')->limit(20)->get()])
        </div>
    </div>
</div>
@endsection
