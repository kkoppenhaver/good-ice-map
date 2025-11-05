# Good Ice Map - Project Plan

## Overview
A Laravel-based web application for discovering and rating locations that serve "good ice". Users can browse a map, submit new locations with image proof, and rate existing locations. The design aesthetic combines brutalist and sketch styles (similar to TL;Draw).

## Technology Stack

### Backend
- Laravel 11.x
- MySQL/PostgreSQL
- Laravel Breeze (authentication)
- Intervention/Image (image processing)

### Frontend
- Blade templates
- Alpine.js (reactivity)
- Tailwind CSS
- Leaflet.js (mapping with OpenStreetMap)
- Rough.js (sketchy graphics)

### Storage
- Local filesystem (public/storage) for images

## Database Schema

### users
- id
- name
- email
- password
- email_verified_at
- timestamps

### locations
- id
- name (string)
- description (text, nullable)
- address (string)
- latitude (decimal 10,8)
- longitude (decimal 11,8)
- submitted_by (foreign key to users)
- status (enum: pending, approved, rejected)
- average_rating (decimal 3,2, nullable)
- total_ratings (integer, default 0)
- timestamps
- soft_deletes

### location_images
- id
- location_id (foreign key)
- image_path (string)
- is_primary (boolean)
- uploaded_by (foreign key to users)
- timestamps

### ratings
- id
- location_id (foreign key)
- user_id (foreign key)
- rating (integer 1-5)
- review (text, nullable)
- timestamps
- unique(location_id, user_id) - one rating per user per location

## Core Features

### Guest Users
- View map with all approved locations
- Click location markers to see details (name, images, rating, address)
- Search/filter locations by proximity or name
- View location detail pages

### Authenticated Users
All guest features plus:
- Submit new locations (name, address, description, photos)
- Rate locations (1-5 stars + optional review)
- Edit their own ratings
- View submission history

### Admin (Future)
- Approve/reject pending locations
- Moderate content

## Routes Structure

### Public Routes
- GET  /                          → Map view (home page)
- GET  /locations                 → List view alternative
- GET  /locations/{id}            → Location detail page
- GET  /api/locations             → JSON for map markers
- GET  /api/locations/nearby      → Filter by lat/lng + radius

### Auth Routes (Laravel Breeze)
- GET  /register
- POST /register
- GET  /login
- POST /login
- POST /logout

### Authenticated Routes
- GET  /locations/create          → Submit new location form
- POST /locations                 → Store new location
- GET  /dashboard                 → User's submissions
- POST /locations/{id}/rate       → Submit/update rating
- POST /locations/{id}/images     → Upload additional images

## Design System - Brutalist + Sketch

### Color Palette
- Base: High contrast black/white
- Accent: Purple
- Usage: Map markers, buttons, rating stars, hover states, links

### Typography
- Fonts: Monospace (JetBrains Mono, Courier)
- Weights: Bold
- Headers: ALL CAPS

### Visual Elements
- Borders: Thick (3-5px) black borders
- Shapes: Rough/hand-drawn SVG shapes (via Rough.js)
- Shadows: None or harsh drop shadows (no blur)
- Alignment: Slightly imperfect (subtle rotations)
- Buttons: Chunky, high-contrast, clear labels
- Forms: Bold labels, thick borders, clear validation states
- Map markers: Custom SVG icons with sketchy style

### Layout
- Full-width map on homepage
- Sidebar or overlay for location details
- Grid layout for list views
- Minimal padding/margins for brutalist feel

## Implementation Phases

### Phase 1: Foundation (MVP)
1. Fresh Laravel install + auth (Breeze)
2. Database migrations + models
3. Basic Blade layouts with brutalist styling
4. Leaflet map integration
5. Location CRUD (submit, view)
6. Image upload handling
7. Basic rating system

**Estimated Time: 8-12 hours**

### Phase 2: Enhancement
1. Proximity search/filtering
2. User dashboard
3. Refine sketch design with Rough.js
4. Image gallery/lightbox
5. Form validation improvements
6. Loading states + Alpine.js interactivity

**Estimated Time: 6-8 hours**

### Phase 3: Polish
1. Admin panel for approvals
2. Search functionality
3. Responsive design refinement
4. Performance optimization
5. SEO considerations

**Estimated Time: 4-6 hours**

## Required Dependencies

### PHP (composer)
```json
{
  "laravel/breeze": "^2.0",
  "intervention/image": "^3.0"
}
```

### NPM
```json
{
  "alpinejs": "^3.13",
  "tailwindcss": "^3.4",
  "@tailwindcss/forms": "^0.5",
  "leaflet": "^1.9",
  "roughjs": "^4.6"
}
```

## Technical Considerations

### Image Handling
- Store in public/storage with symlink
- Generate thumbnails for map markers
- Validate file types (jpg, png, webp)
- Max file size: 5MB
- Require at least one image per submission

### Rating System
- 5-star rating (1-5 integer)
- Calculate average on each new rating
- Store total count for performance
- One rating per user per location
- Optional text review

### Map Integration
- Leaflet with OpenStreetMap tiles
- Cluster markers for better performance
- Custom purple markers for locations
- Click to show location popup
- Geolocation API for "near me" feature

### Security
- CSRF protection (Laravel default)
- Image validation and sanitization
- Rate limiting on submissions
- Authenticated routes for submissions/ratings
- Input sanitization for location data

## Future Enhancements
- Email notifications for new locations nearby
- Share location links
- Export locations list
- Mobile app (PWA)
- Advanced filtering (by rating, date added, etc.)
- Comments on locations
- User profiles
- Favorite/bookmark locations
