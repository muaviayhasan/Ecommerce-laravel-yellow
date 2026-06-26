<?php

namespace App\Services;

use App\Models\Bom;

/**
 * Bill-of-materials costing (§7.1). The computed unit cost feeds the suggested
 * price before any production has run.
 */
class BomService
{
    /**
     * Unit cost = (Σ component.cost × qty × (1 + waste%) + labor + overhead) / output_quantity.
     */
    public function unitCost(Bom $bom): float
    {
        $bom->loadMissing('items.component');

        $components = (float) $bom->items->sum(function ($item) {
            $cost = (float) ($item->component?->cost ?? 0);

            return $cost * (float) $item->quantity * (1 + (float) $item->waste_percent / 100);
        });

        $output = max((float) $bom->output_quantity, 0.001);

        return round(($components + (float) $bom->labor_cost + (float) $bom->overhead_cost) / $output, 2);
    }
}
