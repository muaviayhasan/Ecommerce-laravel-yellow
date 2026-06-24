<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id', 'sku',
        'cost', 'retail_price', 'wholesale_price', 'compare_at_price', 'price_is_manual',
        'stock_quantity', 'reserved_quantity', 'low_stock_threshold',
        'weight', 'barcode', 'image_media_id', 'is_active', 'is_default',
    ];

    protected function casts(): array
    {
        return [
            'cost' => 'decimal:2',
            'retail_price' => 'decimal:2',
            'wholesale_price' => 'decimal:2',
            'compare_at_price' => 'decimal:2',
            'price_is_manual' => 'boolean',
            'stock_quantity' => 'decimal:3',
            'reserved_quantity' => 'decimal:3',
            'low_stock_threshold' => 'decimal:3',
            'weight' => 'decimal:3',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
        ];
    }

    // Relations ----------------------------------------------------------------

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function attributeValues(): BelongsToMany
    {
        return $this->belongsToMany(AttributeValue::class, 'attribute_value_product_variant');
    }

    public function image(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'image_media_id');
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    // Scopes / helpers ---------------------------------------------------------

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeInStock(Builder $query): Builder
    {
        return $query->where('stock_quantity', '>', 0);
    }

    /** Quantity sellable now = on-hand minus what unpaid orders are holding. */
    public function availableQuantity(): float
    {
        return (float) $this->stock_quantity - (float) $this->reserved_quantity;
    }

    public function isLowStock(): bool
    {
        return $this->stock_quantity <= $this->low_stock_threshold;
    }

    public function isOnSale(): bool
    {
        return $this->compare_at_price !== null && $this->compare_at_price > $this->retail_price;
    }
}
