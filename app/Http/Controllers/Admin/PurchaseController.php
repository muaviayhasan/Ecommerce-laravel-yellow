<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PurchaseRequest;
use App\Http\Requests\Admin\SupplierPaymentRequest;
use App\Models\ProductVariant;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Services\PurchaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Throwable;

class PurchaseController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:purchases.view', only: ['index', 'show', 'print']),
            new Middleware('can:purchases.create', only: ['create', 'store']),
            new Middleware('can:purchases.edit', only: ['edit', 'update']),
            new Middleware('can:purchases.delete', only: ['destroy']),
            new Middleware('can:purchases.receive', only: ['receive', 'cancel']),
            new Middleware('can:purchases.pay', only: ['payment']),
        ];
    }

    public function index(Request $request): View
    {
        $purchases = Purchase::query()
            ->with('supplier:id,name')
            ->withCount('items')
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = '%' . $request->string('search') . '%';
                $query->where(fn ($q) => $q
                    ->where('purchase_number', 'like', $term)
                    ->orWhere('reference', 'like', $term)
                    ->orWhereHas('supplier', fn ($s) => $s->where('name', 'like', $term)));
            })
            ->when($request->filled('supplier'), fn ($q) => $q->where('supplier_id', $request->integer('supplier')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest('id')
            ->paginate(per_page())
            ->withQueryString();

        return view('admin.purchases.index', [
            'purchases' => $purchases,
            'suppliers' => Supplier::orderBy('name')->pluck('name', 'id'),
            'stats' => [
                'total' => Purchase::count(),
                'draft' => Purchase::where('status', 'draft')->count(),
                'received' => Purchase::where('status', 'received')->count(),
                'payable' => (float) Purchase::where('status', 'received')->sum(DB::raw('grand_total - paid_total')),
            ],
            'filters' => $request->only('search', 'supplier', 'status'),
        ]);
    }

    public function create(): View
    {
        return view('admin.purchases.create', [
            'purchase' => new Purchase(['purchase_date' => now()->toDateString()]),
            'initialItems' => [],
            ...$this->formData(),
        ]);
    }

    public function store(PurchaseRequest $request): RedirectResponse|JsonResponse
    {
        $data = $request->validated();
        $t = $this->totals($data);

        $purchase = Purchase::create([
            'purchase_number' => $this->nextNumber(),
            'supplier_id' => $data['supplier_id'],
            'status' => 'draft',
            'reference' => $data['reference'] ?? null,
            'purchase_date' => $data['purchase_date'],
            'delivery_method' => $data['delivery_method'] ?? null,
            'delivery_agent' => $data['delivery_agent'] ?? null,
            'delivery_contact' => $data['delivery_contact'] ?? null,
            'delivery_charge' => $t['delivery'],
            'subtotal' => $t['subtotal'],
            'discount_type' => $t['discount_type'],
            'discount_value' => $t['discount_value'],
            'discount_total' => $t['discount'],
            'tax_total' => $t['tax'],
            'grand_total' => $t['grand'],
            'paid_total' => round((float) ($data['paid_total'] ?? 0), 2),
            'notes' => $data['notes'] ?? null,
            'created_by' => auth()->id(),
        ]);
        $purchase->items()->createMany($t['rows']);

        return $this->saved($request, $purchase, 'Purchase created as a draft.');
    }

    public function show(Purchase $purchase): View
    {
        $purchase->load([
            'supplier', 'author', 'items.variant.product:id,name',
            'payments' => fn ($q) => $q->latest('paid_on')->latest('id'),
            'payments.author:id,name',
        ]);

        return view('admin.purchases.show', ['purchase' => $purchase]);
    }

    /** Printable purchase order — A4 sheet or 80mm thermal (?format= overrides the store default). */
    public function print(Request $request, Purchase $purchase): View
    {
        $purchase->load('supplier', 'items.variant.product:id,name');

        return view('admin.purchases.print', [
            'purchase' => $purchase,
            'billType' => bill_format($request->query('format')),
        ]);
    }

    public function edit(Purchase $purchase): View|RedirectResponse
    {
        if ($purchase->status !== 'draft') {
            return redirect()->route('admin.purchases.show', $purchase)->with('error', 'Only draft purchases can be edited.');
        }

        $purchase->load('items');

        return view('admin.purchases.edit', [
            'purchase' => $purchase,
            'initialItems' => $purchase->items->map(fn ($i) => [
                'product_variant_id' => (string) $i->product_variant_id,
                'quantity' => rtrim(rtrim(number_format((float) $i->quantity, 3, '.', ''), '0'), '.'),
                'unit_cost' => rtrim(rtrim(number_format((float) $i->unit_cost, 2, '.', ''), '0'), '.'),
            ])->all(),
            ...$this->formData(),
        ]);
    }

    public function update(PurchaseRequest $request, Purchase $purchase): RedirectResponse|JsonResponse
    {
        if ($purchase->status !== 'draft') {
            return back()->with('error', 'Only draft purchases can be edited.');
        }

        $data = $request->validated();
        $t = $this->totals($data);

        $purchase->update([
            'supplier_id' => $data['supplier_id'],
            'reference' => $data['reference'] ?? null,
            'purchase_date' => $data['purchase_date'],
            'delivery_method' => $data['delivery_method'] ?? null,
            'delivery_agent' => $data['delivery_agent'] ?? null,
            'delivery_contact' => $data['delivery_contact'] ?? null,
            'delivery_charge' => $t['delivery'],
            'subtotal' => $t['subtotal'],
            'discount_type' => $t['discount_type'],
            'discount_value' => $t['discount_value'],
            'discount_total' => $t['discount'],
            'tax_total' => $t['tax'],
            'grand_total' => $t['grand'],
            'paid_total' => round((float) ($data['paid_total'] ?? 0), 2),
            'notes' => $data['notes'] ?? null,
        ]);
        $purchase->items()->delete();
        $purchase->items()->createMany($t['rows']);

        return $this->saved($request, $purchase, 'Purchase updated.');
    }

    public function destroy(Purchase $purchase): RedirectResponse
    {
        if ($purchase->status !== 'draft') {
            return back()->with('error', 'Only draft purchases can be deleted. Cancel a received purchase instead.');
        }

        $purchase->items()->delete();
        $purchase->delete();

        return redirect()->route('admin.purchases.index')->with('status', 'Purchase deleted.');
    }

    public function receive(Purchase $purchase, PurchaseService $service): RedirectResponse
    {
        try {
            $service->receive($purchase);
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', "Purchase {$purchase->purchase_number} received — stock and ledger updated.");
    }

    public function cancel(Purchase $purchase, PurchaseService $service): RedirectResponse
    {
        try {
            $service->cancel($purchase);
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', "Purchase {$purchase->purchase_number} cancelled.");
    }

    /** Record a payment made to the supplier against this purchase's outstanding balance. */
    public function payment(SupplierPaymentRequest $request, Purchase $purchase, PurchaseService $service): RedirectResponse
    {
        try {
            $service->recordPayment($purchase, $request->validated());
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', 'Payment recorded.');
    }

    // Helpers ------------------------------------------------------------------

    /**
     * Respond to a save. AJAX submits (the create/edit forms post via fetch so a
     * validation error never reloads/loses the form) get a JSON redirect target;
     * the success toast is flashed for the next page load. Non-JS falls back to a redirect.
     */
    private function saved(Request $request, Purchase $purchase, string $message): RedirectResponse|JsonResponse
    {
        $url = route('admin.purchases.show', $purchase);

        if ($request->expectsJson()) {
            session()->flash('status', $message);

            return response()->json(['redirect' => $url]);
        }

        return redirect($url)->with('status', $message);
    }

    /**
     * Resolve all monetary totals from the submitted data. Discount is a fixed Rs amount
     * or a percentage of the subtotal, capped (≤ subtotal / ≤ 100%) to match the form & request.
     *
     * @return array{rows: array<int, array<string, mixed>>, subtotal: float, discount_type: string, discount_value: float, discount: float, tax: float, grand: float}
     */
    private function totals(array $data): array
    {
        [$rows, $subtotal] = $this->buildItems($data['items']);

        $type = ($data['discount_type'] ?? 'fixed') === 'percent' ? 'percent' : 'fixed';
        $value = round((float) ($data['discount_value'] ?? 0), 2);
        $discount = $type === 'percent'
            ? round($subtotal * min($value, 100) / 100, 2)
            : min($value, $subtotal);
        $tax = round((float) ($data['tax_total'] ?? 0), 2);
        $delivery = round((float) ($data['delivery_charge'] ?? 0), 2);
        $grand = round($subtotal - $discount + $tax + $delivery, 2);

        return [
            'rows' => $rows,
            'subtotal' => $subtotal,
            'discount_type' => $type,
            'discount_value' => $value,
            'discount' => round($discount, 2),
            'tax' => $tax,
            'delivery' => $delivery,
            'grand' => $grand,
        ];
    }

    /** @return array{0: array<int, array<string, mixed>>, 1: float} rows + subtotal */
    private function buildItems(array $items): array
    {
        $rows = [];
        $subtotal = 0.0;
        foreach ($items as $item) {
            $qty = (float) $item['quantity'];
            $cost = (float) $item['unit_cost'];
            $line = round($qty * $cost, 2);
            $subtotal += $line;
            $rows[] = [
                'product_variant_id' => (int) $item['product_variant_id'],
                'quantity' => $qty,
                'unit_cost' => $cost,
                'line_total' => $line,
            ];
        }

        return [$rows, round($subtotal, 2)];
    }

    private function nextNumber(): string
    {
        $prefix = (string) setting('numbering', 'purchase_prefix', 'PUR-');

        return $prefix . str_pad((string) ((Purchase::max('id') ?? 0) + 1), 5, '0', STR_PAD_LEFT);
    }

    private function formData(): array
    {
        return [
            'suppliers' => Supplier::where('is_active', true)->orderBy('name')->pluck('name', 'id')->all(),
            'variantOptions' => $this->variantOptions(),
        ];
    }

    /** @return Collection<int, array{id:int, label:string, cost:string, unit:?string}> purchasable variants */
    private function variantOptions(): Collection
    {
        return ProductVariant::query()
            ->where('is_active', true)
            ->whereHas('product', fn ($q) => $q->where('is_purchasable', true))
            ->with(['product:id,name,unit_id', 'product.unit:id,code'])
            ->orderBy('id')
            ->get(['id', 'product_id', 'sku', 'cost'])
            ->map(fn (ProductVariant $v) => [
                'id' => $v->id,
                'label' => $v->product->name . ' · ' . $v->sku,
                'cost' => rtrim(rtrim(number_format((float) $v->cost, 2, '.', ''), '0'), '.') ?: '0',
                'unit' => $v->product->unit?->code,
            ]);
    }
}
