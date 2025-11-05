<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-mono text-black bg-white">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0">
            <div class="mb-8">
                @if(isset($title))
                    <h1 class="text-4xl font-black uppercase tracking-wider text-black">
                        {{ $title }}
                    </h1>
                @else
                    <a href="/" class="text-4xl font-black uppercase tracking-wider hover:text-primary-600 transition-colors">
                        Good Ice Map
                    </a>
                @endif
            </div>

            <div class="w-full sm:max-w-md px-8 py-8 bg-white border-5 border-black shadow-brutal">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
