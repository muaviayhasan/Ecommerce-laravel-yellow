<?php

namespace App\Services\ImportExport\Exporters;

use App\Models\Customer;
use App\Services\ImportExport\XlsxResponse;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomerExporter
{
    /** Shared with the importer. */
    public const HEADERS = [
        'name', 'email', 'phone', 'address', 'type',
        'price_tier', 'opening_balance', 'is_active', 'notes',
    ];

    public const PDF_ROW_CAP = 2000;

    public function xlsx(Request $request): StreamedResponse
    {
        $rows = function () use ($request) {
            foreach ($this->filteredQuery($request)->orderBy('id')->lazy(500) as $c) {
                yield [
                    $c->name,
                    $c->email,
                    $c->phone,
                    $c->address,
                    $c->type,
                    $c->price_tier,
                    $c->opening_balance,
                    $c->is_active ? 1 : 0,
                    $c->notes,
                ];
            }
        };

        return XlsxResponse::stream('customers-' . now()->format('Y-m-d') . '.xlsx', self::HEADERS, $rows());
    }

    public function pdf(Request $request): ?Response
    {
        $query = $this->filteredQuery($request);

        if ($query->count() > self::PDF_ROW_CAP) {
            return null;
        }

        return Pdf::loadView('admin.exports.pdf.customers', [
            'customers' => $query->orderBy('id')->get(),
            'generatedAt' => now(),
        ])->download('customers-' . now()->format('Y-m-d') . '.pdf');
    }

    public function template(): StreamedResponse
    {
        return XlsxResponse::stream('customers-template.xlsx', self::HEADERS, [
            ['Bilal Traders', 'bilal@example.com', '0301-7654321', 'Shop 12, Hall Road, Lahore', 'wholesale', 'wholesale', 0, 1, 'Regular buyer'],
            ['Ayesha Malik', '', '0333-1112223', '', 'retail', 'retail', 0, 1, ''],
        ]);
    }

    /** Mirrors CustomerController::index() filters. */
    private function filteredQuery(Request $request)
    {
        return Customer::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = '%' . $request->string('search') . '%';
                $query->where(fn ($q) => $q
                    ->where('name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('phone', 'like', $term));
            })
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->string('type')))
            ->when($request->filled('status'), fn ($q) => $q->where('is_active', $request->string('status')->toString() === 'active'));
    }
}
