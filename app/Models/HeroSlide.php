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

    /**
     * A copy field with its price tokens resolved — use this for display instead of
     * reading the attribute directly.
     *
     *     highlight = "[min:coolers]"   renders as   "Rs 28,999"
     *
     * A method rather than an accessor on purpose: an accessor would rewrite the
     * value in the admin edit form too, so the token would be replaced by a frozen
     * price the moment anybody saved the slide — which is exactly the drift this is
     * meant to prevent.
     */
    public function display(string $field): string
    {
        return (string) \App\Support\Storefront::expandPriceTokens((string) ($this->{$field} ?? ''));
    }

    /** Resolved image URL: the library media (as a right-sized WebP), else the static path fallback. */
    public function getImageUrlAttribute(): ?string
    {
        return $this->image?->thumbUrl(800) ?: $this->image_path;
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
