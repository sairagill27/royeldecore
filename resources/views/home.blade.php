@extends('layouts.app')

@section('title', 'SGHomeTrends | Home Décor & Inspiration')

@section('content')

<!-- =========================
     SGHomeTrends Hero Carousel
     ========================= -->

<div id="sghHeroCarousel"
    class="carousel slide"
    data-bs-ride="carousel">

    <div class="carousel-inner">

        <!-- Slide 1 -->
        <div class="carousel-item active"
            data-bs-interval="5000">

            <img src="{{ asset('assets/images/hero-home.webp.jfif') }}"
                class="d-block w-100 sgh-hero-image"
                alt="Modern home interior">

            <div class="carousel-caption d-block">
                <h1>Beautiful Ideas for a Home You Love</h1>
                <p>Discover inspiring home décor and interior design ideas.</p>
            </div>

        </div>


        <!-- Slide 2 -->
        <div class="carousel-item"
            data-bs-interval="5000">

            <img src="{{ asset('assets/images/hero-home.webp.jfif') }}"
                class="d-block w-100 sgh-hero-image"
                alt="Elegant home décor">

            <div class="carousel-caption d-block">
                <h2>Find Your Perfect Design Style</h2>
                <p>Explore modern, minimalist, Japandi, Scandinavian and more.</p>
            </div>

        </div>


        <!-- Slide 3 -->
        <div class="carousel-item"
            data-bs-interval="5000">

            <img src="{{ asset('assets/images/hero-home.webp.jfif') }}"
                class="d-block w-100 sgh-hero-image"
                alt="Stylish home interior">

            <div class="carousel-caption d-block">
                <h2>Transform Your Space</h2>
                <p>Simple and beautiful ideas for every room in your home.</p>
            </div>

        </div>

    </div>


    <!-- Previous -->
    <button class="carousel-control-prev"
        type="button"
        data-bs-target="#sghHeroCarousel"
        data-bs-slide="prev">

        <span class="carousel-control-prev-icon"
            aria-hidden="true"></span>

        <span class="visually-hidden">Previous</span>

    </button>


    <!-- Next -->
    <button class="carousel-control-next"
        type="button"
        data-bs-target="#sghHeroCarousel"
        data-bs-slide="next">

        <span class="carousel-control-next-icon"
            aria-hidden="true"></span>

        <span class="visually-hidden">Next</span>

    </button>


</div>

@endsection