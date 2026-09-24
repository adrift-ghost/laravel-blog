@extends('blog::layouts.app')

@section('title', 'Review queue')

@section('content')
@php
    $sections = [
        ['Pending review', $pending, 'Submitted by writers — approve or send back with comments.', 'submitted_at'],
        ['Approved — ready to publish', $approved, 'Reviewed and waiting for a publisher.', 'reviewed_at'],
        ['Scheduled', $scheduled, 'Will go live automatically.', 'published_at'],
    ];
@endphp

<div class="stack" style="gap:16px">
    @foreach($sections as [$heading, $items, $hint, $dateColumn])
        <div class="card flush">
            <div style="padding:16px 18px 6px">
                <h2 style="margin-bottom:2px">{{ $heading }} <span class="muted">({{ $items->count() }})</span></h2>
                <div class="muted small">{{ $hint }}</div>
            </div>
            <div class="table-wrap">
                <table>
                    <tbody>
                    @forelse($items as $post)
                        <tr>
                            <td><a href="{{ blog_route('posts.show', $post) }}"><strong>{{ $post->title }}</strong></a>
                                <div class="muted small">{{ $post->postType()->icon() }} {{ $post->postType()->label() }}</div></td>
                            <td class="small">{{ $blog->userName($post->author) }}</td>
                            <td class="small muted">{{ $post->{$dateColumn}?->diffForHumans() }}</td>
                            <td style="text-align:right"><a class="btn btn-sm" href="{{ blog_route('posts.show', $post) }}">Open</a></td>
                        </tr>
                    @empty
                        <tr><td class="muted" style="padding:18px">Nothing here.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach
</div>
@endsection
