<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    // Item type (§4.1)
    public const TYPE_TRADING = 'trading';
    public const TYPE_MANUFACTURED = 'manufactured';
    public const TYPE_RAW = 'raw';
    public const TYPE_SERVICE = 'service';

    // Variant mode (§5.2)
    public const VARIANT_SIMPLE = 'simple';
    public const VARIANT_VARIABLE = 'variable';

    protected $fillable = [
        'category_id', 'brand_id', 'unit_id', 'name', 'slug', 'sku',
        'type', 'is_stock_tracked', 'is_purchasable', 'is_manufacturable',
        'is_sellable', 'is_web_listed', 'manufacture_mode', 'variant_mode',
        'short_description', 'description', 'highlights', 'specifications', 'base_price', 'markup_percent',
        'warranty', 'return_policy', 'video_url', 'length', 'width', 'height',
        'is_active', 'is_featured', 'is_trending', 'is_bestseller', 'is_pinned', 'published_at',
        'meta_title', 'meta_description', 'meta_keywords',
        'og_image_media_id', 'canonical_url', 'no_index',
    ];

    protected function casts(): array
    {
        return [
            'is_stock_tracked' => 'boolean',
            'is_purchasable' => 'boolean',
            'is_manufacturable' => 'boolean',
            'is_sellable' => 'boolean',
            'is_web_listed' => 'boolean',
            'highlights' => 'array',
            'specifications' => 'array',
            'base_price' => 'decimal:2',
            'markup_percent' => 'decimal:2',
            'length' => 'decimal:3',
            'width' => 'decimal:3',
            'height' => 'decimal:3',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'is_trending' => 'boolean',
            'is_bestseller' => 'boolean',
            'is_pinned' => 'boolean',
            'no_index' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    // Relations ----------------------------------------------------------------

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function attributes(): BelongsToMany
    {
        return $this->belongsToMany(Attribute::class, 'product_attribute');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function defaultVariant(): HasOne
    {
        return $this->hasOne(ProductVariant::class)->where('is_default', true);
    }

    public function media(): BelongsToMany
    {
        return $this->belongsToMany(Media::class, 'product_media')
            ->withPivot(['sort_order', 'is_primary'])
            ->orderByPivot('sort_order');
    }

    public function ogImage(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'og_image_media_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function boms(): HasMany
    {
        return $this->hasMany(Bom::class);
    }

    // Scopes -------------------------------------------------------------------

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->whereNotNull('published_at')->where('published_at', '<=', now());
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    /** Curated for the home-page "Trending" section. */
    public function scopeTrending(Builder $query): Builder
    {
        return $query->where('is_trending', true);
    }

    /** Curated for the home-page "Bestsellers" section. */
    public function scopeBestseller(Builder $query): Builder
    {
        return $query->where('is_bestseller', true);
    }

    /** The storefront catalog gate (§4.2): web-listed, active, sellable, published. */
    public function scopeWebListed(Builder $query): Builder
    {
        return $query->where('is_web_listed', true)
            ->where('is_active', true)
            ->where('is_sellable', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function isVariable(): bool
    {
        return $this->variant_mode === self::VARIANT_VARIABLE;
    }
}
