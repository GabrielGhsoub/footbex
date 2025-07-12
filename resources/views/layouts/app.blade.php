<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Football') }}</title>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=Nunito" rel="stylesheet">
    @include('flatpickr::components.style')
    <!-- Styles -->
    <link rel="stylesheet" href="{{ asset('css/soccer-theme.css') }}">

    <!-- Scripts -->
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])

    <!-- jQuery (necessary for Bootstrap's JavaScript plugins and other features) -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
</head>

<body>
    <div id="app">
        <main class="py-4">
            @yield('content')
        </main>
    </div>

    <!-- Your Custom Script -->
    <script>
        @include('flatpickr::components.script')
        $(document).ready(function() {
            $('.card').hover(
                function() { // Mouse enter
                    $(this).animate({ marginTop: "-=1%" }, 200);
                }, 
                function() { // Mouse leave
                    $(this).animate({ marginTop: "+=1%" }, 200);
                }
            );
        });
    </script>
</body>
</html>
