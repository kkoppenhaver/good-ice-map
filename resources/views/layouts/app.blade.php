@extends('layouts.base')

@section('body')
    <div class="min-h-screen">
        @include('layouts.navigation', ['header' => $header ?? null])

        <main>
            {{ $slot }}
        </main>
    </div>
@endsection
