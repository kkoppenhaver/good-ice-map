<x-app-layout>
    <x-slot name="header">
        Submit New Location
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white border-5 border-black shadow-brutal-lg p-8">
                <form method="POST" action="{{ route('locations.store') }}" enctype="multipart/form-data" class="space-y-6">
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

                            <!-- Address -->
                            <div>
                                <label for="manual_address" class="block font-bold uppercase text-sm mb-2">
                                    Address *
                                </label>
                                <input
                                    type="text"
                                    id="manual_address"
                                    name="manual_address"
                                    value="{{ old('manual_address') }}"
                                    placeholder="123 Main St, City, State, ZIP"
                                    class="w-full px-4 py-3 border-3 border-black font-mono focus:outline-none focus:border-primary-600 @error('manual_address') border-red-600 @enderror"
                                />
                                <p class="text-sm text-gray-600 mt-2">
                                    We'll automatically determine the coordinates from the address
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
        function switchToManualEntry() {
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
            
            // Clear Google Maps link and hidden fields
            document.getElementById('google_maps_link').value = '';
            document.getElementById('hidden_name').value = '';
            document.getElementById('hidden_address').value = '';
            document.getElementById('hidden_latitude').value = '';
            document.getElementById('hidden_longitude').value = '';
        }

        async function geocodeAddress(address) {
            try {
                // Using Nominatim (OpenStreetMap's free geocoding service)
                const response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(address)}&limit=1`, {
                    headers: {
                        'User-Agent': 'GoodIceMap/1.0'
                    }
                });
                const data = await response.json();
                
                if (data && data.length > 0) {
                    return {
                        lat: data[0].lat,
                        lng: data[0].lon,
                        displayName: data[0].display_name
                    };
                }
                return null;
            } catch (error) {
                console.error('Geocoding error:', error);
                return null;
            }
        }

        // Geocode address on blur
        document.addEventListener('DOMContentLoaded', function() {
            const manualAddressInput = document.getElementById('manual_address');
            if (manualAddressInput) {
                manualAddressInput.addEventListener('blur', async function() {
                    const address = this.value.trim();
                    if (!address) return;

                    const result = await geocodeAddress(address);
                    if (result) {
                        document.getElementById('manual_latitude').value = result.lat;
                        document.getElementById('manual_longitude').value = result.lng;
                    } else {
                        alert('Could not find coordinates for this address. Please check the address and try again.');
                    }
                });
            }
        });

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

                // Extract coordinates - try multiple patterns
                // Pattern 1: @LAT,LNG,ZOOM (most common)
                match = fullUrl.match(/@(-?\d+\.?\d*),(-?\d+\.?\d*),?\d*\.?\d*z?/);
                if (match) {
                    lat = parseFloat(match[1]);
                    lng = parseFloat(match[2]);
                }

                // Pattern 2: !3dLAT!4dLNG (data parameter)
                if (!lat) {
                    match = fullUrl.match(/!3d(-?\d+\.?\d*)!4d(-?\d+\.?\d*)/);
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

            if (data && (data.lat && data.lng)) {
                // We have coordinates - fetch enriched place details from Google Places API
                document.getElementById('preview_content').innerHTML =
                    '<p class="text-gray-600">Fetching place details from Google...</p>';

                try {
                    const response = await fetch('/api/fetch-place-details', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            lat: data.lat,
                            lng: data.lng,
                            place_id: data.placeId || null
                        })
                    });

                    if (response.ok) {
                        const placeDetails = await response.json();

                        if (placeDetails.success) {
                            displayPlaceDetails(placeDetails, data);
                        } else {
                            // Places API failed, use basic data from URL parsing
                            useBasicData(data);
                        }
                    } else {
                        // API call failed, use basic data from URL parsing
                        useBasicData(data);
                    }
                } catch (error) {
                    console.error('Error fetching place details:', error);
                    // On error, use basic data from URL parsing
                    useBasicData(data);
                }
            } else if (data && data.name) {
                // No coordinates but we have a place name - try text search
                document.getElementById('preview_content').innerHTML =
                    '<p class="text-gray-600">Searching for place by name...</p>';

                try {
                    const response = await fetch('/api/search-place', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            query: data.name
                        })
                    });

                    if (response.ok) {
                        const searchResult = await response.json();

                        if (searchResult.success) {
                            // Update data with search results
                            data.lat = searchResult.lat;
                            data.lng = searchResult.lng;
                            data.address = searchResult.address;
                            data.placeId = searchResult.place_id;

                            displayPlaceDetails(searchResult, data);
                        } else {
                            showError();
                        }
                    } else {
                        showError();
                    }
                } catch (error) {
                    console.error('Error searching for place:', error);
                    showError();
                }
            } else {
                // Invalid link - show error
                showError();
            }
        });

        function displayPlaceDetails(placeDetails, data) {
            // Prioritize place name from URL parsing, use API name only as fallback
            const name = data.name || placeDetails.name || '';
            // For address, prefer API's full address
            const address = placeDetails.address || data.address || '';
            const description = placeDetails.description || '';
            const lat = placeDetails.lat || data.lat;
            const lng = placeDetails.lng || data.lng;

            // Update hidden fields
            document.getElementById('hidden_name').value = name;
            document.getElementById('hidden_address').value = address;
            document.getElementById('hidden_latitude').value = lat;
            document.getElementById('hidden_longitude').value = lng;

            // Auto-fill description if we got one from Places API
            if (description) {
                document.getElementById('description').value = description;
            }

            // Update preview - prominently show address, hide coordinates
            let previewHTML = '<div class="space-y-3">';
            if (name) {
                previewHTML += `<p class="text-lg"><span class="font-bold">Location:</span> ${name}</p>`;
            }
            if (address && address !== name) {
                previewHTML += `<p class="text-base"><span class="font-bold">Address:</span> ${address}</p>`;
            }
            if (description) {
                previewHTML += `<p class="text-sm text-gray-700"><span class="font-bold">Description:</span> ${description}</p>`;
            }
            if (placeDetails.rating) {
                previewHTML += `<p class="text-sm"><span class="font-bold">Google Rating:</span> ${placeDetails.rating} ⭐ (${placeDetails.user_ratings_total} reviews)</p>`;
            }
            previewHTML += `<p class="text-xs text-gray-500">Coordinates: ${lat}, ${lng}</p>`;
            previewHTML += '</div>';

            document.getElementById('preview_content').innerHTML = previewHTML;
        }

        function useBasicData(data) {
            // Update hidden fields with extracted data from URL
            document.getElementById('hidden_name').value = data.name || '';
            document.getElementById('hidden_address').value = data.address || data.name || '';
            document.getElementById('hidden_latitude').value = data.lat;
            document.getElementById('hidden_longitude').value = data.lng;

            // Update preview
            let previewHTML = '<div class="space-y-2">';
            if (data.name) {
                previewHTML += `<p><span class="font-bold">Name:</span> ${data.name}</p>`;
            }
            if (data.address) {
                previewHTML += `<p><span class="font-bold">Address:</span> ${data.address}</p>`;
            }
            previewHTML += `<p><span class="font-bold">Coordinates:</span> ${data.lat}, ${data.lng}</p>`;
            previewHTML += '<p class="text-gray-500 text-xs italic mt-2">Note: Could not fetch additional details from Google Places API. Data extracted from URL only.</p>';
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
