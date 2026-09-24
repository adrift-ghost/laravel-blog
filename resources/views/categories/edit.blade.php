@extends('blog::layouts.app')

@section('title', 'Edit category')
@section('breadcrumb')<a href="{{ blog_route('categories.index') }}">Categories</a> / {{ $category->name }}@endsection

@section('content')
<form method="POST" action="{{ blog_route('categories.update', $category) }}" class="card" style="max-width:640px">
    @csrf @method('PUT')
    @include('blog::categories._form')
    <div class="row">
        <button class="btn btn-primary">Save</button>
        <a href="{{ blog_route('categories.index') }}" class="btn">Cancel</a>
    </div>
</form>
@endsection
