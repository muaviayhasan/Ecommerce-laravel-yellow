<?php

namespace App\Services;

use App\Models\ProductVariant;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

/**
 * Manual inventory adjustments (§8). A signed correction that records a stock
 * movement AND posts the gain/write-off to the ledger, in one transaction.
 * Stock itself still flows through StockService (the only writer).
 */
class InventoryService
{
    public function __construct(
        private StockService $stock,
        private LedgerService $ledger,
    ) {}

    /**
     * Adjust a variant's on-hand by a signed delta with a required reason.
     * Positive = gain (found/returned), negative = write-off (damage/shrinkage).
     */
    public function adjust(ProductVariant $variant, float $delta, string $reason): StockMovement
    {
        return DB::transaction(function () use ($variant, $delta, $reason) {
            $cost = (float) $variant->cost;

            // StockService validates (rejects going negative) and writes the movement.
            $movement = $this->stock->move($variant, StockMovement::TYPE_ADJUSTMENT, $delta, $cost, null, $reason);

            $value = round(abs($delta) * $cost, 2);
            if ($value > 0) {
                // Gain: Inventory up vs adjustment income. Write-off: adjustment expense vs Inventory.
                $lines = $delta > 0
                    ? [['account' => 'inventory', 'debit' => $value], ['account' => 'inventory_adjustment', 'credit' => $value]]
                    : [['account' => 'inventory_adjustment', 'debit' => $value], ['account' => 'inventory', 'credit' => $value]];

                $this->ledger->post($lines, $movement, "Stock adjustment: {$reason}");
            }

            return $movement;
        });
    }
}
