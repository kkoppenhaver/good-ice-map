<?php

namespace App\Models;

use App\Support\StructuredData;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Location extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'address',
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

    protected $appends = ['url'];

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
     * Accept both "/locations/12" and "/locations/12-any-slug"; the leading
     * integer is what identifies the record, the slug is cosmetic.
     */
    public function resolveRouteBinding($value, $field = null): ?self
    {
        $id = (int) $value;

        if ($id <= 0 || ! preg_match('/^\d+(?:-.*)?$/', (string) $value)) {
            return null;
        }

        return $this->where('id', $id)->first();
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
