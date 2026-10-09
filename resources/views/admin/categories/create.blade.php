@extends('layouts.admin')

@section('title', 'Add category')
@section('heading', 'Add category')

@section('content')

<p class="mb-3">
    <a href="{{ route('admin.categories.index') }}"><i class="bi bi-arrow-left"></i> Back to categories</a>
</p>

{{-- enctype="multipart/form-data" is REQUIRED for photo upload. @csrf = security token. --}}
<form action="{{ route('admin.categories.store') }}" method="POST" enctype="multipart/form-data" novalidate>
    @csrf
    @include('admin.categories._form')
</form>

@endsection