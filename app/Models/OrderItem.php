<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'order_id', 'product_variant_id', 'deal_id', 'name_snapshot', 'sku_snapshot',
        'attributes_snapshot', 'unit_price', 'quantity', 'line_total', 'cost_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'attributes_snapshot' => 'array',
            'unit_price' => 'decimal:2',
            'quantity' => 'decimal:3',
            'line_total' => 'decimal:2',
            'cost_snapshot' => 'decimal:2',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}
