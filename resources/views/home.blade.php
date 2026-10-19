@extends('layouts.app')

@php
// Tells search engines who you are, and that the site has a search box
$ld = [
'@context' => 'https://schema.org',
'@graph' => [
[
'@type' => 'Organization',
'name' => config('app.name'),
'url' => url('/'),
'logo' => asset('assets/images/logo.png'),
],
[
'@type' => 'WebSite',
'name' => config('app.name'),
'url' => url('/'),
'potentialAction' => [
'@type' => 'SearchAction',
'target' => route('search') . '?q={search_term_string}',
'query-input' => 'required name=search_term_string',
],
],
],
];
@endphp

@section('title', config('app.name') . ' | Home Décor & Inspiration')

@push('head')
<link rel="canonical" href="{{ url('/') }}">
{!! \App\Services\JsonLd::tag($ld) !!}
@endpush

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