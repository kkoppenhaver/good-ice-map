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
        [x-cloak] { display: none !important; }

        #map {
            height: calc(100vh - 64px);
            width: 100%;
        }

        /* Brutalist styling for Leaflet zoom controls */
        .leaflet-control-zoom {
            border: 3px solid black !important;
            box-shadow: 4px 4px 0 0 rgba(0, 0, 0, 1) !important;
        }

        .leaflet-control-zoom a {
            background-color: white !important;
            border: none !important;
            border-bottom: 3px solid black !important;
            color: black !important;
            font-family: ui-monospace, monospace !important;
            font-size: 24px !important;
            font-weight: 900 !important;
            width: 40px !important;
            height: 40px !important;
            line-height: 36px !important;
            transition: all 0.2s !important;
        }

        .leaflet-control-zoom a:last-child {
            border-bottom: none !important;
        }

        .leaflet-control-zoom a:hover {
            background-color: #9333ea !important;
            color: white !important;
        }

        .leaflet-control-zoom-in,
        .leaflet-control-zoom-out {
            text-indent: 0 !important;
        }

        /* Brutalist styling for popups */
        .leaflet-popup-content-wrapper {
            background: white !important;
            border: 4px solid black !important;
            border-radius: 0 !important;
            box-shadow: 6px 6px 0 0 rgba(0, 0, 0, 1) !important;
            padding: 0 !important;
        }

        .leaflet-popup-content {
            margin: 0 !important;
            padding: 0 !important;
            font-family: ui-monospace, monospace !important;
        }

        .leaflet-popup-tip-container {
            display: none !important;
        }

        .leaflet-popup-close-button {
            z-index: 1000 !important;
            background: white !important;
            border: 3px solid black !important;
            border-radius: 0 !important;
            box-shadow: 3px 3px 0 0 rgba(0, 0, 0, 1) !important;
            color: black !important;
            font-family: ui-monospace, monospace !important;
            font-size: 20px !important;
            font-weight: 900 !important;
            width: 32px !important;
            height: 32px !important;
            line-height: 28px !important;
            text-align: center !important;
            padding: 0 !important;
            top: 8px !important;
            right: 8px !important;
            transition: all 0.2s !important;
        }

        .leaflet-popup-close-button:hover {
            background: #9333ea !important;
            color: white !important;
            transform: translate(-2px, -2px) !important;
            box-shadow: 5px 5px 0 0 rgba(0, 0, 0, 1) !important;
        }

        /* Attribution styling */
        .leaflet-control-attribution {
            background: white !important;
            border: 3px solid black !important;
            border-bottom: none !important;
            border-right: none !important;
            box-shadow: 4px 4px 0 0 rgba(0, 0, 0, 1) !important;
            font-family: ui-monospace, monospace !important;
            font-weight: 700 !important;
            font-size: 11px !important;
        }

        .leaflet-control-attribution a {
            color: #9333ea !important;
            font-weight: 900 !important;
        }

        /* Remove default Leaflet container border */
        .leaflet-container {
            border: none !important;
        }

        /* Brutalist map styling - high contrast, stark aesthetic */
        .leaflet-tile-container {
            filter: contrast(1.15) brightness(1.05);
        }

        /* Make tile transitions instant for brutalist feel */
        .leaflet-tile {
            image-rendering: crisp-edges;
            image-rendering: -webkit-optimize-contrast;
        }

        /* Style the map container with border */
        #map {
            border-left: 5px solid black;
            border-right: 5px solid black;
            border-bottom: 5px solid black;
        }
    </style>
</head>
<body class="font-mono bg-white text-black">
    <nav class="bg-white border-b-5 border-black">
        <div class="max-w-7xl mx-auto px-2 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center shrink-0">
                    <a href="{{ route('home') }}" class="flex items-center text-lg sm:text-2xl font-bold uppercase tracking-wider hover:text-primary-600">
                        <img src="/logo.png" alt="Good Ice Map" class="block h-10 sm:h-12 w-auto me-2 sm:me-3">
                        <span class="hidden sm:block">Good Ice Map</span>
                    </a>
                </div>

                <div class="flex items-center space-x-2 sm:space-x-4">
                    @auth
                        <a href="{{ route('locations.create') }}"
                           class="px-3 sm:px-6 py-2 bg-primary-600 text-white text-xs sm:text-base font-bold uppercase border-3 border-black shadow-brutal hover:shadow-brutal-lg hover:translate-x-[-2px] hover:translate-y-[-2px] transition-all whitespace-nowrap">
                            <span class="hidden sm:block">Submit Location</span>
                            <span class="block sm:hidden">Submit</span>
                        </a>
                        <a href="{{ route('dashboard') }}"
                           class="px-3 sm:px-4 py-2 bg-white text-xs sm:text-base font-bold uppercase border-3 border-black shadow-brutal hover:shadow-brutal-lg hover:translate-x-[-2px] hover:translate-y-[-2px] transition-all whitespace-nowrap">
                            Dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}"
                           class="px-3 sm:px-4 py-2 text-xs sm:text-base font-bold uppercase hover:text-primary-600">
                            Login
                        </a>
                        <a href="{{ route('register') }}"
                           class="px-3 sm:px-6 py-2 bg-primary-600 text-white text-xs sm:text-base font-bold uppercase border-3 border-black shadow-brutal hover:shadow-brutal-lg hover:translate-x-[-2px] hover:translate-y-[-2px] transition-all whitespace-nowrap">
                            Register
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <div id="map"></div>

    @guest
    <div
        x-data="{ show: !localStorage.getItem('welcome_seen') }"
        x-cloak
        x-show="show"
        x-on:keydown.escape.window="show = false; localStorage.setItem('welcome_seen', '1')"
        class="fixed inset-0 flex items-center justify-center"
        style="z-index: 1000; background: rgba(0,0,0,0.7);"
        x-on:click.self="show = false; localStorage.setItem('welcome_seen', '1')"
    >
        <div class="bg-white border-5 border-black shadow-brutal mx-4 max-w-lg w-full font-mono p-8">
            <h2 class="text-2xl font-black uppercase tracking-wider mb-4 border-b-5 border-black pb-3">WHAT IS GOOD ICE?</h2>
            <div class="space-y-3 mb-6 text-base">
                <p><span class="font-black">Good ice</span> is nugget ice (also called pellet, sonic, or hospital ice) — or any ice that's soft, chewable, and delightfully crunchable.</p>
                <p>This map helps you <span class="font-black">find places near you</span> that serve good ice, so you never have to settle for hard, hollow cubes again.</p>
                <p><span class="font-black">Sign up for a free account</span> (we only ask to prevent spam) and post your favorite good-ice spots with a photo.</p>
                <p>Now get out there and <span class="font-black">explore the map!</span></p>
            </div>
            <button
                x-on:click="show = false; localStorage.setItem('welcome_seen', '1')"
                class="w-full px-6 py-3 bg-primary-600 text-white font-black uppercase text-lg border-3 border-black shadow-brutal hover:shadow-brutal-lg hover:translate-x-[-2px] hover:translate-y-[-2px] transition-all"
            >
                GOT IT
            </button>
        </div>
    </div>
    @endguest

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        const map = L.map('map').setView([39.8283, -98.5795], 5);

        // Stamen Toner tiles for brutalist black and white aesthetic
        L.tileLayer('https://tiles.stadiamaps.com/tiles/stamen_toner/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; <a href="https://www.stadiamaps.com/" target="_blank">Stadia Maps</a> &copy; <a href="https://www.stamen.com/" target="_blank">Stamen Design</a> &copy; <a href="https://openmaptiles.org/" target="_blank">OpenMapTiles</a> &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            maxZoom: 20
        }).addTo(map);

        // Custom marker icon with purple color - map pin shape
        const customIcon = L.divIcon({
            className: 'custom-marker',
            html: `
                <svg width="40" height="52" viewBox="0 0 40 52" xmlns="http://www.w3.org/2000/svg">
                    <!-- Shadow circle -->
                    <circle cx="22" cy="17" r="13" fill="rgba(0,0,0,0.3)"/>
                    <!-- Shadow point -->
                    <path d="M 22 28 Q 22 35 22 48 Q 22 35 22 28 Z" fill="rgba(0,0,0,0.3)"/>

                    <!-- Main pin circle (top part) -->
                    <circle cx="20" cy="15" r="13" fill="#9333ea" stroke="black" stroke-width="3"/>

                    <!-- Main pin point (bottom part) -->
                    <path d="M 9 20 Q 11 25 20 50 Q 29 25 31 20"
                          fill="#9333ea"
                          stroke="black"
                          stroke-width="3"
                          stroke-linejoin="round"/>

                    <!-- Cover the seam between circle and point -->
                    <rect x="9" y="15" width="22" height="10" fill="#9333ea"/>

                    <!-- Redraw the circle border over the seam -->
                    <circle cx="20" cy="15" r="13" fill="none" stroke="black" stroke-width="3"/>

                    <!-- Inner white circle -->
                    <circle cx="20" cy="15" r="6" fill="white" stroke="black" stroke-width="2"/>
                </svg>
            `,
            iconSize: [40, 52],
            iconAnchor: [20, 50],
            popupAnchor: [0, -50]
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

                    const imageUrl = location.images && location.images.length > 0 ? location.images[0].url : null;
                    const imageHtml = imageUrl
                        ? `<img src="${imageUrl}" style="width: 100%; height: 160px; object-fit: cover; display: block; border-bottom: 3px solid black;" alt="${location.name}" />`
                        : '';

                    marker.bindPopup(`
                        <div style="font-family: ui-monospace, monospace;">
                            ${imageHtml}
                            <div style="padding: 16px;">
                                <h3 style="font-weight: 900; font-size: 18px; text-transform: uppercase; margin-bottom: 8px;">${location.name}</h3>
                                <p style="font-size: 14px; margin-bottom: 8px;">${location.address}</p>
                                <p style="font-size: 14px; margin-bottom: 12px;">${rating}</p>
                                <a href="/locations/${location.id}"
                                   style="display: inline-block; padding: 8px 16px; background: #9333ea; color: white; font-weight: 900; text-transform: uppercase; font-size: 14px; border: 2px solid black; text-decoration: none;">
                                    View Details
                                </a>
                            </div>
                        </div>
                    `, { minWidth: 250 });
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
