<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\HandlesTableSort;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StockAdjustmentRequest;
use App\Models\Category;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Throwable;

class InventoryController extends Controller implements HasMiddleware
{
    use HandlesTableSort;

    public static function middleware(): array
    {
        return [
            new Middleware('can:stock.view', only: ['index', 'show']),
            new Middleware('can:stock.adjust', only: ['adjust']),
        ];
    }

    public function index(Request $request): View
    {
        $variants = ProductVariant::query()
            ->join('products', 'products.id', '=', 'product_variants.product_id')
            ->whereNull('products.deleted_at')
            ->where('product_variants.is_active', true)
            ->where('products.is_stock_tracked', true)
            ->select('product_variants.*')
            ->with(['product:id,name,category_id', 'product.category:id,name'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%' . $request->string('search') . '%';
                $q->where(fn ($w) => $w->where('products.name', 'like', $term)->orWhere('product_variants.sku', 'like', $term));
            })
            ->when($request->filled('category'), fn ($q) => $q->where('products.category_id', $request->integer('category')))
            ->when($request->filled('status'), function ($q) use ($request) {
                match ((string) $request->string('status')) {
                    'out' => $q->where('product_variants.stock_quantity', '<=', 0),
                    'low' => $q->whereColumn('product_variants.stock_quantity', '<=', 'product_variants.low_stock_threshold')->where('product_variants.stock_quantity', '>', 0),
                    'in' => $q->whereColumn('product_variants.stock_quantity', '>', 'product_variants.low_stock_threshold'),
                    default => null,
                };
            });

        $this->applyTableSort($variants, $request, [
            'variant' => 'products.name',
            'onhand' => 'product_variants.stock_quantity',
            'reserved' => 'product_variants.reserved_quantity',
            'available' => fn ($q, $d) => $q->orderByRaw('(product_variants.stock_quantity - product_variants.reserved_quantity) ' . $d),
            'cost' => 'product_variants.cost',
            'value' => fn ($q, $d) => $q->orderByRaw('(product_variants.stock_quantity * product_variants.cost) ' . $d),
        ], fn ($q) => $q->orderBy('products.name')->orderBy('product_variants.id'));

        $perPage = $this->perPageFor($request);
        $variants = $variants->paginate($perPage)->withQueryString();

        // KPI base: active, stock-tracked variants.
        $base = fn () => ProductVariant::where('is_active', true)
            ->whereHas('product', fn ($q) => $q->where('is_stock_tracked', true));

        return view('admin.inventory.index', [
            'variants' => $variants,
            'perPage' => $perPage,
            'categories' => Category::orderBy('name')->pluck('name', 'id'),
            'stats' => [
                'variants' => $base()->count(),
                'low' => $base()->whereColumn('stock_quantity', '<=', 'low_stock_threshold')->where('stock_quantity', '>', 0)->count(),
                'out' => $base()->where('stock_quantity', '<=', 0)->count(),
                'value' => (float) $base()->sum(DB::raw('stock_quantity * cost')),
            ],
            'filters' => $request->only('search', 'category', 'status', 'sort', 'dir', 'per_page'),
        ]);
    }

    public function show(ProductVariant $variant): View
    {
        $variant->load(['product.category', 'attributeValues:id,label,value']);

        $movements = StockMovement::where('product_variant_id', $variant->id)
            ->with('author:id,name')
            ->latest('id')
            ->paginate(per_page());

        return view('admin.inventory.show', [
            'variant' => $variant,
            'movements' => $movements,
        ]);
    }

    public function adjust(StockAdjustmentRequest $request, ProductVariant $variant, InventoryService $service): RedirectResponse
    {
        $data = $request->validated();

        // "set" gives a new absolute on-hand; "add" gives a signed delta directly.
        $delta = $data['mode'] === 'set'
            ? round((float) $data['quantity'] - (float) $variant->stock_quantity, 3)
            : (float) $data['quantity'];

        if ($data['mode'] === 'set' && (float) $data['quantity'] < 0) {
            return back()->with('error', 'A new on-hand count cannot be negative.');
        }
        if ($delta === 0.0) {
            return back()->with('error', 'That leaves the quantity unchanged.');
        }

        try {
            $service->adjust($variant, $delta, $data['reason']);
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', "Stock adjusted for {$variant->sku}.");
    }
}
