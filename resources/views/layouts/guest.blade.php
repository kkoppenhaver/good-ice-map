@extends('layouts.base')

@section('body')
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0">
        <div class="mb-8">
            @if(isset($title))
                <h1 class="text-4xl font-black uppercase tracking-wider text-black">
                    {{ $title }}
                </h1>
            @else
                <a href="/" class="flex items-center text-4xl font-black uppercase tracking-wider hover:text-primary-600 transition-colors">
                    <img src="/logo.png" alt="Good Ice Map" class="block h-12 w-auto me-4">
                    Good Ice Map
                </a>
            @endif
        </div>

        <div class="w-full sm:max-w-md px-8 py-8 bg-white border-5 border-black shadow-brutal">
            {{ $slot }}
        </div>
    </div>
@endsection
