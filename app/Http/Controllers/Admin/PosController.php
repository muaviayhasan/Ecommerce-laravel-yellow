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
            'defaultCustomerId' => $this->defaultCustomerId(),
            'allowNegative' => (bool) setting('inventory', 'allow_negative_stock', false),
            'taxEnabled' => (bool) setting('tax', 'enabled', false),
            'taxRate' => (float) setting('tax', 'rate', 0),
            'currency' => setting('general', 'currency_symbol', 'Rs'),
        ]);
    }

    /**
     * Live product search (JSON) — sellable variants by name / SKU / barcode.
     * With no query it returns the latest products; `offset` drives infinite scroll.
     */
    public function search(Request $request): JsonResponse
    {
        $term = trim((string) $request->string('q'));
        $offset = max(0, $request->integer('offset'));
        $limit = 15;

        $query = ProductVariant::query()
            ->where('product_variants.is_active', true)
            ->whereHas('product', fn ($q) => $q->where('is_sellable', true)->where('is_active', true))
            ->with(['product:id,name,is_stock_tracked', 'product.media', 'image']);

        if ($term !== '') {
            $like = '%' . $term . '%';
            $query->where(fn ($q) => $q
                ->where('sku', 'like', $like)
                ->orWhere('barcode', $term)
                ->orWhereHas('product', fn ($p) => $p->where('name', 'like', $like)));
        }

        $variants = $query->orderByDesc('id')
            ->skip($offset)->take($limit)
            ->get(['id', 'product_id', 'sku', 'retail_price', 'stock_quantity', 'image_media_id']);

        return response()->json($variants->map(fn (ProductVariant $v) => [
            'id' => $v->id,
            'name' => $v->product?->name ?? 'Item',
            'sku' => $v->sku,
            'price' => (float) $v->retail_price,
            'stock' => (float) $v->stock_quantity,
            'tracked' => (bool) $v->product?->is_stock_tracked,
            'image' => $this->variantImage($v),
        ]));
    }

    /** Default "Walk-in" customer: the configured POS customer, else the walk-in record. */
    private function defaultCustomerId(): ?int
    {
        return (int) setting('pos', 'default_customer', 0)
            ?: Customer::where('is_active', true)->where('name', 'like', 'Walk-in%')->value('id');
    }

    /** Thumbnail URL for a variant: its own image, else the product's primary image. */
    private function variantImage(ProductVariant $variant): ?string
    {
        if ($variant->image) {
            return $variant->image->url;
        }

        $media = $variant->product?->media;

        return ($media?->firstWhere('pivot.is_primary', true) ?? $media?->first())?->url;
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
                'discount_type' => $data['discount_type'],
                'discount_value' => (float) ($data['discount_value'] ?? 0),
                'shipping_method' => $data['shipping_method'] ?? null,
                'courier' => $data['courier'] ?? null,
                'tracking_number' => $data['tracking_number'] ?? null,
                'shipping_total' => (float) ($data['shipping_total'] ?? 0),
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
