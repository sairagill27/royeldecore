@extends('layouts.admin')

@section('title', 'Edit post')
@section('heading', 'Edit post')

@section('content')

<p class="mb-3">
    <a href="{{ route('admin.posts.index') }}"><i class="bi bi-arrow-left"></i> Back to posts</a>
</p>

{{-- @method('PUT') tells Laravel "this is an update" --}}
<form action="{{ route('admin.posts.update', $post) }}" method="POST" enctype="multipart/form-data" novalidate>
    @csrf
    @method('PUT')
    @include('admin.posts._form')
</form>

@endsection