# Good Ice Map

A community-driven web application for discovering and rating restaurants, convenience stores, and gas stations that serve high-quality ice. Because sometimes you just need that perfect nugget ice for your drink.

## About

Good Ice Map helps you find locations with "good ice" — whether it's nugget ice, pebble ice, or just really good cubed ice. Users can:

- Browse an interactive map of approved locations
- Submit new locations with photo proof
- Rate locations (1-5 stars)
- Search for good ice spots near you

## Technology Stack

- **Backend:** Laravel 12.x with Breeze authentication
- **Frontend:** Blade templates, Alpine.js, Tailwind CSS
- **Mapping:** Leaflet.js with OpenStreetMap tiles
- **Design:** Brutalist aesthetic — monospace fonts, thick black borders, harsh drop shadows, purple accent
- **Graphics:** Rough.js for sketchy visual elements
- **Database:** SQLite (development)
- **Image Storage:** Cloudflare R2 (S3-compatible via `league/flysystem-aws-s3-v3`)

## Getting Started

### Requirements

- PHP 8.4+
- Composer
- Node.js & NPM
- SQLite

### Installation

1. Clone the repository
```bash
git clone <repository-url>
cd good-ice-map
```

2. Run the setup script (installs deps, generates key, runs migrations, builds frontend)
```bash
composer setup
```

Or manually:

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm run build
```

3. Configure environment variables in `.env`:
   - `GOOGLE_PLACES_API_KEY` — required for location search and place details
   - R2 storage credentials for image uploads (see `.env.example`)

### Development

```bash
composer dev
```

This runs PHP server, queue worker, log tail (pail), and Vite concurrently.

The application will be available at `http://127.0.0.1:8000`

## Features

### For Everyone
- View interactive map with all approved locations
- See location details, photos, and ratings
- Search by name or location via Google Places

### For Registered Users
- Submit new locations (supports Google Maps link parsing or manual coordinate entry)
- Upload photo proof (stored on Cloudflare R2)
- Rate locations 1-5 stars (one rating per user per location)
- Track and delete your submitted locations from the dashboard

## Key Routes

| Route | Description |
|---|---|
| `GET /` | Map homepage — all approved locations |
| `GET /api/locations` | JSON API for map markers |
| `GET /locations/{id}` | Location detail with ratings and photos |
| `GET /locations/create` | Submit a new location (auth required) |
| `GET /dashboard` | User's submitted locations (auth required) |
| `POST /locations/{id}/rate` | Submit a rating (auth required) |
| `POST /api/expand-url` | Expand shortened Google Maps URLs |
| `POST /api/search-place` | Search Google Places by text query |
| `POST /api/fetch-place-details` | Fetch place details by coordinates or place ID |

## Database Models

- **Location** — has many `LocationImage` and `Rating`, belongs to `User` (submitted_by), uses `SoftDeletes`. Tracks `status` (approved/pending), `source`, `ice_score`, `business_type`, and `place_id` (Google Places or OSM ID for deduplication).
- **Rating** — unique constraint on `(location_id, user_id)`, 1-5 integer scale.
- **LocationImage** — stores R2 path, has `url` accessor for generating storage URLs. First uploaded image is marked `is_primary`.

## Data Import & Scraping

The app includes several Artisan commands for seeding the database with known good-ice locations.

### Import via OpenStreetMap (free, no API key)

```bash
# Import a specific chain nationwide
php artisan import:chains --chain=sonic

# Import all known good-ice chains nationwide
php artisan import:chains --all

# Limit to a state or city
php artisan import:chains --chain=sonic --state=TX
php artisan import:chains --all --city=Austin

# Preview without saving
php artisan import:chains --all --dry-run
```

**Supported chains:** Sonic Drive-In, Chick-fil-A, Zaxby's, Raising Cane's, Cook Out, QuikTrip, Buc-ee's, Dutch Bros Coffee

### Batch import across major US cities

```bash
# Import all chains across ~48 major US cities
php artisan import:batch

# Import a specific chain across all cities
php artisan import:batch --chain=sonic

# Preview without saving
php artisan import:batch --dry-run
```

### Scrape via Google Places API (requires `GOOGLE_PLACES_API_KEY`)

```bash
# Scrape by coordinates
php artisan scrape:good-ice --lat=30.2672 --lng=-97.7431

# Scrape a pre-configured city
php artisan scrape:city Austin

# Scrape specific chains in a city
php artisan scrape:chains Austin --chains=Sonic --chains=QuikTrip

# All commands support --dry-run to preview results
```

### Reddit scraping (research tool)

```bash
# Search Reddit for good ice mentions and location recommendations
php artisan scrape:reddit
php artisan scrape:reddit --subreddit=texas --limit=200
```

## Development Commands

```bash
composer test          # Run PHPUnit test suite
./vendor/bin/pint      # Run Laravel Pint linter (PSR-12)
php artisan migrate    # Run database migrations
php artisan tinker     # Interactive REPL
npm run build          # Build frontend assets
```

## Project Structure

```
app/
  Http/Controllers/
    LocationController.php   — main CRUD + Google Places API helpers
    RatingController.php     — rating submission (updateOrCreate)
  Models/
    Location.php             — core model with SoftDeletes
    Rating.php               — per-user rating
    LocationImage.php        — R2 image storage
  Console/Commands/
    ImportChainLocations.php — OpenStreetMap-based chain importer
    BatchImportChains.php    — batch importer across major US cities
    ScrapeGoodIceLocations.php — Google Places review scraper
    ScrapeCityByName.php     — city-specific Google Places scraper
    ScrapeChains.php         — chain-specific Google Places scraper
    ScrapeReddit.php         — Reddit research scraper
  Services/
    GooglePlacesScraperService.php — Google Places API integration
    OpenStreetMapService.php       — OSM Overpass API integration
    RedditScraperService.php       — Reddit API integration
resources/views/
  locations/
    index.blade.php          — map homepage with Leaflet.js
    show.blade.php           — location detail page
    create.blade.php         — submission form
  dashboard.blade.php        — user's submitted locations
```

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
