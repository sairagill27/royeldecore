@extends('layouts.admin')

@section('title', 'Edit product')
@section('heading', 'Edit product')

@section('content')

<p class="mb-3">
    <a href="{{ route('admin.products.index') }}"><i class="bi bi-arrow-left"></i> Back to products</a>
</p>

{{-- A browser form can only send POST, so @method('PUT') tells Laravel "this is an update" --}}
<form action="{{ route('admin.products.update', $product) }}" method="POST" enctype="multipart/form-data" novalidate>
    @csrf
    @method('PUT')
    @include('admin.products._form')
</form>

@endsection