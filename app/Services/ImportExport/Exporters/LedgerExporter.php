<?php

namespace App\Services\ImportExport\Exporters;

use App\Models\LedgerEntry;
use App\Services\ImportExport\XlsxResponse;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LedgerExporter
{
    public const HEADERS = ['date', 'account', 'debit', 'credit', 'memo', 'reference', 'entered_by'];

    public const PDF_ROW_CAP = 2000;

    public function xlsx(Request $request): StreamedResponse
    {
        $rows = function () use ($request) {
            foreach ($this->filteredQuery($request)->with(['author:id,name', 'reference'])->lazy(500) as $e) {
                yield [
                    $e->entry_date?->format('Y-m-d'),
                    $e->account,
                    $e->debit,
                    $e->credit,
                    $e->memo,
                    self::referenceLabel($e),
                    $e->author?->name,
                ];
            }
        };

        return XlsxResponse::stream('ledger-' . now()->format('Y-m-d') . '.xlsx', self::HEADERS, $rows());
    }

    public function pdf(Request $request): ?Response
    {
        $query = $this->filteredQuery($request);

        if ($query->count() > self::PDF_ROW_CAP) {
            return null;
        }

        $entries = $query->with(['author:id,name', 'reference'])->get();

        return Pdf::loadView('admin.exports.pdf.ledger', [
            'entries' => $entries,
            'totals' => ['debit' => $entries->sum('debit'), 'credit' => $entries->sum('credit')],
            'generatedAt' => now(),
        ])->download('ledger-' . now()->format('Y-m-d') . '.pdf');
    }

    /** Human label for the polymorphic reference (purchase/production numbers when present). */
    public static function referenceLabel(LedgerEntry $e): string
    {
        if (! $e->reference_type) {
            return '';
        }

        $number = $e->reference?->purchase_number ?? $e->reference?->production_number ?? "#{$e->reference_id}";

        return class_basename($e->reference_type) . ' ' . $number;
    }

    /** Mirrors LedgerController::index() filters. */
    private function filteredQuery(Request $request)
    {
        return LedgerEntry::query()
            ->when($request->filled('account'), fn ($q) => $q->where('account', $request->string('account')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('entry_date', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('entry_date', '<=', $request->date('to')))
            ->when($request->filled('search'), fn ($q) => $q->where('memo', 'like', '%' . $request->string('search') . '%'))
            ->orderByDesc('entry_date')
            ->orderByDesc('id');
    }
}
