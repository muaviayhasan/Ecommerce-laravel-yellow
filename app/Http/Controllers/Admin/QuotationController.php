<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\QuotationRequest;
use App\Models\Customer;
use App\Models\ProductVariant;
use App\Models\Quotation;
use App\Services\QuotationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Throwable;

class QuotationController extends Controller implements HasMiddleware
{
    /** Statuses a quote can be moved to from the show page (not "converted"). */
    private const STATUSES = ['draft', 'sent', 'accepted', 'rejected', 'expired'];

    public static function middleware(): array
    {
        return [
            new Middleware('can:quotations.view', only: ['index', 'show']),
            new Middleware('can:quotations.create', only: ['create', 'store']),
            new Middleware('can:quotations.edit', only: ['edit', 'update', 'status']),
            new Middleware('can:quotations.delete', only: ['destroy']),
            new Middleware('can:quotations.convert', only: ['convert']),
        ];
    }

    public function index(Request $request): View
    {
        $quotations = Quotation::query()
            ->with('customer:id,name')
            ->withCount('items')
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = '%' . $request->string('search') . '%';
                $query->where(fn ($q) => $q->where('quotation_number', 'like', $term)
                    ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', $term)));
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest('id')
            ->paginate(per_page())
            ->withQueryString();

        return view('admin.quotations.index', [
            'quotations' => $quotations,
            'filters' => $request->only('search', 'status'),
            'statuses' => self::STATUSES,
            'stats' => [
                'total' => Quotation::count(),
                'open' => Quotation::whereIn('status', ['draft', 'sent'])->count(),
                'accepted' => Quotation::where('status', 'accepted')->count(),
                'converted' => Quotation::where('status', 'converted')->count(),
            ],
        ]);
    }

    public function create(): View
    {
        return view('admin.quotations.create', [
            'quotation' => new Quotation([
                'price_tier' => 'retail',
                'valid_until' => now()->addDays((int) setting('quotation', 'default_validity_days', 14)),
                'notes' => setting('quotation', 'default_terms'),
            ]),
            'customers' => $this->customers(),
            'variantOptions' => $this->variantOptions(),
            'initialItems' => [['product_variant_id' => '', 'quantity' => '1', 'unit_price' => '', 'description' => '']],
            'taxRate' => (float) setting('tax', 'rate', 0),
        ]);
    }

    public function store(QuotationRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $quotation = DB::transaction(function () use ($data) {
            $quotation = Quotation::create($this->header($data) + [
                'quotation_number' => $this->nextNumber(),
                'status' => 'draft',
                'created_by' => auth()->id(),
            ]);
            $this->syncItems($quotation, $data['items']);
            $this->recalculate($quotation);

            return $quotation;
        });

        return redirect()->route('admin.quotations.show', $quotation)->with('status', 'Quotation created.');
    }

    public function show(Quotation $quotation): View
    {
        $quotation->load('items.variant.product:id,name,slug', 'customer', 'creator:id,name', 'convertedOrder:id,order_number');

        return view('admin.quotations.show', [
            'quotation' => $quotation,
            'statuses' => self::STATUSES,
        ]);
    }

    public function edit(Quotation $quotation): View
    {
        abort_if($quotation->status === 'converted', 403, 'Converted quotations are locked.');
        $quotation->load('items');

        return view('admin.quotations.edit', [
            'quotation' => $quotation,
            'customers' => $this->customers(),
            'variantOptions' => $this->variantOptions(),
            'initialItems' => $quotation->items->map(fn ($i) => [
                'product_variant_id' => (string) $i->product_variant_id,
                'quantity' => (string) $i->quantity,
                'unit_price' => (string) $i->unit_price,
                'description' => $i->description,
            ])->all(),
            'taxRate' => (float) setting('tax', 'rate', 0),
        ]);
    }

    public function update(QuotationRequest $request, Quotation $quotation): RedirectResponse
    {
        abort_if($quotation->status === 'converted', 403, 'Converted quotations are locked.');
        $data = $request->validated();

        DB::transaction(function () use ($data, $quotation) {
            $quotation->update($this->header($data));
            $this->syncItems($quotation, $data['items']);
            $this->recalculate($quotation);
        });

        return redirect()->route('admin.quotations.show', $quotation)->with('status', 'Quotation updated.');
    }

    /** Move a quote along its lifecycle (sent / accepted / rejected / expired). */
    public function status(Request $request, Quotation $quotation): RedirectResponse
    {
        $to = (string) $request->string('status');
        if (! in_array($to, self::STATUSES, true)) {
            return back()->with('error', 'Unknown status.');
        }
        if ($quotation->status === 'converted') {
            return back()->with('error', 'Converted quotations are locked.');
        }

        $quotation->update(['status' => $to]);

        return back()->with('status', "Quotation marked {$to}.");
    }

    public function convert(Quotation $quotation, QuotationService $service): RedirectResponse
    {
        if ($quotation->status !== 'accepted') {
            return back()->with('error', 'Only accepted quotations can be converted to an order.');
        }

        try {
            $order = $service->convert($quotation);
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.orders.show', $order)
            ->with('status', "Quotation converted to order {$order->order_number}.");
    }

    public function destroy(Quotation $quotation): RedirectResponse
    {
        if ($quotation->status === 'converted') {
            return back()->with('error', 'Converted quotations cannot be deleted.');
        }

        $quotation->delete();

        return redirect()->route('admin.quotations.index')->with('status', 'Quotation deleted.');
    }

    // ----- helpers ------------------------------------------------------------

    /** @param array<string, mixed> $data */
    private function header(array $data): array
    {
        return [
            'customer_id' => $data['customer_id'] ?? null,
            'valid_until' => $data['valid_until'] ?? null,
            'price_tier' => $data['price_tier'],
            'discount_total' => round((float) ($data['discount_total'] ?? 0), 2),
            'tax_total' => round((float) ($data['tax_total'] ?? 0), 2),
            'notes' => $data['notes'] ?? null,
        ];
    }

    /** @param array<int, array<string, mixed>> $items */
    private function syncItems(Quotation $quotation, array $items): void
    {
        $quotation->items()->delete();

        $variants = ProductVariant::with('product:id,name')
            ->whereIn('id', collect($items)->pluck('product_variant_id'))
            ->get()->keyBy('id');

        foreach ($items as $item) {
            $variant = $variants->get((int) $item['product_variant_id']);
            if (! $variant) {
                continue;
            }
            $qty = (float) $item['quantity'];
            $price = (float) $item['unit_price'];
            $quotation->items()->create([
                'product_variant_id' => $variant->id,
                'name_snapshot' => $variant->product?->name ?? 'Item',
                'description' => $item['description'] ?? null,
                'quantity' => $qty,
                'unit_price' => $price,
                'line_total' => round($qty * $price, 2),
            ]);
        }
    }

    private function recalculate(Quotation $quotation): void
    {
        $subtotal = round((float) $quotation->items()->sum('line_total'), 2);
        $grand = round($subtotal - (float) $quotation->discount_total + (float) $quotation->tax_total, 2);
        $quotation->update(['subtotal' => $subtotal, 'grand_total' => $grand]);
    }

    /** @return array<int, string> */
    private function customers(): array
    {
        return Customer::where('is_active', true)->orderBy('name')->pluck('name', 'id')->all();
    }

    /** @return array<int, array{id:int, label:string, retail:float, wholesale:float}> */
    private function variantOptions(): array
    {
        return ProductVariant::query()
            ->where('is_active', true)
            ->whereHas('product', fn ($q) => $q->where('is_sellable', true)->where('is_active', true))
            ->with('product:id,name')
            ->orderBy('id')
            ->limit(500)
            ->get(['id', 'product_id', 'sku', 'retail_price', 'wholesale_price'])
            ->map(fn (ProductVariant $v) => [
                'id' => $v->id,
                'label' => ($v->product?->name ?? 'Item') . ' — ' . $v->sku,
                'retail' => (float) $v->retail_price,
                'wholesale' => (float) ($v->wholesale_price ?? $v->retail_price),
            ])->all();
    }

    private function nextNumber(): string
    {
        $prefix = (string) setting('numbering', 'quotation_prefix', 'QUO-');

        return $prefix . str_pad((string) ((Quotation::max('id') ?? 0) + 1), 5, '0', STR_PAD_LEFT);
    }
}
