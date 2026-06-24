<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StockMovement extends Model
{
    use HasFactory;

    // Signed movement types (§3.5 / §8)
    public const TYPE_PURCHASE_IN = 'purchase_in';
    public const TYPE_SALE_OUT = 'sale_out';
    public const TYPE_PRODUCTION_CONSUME = 'production_consume';
    public const TYPE_PRODUCTION_OUTPUT = 'production_output';
    public const TYPE_ADJUSTMENT = 'adjustment';
    public const TYPE_RETURN_IN = 'return_in';
    public const TYPE_TRANSFER = 'transfer';

    protected $fillable = [
        'product_variant_id', 'type', 'quantity', 'balance_after', 'unit_cost',
        'reference_type', 'reference_id', 'reason', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'balance_after' => 'decimal:3',
            'unit_cost' => 'decimal:2',
        ];
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
