<x-app-layout>
    <x-slot name="header">
        My Submissions
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-6">
                <a href="{{ route('locations.create') }}"
                   class="inline-block px-6 py-3 bg-primary-600 text-white font-bold uppercase border-3 border-black shadow-brutal hover:shadow-brutal-lg hover:translate-x-[-2px] hover:translate-y-[-2px] transition-all">
                    Submit New Location
                </a>
            </div>

            @if ($locations->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach ($locations as $location)
                        <div class="bg-white border-5 border-black shadow-brutal-lg overflow-hidden">
                            @if ($location->images->count() > 0)
                                <img src="{{ asset('storage/' . $location->images->first()->image_path) }}"
                                     alt="{{ $location->name }}"
                                     class="w-full h-48 object-cover border-b-5 border-black">
                            @endif

                            <div class="p-4">
                                <h3 class="font-bold text-xl uppercase mb-2">{{ $location->name }}</h3>
                                <p class="text-sm mb-2">{{ Str::limit($location->address, 50) }}</p>

                                <div class="flex items-center justify-between mb-4">
                                    @if ($location->average_rating)
                                        <span class="font-bold text-primary-600">
                                            ⭐ {{ number_format($location->average_rating, 1) }}
                                        </span>
                                    @else
                                        <span class="text-gray-500 text-sm">No ratings</span>
                                    @endif

                                    <span class="text-sm">
                                        {{ $location->ratings_count }} {{ Str::plural('rating', $location->ratings_count) }}
                                    </span>
                                </div>

                                <a href="{{ route('locations.show', $location) }}"
                                   class="block text-center px-4 py-2 bg-white font-bold uppercase text-sm border-3 border-black shadow-brutal hover:shadow-brutal-sm hover:translate-x-[-1px] hover:translate-y-[-1px] transition-all">
                                    View Details
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="bg-white border-5 border-black shadow-brutal-lg p-8 text-center">
                    <p class="text-xl font-bold uppercase mb-4">No Submissions Yet</p>
                    <p class="mb-6">You haven't submitted any locations yet. Be the first to share a good ice spot!</p>
                    <a href="{{ route('locations.create') }}"
                       class="inline-block px-6 py-3 bg-primary-600 text-white font-bold uppercase border-3 border-black shadow-brutal hover:shadow-brutal-lg hover:translate-x-[-2px] hover:translate-y-[-2px] transition-all">
                        Submit Your First Location
                    </a>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
