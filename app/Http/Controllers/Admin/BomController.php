<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\HandlesTableSort;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BomRequest;
use App\Models\Bom;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\BomService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class BomController extends Controller implements HasMiddleware
{
    use HandlesTableSort;

    public static function middleware(): array
    {
        return [
            new Middleware('can:boms.view', only: ['index', 'show']),
            new Middleware('can:boms.create', only: ['create', 'store']),
            new Middleware('can:boms.edit', only: ['edit', 'update']),
            new Middleware('can:boms.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request, BomService $service): View
    {
        $boms = Bom::query()
            ->with(['product:id,name', 'items.component'])
            ->withCount(['items', 'productionOrders'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%' . $request->string('search') . '%';
                $q->where('name', 'like', $term)->orWhereHas('product', fn ($p) => $p->where('name', 'like', $term));
            })
            ->when($request->filled('status'), fn ($q) => $q->where('is_active', $request->string('status') === 'active'));

        $this->applyTableSort($boms, $request, [
            'product' => fn ($q, $d) => $q->orderBy(Product::select('name')->whereColumn('products.id', 'boms.product_id'), $d),
            'output' => 'output_quantity',
            'components' => 'items_count',
            'status' => 'is_active',
        ], fn ($q) => $q->latest('id'));

        $perPage = $this->perPageFor($request);
        $boms = $boms->paginate($perPage)->withQueryString();

        $boms->each(fn (Bom $b) => $b->computed_cost = $service->unitCost($b));

        return view('admin.boms.index', [
            'boms' => $boms,
            'perPage' => $perPage,
            'stats' => [
                'total' => Bom::count(),
                'active' => Bom::where('is_active', true)->count(),
            ],
            'filters' => $request->only('search', 'status', 'sort', 'dir', 'per_page'),
        ]);
    }

    public function create(): View
    {
        return view('admin.boms.create', [
            'bom' => new Bom(['output_quantity' => 1, 'labor_cost' => 0, 'overhead_cost' => 0, 'is_active' => true]),
            'initialItems' => [],
            ...$this->formData(),
        ]);
    }

    public function store(BomRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $product = Product::findOrFail($data['product_id']);
        $finishedVariantId = $product->defaultVariant?->id ?? $product->variants()->value('id');

        if (! $finishedVariantId) {
            return back()->withInput()->with('error', 'That product has no variant to produce. Add one first.');
        }

        $bom = Bom::create([
            'product_id' => $product->id,
            'product_variant_id' => $finishedVariantId,
            'name' => $data['name'] ?? null,
            'output_quantity' => $data['output_quantity'],
            'labor_cost' => round((float) ($data['labor_cost'] ?? 0), 2),
            'overhead_cost' => round((float) ($data['overhead_cost'] ?? 0), 2),
            'is_active' => $data['is_active'],
        ]);
        $bom->items()->createMany($this->itemRows($data['items']));

        // Defining a BOM makes the product manufacturable.
        $product->update(['is_manufacturable' => true]);

        return redirect()->route('admin.boms.show', $bom)->with('status', 'BOM created.');
    }

    public function show(Bom $bom, BomService $service): View
    {
        $bom->load(['product', 'variant', 'items.component.product:id,name', 'productionOrders' => fn ($q) => $q->latest('id')->limit(8)]);

        return view('admin.boms.show', [
            'bom' => $bom,
            'unitCost' => $service->unitCost($bom),
        ]);
    }

    public function edit(Bom $bom): View
    {
        $bom->load('items');

        return view('admin.boms.edit', [
            'bom' => $bom,
            'initialItems' => $bom->items->map(fn ($i) => [
                'component_variant_id' => (string) $i->component_variant_id,
                'quantity' => rtrim(rtrim(number_format((float) $i->quantity, 3, '.', ''), '0'), '.'),
                'waste_percent' => rtrim(rtrim(number_format((float) $i->waste_percent, 2, '.', ''), '0'), '.') ?: '0',
            ])->all(),
            ...$this->formData(),
        ]);
    }

    public function update(BomRequest $request, Bom $bom): RedirectResponse
    {
        $data = $request->validated();

        $bom->update([
            'product_id' => $data['product_id'],
            'name' => $data['name'] ?? null,
            'output_quantity' => $data['output_quantity'],
            'labor_cost' => round((float) ($data['labor_cost'] ?? 0), 2),
            'overhead_cost' => round((float) ($data['overhead_cost'] ?? 0), 2),
            'is_active' => $data['is_active'],
        ]);
        $bom->items()->delete();
        $bom->items()->createMany($this->itemRows($data['items']));

        return redirect()->route('admin.boms.show', $bom)->with('status', 'BOM updated.');
    }

    public function destroy(Bom $bom): RedirectResponse
    {
        if ($bom->productionOrders()->exists()) {
            return back()->with('error', 'Cannot delete a BOM with production history. Deactivate it instead.');
        }

        $bom->items()->delete();
        $bom->delete();

        return redirect()->route('admin.boms.index')->with('status', 'BOM deleted.');
    }

    // Helpers ------------------------------------------------------------------

    /** @return array<int, array<string, mixed>> */
    private function itemRows(array $items): array
    {
        return collect($items)->map(fn ($i) => [
            'component_variant_id' => (int) $i['component_variant_id'],
            'quantity' => (float) $i['quantity'],
            'waste_percent' => round((float) ($i['waste_percent'] ?? 0), 2),
        ])->all();
    }

    private function formData(): array
    {
        return [
            'productOptions' => Product::where('is_active', true)->orderBy('name')->pluck('name', 'id')->all(),
            'variantOptions' => $this->variantOptions(),
        ];
    }

    /** @return Collection<int, array{id:int, label:string, cost:string}> component variants */
    private function variantOptions(): Collection
    {
        return ProductVariant::query()
            ->where('is_active', true)
            ->with('product:id,name')
            ->orderBy('id')
            ->get(['id', 'product_id', 'sku', 'cost', 'stock_quantity'])
            ->map(fn (ProductVariant $v) => [
                'id' => $v->id,
                'label' => ($v->product?->name ?? 'Variant') . ' · ' . $v->sku,
                'cost' => rtrim(rtrim(number_format((float) $v->cost, 2, '.', ''), '0'), '.') ?: '0',
                'stock' => (float) $v->stock_quantity,
            ]);
    }
}
