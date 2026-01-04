<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class LocationImage extends Model
{
    protected $fillable = [
        'location_id',
        'image_path',
        'is_primary',
        'uploaded_by',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    protected $appends = ['url'];

    public function getUrlAttribute(): string
    {
        return Storage::disk('r2')->url($this->image_path);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
