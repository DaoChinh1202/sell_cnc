<!doctype html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'InApp Admin')</title>
    @include('partials.favicon')
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
</head>
<body>
    <div id="overlay" class="overlay"></div>
    @include('partials.topbar')
    @include('partials.sidebar')

    <main id="content" class="content py-10">
        <div class="container-fluid">
            @yield('content')
            @include('partials.footer')
        </div>
    </main>

    @stack('scripts')
</body>
</html>
