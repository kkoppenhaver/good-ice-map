# Good Ice Map

A community-driven web application for discovering and rating restaurants, convenience stores, and gas stations that serve high-quality ice. Because sometimes you just need that perfect nugget ice for your drink.

## About

Good Ice Map helps you find locations with "good ice" - whether it's nugget ice, pebble ice, or just really good cubed ice. Users can:

- Browse an interactive map of approved locations
- Submit new locations with photo proof
- Rate and review locations (1-5 stars)
- Search for good ice spots near you

## Technology Stack

- **Backend:** Laravel 12.x with Breeze authentication
- **Frontend:** Blade templates, Alpine.js, Tailwind CSS
- **Mapping:** Leaflet.js with OpenStreetMap
- **Design:** Brutalist + sketch aesthetic using Rough.js
- **Database:** SQLite (development) / MySQL (production)

## Getting Started

### Requirements

- PHP 8.4+
- Composer
- Node.js & NPM
- SQLite or MySQL

### Installation

1. Clone the repository
```bash
git clone <repository-url>
cd good-ice-map
```

2. Install dependencies
```bash
composer install
npm install
```

3. Set up environment
```bash
cp .env.example .env
php artisan key:generate
```

4. Run migrations
```bash
php artisan migrate
```

5. Create storage symlink
```bash
php artisan storage:link
```

6. Start development servers
```bash
php artisan serve
npm run dev
```

The application will be available at `http://127.0.0.1:8000`

## Features

### For Everyone
- View interactive map with all approved locations
- See location details, photos, and ratings
- Search by name or location

### For Registered Users
- Submit new locations (requires at least one photo)
- Rate locations (1-5 stars)
- Write reviews
- Track your submissions

## Project Structure

See [PROJECT_PLAN.md](PROJECT_PLAN.md) for detailed technical documentation, database schema, and implementation phases.

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
