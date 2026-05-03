<x-app-layout>
    <x-slot name="header">
        Edit Location
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white border-5 border-black shadow-brutal-lg p-8">
                <div class="text-center mb-6">
                    <h2 class="font-bold uppercase text-2xl mb-2">Edit Location</h2>
                    <p class="text-gray-600">Update the name or description for this location.</p>
                    <p class="text-xs text-gray-500 mt-2">Address and coordinates can't be changed — submit a new location if those need updating.</p>
                </div>

                <form method="POST" action="{{ route('locations.update', $location) }}" class="space-y-6">
                    @csrf
                    @method('PATCH')

                    <div>
                        <label for="name" class="block font-bold uppercase text-sm mb-2">
                            Location Name *
                        </label>
                        <input
                            type="text"
                            id="name"
                            name="name"
                            value="{{ old('name', $location->name) }}"
                            required
                            class="w-full px-4 py-3 border-3 border-black font-mono focus:outline-none focus:border-primary-600 @error('name') border-red-600 @enderror"
                        />
                        @error('name')
                            <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="description" class="block font-bold uppercase text-sm mb-2">
                            Description
                        </label>
                        <textarea
                            id="description"
                            name="description"
                            rows="4"
                            placeholder="Tell us about the ice quality, accessibility, etc."
                            class="w-full px-4 py-3 border-3 border-black font-mono focus:outline-none focus:border-primary-600 @error('description') border-red-600 @enderror"
                        >{{ old('description', $location->description) }}</textarea>
                        @error('description')
                            <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <span class="block font-bold uppercase text-sm mb-2">Address (read-only)</span>
                        <p class="px-4 py-3 border-3 border-gray-300 bg-gray-50 font-mono text-sm">{{ $location->address }}</p>
                    </div>

                    <div class="flex items-center justify-end space-x-4">
                        <a href="{{ route('locations.show', $location) }}"
                           class="px-6 py-3 bg-white font-bold uppercase border-3 border-black shadow-brutal hover:shadow-brutal-lg hover:translate-x-[-2px] hover:translate-y-[-2px] transition-all">
                            Cancel
                        </a>
                        <button
                            type="submit"
                            class="px-6 py-3 bg-primary-600 text-white font-bold uppercase border-3 border-black shadow-brutal hover:shadow-brutal-lg hover:translate-x-[-2px] hover:translate-y-[-2px] transition-all">
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
