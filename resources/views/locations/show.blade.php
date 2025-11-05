<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $location->name }} - Good Ice Map</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-mono bg-white text-black">
    <nav class="bg-white border-b-5 border-black">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="{{ route('home') }}" class="text-2xl font-bold uppercase tracking-wider hover:text-primary-600">
                        Good Ice Map
                    </a>
                </div>

                <div class="flex items-center space-x-4">
                    @auth
                        <a href="{{ route('locations.create') }}"
                           class="px-6 py-2 bg-primary-600 text-white font-bold uppercase border-3 border-black shadow-brutal hover:shadow-brutal-lg hover:translate-x-[-2px] hover:translate-y-[-2px] transition-all">
                            Submit Location
                        </a>
                    @else
                        <a href="{{ route('login') }}"
                           class="px-6 py-2 bg-primary-600 text-white font-bold uppercase border-3 border-black shadow-brutal hover:shadow-brutal-lg hover:translate-x-[-2px] hover:translate-y-[-2px] transition-all">
                            Login to Submit
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-6xl mx-auto px-4 py-12">
        @if (session('success'))
            <div class="mb-6 p-4 bg-primary-100 border-3 border-black shadow-brutal">
                <p class="font-bold">{{ session('success') }}</p>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Left Column: Images and Info -->
            <div class="space-y-6">
                <!-- Images -->
                @if ($location->images->count() > 0)
                    <div class="border-5 border-black shadow-brutal-lg overflow-hidden">
                        <img src="{{ asset('storage/' . $location->images->first()->image_path) }}"
                             alt="{{ $location->name }}"
                             class="w-full h-96 object-cover">
                    </div>

                    @if ($location->images->count() > 1)
                        <div class="grid grid-cols-3 gap-4">
                            @foreach ($location->images->skip(1) as $image)
                                <div class="border-3 border-black shadow-brutal overflow-hidden">
                                    <img src="{{ asset('storage/' . $image->image_path) }}"
                                         alt="{{ $location->name }}"
                                         class="w-full h-24 object-cover">
                                </div>
                            @endforeach
                        </div>
                    @endif
                @endif

                <!-- Location Info -->
                <div class="border-5 border-black shadow-brutal-lg p-6 bg-white">
                    <h1 class="text-3xl font-bold uppercase mb-4">{{ $location->name }}</h1>

                    <div class="space-y-3">
                        <div>
                            <span class="font-bold uppercase text-sm">Address:</span>
                            <p class="mt-1">{{ $location->address }}</p>
                        </div>

                        @if ($location->description)
                            <div>
                                <span class="font-bold uppercase text-sm">Description:</span>
                                <p class="mt-1">{{ $location->description }}</p>
                            </div>
                        @endif

                        <div>
                            <span class="font-bold uppercase text-sm">Submitted by:</span>
                            <p class="mt-1">{{ $location->submittedBy->name }}</p>
                        </div>

                        <div>
                            <span class="font-bold uppercase text-sm">Coordinates:</span>
                            <p class="mt-1">{{ $location->latitude }}, {{ $location->longitude }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Rating and Reviews -->
            <div class="space-y-6">
                <!-- Average Rating -->
                <div class="border-5 border-black shadow-brutal-lg p-6 bg-white text-center">
                    <h2 class="text-xl font-bold uppercase mb-4">Rating</h2>
                    @if ($location->average_rating)
                        <div class="text-6xl font-bold text-primary-600 mb-2">
                            {{ number_format($location->average_rating, 1) }}
                        </div>
                        <div class="text-lg">
                            {{ str_repeat('⭐', round($location->average_rating)) }}
                        </div>
                        <p class="mt-2 text-sm">Based on {{ $location->total_ratings }} {{ Str::plural('rating', $location->total_ratings) }}</p>
                    @else
                        <p class="text-lg mb-4">No ratings yet</p>
                        <p class="text-sm">Be the first to rate this location!</p>
                    @endif
                </div>

                <!-- Submit Rating -->
                @auth
                    <div class="border-5 border-black shadow-brutal-lg p-6 bg-white">
                        <h2 class="text-xl font-bold uppercase mb-4">Rate This Location</h2>
                        <form method="POST" action="{{ route('locations.rate', $location) }}" x-data="{ rating: {{ auth()->user()->ratings()->where('location_id', $location->id)->first()?->rating ?? 0 }} }">
                            @csrf

                            <!-- Star Rating -->
                            <div class="mb-4">
                                <label class="block font-bold uppercase text-sm mb-2">Your Rating *</label>
                                <div class="flex space-x-2">
                                    @for ($i = 1; $i <= 5; $i++)
                                        <button type="button"
                                                @click="rating = {{ $i }}"
                                                class="text-4xl transition-all"
                                                :class="rating >= {{ $i }} ? 'text-primary-600' : 'text-gray-300'">
                                            ⭐
                                        </button>
                                    @endfor
                                </div>
                                <input type="hidden" name="rating" x-model="rating">
                                @error('rating')
                                    <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Review -->
                            <div class="mb-4">
                                <label for="review" class="block font-bold uppercase text-sm mb-2">
                                    Review (Optional)
                                </label>
                                <textarea
                                    id="review"
                                    name="review"
                                    rows="4"
                                    class="w-full px-4 py-3 border-3 border-black font-mono focus:outline-none focus:border-primary-600"
                                >{{ auth()->user()->ratings()->where('location_id', $location->id)->first()?->review }}</textarea>
                                @error('review')
                                    <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                                @enderror
                            </div>

                            <button type="submit"
                                    class="w-full px-6 py-3 bg-primary-600 text-white font-bold uppercase border-3 border-black shadow-brutal hover:shadow-brutal-lg hover:translate-x-[-2px] hover:translate-y-[-2px] transition-all">
                                Submit Rating
                            </button>
                        </form>
                    </div>
                @else
                    <div class="border-5 border-black shadow-brutal-lg p-6 bg-white text-center">
                        <p class="mb-4">Please log in to rate this location</p>
                        <a href="{{ route('login') }}"
                           class="inline-block px-6 py-3 bg-primary-600 text-white font-bold uppercase border-3 border-black shadow-brutal hover:shadow-brutal-lg hover:translate-x-[-2px] hover:translate-y-[-2px] transition-all">
                            Login
                        </a>
                    </div>
                @endauth

                <!-- Reviews List -->
                @if ($location->ratings->where('review', '!=', null)->count() > 0)
                    <div class="border-5 border-black shadow-brutal-lg p-6 bg-white">
                        <h2 class="text-xl font-bold uppercase mb-4">Reviews</h2>
                        <div class="space-y-4">
                            @foreach ($location->ratings->where('review', '!=', null) as $rating)
                                <div class="border-3 border-black p-4">
                                    <div class="flex justify-between items-start mb-2">
                                        <span class="font-bold">{{ $rating->user->name }}</span>
                                        <span class="text-primary-600">{{ str_repeat('⭐', $rating->rating) }}</span>
                                    </div>
                                    <p class="text-sm">{{ $rating->review }}</p>
                                    <p class="text-xs text-gray-500 mt-2">{{ $rating->created_at->diffForHumans() }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</body>
</html>
