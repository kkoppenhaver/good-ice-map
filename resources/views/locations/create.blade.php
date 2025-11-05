<x-app-layout>
    <x-slot name="header">
        Submit New Location
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white border-5 border-black shadow-brutal-lg p-8">
                <form method="POST" action="{{ route('locations.store') }}" enctype="multipart/form-data" class="space-y-6">
                    @csrf

                    <!-- Name -->
                    <div>
                        <label for="name" class="block font-bold uppercase text-sm mb-2">
                            Location Name *
                        </label>
                        <input
                            type="text"
                            id="name"
                            name="name"
                            value="{{ old('name') }}"
                            required
                            class="w-full px-4 py-3 border-3 border-black font-mono focus:outline-none focus:border-primary-600 @error('name') border-red-600 @enderror"
                        />
                        @error('name')
                            <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Address -->
                    <div>
                        <label for="address" class="block font-bold uppercase text-sm mb-2">
                            Address *
                        </label>
                        <input
                            type="text"
                            id="address"
                            name="address"
                            value="{{ old('address') }}"
                            required
                            class="w-full px-4 py-3 border-3 border-black font-mono focus:outline-none focus:border-primary-600 @error('address') border-red-600 @enderror"
                        />
                        @error('address')
                            <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Coordinates -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="latitude" class="block font-bold uppercase text-sm mb-2">
                                Latitude *
                            </label>
                            <input
                                type="number"
                                step="any"
                                id="latitude"
                                name="latitude"
                                value="{{ old('latitude') }}"
                                required
                                placeholder="37.7749"
                                class="w-full px-4 py-3 border-3 border-black font-mono focus:outline-none focus:border-primary-600 @error('latitude') border-red-600 @enderror"
                            />
                            @error('latitude')
                                <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="longitude" class="block font-bold uppercase text-sm mb-2">
                                Longitude *
                            </label>
                            <input
                                type="number"
                                step="any"
                                id="longitude"
                                name="longitude"
                                value="{{ old('longitude') }}"
                                required
                                placeholder="-122.4194"
                                class="w-full px-4 py-3 border-3 border-black font-mono focus:outline-none focus:border-primary-600 @error('longitude') border-red-600 @enderror"
                            />
                            @error('longitude')
                                <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Description -->
                    <div>
                        <label for="description" class="block font-bold uppercase text-sm mb-2">
                            Description
                        </label>
                        <textarea
                            id="description"
                            name="description"
                            rows="4"
                            class="w-full px-4 py-3 border-3 border-black font-mono focus:outline-none focus:border-primary-600 @error('description') border-red-600 @enderror"
                        >{{ old('description') }}</textarea>
                        @error('description')
                            <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Images -->
                    <div>
                        <label for="images" class="block font-bold uppercase text-sm mb-2">
                            Images (Proof of Good Ice) *
                        </label>
                        <input
                            type="file"
                            id="images"
                            name="images[]"
                            multiple
                            accept="image/jpeg,image/png,image/jpg,image/webp"
                            required
                            class="w-full px-4 py-3 border-3 border-black font-mono focus:outline-none focus:border-primary-600 @error('images.*') border-red-600 @enderror"
                        />
                        <p class="text-sm text-gray-600 mt-2">
                            Upload at least one image. Max 5MB per image. Formats: JPEG, PNG, WEBP
                        </p>
                        @error('images.*')
                            <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Submit Button -->
                    <div class="flex items-center justify-end space-x-4">
                        <a href="{{ route('home') }}"
                           class="px-6 py-3 bg-white font-bold uppercase border-3 border-black shadow-brutal hover:shadow-brutal-lg hover:translate-x-[-2px] hover:translate-y-[-2px] transition-all">
                            Cancel
                        </a>
                        <button
                            type="submit"
                            class="px-6 py-3 bg-primary-600 text-white font-bold uppercase border-3 border-black shadow-brutal hover:shadow-brutal-lg hover:translate-x-[-2px] hover:translate-y-[-2px] transition-all">
                            Submit Location
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
