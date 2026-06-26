<?php

namespace App\Services;

use App\Models\Purchase;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Purchasing (§6). Receiving a purchase restocks each line (moving-average cost),
 * and posts the inventory/cash/payable entries to the ledger — all in one
 * transaction. Cancelling a received purchase reverses both.
 */
class PurchaseService
{
    public function __construct(
        private StockService $stock,
        private CostingService $costing,
        private LedgerService $ledger,
    ) {}

    /** Receive goods: for each line recompute moving-avg cost, stock-in, then post the ledger. */
    public function receive(Purchase $purchase): void
    {
        if ($purchase->status === 'received') {
            throw new RuntimeException('This purchase has already been received.');
        }
        if ($purchase->status === 'cancelled') {
            throw new RuntimeException('A cancelled purchase cannot be received.');
        }

        DB::transaction(function () use ($purchase) {
            $purchase->load('items.variant');

            foreach ($purchase->items as $item) {
                $variant = $item->variant;
                if (! $variant) {
                    continue;
                }

                // Order matters: cost first (moving average uses the OLD quantity), then stock-in.
                $this->costing->recordPurchaseCost($variant, (float) $item->quantity, (float) $item->unit_cost);
                $this->stock->move(
                    $variant,
                    StockMovement::TYPE_PURCHASE_IN,
                    (float) $item->quantity,
                    (float) $item->unit_cost,
                    $purchase,
                    "Received {$purchase->purchase_number}",
                );
            }

            $purchase->update(['status' => 'received']);
            $this->ledger->post($this->ledgerLines($purchase), $purchase, "Purchase received {$purchase->purchase_number}", $purchase->purchase_date);
        });
    }

    /** Reverse a received purchase (or just void a draft). Never deletes history. */
    public function cancel(Purchase $purchase): void
    {
        if ($purchase->status === 'cancelled') {
            throw new RuntimeException('This purchase is already cancelled.');
        }

        DB::transaction(function () use ($purchase) {
            if ($purchase->status === 'received') {
                $purchase->load('items.variant');

                foreach ($purchase->items as $item) {
                    if (! $item->variant) {
                        continue;
                    }
                    // Reverse the stock-in (StockService rejects this if the goods were already consumed).
                    $this->stock->move(
                        $item->variant,
                        StockMovement::TYPE_ADJUSTMENT,
                        -(float) $item->quantity,
                        (float) $item->unit_cost,
                        $purchase,
                        "Reversal of cancelled {$purchase->purchase_number}",
                    );
                }

                // Reverse the ledger postings (debits ⇄ credits). Cost is not unwound.
                $this->ledger->post($this->ledgerLines($purchase, reverse: true), $purchase, "Purchase cancelled {$purchase->purchase_number}");
            }

            $purchase->update(['status' => 'cancelled']);
        });
    }

    /**
     * Inventory (debit) vs Cash + Accounts Payable (credit). `reverse` swaps sides.
     *
     * @return array<int, array{account:string, debit?:float, credit?:float}>
     */
    private function ledgerLines(Purchase $purchase, bool $reverse = false): array
    {
        $grand = (float) $purchase->grand_total;
        $cash = min((float) $purchase->paid_total, $grand);
        $payable = round($grand - $cash, 2);

        $d = $reverse ? 'credit' : 'debit';
        $c = $reverse ? 'debit' : 'credit';

        $lines = [['account' => 'inventory', $d => $grand]];
        if ($cash > 0) {
            $lines[] = ['account' => 'cash', $c => $cash];
        }
        if ($payable > 0) {
            $lines[] = ['account' => 'accounts_payable', $c => $payable];
        }

        return $lines;
    }
}
