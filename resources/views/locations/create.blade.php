<x-app-layout>
    @push('head-scripts')
    <script src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google.places_api_key') }}&libraries=places"></script>
    @endpush

    <x-slot name="header">
        Submit New Location
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white border-5 border-black shadow-brutal-lg p-8">
                <form method="POST" action="{{ route('locations.store') }}" enctype="multipart/form-data" class="space-y-6" onsubmit="return validateManualEntry(event)">
                    @csrf

                    <!-- Google Maps Link Section (visible by default) -->
                    <div id="google_maps_section">
                        <div class="text-center mb-6">
                            <h2 class="font-bold uppercase text-2xl mb-2">Add a Good Ice Location</h2>
                            <p class="text-gray-600">Paste a Google Maps link to get started</p>
                        </div>

                        <div>
                            <label for="google_maps_link" class="block font-bold uppercase text-sm mb-2">
                                Google Maps Share Link *
                            </label>
                            <input
                                type="url"
                                id="google_maps_link"
                                name="google_maps_link"
                                value="{{ old('google_maps_link') }}"
                                placeholder="https://maps.app.goo.gl/... or https://www.google.com/maps/place/..."
                                required
                                class="w-full px-4 py-3 border-3 border-black font-mono focus:outline-none focus:border-primary-600 @error('google_maps_link') border-red-600 @enderror"
                            />
                            <p class="text-sm text-gray-600 mt-2">
                                Paste a Google Maps share link and we'll auto-fill everything for you
                            </p>
                            @error('google_maps_link')
                                <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="text-center mt-4">
                            <button type="button" onclick="switchToManualEntry()" class="text-sm underline hover:text-primary-600">
                                Can't find a Google Maps link? Fill in manually
                            </button>
                        </div>
                    </div>

                    <!-- Manual Entry Section (hidden by default) -->
                    <div id="manual_entry_section" style="display: none;">
                        <div class="text-center mb-6">
                            <h2 class="font-bold uppercase text-2xl mb-2">Add a Good Ice Location</h2>
                            <p class="text-gray-600">Fill in the details manually</p>
                        </div>

                        <div class="space-y-6">
                            <!-- Name -->
                            <div>
                                <label for="manual_name" class="block font-bold uppercase text-sm mb-2">
                                    Location Name *
                                </label>
                                <input
                                    type="text"
                                    id="manual_name"
                                    name="name"
                                    value="{{ old('name') }}"
                                    class="w-full px-4 py-3 border-3 border-black font-mono focus:outline-none focus:border-primary-600 @error('name') border-red-600 @enderror"
                                />
                                @error('name')
                                    <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Address with Google Places Autocomplete -->
                            <div>
                                <label for="manual_address" class="block font-bold uppercase text-sm mb-2">
                                    Search for a Place *
                                </label>
                                <input
                                    type="text"
                                    id="manual_address"
                                    name="manual_address"
                                    value="{{ old('manual_address') }}"
                                    placeholder="Start typing to search for a place..."
                                    autocomplete="off"
                                    class="w-full px-4 py-3 border-3 border-black font-mono focus:outline-none focus:border-primary-600 @error('manual_address') border-red-600 @enderror"
                                />
                                <p id="autocomplete_hint" class="text-sm text-gray-600 mt-2">
                                    Type an address or place name and select from the dropdown
                                </p>
                                <p id="autocomplete_success" class="text-sm text-green-600 mt-2 hidden">
                                    ✓ Place selected successfully
                                </p>
                                <p id="autocomplete_error" class="text-sm text-red-600 mt-2 hidden">
                                    Please select a place from the dropdown list
                                </p>
                                @error('manual_address')
                                    <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                                @enderror
                            </div>

                            <input type="hidden" id="manual_latitude" name="latitude" value="{{ old('latitude') }}" />
                            <input type="hidden" id="manual_longitude" name="longitude" value="{{ old('longitude') }}" />
                        </div>
                    </div>

                    <!-- Additional Fields Section (hidden until Google Maps link is parsed or manual mode selected) -->
                    <div id="additional_fields_section" style="display: none;">
                        <!-- Auto-filled Preview (only shown in Google Maps mode) -->
                        <div id="autofilled_preview" class="border-3 border-primary-600 bg-primary-50 p-4 mb-6">
                            <p class="font-bold uppercase text-sm mb-2">Auto-filled Information:</p>
                            <div id="preview_content" class="space-y-2 text-sm"></div>
                        </div>

                        <!-- Hidden fields for auto-filled data -->
                        <input type="hidden" id="hidden_name" name="name" value="{{ old('name') }}" />
                        <input type="hidden" id="hidden_address" name="address" value="{{ old('address') }}" />
                        <input type="hidden" id="hidden_latitude" name="latitude" value="{{ old('latitude') }}" />
                        <input type="hidden" id="hidden_longitude" name="longitude" value="{{ old('longitude') }}" />

                        <!-- Description -->
                        <div class="mb-6">
                            <label for="description" class="block font-bold uppercase text-sm mb-2">
                                Description
                            </label>
                            <textarea
                                id="description"
                                name="description"
                                rows="4"
                                placeholder="Tell us about the ice quality, accessibility, etc."
                                class="w-full px-4 py-3 border-3 border-black font-mono focus:outline-none focus:border-primary-600 @error('description') border-red-600 @enderror"
                            >{{ old('description') }}</textarea>
                            @error('description')
                                <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Images -->
                        <div class="mb-6">
                            <label for="images" class="block font-bold uppercase text-sm mb-2">
                                Images (Proof of Good Ice)
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
                                Optional: Upload images of the good ice. Max 5MB per image. Formats: JPEG, PNG, WEBP
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
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        let isManualMode = false;

        // Form validation function called by onsubmit
        function validateManualEntry(event) {
            if (isManualMode && !manualPlaceSelected) {
                event.preventDefault();
                showAutocompleteError();
                return false;
            }
            return true;
        }

        function switchToManualEntry() {
            isManualMode = true;

            // Hide Google Maps section
            document.getElementById('google_maps_section').style.display = 'none';

            // Show manual entry section
            document.getElementById('manual_entry_section').style.display = 'block';

            // Show additional fields (but hide the auto-fill preview)
            document.getElementById('additional_fields_section').style.display = 'block';
            document.getElementById('autofilled_preview').style.display = 'none';

            // Update required fields
            document.getElementById('google_maps_link').removeAttribute('required');
            document.getElementById('manual_address').setAttribute('required', 'required');
            document.getElementById('manual_name').setAttribute('required', 'required');

            // DISABLE hidden fields so they don't override manual entry fields
            // (both have name="name", name="latitude", name="longitude")
            document.getElementById('hidden_name').disabled = true;
            document.getElementById('hidden_address').disabled = true;
            document.getElementById('hidden_latitude').disabled = true;
            document.getElementById('hidden_longitude').disabled = true;

            // Clear Google Maps link
            document.getElementById('google_maps_link').value = '';
        }

        // Track whether a valid place was selected from autocomplete
        let manualPlaceSelected = false;
        let autocomplete = null;

        // Initialize Google Places Autocomplete for manual entry
        function initAutocomplete() {
            const manualAddressInput = document.getElementById('manual_address');
            if (!manualAddressInput || !google?.maps?.places) return;

            autocomplete = new google.maps.places.Autocomplete(manualAddressInput, {
                types: ['establishment', 'geocode'],
                fields: ['name', 'formatted_address', 'geometry', 'place_id']
            });

            // Handle place selection
            autocomplete.addListener('place_changed', function() {
                const place = autocomplete.getPlace();

                if (place.geometry && place.geometry.location) {
                    // Valid place selected
                    manualPlaceSelected = true;

                    // Populate the name field with the place name
                    document.getElementById('manual_name').value = place.name || place.formatted_address;

                    // Populate coordinates
                    document.getElementById('manual_latitude').value = place.geometry.location.lat();
                    document.getElementById('manual_longitude').value = place.geometry.location.lng();

                    // Update UI to show success
                    document.getElementById('autocomplete_hint').classList.add('hidden');
                    document.getElementById('autocomplete_error').classList.add('hidden');
                    document.getElementById('autocomplete_success').classList.remove('hidden');
                    manualAddressInput.classList.remove('border-red-600');
                    manualAddressInput.classList.add('border-green-600');

                    console.log('Place selected:', {
                        name: place.name,
                        address: place.formatted_address,
                        lat: place.geometry.location.lat(),
                        lng: place.geometry.location.lng()
                    });
                } else {
                    // No valid place selected (user pressed enter without selecting)
                    invalidatePlaceSelection();
                }
            });

            // Reset validation when user types (they need to select from dropdown again)
            manualAddressInput.addEventListener('input', function() {
                if (manualPlaceSelected) {
                    invalidatePlaceSelection();
                }
            });
        }

        function invalidatePlaceSelection() {
            manualPlaceSelected = false;
            document.getElementById('manual_latitude').value = '';
            document.getElementById('manual_longitude').value = '';
            document.getElementById('autocomplete_success').classList.add('hidden');
            document.getElementById('autocomplete_hint').classList.remove('hidden');

            const manualAddressInput = document.getElementById('manual_address');
            manualAddressInput.classList.remove('border-green-600');
        }

        function showAutocompleteError() {
            document.getElementById('autocomplete_hint').classList.add('hidden');
            document.getElementById('autocomplete_success').classList.add('hidden');
            document.getElementById('autocomplete_error').classList.remove('hidden');

            const manualAddressInput = document.getElementById('manual_address');
            manualAddressInput.classList.add('border-red-600');
            manualAddressInput.focus();
        }

        // Initialize form validation and autocomplete
        function initFormValidation() {
            initAutocomplete();

            // Add form submission validation for manual entry mode
            const form = document.querySelector('form');
            form.addEventListener('submit', function(e) {
                // Only validate if in manual entry mode
                if (isManualMode) {
                    if (!manualPlaceSelected) {
                        e.preventDefault();
                        e.stopPropagation();
                        showAutocompleteError();
                        return false;
                    }
                }
                return true;
            });
        }

        // Initialize when DOM is ready (handle both cases: already loaded or still loading)
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initFormValidation);
        } else {
            initFormValidation();
        }

        async function parseGoogleMapsLink(url) {
            try {
                let fullUrl = url;

                // If it's a shortened URL, we need to expand it server-side
                if (url.includes('goo.gl') || url.includes('maps.app.goo.gl')) {
                    try {
                        const response = await fetch('/api/expand-url', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({ url: url })
                        });

                        if (response.ok) {
                            const data = await response.json();
                            fullUrl = data.expanded_url || url;
                            console.log('Expanded URL:', fullUrl);
                        } else {
                            console.error('Failed to expand URL');
                        }
                    } catch (error) {
                        console.error('Error expanding shortened URL:', error);
                    }
                }

                // Extract data from the URL
                let name = null, address = null, lat = null, lng = null, placeId = null;

                // Extract Place ID - only accept ChIJ format (valid Google Places IDs)
                // Pattern 1: !1s[PLACE_ID]! where PLACE_ID starts with ChIJ
                let match = fullUrl.match(/!1s(ChIJ[A-Za-z0-9_-]+)/);
                if (match) {
                    placeId = match[1];
                }

                // Pattern 2: data=...!4m...!1s[PLACE_ID] where PLACE_ID starts with ChIJ
                if (!placeId) {
                    match = fullUrl.match(/data=[^!]*!4m[^!]*!1s(ChIJ[A-Za-z0-9_-]+)/);
                    if (match) {
                        placeId = match[1];
                    }
                }

                // Extract coordinates - prioritize precise place coordinates over viewport
                // Pattern 1: !3dLAT!4dLNG (MOST ACCURATE - actual place coordinates)
                match = fullUrl.match(/!3d(-?\d+\.?\d*)!4d(-?\d+\.?\d*)/);
                if (match) {
                    lat = parseFloat(match[1]);
                    lng = parseFloat(match[2]);
                }

                // Pattern 2: @LAT,LNG,ZOOM (viewport center - fallback)
                if (!lat) {
                    match = fullUrl.match(/@(-?\d+\.?\d*),(-?\d+\.?\d*),?\d*\.?\d*z?/);
                    if (match) {
                        lat = parseFloat(match[1]);
                        lng = parseFloat(match[2]);
                    }
                }

                // Pattern 3: ll=LAT,LNG
                if (!lat) {
                    match = fullUrl.match(/[?&]ll=(-?\d+\.?\d*),(-?\d+\.?\d*)/);
                    if (match) {
                        lat = parseFloat(match[1]);
                        lng = parseFloat(match[2]);
                    }
                }

                // Pattern 4: query parameter with coordinates
                if (!lat) {
                    match = fullUrl.match(/[?&]q=(-?\d+\.?\d*),(-?\d+\.?\d*)/);
                    if (match) {
                        lat = parseFloat(match[1]);
                        lng = parseFloat(match[2]);
                    }
                }

                // Extract name from /place/NAME/
                match = fullUrl.match(/\/place\/([^/@?#]+)/);
                if (match) {
                    name = decodeURIComponent(match[1].replace(/\+/g, ' '));
                }

                // If we have a place name, use it as address too
                if (name) {
                    address = name;
                }

                console.log('Parsed data:', { name, address, lat, lng, placeId });
                return { name, address, lat, lng, placeId };
            } catch (error) {
                console.error('Error parsing Google Maps link:', error);
                return null;
            }
        }

        document.getElementById('google_maps_link').addEventListener('input', async function() {
            const url = this.value.trim();
            if (!url) {
                // Hide additional fields if URL is cleared
                document.getElementById('additional_fields_section').style.display = 'none';
                return;
            }

            // Show loading state in preview
            document.getElementById('additional_fields_section').style.display = 'block';
            document.getElementById('autofilled_preview').style.display = 'block';
            document.getElementById('preview_content').innerHTML = 
                '<p class="text-gray-600">Parsing Google Maps link...</p>';

            const data = await parseGoogleMapsLink(url);

            if (data && data.lat && data.lng && data.name) {
                // We have everything we need from the URL - display immediately with map preview
                displayLocationWithMap(data, url);
            } else {
                // Invalid link - show error
                showError();
            }
        });



        function displayLocationWithMap(data, mapsUrl) {
            const name = data.name || '';
            const lat = data.lat;
            const lng = data.lng;

            // Update hidden fields
            document.getElementById('hidden_name').value = name;
            document.getElementById('hidden_address').value = name; // Use name as address for consistency
            document.getElementById('hidden_latitude').value = lat;
            document.getElementById('hidden_longitude').value = lng;

            // Generate Google Maps Static API image URL
            const mapImageUrl = `https://maps.googleapis.com/maps/api/staticmap?center=${lat},${lng}&zoom=15&size=600x300&markers=color:red%7C${lat},${lng}&key={{ config('services.google.places_api_key') }}`;

            // Update preview with map image
            let previewHTML = '<div class="space-y-3">';
            previewHTML += `<p class="text-lg"><span class="font-bold">Location:</span> ${name}</p>`;
            previewHTML += `<div class="mt-3"><img src="${mapImageUrl}" alt="Map preview" class="w-full border-2 border-black" /></div>`;
            previewHTML += `<p class="text-xs text-gray-500">Coordinates: ${lat}, ${lng}</p>`;
            previewHTML += `<p class="text-xs text-gray-500"><a href="${mapsUrl}" target="_blank" class="underline hover:text-primary-600">View on Google Maps</a></p>`;
            previewHTML += '</div>';

            document.getElementById('preview_content').innerHTML = previewHTML;
        }

        function showError() {
            document.getElementById('preview_content').innerHTML =
                '<p class="text-red-600">Could not extract location data from this link. Please check the URL or use manual entry.</p>';

            // Clear hidden fields
            document.getElementById('hidden_name').value = '';
            document.getElementById('hidden_address').value = '';
            document.getElementById('hidden_latitude').value = '';
            document.getElementById('hidden_longitude').value = '';
        }
    </script>
    @endpush
</x-app-layout>
