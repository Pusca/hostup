<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Property extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'lat' => 'float',
        'lng' => 'float',
        'base_price' => 'decimal:2',
        'cleaning_fee' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saving(function (Property $property) {
            if (empty($property->slug)) {
                $property->slug = Str::slug($property->title) ?: Str::random(8);
            }
            if (empty($property->ical_token)) {
                $property->ical_token = Str::random(32);
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function photos(): HasMany
    {
        return $this->hasMany(PropertyPhoto::class)->orderBy('sort_order');
    }

    public function coverPhoto()
    {
        return $this->hasOne(PropertyPhoto::class)->where('is_cover', true);
    }

    public function amenities(): BelongsToMany
    {
        return $this->belongsToMany(Amenity::class);
    }

    public function ratePlans(): HasMany
    {
        return $this->hasMany(RatePlan::class);
    }

    public function availability(): HasMany
    {
        return $this->hasMany(Availability::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function channelLinks(): HasMany
    {
        return $this->hasMany(ChannelLink::class);
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }
}
