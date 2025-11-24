# Good Ice Scraper Guide

This guide explains how to use the Google Reviews scraper to find locations with good ice mentions.

## Setup

The scraper is already configured and ready to use. It uses SerpAPI to search Google Maps and reviews.

**API Usage**: You have 250 free searches per month with SerpAPI.

## Command Usage

### Basic Command

```bash
php artisan scrape:good-ice --lat=YOUR_LAT --lng=YOUR_LNG
```

### Options

- `--lat` (required): Latitude coordinate
- `--lng` (required): Longitude coordinate
- `--radius` (optional): Search radius in meters (default: 5000)
- `--types` (optional): Business types to search (default: gas station, restaurant, convenience store)
- `--min-score` (optional): Minimum number of ice mentions required (default: 1)
- `--auto-approve` (optional): Automatically approve found locations
- `--dry-run` (optional): Test without saving to database

### Examples

**Basic search near Austin, TX:**
```bash
php artisan scrape:good-ice --lat=30.2672 --lng=-97.7431
```

**Search only gas stations with larger radius:**
```bash
php artisan scrape:good-ice --lat=30.2672 --lng=-97.7431 --radius=10000 --types="gas station"
```

**Search multiple types:**
```bash
php artisan scrape:good-ice --lat=30.2672 --lng=-97.7431 --types="gas station" --types="convenience store"
```

**Test without saving (dry run):**
```bash
php artisan scrape:good-ice --lat=30.2672 --lng=-97.7431 --dry-run
```

**Auto-approve high-quality locations:**
```bash
php artisan scrape:good-ice --lat=30.2672 --lng=-97.7431 --min-score=3 --auto-approve
```

## How It Works

1. **Search**: Finds businesses by type near the specified coordinates
2. **Fetch Reviews**: Gets all reviews for each business
3. **Filter**: Looks for ice-related keywords in reviews:
   - ✅ "good ice", "best ice", "favorite ice"
   - ✅ "love the ice", "come for the ice"
   - ✅ "nugget ice", "pebble ice", "sonic ice"
   - ❌ Excludes: "ice cream", "iced coffee", "iced tea"
4. **Score**: Counts matching reviews (ice_score)
5. **Save**: Creates location records with:
   - `source = 'scraped'`
   - `status = 'pending'` (or 'approved' with --auto-approve)
   - `ice_score` = number of matching reviews

## Finding Good Coordinates

### Get coordinates for a city:
1. Go to Google Maps
2. Right-click on the city center
3. Click the coordinates to copy them

### Popular areas to try:
- Austin, TX: `30.2672, -97.7431`
- Dallas, TX: `32.7767, -96.7970`
- Houston, TX: `29.7604, -95.3698`
- Nashville, TN: `36.1627, -86.7816`
- Phoenix, AZ: `33.4484, -112.0740`

## API Cost Management

Each scraping run uses:
- 1 search per business type (default: 3 searches)
- 1 search per location with reviews (~20 searches per business type)

**Example**: A typical run might use 60-100 searches.

**Tips to conserve API calls:**
- Use smaller radius (--radius=3000)
- Search one business type at a time
- Use --dry-run first to preview results
- Increase --min-score to filter out low-quality matches

## Reviewing & Approving Locations

After scraping, locations are saved with `status = 'pending'`.

**To approve locations manually:**
```sql
-- View pending scraped locations
SELECT id, name, address, ice_score, business_type 
FROM locations 
WHERE source = 'scraped' AND status = 'pending'
ORDER BY ice_score DESC;

-- Approve a specific location
UPDATE locations SET status = 'approved' WHERE id = X;

-- Approve all with score >= 2
UPDATE locations SET status = 'approved' 
WHERE source = 'scraped' AND ice_score >= 2;
```

Or use `--auto-approve` flag to skip manual review:
```bash
php artisan scrape:good-ice --lat=30.2672 --lng=-97.7431 --min-score=2 --auto-approve
```

## Troubleshooting

### "Too many requests" error
- You've hit your API limit (250/month)
- Wait until next month or upgrade SerpAPI plan

### No results found
- Try a different location (some areas may not have good ice!)
- Lower --min-score to 1
- Increase --radius

### Results not showing on map
- Check that locations are approved: `status = 'approved'`
- Run: `SELECT * FROM locations WHERE source = 'scraped'` to verify they were saved

## Next Steps

Once you've scraped locations:
1. Review the ice_score and reviews
2. Approve the best ones
3. They'll automatically appear on the map!
4. Consider adding review snippets to location details page (future enhancement)
