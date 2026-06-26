<?php

namespace App\Services;

use App\Models\ProductVariant;

/**
 * Costing (§9.1). Default method = moving average; all costing flows through here
 * so the method can change via `setting('inventory','costing_method')` later
 * without touching call sites.
 */
class CostingService
{
    /**
     * Re-blend the variant's moving-average cost on a stock-in, and persist it.
     * MUST be called BEFORE the stock-in increments `stock_quantity` (the formula
     * uses the OLD on-hand quantity). Returns the new unit cost.
     */
    public function recordPurchaseCost(ProductVariant $variant, float $receivedQty, float $receivedUnitCost): float
    {
        if (setting('inventory', 'costing_method', 'moving_average') !== 'moving_average') {
            return (float) $variant->cost; // other methods not implemented yet
        }

        $oldQty = (float) $variant->stock_quantity;
        $oldCost = (float) $variant->cost;
        $newQty = $oldQty + $receivedQty;

        // new = (oldQty·oldCost + recvQty·recvCost) / (oldQty + recvQty)
        $newCost = $newQty > 0
            ? round((($oldQty * $oldCost) + ($receivedQty * $receivedUnitCost)) / $newQty, 2)
            : round($receivedUnitCost, 2);

        $variant->forceFill(['cost' => $newCost])->save();

        return $newCost;
    }
}
