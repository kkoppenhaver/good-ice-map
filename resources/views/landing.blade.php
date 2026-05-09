@extends('layouts.base')

@section('title', 'Good Ice Map — Find good ice, anywhere.')

@section('meta')
    <meta name="description" content="A community-built map of every place that serves the good stuff — nugget, pellet, sonic, hospital ice. Find chewable ice near you and never settle for hard, hollow cubes again.">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Good Ice Map">
    <meta property="og:title" content="Good Ice Map — Find good ice, anywhere.">
    <meta property="og:description" content="A community-built map of every place that serves the good stuff — nugget, pellet, sonic, hospital ice. Find chewable ice near you and never settle for hard, hollow cubes again.">
    <meta property="og:url" content="{{ url('/') }}">
    <meta property="og:image" content="{{ asset('images/map-hero.png') }}">
    <meta property="og:image:alt" content="A map of good ice locations across the United States">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Good Ice Map — Find good ice, anywhere.">
    <meta name="twitter:description" content="A community-built map of every place that serves nugget, pellet, sonic, or hospital ice. Find chewable ice near you.">
    <meta name="twitter:image" content="{{ asset('images/map-hero.png') }}">

    <link rel="canonical" href="{{ url('/') }}">
@endsection

@section('head')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

    <style>
        #mini-map {
            height: 480px;
            width: 100%;
            cursor: default;
        }

        /* Hero map preview + polaroid collage overlay */
        .hero-preview {
            position: relative;
        }
        .hero-collage {
            position: absolute;
            bottom: -32px;
            left: -32px;
            display: flex;
            pointer-events: none;
            z-index: 5;
        }
        .hero-collage .polaroid {
            background: white;
            border: 4px solid black;
            box-shadow: 6px 6px 0 0 rgba(0, 0, 0, 1);
            padding: 10px 10px 36px 10px;
            width: 160px;
            text-decoration: none;
            color: black;
            position: relative;
            pointer-events: auto;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .hero-collage .polaroid img { cursor: pointer; }
        .hero-collage .polaroid:hover {
            transform: translate(-3px, -3px) rotate(0deg) !important;
            box-shadow: 9px 9px 0 0 rgba(0, 0, 0, 1);
            z-index: 20;
        }
        .hero-collage .polaroid img {
            display: block;
            width: 100%;
            height: 140px;
            object-fit: cover;
            border: 2px solid black;
        }
        .hero-collage .polaroid .caption {
            position: absolute;
            bottom: 8px;
            left: 10px;
            right: 10px;
            font-size: 11px;
            font-weight: 900;
            text-transform: uppercase;
            text-align: center;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .hero-collage .polaroid:nth-child(1) { transform: rotate(-7deg); margin-right: -28px; z-index: 1; }
        .hero-collage .polaroid:nth-child(2) { transform: rotate(3deg);  margin-right: -28px; z-index: 3; }
        .hero-collage .polaroid:nth-child(3) { transform: rotate(-2deg); z-index: 2; }

        @media (max-width: 1023px) {
            .hero-collage { bottom: -20px; left: -16px; }
            .hero-collage .polaroid { width: 130px; }
            .hero-collage .polaroid img { height: 110px; }
        }
        @media (max-width: 640px) {
            .hero-collage { display: none; }
        }

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
        }

        .leaflet-control-zoom a:last-child {
            border-bottom: none !important;
        }

        .leaflet-control-attribution {
            background: white !important;
            border: 3px solid black !important;
            border-bottom: none !important;
            border-right: none !important;
            font-family: ui-monospace, monospace !important;
            font-weight: 700 !important;
            font-size: 11px !important;
        }

        .leaflet-control-attribution a {
            color: #9333ea !important;
            font-weight: 900 !important;
        }

        .leaflet-tile-container {
            filter: contrast(1.15) brightness(1.05);
        }

        .leaflet-container {
            border: none !important;
            background: white !important;
        }
    </style>
@endsection

@section('body')
    {{-- Nav --}}
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
                    <a href="{{ route('login') }}"
                       class="px-3 sm:px-4 py-2 text-xs sm:text-base font-bold uppercase hover:text-primary-600">
                        Login
                    </a>
                    <a href="{{ route('register') }}"
                       class="px-3 sm:px-6 py-2 bg-primary-600 text-white text-xs sm:text-base font-bold uppercase border-3 border-black shadow-brutal hover:shadow-brutal-lg hover:translate-x-[-2px] hover:translate-y-[-2px] transition-all whitespace-nowrap">
                        Register
                    </a>
                </div>
            </div>
        </div>
    </nav>

    @php
        $heroImages = $recentImages->take(4)->map(fn ($img) => [
            'id' => $img->location_id,
            'name' => $img->location->name ?? 'Good ice spot',
            'url' => $img->url,
        ])->values();

        // Dev fallback when no real ice photos exist yet
        if ($heroImages->isEmpty()) {
            $heroImages = collect([
                ['id' => 1, 'name' => 'Sample Spot A', 'url' => 'https://picsum.photos/seed/ice-1/400/300'],
                ['id' => 2, 'name' => 'Sample Spot B', 'url' => 'https://picsum.photos/seed/ice-2/400/300'],
                ['id' => 3, 'name' => 'Sample Spot C', 'url' => 'https://picsum.photos/seed/ice-3/400/300'],
                ['id' => 4, 'name' => 'Sample Spot D', 'url' => 'https://picsum.photos/seed/ice-4/400/300'],
            ]);
        }
    @endphp

    {{-- Hero --}}
    <section class="border-b-5 border-black">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-24">
            <div class="grid lg:grid-cols-2 gap-12 items-center">
                <div>
                    <h1 class="text-5xl sm:text-6xl xl:text-7xl font-black uppercase leading-none tracking-tight mb-6">
                        Find good ice, <span class="text-primary-600">anywhere.</span>
                    </h1>
                    <p class="text-lg sm:text-xl mb-8 max-w-xl leading-relaxed">
                        A community-built map of every place that serves the good stuff — nugget, pellet, sonic, hospital ice. The chewable kind. Never settle for hard, hollow cubes again.
                    </p>
                    <div class="flex flex-col sm:flex-row gap-4">
                        <a href="{{ route('register') }}"
                           class="inline-block text-center px-8 py-4 bg-primary-600 text-white font-black uppercase text-base sm:text-lg border-3 border-black shadow-brutal hover:shadow-brutal-lg hover:translate-x-[-2px] hover:translate-y-[-2px] transition-all">
                            Sign Up Free
                        </a>
                        <a href="{{ route('map') }}"
                           class="inline-block text-center px-8 py-4 bg-white text-black font-black uppercase text-base sm:text-lg border-3 border-black shadow-brutal hover:shadow-brutal-lg hover:translate-x-[-2px] hover:translate-y-[-2px] transition-all">
                            Explore the Map →
                        </a>
                    </div>
                </div>

                <div class="hero-preview">
                    <a href="{{ route('map') }}" class="block group">
                        <div class="border-5 border-black shadow-brutal-lg group-hover:shadow-brutal-lg group-hover:translate-x-[-3px] group-hover:translate-y-[-3px] transition-all bg-white">
                            <img src="/images/map-hero.png"
                                 alt="A map of good ice locations across the United States"
                                 class="block w-full h-auto">
                        </div>
                    </a>

                    @if($heroImages->isNotEmpty())
                        <div class="hero-collage">
                            @foreach($heroImages->take(3) as $img)
                                <a href="{{ route('locations.show', $img['id']) }}" class="polaroid">
                                    <img src="{{ $img['url'] }}" alt="{{ $img['name'] }}">
                                    <div class="caption">{{ $img['name'] }}</div>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>

    {{-- What is Good Ice --}}
    <section class="border-b-5 border-black bg-primary-50">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-20">
            <h2 class="text-3xl sm:text-4xl font-black uppercase tracking-wider mb-8 inline-block bg-black text-white px-4 py-2">
                What is Good Ice?
            </h2>
            <div class="space-y-5 text-lg sm:text-xl leading-relaxed max-w-3xl">
                <p>
                    <span class="font-black">Good ice</span> is nugget ice — also called pellet, sonic, or hospital ice. It's soft, chewable, and delightfully crunchable.
                </p>
                <p>
                    It's the ice that makes you order a refill just to get more of it. It's the ice that turns a mediocre fountain drink into a religious experience.
                </p>
                <p>
                    This map helps you <span class="font-black bg-yellow-200 px-1">find places near you</span> that serve good ice — so you never have to settle for hard, hollow cubes again.
                </p>
            </div>
        </div>
    </section>

    {{-- How It Works --}}
    <section class="border-b-5 border-black">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-20">
            <h2 class="text-3xl sm:text-4xl font-black uppercase tracking-wider mb-12 inline-block bg-black text-white px-4 py-2">
                How It Works
            </h2>

            <div class="grid md:grid-cols-3 gap-8">
                <div class="bg-white border-5 border-black shadow-brutal-lg p-6">
                    <div class="inline-block bg-primary-600 text-white border-3 border-black w-14 h-14 text-2xl font-black flex items-center justify-center mb-5">
                        01
                    </div>
                    <h3 class="text-xl font-black uppercase mb-3">Open the Map</h3>
                    <p class="text-base leading-relaxed">
                        Pull up the map. Pins mark every good-ice spot the community has found. Tap one to see the photo, the rating, and what people had to say.
                    </p>
                </div>

                <div class="bg-white border-5 border-black shadow-brutal-lg p-6">
                    <div class="inline-block bg-primary-600 text-white border-3 border-black w-14 h-14 text-2xl font-black flex items-center justify-center mb-5">
                        02
                    </div>
                    <h3 class="text-xl font-black uppercase mb-3">Rate What You Find</h3>
                    <p class="text-base leading-relaxed">
                        Tried a spot? Give it 1–5 stars. Leave a quick note about the ice. One rating per person, per place. No gaming the system.
                    </p>
                </div>

                <div class="bg-white border-5 border-black shadow-brutal-lg p-6">
                    <div class="inline-block bg-primary-600 text-white border-3 border-black w-14 h-14 text-2xl font-black flex items-center justify-center mb-5">
                        03
                    </div>
                    <h3 class="text-xl font-black uppercase mb-3">Submit Your Own</h3>
                    <p class="text-base leading-relaxed">
                        Found a place that's not on the map? Paste a Google Maps link, snap a photo of the ice, and submit it. Every good spot makes the map better.
                    </p>
                </div>
            </div>
        </div>
    </section>

    {{-- Mini-map preview --}}
    <section id="explore" class="border-b-5 border-black bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-20">
            <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between mb-8 gap-4">
                <div>
                    <h2 class="text-3xl sm:text-4xl font-black uppercase tracking-wider mb-3 inline-block bg-black text-white px-4 py-2">
                        The Map
                    </h2>
                    <p class="text-lg max-w-2xl">Every pin is a place someone vouched for. Tap one to see the goods.</p>
                </div>
                <a href="{{ route('map') }}"
                   class="inline-block text-center px-6 py-3 bg-primary-600 text-white font-black uppercase border-3 border-black shadow-brutal hover:shadow-brutal-lg hover:translate-x-[-2px] hover:translate-y-[-2px] transition-all whitespace-nowrap">
                    Open Full Map →
                </a>
            </div>

            <div class="border-5 border-black shadow-brutal-lg bg-white">
                <div id="mini-map"></div>
            </div>
        </div>
    </section>

    {{-- Gallery --}}
    @if($recentImages->count() > 0)
    <section class="border-b-5 border-black bg-primary-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-20">
            <h2 class="text-3xl sm:text-4xl font-black uppercase tracking-wider mb-3 inline-block bg-black text-white px-4 py-2">
                Receipts
            </h2>
            <p class="text-lg mb-10 max-w-2xl">Real photos from the community. No good ice, no submission.</p>

            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-5">
                @foreach($recentImages as $image)
                    <a href="{{ route('locations.show', $image->location_id) }}"
                       class="block group">
                        <div class="border-5 border-black shadow-brutal hover:shadow-brutal-lg hover:translate-x-[-2px] hover:translate-y-[-2px] transition-all bg-white">
                            <div class="aspect-square overflow-hidden border-b-3 border-black">
                                <img src="{{ $image->url }}"
                                     alt="{{ $image->location->name ?? 'Good ice spot' }}"
                                     class="w-full h-full object-cover">
                            </div>
                            @if($image->location)
                                <p class="px-3 py-2 text-xs font-black uppercase truncate">{{ $image->location->name }}</p>
                            @endif
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    {{-- Stats --}}
    <section class="border-b-5 border-black bg-black text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-20">
            <div class="grid sm:grid-cols-3 gap-8 mb-10">
                <div>
                    <div class="text-6xl sm:text-7xl font-black text-primary-400 mb-2 leading-none">
                        {{ number_format($stats['locations']) }}
                    </div>
                    <p class="text-lg font-bold uppercase tracking-wider">{{ $stats['locations'] === 1 ? 'Spot' : 'Spots' }} on the Map</p>
                </div>
                <div>
                    <div class="text-6xl sm:text-7xl font-black text-primary-400 mb-2 leading-none">
                        {{ number_format($stats['ratings']) }}
                    </div>
                    <p class="text-lg font-bold uppercase tracking-wider">{{ $stats['ratings'] === 1 ? 'Rating' : 'Ratings' }} Submitted</p>
                </div>
                <div>
                    <div class="text-6xl sm:text-7xl font-black text-primary-400 mb-2 leading-none">
                        {{ number_format($stats['contributors']) }}
                    </div>
                    <p class="text-lg font-bold uppercase tracking-wider">{{ $stats['contributors'] === 1 ? 'Contributor' : 'Contributors' }}</p>
                </div>
            </div>

            <div class="bg-primary-600 border-3 border-white p-5 sm:p-6 inline-block">
                <p class="text-base sm:text-lg font-bold uppercase tracking-wider">
                    → Always accepting contributions. Know a spot? <a href="{{ route('register') }}" class="underline decoration-3 underline-offset-4 hover:bg-white hover:text-primary-600 px-1">Add it.</a>
                </p>
            </div>
        </div>
    </section>

    {{-- Final CTA --}}
    <section class="border-b-5 border-black">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-24 text-center">
            <h2 class="text-4xl sm:text-5xl lg:text-6xl font-black uppercase leading-none tracking-tight mb-6">
                Now get out there <br class="hidden sm:block">and <span class="text-primary-600">explore the map.</span>
            </h2>
            <p class="text-lg sm:text-xl mb-10 max-w-2xl mx-auto">
                Free account. Takes ten seconds. We only ask so the spam bots stay away.
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ route('register') }}"
                   class="inline-block px-8 py-4 bg-primary-600 text-white font-black uppercase text-base sm:text-lg border-3 border-black shadow-brutal hover:shadow-brutal-lg hover:translate-x-[-2px] hover:translate-y-[-2px] transition-all">
                    Create an Account
                </a>
                <a href="{{ route('login') }}"
                   class="inline-block px-8 py-4 bg-white text-black font-black uppercase text-base sm:text-lg border-3 border-black shadow-brutal hover:shadow-brutal-lg hover:translate-x-[-2px] hover:translate-y-[-2px] transition-all">
                    I Already Have One
                </a>
            </div>
        </div>
    </section>

    {{-- Footer --}}
    <footer class="bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                <div class="flex items-center">
                    <img src="/logo.png" alt="Good Ice Map" class="h-8 w-auto me-2">
                    <span class="text-sm font-black uppercase tracking-wider">Good Ice Map</span>
                </div>
                <p class="text-xs sm:text-sm font-bold uppercase tracking-wider text-center sm:text-right">
                    Built for the chewers, by the chewers.
                </p>
            </div>
        </div>
    </footer>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        const miniMap = L.map('mini-map', {
            zoomControl: true,
            scrollWheelZoom: false,
            doubleClickZoom: false,
            dragging: true,
            touchZoom: true,
            boxZoom: false,
            keyboard: false,
        }).setView([39.8283, -98.5795], 4);

        L.tileLayer('https://tiles.stadiamaps.com/tiles/stamen_toner/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; <a href="https://www.stadiamaps.com/" target="_blank">Stadia Maps</a> &copy; <a href="https://www.stamen.com/" target="_blank">Stamen Design</a> &copy; <a href="https://openmaptiles.org/" target="_blank">OpenMapTiles</a> &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            maxZoom: 18
        }).addTo(miniMap);

        const customIcon = L.divIcon({
            className: 'custom-marker',
            html: `
                <svg width="40" height="52" viewBox="0 0 40 52" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="22" cy="17" r="13" fill="rgba(0,0,0,0.3)"/>
                    <path d="M 22 28 Q 22 35 22 48 Q 22 35 22 28 Z" fill="rgba(0,0,0,0.3)"/>
                    <circle cx="20" cy="15" r="13" fill="#9333ea" stroke="black" stroke-width="3"/>
                    <path d="M 9 20 Q 11 25 20 50 Q 29 25 31 20"
                          fill="#9333ea"
                          stroke="black"
                          stroke-width="3"
                          stroke-linejoin="round"/>
                    <rect x="9" y="15" width="22" height="10" fill="#9333ea"/>
                    <circle cx="20" cy="15" r="13" fill="none" stroke="black" stroke-width="3"/>
                    <circle cx="20" cy="15" r="6" fill="white" stroke="black" stroke-width="2"/>
                </svg>
            `,
            iconSize: [40, 52],
            iconAnchor: [20, 50],
            popupAnchor: [0, -50]
        });

        fetch('{{ route('api.locations') }}')
            .then(response => response.json())
            .then(locations => {
                locations.forEach(location => {
                    L.marker([location.latitude, location.longitude], {
                        icon: customIcon,
                        interactive: false,
                        keyboard: false,
                    }).addTo(miniMap);
                });

                if (locations.length > 0) {
                    const bounds = L.latLngBounds(locations.map(loc => [loc.latitude, loc.longitude]));
                    miniMap.fitBounds(bounds, { padding: [40, 40], maxZoom: 6 });
                }
            });
    </script>
@endsection
