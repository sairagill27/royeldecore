@extends('layouts.admin')

@section('title', 'Add an answer')
@section('heading', 'Add an answer')

@section('content')

<p class="mb-3"><a href="{{ route('admin.assistant-answers.index') }}"><i class="bi bi-arrow-left"></i> Back to answers</a></p>

{{-- @csrf = security token --}}
<form action="{{ route('admin.assistant-answers.store') }}" method="POST" novalidate>
    @csrf
    @include('admin.assistant._form')
</form>

@endsection