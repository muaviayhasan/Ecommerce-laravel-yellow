<?php

namespace App\Services\ImportExport\Exporters;

use App\Models\Order;
use App\Services\ImportExport\XlsxResponse;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrderExporter
{
    public const HEADERS = [
        'order_number', 'date', 'channel', 'customer', 'status', 'payment_method', 'payment_status',
        'items', 'subtotal', 'discount_total', 'tax_total', 'shipping_total', 'grand_total', 'paid_total',
        'courier', 'tracking_number', 'delivered_at',
    ];

    public const PDF_ROW_CAP = 2000;

    public function xlsx(Request $request): StreamedResponse
    {
        $rows = function () use ($request) {
            foreach ($this->filteredQuery($request)->with('customer:id,name')->withCount('items')->latest('id')->lazy(500) as $o) {
                yield [
                    $o->order_number,
                    ($o->placed_at ?? $o->created_at)?->format('Y-m-d H:i'),
                    $o->channel,
                    $o->customer?->name ?? $o->walk_in_name ?? 'Guest',
                    $o->status,
                    $o->payment_method,
                    $o->payment_status,
                    $o->items_count,
                    $o->subtotal,
                    $o->discount_total,
                    $o->tax_total,
                    $o->shipping_total,
                    $o->grand_total,
                    $o->paid_total,
                    $o->courier,
                    $o->tracking_number,
                    $o->delivered_at?->format('Y-m-d H:i'),
                ];
            }
        };

        return XlsxResponse::stream('orders-' . now()->format('Y-m-d') . '.xlsx', self::HEADERS, $rows());
    }

    public function pdf(Request $request): ?Response
    {
        $query = $this->filteredQuery($request);

        if ($query->count() > self::PDF_ROW_CAP) {
            return null;
        }

        return Pdf::loadView('admin.exports.pdf.orders', [
            'orders' => $query->with('customer:id,name')->withCount('items')->latest('id')->get(),
            'generatedAt' => now(),
        ])->setPaper('a4', 'landscape')->download('orders-' . now()->format('Y-m-d') . '.pdf');
    }

    /** Mirrors OrderController::index() filters. */
    private function filteredQuery(Request $request)
    {
        return Order::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = '%' . $request->string('search') . '%';
                $query->where(fn ($q) => $q->where('order_number', 'like', $term)
                    ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', $term)));
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('payment'), fn ($q) => $q->where('payment_status', $request->string('payment')));
    }
}
