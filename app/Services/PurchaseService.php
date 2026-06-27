<?php

namespace App\Services;

use App\Models\Purchase;
use App\Models\StockMovement;
use App\Models\SupplierPayment;
use Illuminate\Support\Carbon;
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

    /**
     * Record a payment to the supplier against a received purchase. Bumps `paid_total`
     * and posts Accounts Payable (debit) ↔ Cash/Bank (credit) — all in one transaction.
     *
     * @param  array{amount: mixed, paid_on?: ?string, method?: ?string, reference?: ?string, note?: ?string}  $data
     */
    public function recordPayment(Purchase $purchase, array $data): SupplierPayment
    {
        if ($purchase->status !== 'received') {
            throw new RuntimeException('Payments can only be recorded against a received purchase.');
        }

        $outstanding = $purchase->outstanding();
        if ($outstanding <= 0) {
            throw new RuntimeException('This purchase is already fully paid.');
        }

        $amount = round((float) $data['amount'], 2);
        if ($amount <= 0) {
            throw new RuntimeException('Enter a payment amount greater than zero.');
        }
        if ($amount > $outstanding) {
            throw new RuntimeException("Payment cannot exceed the outstanding balance of {$outstanding}.");
        }

        $method = ($data['method'] ?? 'cash') === 'bank' ? 'bank' : 'cash';
        $paidOn = ! empty($data['paid_on']) ? Carbon::parse($data['paid_on']) : now();

        return DB::transaction(function () use ($purchase, $data, $amount, $method, $paidOn) {
            $payment = $purchase->payments()->create([
                'supplier_id' => $purchase->supplier_id,
                'amount' => $amount,
                'paid_on' => $paidOn->toDateString(),
                'method' => $method,
                'reference' => $data['reference'] ?? null,
                'note' => $data['note'] ?? null,
                'created_by' => auth()->id(),
            ]);

            $purchase->increment('paid_total', $amount);

            $this->ledger->post([
                ['account' => 'accounts_payable', 'debit' => $amount],
                ['account' => $method, 'credit' => $amount],   // 'cash' or 'bank'
            ], $payment, "Payment for {$purchase->purchase_number}", $paidOn);

            return $payment;
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
