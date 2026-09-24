@foreach(['blog_success' => 'success', 'blog_error' => 'error', 'blog_warning' => 'warning'] as $key => $kind)
    @if(session($key))
        <div class="alert alert-{{ $kind }}" role="status">{{ session($key) }}</div>
    @endif
@endforeach

@if($errors->any())
    <div class="alert alert-error" role="alert">
        <strong>Please fix the following:</strong>
        <ul style="margin:6px 0 0 18px;padding:0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
