@extends('layouts.app')

@section('title', config('app.name') . ' | Home Décor & Inspiration')

@section('content')

{{-- Section 1: Hero --}}
@include('home.hero')

{{-- Section 2: Featured Categories --}}
@include('home.categories')

{{-- Section 3: Featured Products --}}
@include('home.products')

{{-- Section 4: Latest from the blog --}}
@include('home.blog')

@endsection