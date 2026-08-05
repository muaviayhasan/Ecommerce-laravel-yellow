<?php

namespace App\Services\ImportExport\Exporters;

use App\Models\Order;
use App\Services\ImportExport\XlsxResponse;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The orders report behind Admin → Reports. Same columns the old CSV export
 * carried, now as Excel/PDF like every other module.
 */
class ReportExporter
{
    public const HEADERS = ['order_number', 'date', 'customer', 'status', 'payment_status', 'grand_total', 'paid_total'];

    public const PDF_ROW_CAP = 2000;

    public function xlsx(Request $request): StreamedResponse
    {
        $rows = function () {
            foreach (Order::with('customer:id,name')->latest('id')->lazy(500) as $o) {
                yield [
                    $o->order_number,
                    ($o->placed_at ?? $o->created_at)?->format('Y-m-d'),
                    $o->customer?->name ?? 'Guest',
                    $o->status,
                    $o->payment_status,
                    $o->grand_total,
                    $o->paid_total,
                ];
            }
        };

        return XlsxResponse::stream('orders-report-' . now()->format('Y-m-d') . '.xlsx', self::HEADERS, $rows());
    }

    public function pdf(Request $request): ?Response
    {
        if (Order::count() > self::PDF_ROW_CAP) {
            return null;
        }

        return Pdf::loadView('admin.exports.pdf.report', [
            'orders' => Order::with('customer:id,name')->latest('id')->get(),
            'generatedAt' => now(),
        ])->download('orders-report-' . now()->format('Y-m-d') . '.pdf');
    }
}
