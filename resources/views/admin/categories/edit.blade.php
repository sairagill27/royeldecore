@extends('layouts.admin')

@section('title', 'Edit category')
@section('heading', 'Edit category')

@section('content')

<p class="mb-3">
    <a href="{{ route('admin.categories.index') }}"><i class="bi bi-arrow-left"></i> Back to categories</a>
</p>

{{-- @method('PUT') tells Laravel "this is an update" --}}
<form action="{{ route('admin.categories.update', $category) }}" method="POST" enctype="multipart/form-data" novalidate>
    @csrf
    @method('PUT')
    @include('admin.categories._form')
</form>

@endsection