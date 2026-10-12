{{-- Standalone frame for every error page. It makes NO database calls,
     so it still works when the database is down. --}}
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('code') @yield('title') | {{ config('app.name') }}</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB"
        crossorigin="anonymous">
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
</head>

<body class="sgh-error-body">
    <main class="sgh-error">
        <a href="{{ url('/') }}">
            <img src="{{ asset('assets/images/logo.png') }}" alt="{{ config('app.name') }}" height="56">
        </a>

        <p class="sgh-error-code" aria-hidden="true">@yield('code')</p>
        <h1 class="h3 mb-2">@yield('title')</h1>
        <p class="text-secondary mb-4">@yield('message')</p>

        <div class="d-flex flex-wrap justify-content-center gap-2">
            <a href="{{ url('/') }}" class="btn btn-primary rounded-pill px-4">Back to homepage</a>
            <a href="{{ url('/shop') }}" class="btn btn-outline-primary rounded-pill px-4">Visit the shop</a>
        </div>
    </main>
</body>

</html>