@if($activities->isEmpty())
    <p class="muted">No activity yet.</p>
@else
    <ul class="timeline">
        @foreach($activities as $a)
            <li>
                <strong>{{ $a->user ? $blog->userName($a->user) : 'System' }}</strong>
                <span class="muted">{{ $a->label }}</span>
                @if(!empty($showPost) && $a->post)
                    — <a href="{{ blog_route('posts.show', $a->post) }}">{{ \Illuminate\Support\Str::limit($a->post->title, 60) }}</a>
                @endif
                <div class="muted small">{{ $a->created_at?->diffForHumans() }}</div>
                @if($a->comment)
                    <div class="comment">{{ $a->comment }}</div>
                @endif
            </li>
        @endforeach
    </ul>
@endif
