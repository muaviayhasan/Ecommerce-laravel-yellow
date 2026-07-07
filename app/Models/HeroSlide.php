<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A single slide in the storefront home hero carousel. Managed from
 * Admin → Ecommerce → Hero Slides. Rendered by resources/views/storefront/home.blade.php.
 */
class HeroSlide extends Model
{
    protected $fillable = [
        'kicker', 'line1', 'line2', 'tail', 'highlight',
        'cta_label', 'cta_url', 'image_media_id', 'image_path', 'image_alt',
        'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function image(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'image_media_id');
    }

    /** Resolved image URL: the library media if chosen, else the static path fallback. */
    public function getImageUrlAttribute(): ?string
    {
        return $this->image?->url ?: $this->image_path;
    }

    /** Button target: the configured URL, else the storefront shop page. */
    public function getCtaLinkAttribute(): string
    {
        return filled($this->cta_url) ? $this->cta_url : route('shop');
    }

    /** Active slides in display order. */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }
}
