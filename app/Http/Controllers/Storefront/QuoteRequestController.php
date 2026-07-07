<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Mail\Admin\NewQuoteRequestMail;
use App\Models\Customer;
use App\Models\ProductVariant;
use App\Models\Quotation;
use App\Support\Mail\Notifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Customer-facing "Request a quote". Captures contact details, the specific
 * catalogue items they want priced (added via a live search) and/or a free-text
 * requirement, saves it as a draft quotation with line items for staff to price,
 * and alerts the team. Staff then price it in the admin and mark it "sent",
 * which emails the customer their quotation.
 */
class QuoteRequestController extends Controller
{
    public function create(Request $request): View
    {
        return view('storefront.request-quote', [
            'product' => $request->query('product'), // optional context from a product page
            'oldItems' => $this->hydrateOldItems(),  // re-populate the picker after a validation error
        ]);
    }

    /**
     * Rebuild the item picker's rows (with names) from old input after a failed
     * submission — the form only posts ids + quantities.
     *
     * @return list<array<string, mixed>>
     */
    private function hydrateOldItems(): array
    {
        $old = collect(old('items', []))
            ->filter(fn ($item) => ! empty($item['product_variant_id']))
            ->values();

        if ($old->isEmpty()) {
            return [];
        }

        $variants = ProductVariant::with('product:id,name')
            ->whereIn('id', $old->pluck('product_variant_id'))
            ->get()->keyBy('id');

        return $old->map(function ($item) use ($variants) {
            $variant = $variants->get((int) $item['product_variant_id']);

            return $variant ? [
                'id' => $variant->id,
                'name' => $variant->product?->name ?? 'Item',
                'sku' => $variant->sku,
                'qty' => (int) $item['quantity'],
            ] : null;
        })->filter()->values()->all();
    }

    /** Live product search for the "add items" picker (web-listed, sellable only). */
    public function search(Request $request): JsonResponse
    {
        $term = trim((string) $request->string('q'));

        $query = ProductVariant::query()
            ->where('is_active', true)
            ->whereHas('product', fn ($p) => $p->where('is_active', true)->where('is_sellable', true)->where('is_web_listed', true))
            ->with('product:id,name');

        if ($term !== '') {
            $like = '%' . $term . '%';
            $query->where(fn ($q) => $q->where('sku', 'like', $like)
                ->orWhereHas('product', fn ($p) => $p->where('name', 'like', $like)));
        }

        $variants = $query->orderByDesc('id')->take(12)->get(['id', 'product_id', 'sku', 'retail_price']);

        return response()->json($variants->map(fn (ProductVariant $v) => [
            'id' => $v->id,
            'name' => $v->product?->name ?? 'Item',
            'sku' => $v->sku,
            'price' => (float) $v->retail_price,
        ])->all());
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:255'],
            // Optional, but must be a valid Pakistani mobile (0300-0000000) when given.
            'phone' => ['nullable', 'regex:/^03\d{2}-?\d{7}$/'],
            'company' => ['nullable', 'string', 'max:150'],
            'message' => ['nullable', 'string', 'max:2000'],
            'items' => ['nullable', 'array', 'max:50'],
            'items.*.product_variant_id' => ['required_with:items', 'integer', Rule::exists('product_variants', 'id')->where('is_active', true)],
            'items.*.quantity' => ['required_with:items', 'numeric', 'gt:0', 'max:9999999'],
        ], [
            'phone.regex' => 'Enter a valid mobile number like 0300-0000000.',
        ]);

        $items = collect($data['items'] ?? [])
            ->filter(fn ($item) => ! empty($item['product_variant_id']))
            ->values();

        // Need something to quote: at least one product, or a written requirement.
        if ($items->isEmpty() && blank($data['message'] ?? null)) {
            return back()->withInput()->withErrors([
                'message' => 'Tell us what you need — add a product above or describe your requirement.',
            ]);
        }

        $customer = Customer::firstOrCreate(
            ['email' => $data['email']],
            ['name' => $data['name'], 'phone' => $data['phone'] ?? null, 'type' => 'retail', 'price_tier' => 'retail', 'is_active' => true, 'user_id' => auth()->id()],
        );

        $notes = trim((string) ($data['message'] ?? ''));
        if (! empty($data['company'])) {
            $notes = "Company: {$data['company']}\n\n" . $notes;
        }

        $quotation = Quotation::create([
            'quotation_number' => $this->nextNumber(),
            'customer_id' => $customer->id,
            'status' => 'draft',
            'price_tier' => 'retail',
            'valid_until' => now()->addDays((int) setting('quotation', 'default_validity_days', 14)),
            'subtotal' => 0,
            'grand_total' => 0,
            'notes' => $notes !== '' ? $notes : null,
        ]);

        $this->attachItems($quotation, $items);

        $quotation->loadMissing('customer', 'items');
        $adminEmail = setting('store', 'support_email') ?: setting('mail', 'from_address') ?: config('mail.from.address');
        Notifier::send('admin_new_quote_request', $adminEmail, new NewQuoteRequestMail($quotation, route('admin.quotations.show', $quotation)));

        return redirect()->route('quote.request')->with('quote_status', 'Thanks! We’ve received your request and will get back to you shortly.');
    }

    /**
     * Save the requested products as draft line items — unpriced, since staff set
     * the unit price when they work the quote.
     *
     * @param  Collection<int, array<string, mixed>>  $items
     */
    private function attachItems(Quotation $quotation, Collection $items): void
    {
        if ($items->isEmpty()) {
            return;
        }

        $variants = ProductVariant::with('product:id,name')
            ->whereIn('id', $items->pluck('product_variant_id'))
            ->get()->keyBy('id');

        foreach ($items as $item) {
            $variant = $variants->get((int) $item['product_variant_id']);
            if (! $variant) {
                continue;
            }
            $quotation->items()->create([
                'product_variant_id' => $variant->id,
                'name_snapshot' => $variant->product?->name ?? 'Item',
                'quantity' => (float) $item['quantity'],
                'unit_price' => 0,
                'line_total' => 0,
            ]);
        }
    }

    private function nextNumber(): string
    {
        $prefix = (string) setting('numbering', 'quotation_prefix', 'QUO-');

        return $prefix . str_pad((string) ((Quotation::max('id') ?? 0) + 1), 5, '0', STR_PAD_LEFT);
    }
}
