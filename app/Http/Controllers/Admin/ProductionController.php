<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\HandlesTableSort;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductionRequest;
use App\Models\Bom;
use App\Models\ProductionOrder;
use App\Services\BomService;
use App\Services\ProductionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;
use Throwable;

class ProductionController extends Controller implements HasMiddleware
{
    use HandlesTableSort;

    public static function middleware(): array
    {
        return [
            new Middleware('can:production.view', only: ['index', 'show']),
            new Middleware('can:production.create', only: ['create', 'store']),
            new Middleware('can:production.edit', only: ['edit', 'update']),
            new Middleware('can:production.delete', only: ['destroy']),
            new Middleware('can:production.complete', only: ['complete', 'cancel']),
        ];
    }

    public function index(Request $request): View
    {
        $orders = ProductionOrder::query()
            ->with(['variant.product:id,name', 'bom:id,product_id'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%' . $request->string('search') . '%';
                $q->where('production_number', 'like', $term)
                    ->orWhereHas('variant.product', fn ($p) => $p->where('name', 'like', $term));
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')));

        $this->applyTableSort($orders, $request, [
            'run' => 'production_number',
            'quantity' => 'quantity',
            'cost' => 'unit_cost',
            'status' => 'status',
        ], fn ($q) => $q->latest('id'));

        $perPage = $this->perPageFor($request);
        $orders = $orders->paginate($perPage)->withQueryString();

        return view('admin.production.index', [
            'orders' => $orders,
            'perPage' => $perPage,
            'stats' => [
                'total' => ProductionOrder::count(),
                'draft' => ProductionOrder::where('status', 'draft')->count(),
                'completed' => ProductionOrder::where('status', 'completed')->count(),
            ],
            'filters' => $request->only('search', 'status', 'sort', 'dir', 'per_page'),
        ]);
    }

    public function create(Request $request, BomService $service): View
    {
        return view('admin.production.create', [
            'order' => new ProductionOrder(['quantity' => 1]),
            'preselect' => $request->integer('bom'),
            'bomData' => $this->bomData($service),
        ]);
    }

    public function store(ProductionRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $bom = Bom::with('product')->findOrFail($data['bom_id']);
        $finishedVariantId = $bom->product_variant_id ?? $bom->product?->defaultVariant?->id ?? $bom->product?->variants()->value('id');

        if (! $finishedVariantId) {
            return back()->withInput()->with('error', 'This BOM has no finished variant to produce.');
        }

        $order = ProductionOrder::create([
            'production_number' => $this->nextNumber(),
            'bom_id' => $bom->id,
            'product_variant_id' => $finishedVariantId,
            'quantity' => $data['quantity'],
            'status' => 'draft',
            'notes' => $data['notes'] ?? null,
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('admin.production.show', $order)->with('status', 'Production order created as a draft.');
    }

    public function show(ProductionOrder $order, BomService $service): View
    {
        $order->load(['bom.items.component.product:id,name', 'variant.product:id,name', 'consumptions.component.product:id,name', 'creator']);

        return view('admin.production.show', [
            'order' => $order,
            'bomUnitCost' => $order->bom ? $service->unitCost($order->bom) : 0,
        ]);
    }

    public function edit(ProductionOrder $order, BomService $service): View|RedirectResponse
    {
        if ($order->status !== 'draft') {
            return redirect()->route('admin.production.show', $order)->with('error', 'Only draft production orders can be edited.');
        }

        return view('admin.production.edit', [
            'order' => $order,
            'preselect' => $order->bom_id,
            'bomData' => $this->bomData($service),
        ]);
    }

    public function update(ProductionRequest $request, ProductionOrder $order): RedirectResponse
    {
        if ($order->status !== 'draft') {
            return back()->with('error', 'Only draft production orders can be edited.');
        }

        $data = $request->validated();
        $bom = Bom::with('product')->findOrFail($data['bom_id']);
        $finishedVariantId = $bom->product_variant_id ?? $bom->product?->defaultVariant?->id ?? $bom->product?->variants()->value('id');

        $order->update([
            'bom_id' => $bom->id,
            'product_variant_id' => $finishedVariantId ?? $order->product_variant_id,
            'quantity' => $data['quantity'],
            'notes' => $data['notes'] ?? null,
        ]);

        return redirect()->route('admin.production.show', $order)->with('status', 'Production order updated.');
    }

    public function destroy(ProductionOrder $order): RedirectResponse
    {
        if ($order->status !== 'draft') {
            return back()->with('error', 'Only draft production orders can be deleted. Cancel a completed run instead.');
        }

        $order->delete();

        return redirect()->route('admin.production.index')->with('status', 'Production order deleted.');
    }

    public function complete(ProductionOrder $order, ProductionService $service): RedirectResponse
    {
        try {
            $service->complete($order);
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', "Production {$order->production_number} completed — components consumed, finished stock produced.");
    }

    public function cancel(ProductionOrder $order, ProductionService $service): RedirectResponse
    {
        try {
            $service->cancel($order);
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', "Production {$order->production_number} cancelled.");
    }

    // Helpers ------------------------------------------------------------------

    private function nextNumber(): string
    {
        $prefix = (string) setting('numbering', 'production_prefix', 'PRD-');

        return $prefix . str_pad((string) ((ProductionOrder::max('id') ?? 0) + 1), 5, '0', STR_PAD_LEFT);
    }

    /** Active BOMs with their unit cost + per-run component needs (for the create preview). */
    private function bomData(BomService $service): array
    {
        return Bom::where('is_active', true)
            ->with(['product:id,name', 'items.component.product:id,name'])
            ->orderBy('product_id')
            ->get()
            ->map(fn (Bom $bom) => [
                'id' => $bom->id,
                'label' => ($bom->product?->name ?? 'Product') . ($bom->name ? ' — ' . $bom->name : ''),
                'productName' => $bom->product?->name ?? 'Product',
                'output' => (float) $bom->output_quantity,
                'unitCost' => $service->unitCost($bom),
                'components' => $bom->items->map(fn ($i) => [
                    'label' => $i->component?->product?->name ?? 'Component',
                    'sku' => $i->component?->sku,
                    'perRun' => (float) $i->quantity * (1 + (float) $i->waste_percent / 100),
                    'stock' => (float) ($i->component?->stock_quantity ?? 0),
                ])->all(),
            ])->all();
    }
}
