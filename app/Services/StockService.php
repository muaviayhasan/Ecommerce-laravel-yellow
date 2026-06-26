<?php

namespace App\Services;

use App\Models\ProductVariant;
use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

/**
 * The ONLY place stock changes (§8). Every change is one signed `stock_movements`
 * row carrying `balance_after`; the variant's cached `stock_quantity` is updated
 * atomically here. Services never write `stock_quantity` directly.
 */
class StockService
{
    /**
     * Record a stock movement and update the variant's cached on-hand.
     *
     * @param  float  $quantity  signed — positive adds stock, negative removes
     */
    public function move(
        ProductVariant $variant,
        string $type,
        float $quantity,
        ?float $unitCost = null,
        ?Model $reference = null,
        ?string $reason = null,
    ): StockMovement {
        $balanceAfter = round((float) $variant->stock_quantity + $quantity, 3);

        if ($balanceAfter < 0 && ! $this->allowsNegativeStock()) {
            throw new RuntimeException("Insufficient stock for {$variant->sku} (need " . abs($quantity) . ", have {$variant->stock_quantity}).");
        }

        $movement = StockMovement::create([
            'product_variant_id' => $variant->id,
            'type' => $type,
            'quantity' => $quantity,
            'balance_after' => $balanceAfter,
            'unit_cost' => $unitCost,
            'reference_type' => $reference?->getMorphClass(),
            'reference_id' => $reference?->getKey(),
            'reason' => $reason,
            'created_by' => auth()->id(),
        ]);

        // The single permitted write to the cached running total.
        $variant->forceFill(['stock_quantity' => $balanceAfter])->save();

        return $movement;
    }

    private function allowsNegativeStock(): bool
    {
        return (bool) setting('inventory', 'allow_negative_stock', false);
    }
}
