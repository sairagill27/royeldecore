@extends('layouts.admin')

@section('title', 'Edit affiliate link')
@section('heading', 'Edit affiliate link')

@section('content')

<p class="mb-3"><a href="{{ route('admin.affiliate-links.index') }}"><i class="bi bi-arrow-left"></i> Back to links</a></p>

{{-- @method('PUT') tells Laravel "this is an update" --}}
<form action="{{ route('admin.affiliate-links.update', $link) }}" method="POST" enctype="multipart/form-data" novalidate>
    @csrf
    @method('PUT')
    @include('admin.affiliate._form')
</form>

@endsection