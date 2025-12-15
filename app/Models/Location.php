<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

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

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'average_rating' => 'decimal:2',
        'total_ratings' => 'integer',
        'ice_score' => 'integer',
        'scraped_at' => 'datetime',
    ];

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
}
