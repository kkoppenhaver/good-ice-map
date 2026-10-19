<?php

namespace App\Models;

use App\Jobs\GeocodeLocation;
use App\Support\AddressParser;
use App\Support\ChainMatcher;
use App\Support\StructuredData;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class Location extends Model
{
    use SoftDeletes;

    /**
     * Guards against dispatching a second geocode job for the same instance
     * within one request (wasRecentlyCreated stays true after the first save).
     */
    public bool $geocodeDispatched = false;

    protected $fillable = [
        'name',
        'description',
        'address',
        'city',
        'state',
        'country',
        'chain',
        'google_maps_link',
        'latitude',
        'longitude',
        'submitted_by',
        'status',
        'average_rating',
        'total_ratings',
        'place_id',
        'source',
        'scraped_at',
        'ice_score',
        'business_type',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'average_rating' => 'decimal:2',
        'total_ratings' => 'integer',
        'ice_score' => 'integer',
        'scraped_at' => 'datetime',
    ];

    /**
     * URL-safe slug derived from the name, e.g. "hilton-new-york-fashion-district".
     */
    public function getSlugAttribute(): string
    {
        return Str::slug($this->name) ?: 'location';
    }

    /**
     * Canonical public URL for this location (/locations/{id}-{slug}).
     */
    public function getUrlAttribute(): string
    {
        return route('locations.show', $this);
    }

    /**
     * Route key is "{id}-{slug}" so generated links are always canonical.
     */
    public function getRouteKey(): mixed
    {
        return $this->id.'-'.$this->slug;
    }

    /**
     * Accept both "/locations/12" and "/locations/12-any-slug": the leading
     * integer identifies the record and the slug is cosmetic. The route's
     * where() constraint guarantees the value starts with digits. An explicit
     * $field (e.g. {location:place_id} or a scoped binding) falls back to the
     * default column lookup.
     */
    public function resolveRouteBinding($value, $field = null): ?Model
    {
        if ($field !== null) {
            return parent::resolveRouteBinding($value, $field);
        }

        return $this->where('id', (int) $value)->first();
    }

    protected static function booted(): void
    {
        // Derive city/state/country from the address and chain from the name
        // whenever either changes (or the derived fields are still empty).
        // (Wrapped in braces: a listener returning false would cancel the save.)
        static::saving(function (self $location) {
            $location->deriveGeoFields();
        });

        // If the address could not be parsed, reverse geocode after the
        // response is sent. Only on create or an address change, so a failed
        // lookup cannot re-trigger itself.
        static::saved(function (self $location) {
            if ($location->city === null
                && ! $location->geocodeDispatched
                && ($location->wasRecentlyCreated || $location->wasChanged('address'))
                && $location->latitude !== null && $location->longitude !== null) {
                $location->geocodeDispatched = true;
                GeocodeLocation::dispatch($location)->afterResponse();
            }
        });

        // Only changes that alter the sitemap bust its cache. Rating updates
        // touch average_rating/total_ratings on every submission and must not.
        static::created(fn () => Cache::forget('sitemap.xml'));
        static::updated(function (self $location) {
            if ($location->wasChanged(['status', 'name'])) {
                Cache::forget('sitemap.xml');
            }
        });
        static::deleted(fn () => Cache::forget('sitemap.xml'));
        static::restored(fn () => Cache::forget('sitemap.xml'));
    }

    /**
     * Fill city/state/country from the address and chain from the name.
     * Returns true when anything changed.
     *
     * Geo (city/state/country), in order:
     *  1. $force: always re-parse the address, even over manual or geocoded values.
     *  2. Any of city/state/country set explicitly in this save: keep them.
     *  3. Address unchanged and city already known: nothing to do.
     *  4. Address changed: apply the parse result, nulls included, so a stale
     *     city never survives a new address (the geocode job then refills it).
     *  5. Address unchanged and city unknown: apply the parse only if it found
     *     a city, so a later geocode result is not needed twice.
     *
     * Chain, in order:
     *  1. $force: always re-match the name.
     *  2. Chain set explicitly in this save: keep it.
     *  3. Name unchanged and chain already known: nothing to do.
     *  4. Otherwise match the name (null when it is not a known chain).
     */
    public function deriveGeoFields(bool $force = false): bool
    {
        $before = $this->only(['city', 'state', 'country', 'chain']);

        $this->deriveGeo($force);
        $this->deriveChain($force);

        return $before !== $this->only(['city', 'state', 'country', 'chain']);
    }

    private function deriveGeo(bool $force): void
    {
        if (! $force && $this->isDirty(['city', 'state', 'country'])) {
            return;
        }

        $addressChanged = $this->isDirty('address');

        if (! $force && ! $addressChanged && $this->city !== null) {
            return;
        }

        $parsed = AddressParser::parse($this->address);

        if (! $force && ! $addressChanged && $parsed['city'] === null) {
            return;
        }

        $this->city = $parsed['city'];
        $this->state = $parsed['state'];
        $this->country = $parsed['country'];
    }

    private function deriveChain(bool $force): void
    {
        if (! $force && $this->isDirty('chain')) {
            return;
        }

        if (! $force && ! $this->isDirty('name') && $this->chain !== null) {
            return;
        }

        $this->chain = ChainMatcher::match($this->name);
    }

    /**
     * Config entry for this location's chain, if any.
     */
    public function chainConfig(): ?array
    {
        return $this->chain ? config("chains.{$this->chain}") : null;
    }

    public function getChainNameAttribute(): ?string
    {
        return $this->chainConfig()['name'] ?? null;
    }

    /**
     * "New York, NY" style label, or null when the city is unknown.
     */
    public function getPlaceLabelAttribute(): ?string
    {
        if (! $this->city) {
            return null;
        }

        return $this->state ? "{$this->city}, {$this->state}" : $this->city;
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Short, human-readable summary used for meta/OG descriptions.
     */
    public function metaDescription(): string
    {
        $parts = ["Good ice at {$this->name}, {$this->address}."];

        if ($this->total_ratings > 0 && $this->average_rating) {
            $parts[] = sprintf(
                'Rated %s/5 by %d ice %s.',
                number_format((float) $this->average_rating, 1),
                $this->total_ratings,
                Str::plural('chewer', $this->total_ratings),
            );
        }

        if ($this->description) {
            $parts[] = Str::limit(trim(preg_replace('/\s+/', ' ', $this->description)), 120);
        }

        return Str::limit(implode(' ', $parts), 160);
    }

    /**
     * Schema.org LocalBusiness JSON-LD for the detail page.
     */
    public function structuredData(): array
    {
        $data = [
            '@context' => StructuredData::CONTEXT,
            '@type' => 'LocalBusiness',
            '@id' => $this->url,
            'name' => $this->name,
            'url' => $this->url,
            'address' => $this->address,
            'geo' => [
                '@type' => 'GeoCoordinates',
                'latitude' => (float) $this->latitude,
                'longitude' => (float) $this->longitude,
            ],
        ];

        if ($this->description) {
            $data['description'] = $this->description;
        }

        $image = $this->relationLoaded('images') ? $this->images->first() : $this->primaryImage();
        if ($image) {
            $data['image'] = $image->url;
        }

        if ($this->total_ratings > 0 && $this->average_rating) {
            $data['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => round((float) $this->average_rating, 1),
                'bestRating' => 5,
                'worstRating' => 1,
                'ratingCount' => (int) $this->total_ratings,
            ];
        }

        return $data;
    }

    public function breadcrumbStructuredData(): array
    {
        return StructuredData::breadcrumbs([
            ['name' => 'Good Ice Map', 'url' => route('home')],
            ['name' => 'Map', 'url' => route('map')],
            ['name' => $this->name, 'url' => $this->url],
        ]);
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function images(): HasMany
    {
        return $this->hasMany(LocationImage::class);
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class);
    }

    public function primaryImage(): ?LocationImage
    {
        return $this->images()->where('is_primary', true)->first();
    }

    public static function normalizeAddress(string $address): string
    {
        return strtolower(trim(preg_replace('/\s+/', ' ', $address)));
    }

    public static function findDuplicateByAddress(string $address): ?self
    {
        $needle = self::normalizeAddress($address);

        return self::where('status', 'approved')
            ->whereRaw('LOWER(TRIM(address)) = ?', [$needle])
            ->first();
    }
}
