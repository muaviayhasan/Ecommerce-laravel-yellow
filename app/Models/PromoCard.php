<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A promo card in the grid below the storefront home hero. Managed from
 * Admin → Ecommerce → Promo Cards.
 */
class PromoCard extends Model
{
    public const TYPE_SHOP = 'shop';
    public const TYPE_PRICE = 'price';
    public const TYPE_PERCENT = 'percent';

    protected $fillable = [
        'kicker', 'title', 'subtitle', 'display_type', 'prefix', 'currency',
        'amount', 'cents', 'url', 'image_media_id', 'image_path', 'image_alt',
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

    /** Resolved image URL: the library media (as a right-sized WebP), else the static path fallback. */
    public function getImageUrlAttribute(): ?string
    {
        // Rendered in a 96px slot (w-24) — 192 covers 2x retina.
        return $this->image?->thumbUrl(192) ?: $this->image_path;
    }

    /** Card link: the configured URL, else the storefront shop page. */
    public function getLinkAttribute(): string
    {
        return filled($this->url) ? $this->url : route('shop');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }
}
