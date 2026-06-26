<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LedgerEntry;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class LedgerController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('can:ledger.view')];
    }

    public function index(Request $request): View
    {
        // Filtered, paginated entries (the audit log).
        $entries = LedgerEntry::query()
            ->with(['author:id,name', 'reference'])
            ->when($request->filled('account'), fn ($q) => $q->where('account', $request->string('account')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('entry_date', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('entry_date', '<=', $request->date('to')))
            ->when($request->filled('search'), fn ($q) => $q->where('memo', 'like', '%' . $request->string('search') . '%'))
            ->orderByDesc('entry_date')
            ->orderByDesc('id')
            ->paginate(per_page())
            ->withQueryString();

        // All-time account totals → trial balance + derived position.
        $totals = LedgerEntry::query()
            ->selectRaw('account, SUM(debit) as debit, SUM(credit) as credit')
            ->groupBy('account')
            ->get()
            ->keyBy('account');

        // Net movement on an account, debit-positive (assets/expenses normal; liabilities/income flip).
        $bal = fn (string $account) => (float) (($totals[$account]->debit ?? 0) - ($totals[$account]->credit ?? 0));
        $sum = fn (array $accounts) => array_sum(array_map($bal, $accounts));

        $summary = [
            'cash' => $sum(['cash', 'bank']),
            'inventory' => $sum(['inventory', 'inventory_raw', 'inventory_finished']),
            'payable' => -$bal('accounts_payable'),
            'receivable' => $bal('accounts_receivable'),
            'revenue' => -$bal('sales_revenue'),
            'cogs' => $bal('cogs'),
            'tax' => -$bal('tax_payable'),
            'refunds' => $bal('refunds'),
        ];
        $summary['gross_profit'] = $summary['revenue'] - $summary['cogs'];

        $trial = $totals->map(fn ($t) => [
            'account' => $t->account,
            'debit' => (float) $t->debit,
            'credit' => (float) $t->credit,
        ])->sortBy('account')->values();

        return view('admin.ledger.index', [
            'entries' => $entries,
            'summary' => $summary,
            'trial' => $trial,
            'trialTotals' => ['debit' => (float) $trial->sum('debit'), 'credit' => (float) $trial->sum('credit')],
            'accounts' => $this->accountOptions($totals),
            'filters' => $request->only('account', 'from', 'to', 'search'),
        ]);
    }

    /** @return Collection<int, string> distinct account names for the filter. */
    private function accountOptions(Collection $totals): Collection
    {
        return $totals->keys()->sort()->values();
    }
}
