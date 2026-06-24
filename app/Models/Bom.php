<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bom extends Model
{
    use HasFactory;

    protected $table = 'boms';

    protected $fillable = [
        'product_id', 'product_variant_id', 'name', 'output_quantity',
        'labor_cost', 'overhead_cost', 'is_active', 'version',
    ];

    protected function casts(): array
    {
        return [
            'output_quantity' => 'decimal:3',
            'labor_cost' => 'decimal:2',
            'overhead_cost' => 'decimal:2',
            'is_active' => 'boolean',
            'version' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(BomItem::class);
    }

    public function productionOrders(): HasMany
    {
        return $this->hasMany(ProductionOrder::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
