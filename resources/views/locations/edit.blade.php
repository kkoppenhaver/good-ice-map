<x-app-layout>
    @push('head-scripts')
    <script src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google.places_api_key') }}&libraries=places"></script>
    @endpush

    <x-slot name="header">
        Edit Location
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white border-5 border-black shadow-brutal-lg p-8">
                <form method="POST" action="{{ route('locations.update', $location) }}" enctype="multipart/form-data" class="space-y-6" onsubmit="return validateEditForm(event)">
                    @csrf
                    @method('PUT')

                    <div class="text-center mb-6">
                        <h2 class="font-bold uppercase text-2xl mb-2">Edit Location</h2>
                        <p class="text-gray-600">Update the details for this location</p>
                    </div>

                    <div class="space-y-6">
                        <!-- Name -->
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

                        <!-- Address with Google Places Autocomplete -->
                        <div>
                            <label for="address" class="block font-bold uppercase text-sm mb-2">
                                Address *
                            </label>
                            <input
                                type="text"
                                id="address"
                                name="address"
                                value="{{ old('address', $location->address) }}"
                                placeholder="Start typing to search for a place..."
                                autocomplete="off"
                                required
                                class="w-full px-4 py-3 border-3 border-black font-mono focus:outline-none focus:border-primary-600 @error('address') border-red-600 @enderror"
                            />
                            <p id="autocomplete_hint" class="text-sm text-gray-600 mt-2">
                                Type an address or place name and select from the dropdown to update coordinates
                            </p>
                            <p id="autocomplete_success" class="text-sm text-green-600 mt-2 hidden">
                                ✓ New place selected — coordinates updated
                            </p>
                            @error('address')
                                <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                            @enderror
                        </div>

                        <input type="hidden" id="latitude" name="latitude" value="{{ old('latitude', $location->latitude) }}" />
                        <input type="hidden" id="longitude" name="longitude" value="{{ old('longitude', $location->longitude) }}" />
                        <input type="hidden" id="place_id" name="place_id" value="{{ old('place_id', $location->place_id) }}" />

                        <!-- Description -->
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

                        <!-- Existing Images -->
                        @if ($location->images->count() > 0)
                            <div>
                                <label class="block font-bold uppercase text-sm mb-2">
                                    Current Images
                                </label>
                                <div class="grid grid-cols-3 gap-4">
                                    @foreach ($location->images as $image)
                                        <div class="border-3 border-black shadow-brutal overflow-hidden">
                                            <img src="{{ $image->url }}"
                                                 alt="{{ $location->name }}"
                                                 class="w-full h-24 object-cover">
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <!-- New Images -->
                        <div>
                            <label for="images" class="block font-bold uppercase text-sm mb-2">
                                Add New Images
                            </label>
                            <input
                                type="file"
                                id="images"
                                name="images[]"
                                multiple
                                accept="image/jpeg,image/png,image/jpg,image/webp"
                                class="w-full px-4 py-3 border-3 border-black font-mono focus:outline-none focus:border-primary-600 @error('images.*') border-red-600 @enderror"
                            />
                            <p class="text-sm text-gray-600 mt-2">
                                Optional: Upload additional images. Max 5MB per image. Formats: JPEG, PNG, WEBP
                            </p>
                            @error('images.*')
                                <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Submit Button -->
                        <div class="flex items-center justify-end space-x-4">
                            <a href="{{ route('locations.show', $location) }}"
                               class="px-6 py-3 bg-white font-bold uppercase border-3 border-black shadow-brutal hover:shadow-brutal-lg hover:translate-x-[-2px] hover:translate-y-[-2px] transition-all">
                                Cancel
                            </a>
                            <button
                                type="submit"
                                class="px-6 py-3 bg-primary-600 text-white font-bold uppercase border-3 border-black shadow-brutal hover:shadow-brutal-lg hover:translate-x-[-2px] hover:translate-y-[-2px] transition-all">
                                Update Location
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        let placeChanged = false;
        let autocomplete = null;

        function validateEditForm(event) {
            const lat = document.getElementById('latitude').value;
            const lng = document.getElementById('longitude').value;
            if (!lat || !lng) {
                event.preventDefault();
                alert('Please select a valid place from the dropdown to set coordinates.');
                return false;
            }
            return true;
        }

        function initAutocomplete() {
            const addressInput = document.getElementById('address');
            if (!addressInput || !google?.maps?.places) return;

            autocomplete = new google.maps.places.Autocomplete(addressInput, {
                types: ['establishment', 'geocode'],
                fields: ['name', 'formatted_address', 'geometry', 'place_id']
            });

            autocomplete.addListener('place_changed', function() {
                const place = autocomplete.getPlace();

                if (place.geometry && place.geometry.location) {
                    placeChanged = true;

                    document.getElementById('latitude').value = place.geometry.location.lat();
                    document.getElementById('longitude').value = place.geometry.location.lng();
                    document.getElementById('place_id').value = place.place_id || '';

                    document.getElementById('autocomplete_hint').classList.add('hidden');
                    document.getElementById('autocomplete_success').classList.remove('hidden');
                    addressInput.classList.add('border-green-600');
                }
            });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initAutocomplete);
        } else {
            initAutocomplete();
        }
    </script>
    @endpush
</x-app-layout>
