<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
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

    /* ---- SEO ------------------------------------------------------------ */

    public function metaTitle(): string
    {
        if ($this->meta_title) {
            return $this->meta_title;
        }

        $where = $this->city ? " a {$this->city}" : '';

        return "{$this->title}{$where} · Affitto breve";
    }

    public function metaDescription(): string
    {
        if ($this->meta_description) {
            return $this->meta_description;
        }

        $base = trim(strip_tags((string) $this->description));
        if ($base !== '') {
            return Str::limit($base, 155);
        }

        return "{$this->title}" . ($this->city ? " a {$this->city}" : '') . ': prenota direttamente il tuo soggiorno.';
    }

    public function locationLabel(): string
    {
        return collect([$this->city, $this->region])->filter()->implode(', ');
    }

    /* ---- Logo immobile -------------------------------------------------- */

    public function getLogoUrlAttribute(): ?string
    {
        if (! $this->logo_path) {
            return null;
        }
        if (str_starts_with($this->logo_path, 'http') || str_starts_with($this->logo_path, '/')) {
            return $this->logo_path;
        }

        return Storage::disk('public')->url($this->logo_path);
    }

    /* ---- Video tour ----------------------------------------------------- */

    public function getHasVideoAttribute(): bool
    {
        return ! empty($this->video_url);
    }

    /**
     * @return array{type:string, embed?:string, src?:string, url:string}|null
     */
    public function videoEmbed(): ?array
    {
        $url = trim((string) $this->video_url);
        if ($url === '') {
            return null;
        }

        if (preg_match('~(?:youtu\.be/|youtube\.com/(?:watch\?v=|embed/|shorts/))([\w-]{11})~i', $url, $m)) {
            return ['type' => 'youtube', 'embed' => "https://www.youtube.com/embed/{$m[1]}?rel=0", 'url' => $url];
        }

        if (preg_match('~vimeo\.com/(?:video/)?(\d+)~i', $url, $m)) {
            return ['type' => 'vimeo', 'embed' => "https://player.vimeo.com/video/{$m[1]}", 'url' => $url];
        }

        if (preg_match('~\.(mp4|webm|mov|m4v)(\?.*)?$~i', $url)) {
            return ['type' => 'file', 'src' => $url, 'url' => $url];
        }

        return ['type' => 'link', 'url' => $url];
    }
}
