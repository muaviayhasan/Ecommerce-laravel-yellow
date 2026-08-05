<?php

namespace App\Services\ImportExport\Exporters;

use App\Models\Quotation;
use App\Services\ImportExport\XlsxResponse;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class QuotationExporter
{
    public const HEADERS = [
        'quotation_number', 'date', 'customer', 'status', 'price_tier', 'items',
        'subtotal', 'discount_total', 'tax_total', 'grand_total', 'valid_until', 'notes',
    ];

    public const PDF_ROW_CAP = 2000;

    public function xlsx(Request $request): StreamedResponse
    {
        $rows = function () use ($request) {
            foreach ($this->filteredQuery($request)->with('customer:id,name')->withCount('items')->latest('id')->lazy(500) as $q) {
                yield [
                    $q->quotation_number,
                    $q->created_at?->format('Y-m-d'),
                    $q->customer?->name,
                    $q->status,
                    $q->price_tier,
                    $q->items_count,
                    $q->subtotal,
                    $q->discount_total,
                    $q->tax_total,
                    $q->grand_total,
                    $q->valid_until?->format('Y-m-d'),
                    $q->notes,
                ];
            }
        };

        return XlsxResponse::stream('quotations-' . now()->format('Y-m-d') . '.xlsx', self::HEADERS, $rows());
    }

    public function pdf(Request $request): ?Response
    {
        $query = $this->filteredQuery($request);

        if ($query->count() > self::PDF_ROW_CAP) {
            return null;
        }

        return Pdf::loadView('admin.exports.pdf.quotations', [
            'quotations' => $query->with('customer:id,name')->withCount('items')->latest('id')->get(),
            'generatedAt' => now(),
        ])->download('quotations-' . now()->format('Y-m-d') . '.pdf');
    }

    /** Mirrors QuotationController::index() filters. */
    private function filteredQuery(Request $request)
    {
        return Quotation::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = '%' . $request->string('search') . '%';
                $query->where(fn ($q) => $q->where('quotation_number', 'like', $term)
                    ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', $term)));
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')));
    }
}
