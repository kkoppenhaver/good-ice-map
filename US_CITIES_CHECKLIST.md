# US Cities Scraping Checklist - Top 50

Track your progress as you scrape each city for good ice locations.

## How to Use

1. Run: `php artisan scrape:city "City Name"`
2. Review results and approve locations if satisfied
3. Mark with ✅ when complete

## Progress: 0/50 Complete

### Top 50 US Cities by Population

- [ ] **New York, NY** - `php artisan scrape:city "New York"`
- [ ] **Los Angeles, CA** - `php artisan scrape:city "Los Angeles"`
- [ ] **Chicago, IL** - `php artisan scrape:city "Chicago"`
- [ ] **Houston, TX** - `php artisan scrape:city "Houston"`
- [ ] **Phoenix, AZ** - `php artisan scrape:city "Phoenix"`
- [ ] **Philadelphia, PA** - `php artisan scrape:city "Philadelphia"`
- [ ] **San Antonio, TX** - `php artisan scrape:city "San Antonio"`
- [ ] **San Diego, CA** - `php artisan scrape:city "San Diego"`
- [ ] **Dallas, TX** - `php artisan scrape:city "Dallas"`
- [ ] **San Jose, CA** - `php artisan scrape:city "San Jose"`
- [ ] **Austin, TX** - `php artisan scrape:city "Austin"`
- [ ] **Jacksonville, FL** - `php artisan scrape:city "Jacksonville"`
- [ ] **Fort Worth, TX** - `php artisan scrape:city "Fort Worth"`
- [ ] **Columbus, OH** - `php artisan scrape:city "Columbus"`
- [ ] **Charlotte, NC** - `php artisan scrape:city "Charlotte"`
- [ ] **San Francisco, CA** - `php artisan scrape:city "San Francisco"`
- [ ] **Indianapolis, IN** - `php artisan scrape:city "Indianapolis"`
- [ ] **Seattle, WA** - `php artisan scrape:city "Seattle"`
- [ ] **Denver, CO** - `php artisan scrape:city "Denver"`
- [ ] **Oklahoma City, OK** - `php artisan scrape:city "Oklahoma City"`
- [ ] **Nashville, TN** - `php artisan scrape:city "Nashville"`
- [ ] **El Paso, TX** - `php artisan scrape:city "El Paso"`
- [ ] **Washington, DC** - `php artisan scrape:city "Washington"`
- [ ] **Las Vegas, NV** - `php artisan scrape:city "Las Vegas"`
- [ ] **Boston, MA** - `php artisan scrape:city "Boston"`
- [ ] **Portland, OR** - `php artisan scrape:city "Portland"`
- [ ] **Detroit, MI** - `php artisan scrape:city "Detroit"`
- [ ] **Louisville, KY** - `php artisan scrape:city "Louisville"`
- [ ] **Memphis, TN** - `php artisan scrape:city "Memphis"`
- [ ] **Baltimore, MD** - `php artisan scrape:city "Baltimore"`
- [ ] **Milwaukee, WI** - `php artisan scrape:city "Milwaukee"`
- [ ] **Albuquerque, NM** - `php artisan scrape:city "Albuquerque"`
- [ ] **Tucson, AZ** - `php artisan scrape:city "Tucson"`
- [ ] **Fresno, CA** - `php artisan scrape:city "Fresno"`
- [ ] **Mesa, AZ** - `php artisan scrape:city "Mesa"`
- [ ] **Sacramento, CA** - `php artisan scrape:city "Sacramento"`
- [ ] **Atlanta, GA** - `php artisan scrape:city "Atlanta"`
- [ ] **Kansas City, MO** - `php artisan scrape:city "Kansas City"`
- [ ] **Colorado Springs, CO** - `php artisan scrape:city "Colorado Springs"`
- [ ] **Raleigh, NC** - `php artisan scrape:city "Raleigh"`
- [ ] **Omaha, NE** - `php artisan scrape:city "Omaha"`
- [ ] **Miami, FL** - `php artisan scrape:city "Miami"`
- [ ] **Long Beach, CA** - `php artisan scrape:city "Long Beach"`
- [ ] **Virginia Beach, VA** - `php artisan scrape:city "Virginia Beach"`
- [ ] **Oakland, CA** - `php artisan scrape:city "Oakland"`
- [ ] **Minneapolis, MN** - `php artisan scrape:city "Minneapolis"`
- [ ] **Tulsa, OK** - `php artisan scrape:city "Tulsa"`
- [ ] **Tampa, FL** - `php artisan scrape:city "Tampa"`
- [ ] **Arlington, TX** - `php artisan scrape:city "Arlington"`
- [ ] **New Orleans, LA** - `php artisan scrape:city "New Orleans"`

## Notes

- Each run uses approximately 60-100 API searches
- Free tier: 250 searches/month (~3-4 cities)
- Developer plan ($75): 5,000 searches (~80 cities)
- Use `--dry-run` first to preview results without saving
- Use `--min-score=2` to filter low-quality matches
- Use `--auto-approve` if you trust the results

## Commands Reference

**Dry run (preview only):**
```bash
php artisan scrape:city "Houston" --dry-run
```

**Scrape and auto-approve high-quality locations:**
```bash
php artisan scrape:city "Houston" --min-score=2 --auto-approve
```

**Review pending locations:**
```sql
SELECT id, name, address, ice_score, business_type 
FROM locations 
WHERE source = 'scraped' AND status = 'pending'
ORDER BY scraped_at DESC, ice_score DESC;
```

**Approve all from a specific scrape:**
```sql
UPDATE locations 
SET status = 'approved' 
WHERE source = 'scraped' 
  AND status = 'pending' 
  AND scraped_at >= '2025-11-24 00:00:00';
```
