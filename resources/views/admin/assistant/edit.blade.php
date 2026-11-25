@extends('layouts.admin')

@section('title', 'Edit answer')
@section('heading', 'Edit answer')

@section('content')

<p class="mb-3"><a href="{{ route('admin.assistant-answers.index') }}"><i class="bi bi-arrow-left"></i> Back to answers</a></p>

{{-- @method('PUT') tells Laravel "this is an update" --}}
<form action="{{ route('admin.assistant-answers.update', $answer) }}" method="POST" novalidate>
    @csrf
    @method('PUT')
    @include('admin.assistant._form')
</form>

@endsection