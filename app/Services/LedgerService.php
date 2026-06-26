<?php

namespace App\Services;

use App\Models\LedgerEntry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use RuntimeException;

/**
 * The ledger is the source of truth (§17). Every money event posts BALANCED
 * double-entry lines through here, inside the caller's transaction.
 */
class LedgerService
{
    /**
     * Post a balanced set of entries for one event.
     *
     * @param  array<int, array{account:string, debit?:float, credit?:float}>  $lines
     */
    public function post(array $lines, ?Model $reference = null, ?string $memo = null, ?Carbon $date = null): void
    {
        $debits = round(array_sum(array_map(fn ($l) => (float) ($l['debit'] ?? 0), $lines)), 2);
        $credits = round(array_sum(array_map(fn ($l) => (float) ($l['credit'] ?? 0), $lines)), 2);

        if (abs($debits - $credits) > 0.005) {
            throw new RuntimeException("Unbalanced ledger entry: debit {$debits} ≠ credit {$credits}.");
        }

        $date ??= now();

        foreach ($lines as $line) {
            $debit = round((float) ($line['debit'] ?? 0), 2);
            $credit = round((float) ($line['credit'] ?? 0), 2);

            if ($debit === 0.0 && $credit === 0.0) {
                continue; // skip empty lines
            }

            LedgerEntry::create([
                'entry_date' => $date,
                'account' => $line['account'],
                'debit' => $debit,
                'credit' => $credit,
                'reference_type' => $reference?->getMorphClass(),
                'reference_id' => $reference?->getKey(),
                'memo' => $memo,
                'created_by' => auth()->id(),
            ]);
        }
    }
}
