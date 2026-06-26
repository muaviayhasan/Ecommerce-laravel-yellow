<?php

namespace App\Services;

use App\Models\ProductionConsumption;
use App\Models\ProductionOrder;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Production / assembly runs (§7.2). Completing an order consumes the BOM's
 * components at their current moving-average cost, produces finished stock at
 * the resulting unit cost, and posts the manufacturing entries to the ledger —
 * all in one transaction. Cancelling a completed run reverses everything.
 */
class ProductionService
{
    public function __construct(
        private StockService $stock,
        private CostingService $costing,
        private LedgerService $ledger,
    ) {}

    public function complete(ProductionOrder $order): void
    {
        if ($order->status === 'completed') {
            throw new RuntimeException('This production order is already completed.');
        }
        if ($order->status === 'cancelled') {
            throw new RuntimeException('A cancelled production order cannot be completed.');
        }

        DB::transaction(function () use ($order) {
            $order->load('bom.items.component', 'variant');
            $bom = $order->bom;

            if (! $bom || ! $order->variant) {
                throw new RuntimeException('This production order is missing its BOM or finished variant.');
            }

            $produceQty = (float) $order->quantity;
            // Scale the recipe: a BOM run yields output_quantity finished units.
            $runs = $produceQty / max((float) $bom->output_quantity, 0.001);

            // 1) Consume each component at its current moving-average cost.
            $componentCost = 0.0;
            foreach ($bom->items as $item) {
                $component = $item->component;
                if (! $component) {
                    continue;
                }

                $consume = round((float) $item->quantity * (1 + (float) $item->waste_percent / 100) * $runs, 3);
                $unitCost = (float) $component->cost;
                $lineCost = round($consume * $unitCost, 2);
                $componentCost += $lineCost;

                $this->stock->move($component, StockMovement::TYPE_PRODUCTION_CONSUME, -$consume, $unitCost, $order, "Production {$order->production_number}");
                ProductionConsumption::create([
                    'production_order_id' => $order->id,
                    'component_variant_id' => $component->id,
                    'quantity' => $consume,
                    'unit_cost' => $unitCost,
                    'line_cost' => $lineCost,
                ]);
            }

            // 2) Resulting finished unit cost.
            $labor = round((float) $bom->labor_cost * $runs, 2);
            $overhead = round((float) $bom->overhead_cost * $runs, 2);
            $totalCost = round($componentCost + $labor + $overhead, 2);
            $unitCost = $produceQty > 0 ? round($totalCost / $produceQty, 2) : 0.0;

            // 3) Produce finished stock — re-blend the finished variant's moving-average cost, then stock in.
            $this->costing->recordPurchaseCost($order->variant, $produceQty, $unitCost);
            $this->stock->move($order->variant, StockMovement::TYPE_PRODUCTION_OUTPUT, $produceQty, $unitCost, $order, "Production {$order->production_number}");

            $order->update([
                'status' => 'completed',
                'total_component_cost' => $componentCost,
                'labor_cost' => $labor,
                'overhead_cost' => $overhead,
                'unit_cost' => $unitCost,
                'produced_at' => now(),
            ]);

            // 4) Ledger: Finished Inventory (debit) = Raw Inventory + Labor + Overhead (credit).
            $this->ledger->post($this->ledgerLines($componentCost, $labor, $overhead, $totalCost), $order, "Production completed {$order->production_number}");
        });
    }

    public function cancel(ProductionOrder $order): void
    {
        if ($order->status === 'cancelled') {
            throw new RuntimeException('This production order is already cancelled.');
        }

        DB::transaction(function () use ($order) {
            if ($order->status === 'completed') {
                $order->load('consumptions.component', 'variant');

                // Remove the produced finished stock (rejected if it has since been sold).
                $this->stock->move($order->variant, StockMovement::TYPE_ADJUSTMENT, -(float) $order->quantity, (float) $order->unit_cost, $order, "Reversal of {$order->production_number}");

                // Return each consumed component to stock.
                foreach ($order->consumptions as $c) {
                    if ($c->component) {
                        $this->stock->move($c->component, StockMovement::TYPE_ADJUSTMENT, (float) $c->quantity, (float) $c->unit_cost, $order, "Reversal of {$order->production_number}");
                    }
                }

                $this->ledger->post(
                    $this->ledgerLines((float) $order->total_component_cost, (float) $order->labor_cost, (float) $order->overhead_cost, null, reverse: true),
                    $order,
                    "Production cancelled {$order->production_number}",
                );
            }

            $order->update(['status' => 'cancelled']);
        });
    }

    /**
     * @return array<int, array{account:string, debit?:float, credit?:float}>
     */
    private function ledgerLines(float $componentCost, float $labor, float $overhead, ?float $totalCost = null, bool $reverse = false): array
    {
        $totalCost ??= round($componentCost + $labor + $overhead, 2);
        $d = $reverse ? 'credit' : 'debit';
        $c = $reverse ? 'debit' : 'credit';

        $lines = [['account' => 'inventory_finished', $d => $totalCost]];
        if ($componentCost > 0) {
            $lines[] = ['account' => 'inventory_raw', $c => $componentCost];
        }
        if ($labor > 0) {
            $lines[] = ['account' => 'labor', $c => $labor];
        }
        if ($overhead > 0) {
            $lines[] = ['account' => 'overhead', $c => $overhead];
        }

        return $lines;
    }
}
