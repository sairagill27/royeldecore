@extends('layouts.app')
@section('title', 'SGHomeTrends | Home Décor & Inspiration')
@section('content')

{{-- Section 1: Hero --}}
@include('home.hero')

{{-- Section 2: Featured Categories --}}
    @include('home.categories')

{{-- More sections (categories, products, blog...) will be added here later --}}

@endsection

  