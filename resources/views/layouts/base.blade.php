<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@yield('title', config('app.name', 'Good Ice Map'))</title>

        @yield('meta')

        <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
        <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
        <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
        <link rel="manifest" href="/site.webmanifest">

        <script>
            if ('serviceWorker' in navigator) {
                window.addEventListener('load', () => {
                    navigator.serviceWorker.register('/sw.js').catch(() => {});
                });
            }
        </script>

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        @if (config('services.fathom.site_id') && app()->environment('production'))
            <!-- Fathom - beautiful, simple website analytics -->
            <script src="https://cdn.usefathom.com/script.js" data-site="{{ config('services.fathom.site_id') }}" defer></script>
            <!-- / Fathom -->
        @endif

        @yield('head')
        @stack('head-scripts')
    </head>
    <body class="font-mono bg-white text-black">
        @yield('body')

        @stack('scripts')

        @if (session('fathom_event'))
            {{-- Conversion event flashed by the previous request (signup, submission, rating). --}}
            <script>
                window.addEventListener('load', function () {
                    if (window.fathom) { window.fathom.trackEvent(@json(session('fathom_event'))); }
                });
            </script>
        @endif
    </body>
</html>
