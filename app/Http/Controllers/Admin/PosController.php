<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PosSaleRequest;
use App\Models\Customer;
use App\Models\ProductVariant;
use App\Services\SalesService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;
use Throwable;

class PosController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:pos.access', only: ['index', 'search']),
            new Middleware('can:pos.sell', only: ['store']),
        ];
    }

    public function index(): View
    {
        return view('admin.pos.index', [
            'customers' => Customer::where('is_active', true)->orderBy('name')
                ->get(['id', 'name', 'price_tier'])
                ->map(fn (Customer $c) => ['id' => $c->id, 'name' => $c->name, 'wholesale' => $c->price_tier === 'wholesale']),
            'taxEnabled' => (bool) setting('tax', 'enabled', false),
            'taxRate' => (float) setting('tax', 'rate', 0),
            'currency' => setting('general', 'currency_symbol', 'Rs'),
        ]);
    }

    /** Live product search (JSON) — sellable variants by name / SKU / barcode. */
    public function search(Request $request): JsonResponse
    {
        $term = trim((string) $request->string('q'));
        if ($term === '') {
            return response()->json([]);
        }

        $like = '%' . $term . '%';
        $variants = ProductVariant::query()
            ->where('product_variants.is_active', true)
            ->whereHas('product', fn ($q) => $q->where('is_sellable', true)->where('is_active', true))
            ->where(fn ($q) => $q
                ->where('sku', 'like', $like)
                ->orWhere('barcode', $term)
                ->orWhereHas('product', fn ($p) => $p->where('name', 'like', $like)))
            ->with('product:id,name')
            ->limit(20)
            ->get(['id', 'product_id', 'sku', 'retail_price', 'stock_quantity']);

        return response()->json($variants->map(fn (ProductVariant $v) => [
            'id' => $v->id,
            'name' => $v->product?->name ?? 'Item',
            'sku' => $v->sku,
            'price' => (float) $v->retail_price,
            'stock' => (float) $v->stock_quantity,
        ]));
    }

    public function store(PosSaleRequest $request, SalesService $sales): RedirectResponse
    {
        $data = $request->validated();
        $customer = ! empty($data['customer_id']) ? Customer::find($data['customer_id']) : null;

        $variants = ProductVariant::with('product:id,name')
            ->whereIn('id', collect($data['items'])->pluck('variant_id'))
            ->get()
            ->keyBy('id');

        $lines = [];
        foreach ($data['items'] as $item) {
            $variant = $variants->get((int) $item['variant_id']);
            if ($variant) {
                $lines[] = ['variant' => $variant, 'quantity' => (float) $item['quantity']];
            }
        }

        if (empty($lines)) {
            return back()->with('error', 'The cart is empty.');
        }

        $taxRate = setting('tax', 'enabled', false) ? (float) setting('tax', 'rate', 0) : 0;

        try {
            $order = $sales->place('pos', $customer, $lines, [
                'payment_method' => $data['payment_method'],
                'tax_rate' => $taxRate,
                'pay_full' => true,
            ]);
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.pos.index')
            ->with('status', "Sale {$order->order_number} completed.")
            ->with('last_order_id', $order->id);
    }
}
