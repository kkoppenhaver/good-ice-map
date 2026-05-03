<x-app-layout>
    <x-slot name="header">
        Moderation Queue
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-6 p-4 bg-primary-100 border-3 border-black shadow-brutal">
                    <p class="font-bold">{{ session('success') }}</p>
                </div>
            @endif

            @if ($locations->isEmpty())
                <div class="bg-white border-5 border-black shadow-brutal-lg p-8 text-center">
                    <p class="text-xl font-bold uppercase mb-2">Queue Empty</p>
                    <p>No pending submissions to review. Nice work.</p>
                </div>
            @else
                <div class="space-y-6">
                    @foreach ($locations as $location)
                        <div class="bg-white border-5 border-black shadow-brutal-lg overflow-hidden">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-0">
                                <div class="md:col-span-1">
                                    @if ($location->images->isNotEmpty())
                                        <img src="{{ $location->images->first()->url }}"
                                             alt="{{ $location->name }}"
                                             class="w-full h-full object-cover border-r-5 border-black">
                                    @else
                                        <div class="w-full h-48 bg-gray-100 border-r-5 border-black flex items-center justify-center">
                                            <span class="text-sm uppercase font-bold text-gray-500">No image</span>
                                        </div>
                                    @endif
                                </div>

                                <div class="md:col-span-2 p-6">
                                    <h2 class="font-bold text-2xl uppercase mb-3">{{ $location->name }}</h2>

                                    <dl class="space-y-2 text-sm mb-6">
                                        <div>
                                            <dt class="font-bold uppercase text-xs">Address:</dt>
                                            <dd>{{ $location->address }}</dd>
                                        </div>

                                        @if ($location->description)
                                            <div>
                                                <dt class="font-bold uppercase text-xs">Description:</dt>
                                                <dd>{{ $location->description }}</dd>
                                            </div>
                                        @endif

                                        <div>
                                            <dt class="font-bold uppercase text-xs">Submitted by:</dt>
                                            <dd>{{ $location->submittedBy?->name ?? 'Unknown' }} · {{ $location->created_at->diffForHumans() }}</dd>
                                        </div>

                                        <div>
                                            <dt class="font-bold uppercase text-xs">Coordinates:</dt>
                                            <dd>{{ $location->latitude }}, {{ $location->longitude }}</dd>
                                        </div>
                                    </dl>

                                    <div class="flex flex-wrap gap-2">
                                        <a href="{{ route('locations.show', $location) }}"
                                           class="px-4 py-2 bg-white font-bold uppercase text-sm border-3 border-black shadow-brutal hover:shadow-brutal-sm hover:translate-x-[-1px] hover:translate-y-[-1px] transition-all">
                                            View
                                        </a>

                                        <form method="POST" action="{{ route('admin.locations.approve', $location) }}">
                                            @csrf
                                            <button type="submit"
                                                    class="px-4 py-2 bg-primary-600 text-white font-bold uppercase text-sm border-3 border-black shadow-brutal hover:shadow-brutal-sm hover:translate-x-[-1px] hover:translate-y-[-1px] transition-all">
                                                Approve
                                            </button>
                                        </form>

                                        <form method="POST" action="{{ route('admin.locations.reject', $location) }}">
                                            @csrf
                                            <button type="submit"
                                                    class="px-4 py-2 bg-red-600 text-white font-bold uppercase text-sm border-3 border-black shadow-brutal hover:shadow-brutal-sm hover:translate-x-[-1px] hover:translate-y-[-1px] transition-all">
                                                Reject
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
