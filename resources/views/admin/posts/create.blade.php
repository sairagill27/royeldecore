@extends('layouts.admin')

@section('title', 'Write a post')
@section('heading', 'Write a post')

@section('content')

<p class="mb-3">
    <a href="{{ route('admin.posts.index') }}"><i class="bi bi-arrow-left"></i> Back to posts</a>
</p>

{{-- enctype="multipart/form-data" is REQUIRED for photo upload. @csrf = security token. --}}
<form action="{{ route('admin.posts.store') }}" method="POST" enctype="multipart/form-data" novalidate>
    @csrf
    @include('admin.posts._form')
</form>

@endsection