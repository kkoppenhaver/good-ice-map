<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Good Ice Map</title>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        #map {
            height: calc(100vh - 64px);
            width: 100%;
        }
    </style>
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
                        <a href="{{ route('dashboard') }}"
                           class="px-4 py-2 bg-white font-bold uppercase border-3 border-black shadow-brutal hover:shadow-brutal-lg hover:translate-x-[-2px] hover:translate-y-[-2px] transition-all">
                            Dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}"
                           class="px-4 py-2 font-bold uppercase hover:text-primary-600">
                            Login
                        </a>
                        <a href="{{ route('register') }}"
                           class="px-6 py-2 bg-primary-600 text-white font-bold uppercase border-3 border-black shadow-brutal hover:shadow-brutal-lg hover:translate-x-[-2px] hover:translate-y-[-2px] transition-all">
                            Register
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <div id="map"></div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        const map = L.map('map').setView([37.7749, -122.4194], 12);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        // Custom marker icon with purple color
        const customIcon = L.divIcon({
            className: 'custom-marker',
            html: `<div style="background: #9333ea; width: 30px; height: 30px; border: 3px solid black; border-radius: 50%; box-shadow: 2px 2px 0 0 rgba(0,0,0,1);"></div>`,
            iconSize: [30, 30],
            iconAnchor: [15, 15]
        });

        // Fetch and display locations
        fetch('{{ route('api.locations') }}')
            .then(response => response.json())
            .then(locations => {
                locations.forEach(location => {
                    const marker = L.marker([location.latitude, location.longitude], {
                        icon: customIcon
                    }).addTo(map);

                    const rating = location.average_rating
                        ? `⭐ ${parseFloat(location.average_rating).toFixed(1)} (${location.total_ratings})`
                        : 'No ratings yet';

                    marker.bindPopup(`
                        <div class="font-mono">
                            <h3 class="font-bold text-lg uppercase mb-2">${location.name}</h3>
                            <p class="text-sm mb-2">${location.address}</p>
                            <p class="text-sm mb-3">${rating}</p>
                            <a href="/locations/${location.id}"
                               class="inline-block px-4 py-2 bg-primary-600 text-white font-bold uppercase text-sm border-2 border-black">
                                View Details
                            </a>
                        </div>
                    `);
                });

                // Fit map to show all markers if locations exist
                if (locations.length > 0) {
                    const bounds = L.latLngBounds(locations.map(loc => [loc.latitude, loc.longitude]));
                    map.fitBounds(bounds, { padding: [50, 50] });
                }
            });
    </script>
</body>
</html>
