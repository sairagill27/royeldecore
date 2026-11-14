@extends('layouts.admin')

@section('title', 'Add an affiliate link')
@section('heading', 'Add an affiliate link')

@section('content')

<p class="mb-3"><a href="{{ route('admin.affiliate-links.index') }}"><i class="bi bi-arrow-left"></i> Back to links</a></p>

{{-- enctype="multipart/form-data" is REQUIRED for photo upload. @csrf = security token. --}}
<form action="{{ route('admin.affiliate-links.store') }}" method="POST" enctype="multipart/form-data" novalidate>
    @csrf
    @include('admin.affiliate._form')
</form>

@endsection