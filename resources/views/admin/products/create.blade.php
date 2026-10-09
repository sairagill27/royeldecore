@extends('layouts.admin')

@section('title', 'Add product')
@section('heading', 'Add product')

@section('content')

<p class="mb-3">
    <a href="{{ route('admin.products.index') }}"><i class="bi bi-arrow-left"></i> Back to products</a>
</p>

{{-- enctype="multipart/form-data" is REQUIRED for photo upload. @csrf = security token. --}}
<form action="{{ route('admin.products.store') }}" method="POST" enctype="multipart/form-data" novalidate>
    @csrf
    @include('admin.products._form')
</form>

@endsection