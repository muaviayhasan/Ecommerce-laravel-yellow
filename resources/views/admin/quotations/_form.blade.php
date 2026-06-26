@php
    $cell = 'w-full bg-surface-container-low border border-outline-variant rounded-lg px-3 py-2 text-sm text-on-surface placeholder:text-outline focus:ring-1 focus:ring-primary focus:border-primary outline-none';
    $qState = [
        'items' => array_values(old('items', $initialItems)),
        'discount' => (string) old('discount_total', $quotation->discount_total ?? ''),
        'tax' => (string) old('tax_total', $quotation->tax_total ?? ''),
        'tier' => old('price_tier', $quotation->price_tier ?? 'retail'),
        'currency' => setting('general', 'currency_symbol', 'Rs'),
    ];
@endphp

<div x-data="quotationItems(@js($qState), @js($variantOptions))" class="space-y-6">
    <x-settings.section title="Quotation details">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-x-6 gap-y-5">
            <div class="space-y-1.5">
                <label class="block text-sm font-medium text-on-surface-variant">Customer</label>
                <select name="customer_id" class="{{ $cell }} cursor-pointer">
                    <option value="">Walk-in / prospect</option>
                    @foreach ($customers as $id => $name)
                        <option value="{{ $id }}" @selected((string) old('customer_id', $quotation->customer_id) === (string) $id)>{{ $name }}</option>
                    @endforeach
                </select>
                @error('customer_id')<p class="text-xs text-error">{{ $message }}</p>@enderror
            </div>
            <div class="space-y-1.5">
                <label class="block text-sm font-medium text-on-surface-variant">Valid until</label>
                <input type="date" name="valid_until" value="{{ old('valid_until', $quotation->valid_until?->format('Y-m-d')) }}" class="{{ $cell }}">
                @error('valid_until')<p class="text-xs text-error">{{ $message }}</p>@enderror
            </div>
            <div class="space-y-1.5">
                <label class="block text-sm font-medium text-on-surface-variant">Price tier</label>
                <select name="price_tier" x-model="tier" @change="repriceEmpty()" data-no-select2 class="{{ $cell }} cursor-pointer">
                    <option value="retail">Retail</option>
                    <option value="wholesale">Wholesale</option>
                </select>
            </div>
        </div>
    </x-settings.section>

    <x-settings.section title="Items">
        <div class="overflow-x-auto border border-outline-variant/60 rounded-lg">
            <table class="w-full text-left text-sm">
                <thead class="text-[10px] font-bold text-outline uppercase tracking-wider border-b border-outline-variant/60 bg-surface-container-low/40">
                    <tr>
                        <th class="px-3 py-2">Product</th>
                        <th class="px-3 py-2 w-24">Quantity</th>
                        <th class="px-3 py-2 w-32">Unit price</th>
                        <th class="px-3 py-2 w-32 text-right">Line total</th>
                        <th class="px-3 py-2 w-10"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/40">
                    <template x-for="(row, i) in items" :key="i">
                        <tr>
                            <td class="px-3 py-2">
                                <select :name="`items[${i}][product_variant_id]`" x-model="row.product_variant_id" @change="onVariantChange(row)" data-no-select2 class="{{ $cell }} cursor-pointer">
                                    <option value="">Select product…</option>
                                    <template x-for="v in variants" :key="v.id"><option :value="v.id" x-text="v.label"></option></template>
                                </select>
                                <input type="text" :name="`items[${i}][description]`" x-model="row.description" maxlength="500" placeholder="Optional note for this line" class="{{ $cell }} mt-1.5 !py-1 text-xs">
                            </td>
                            <td class="px-3 py-2 align-top"><input type="number" step="0.001" min="0" :name="`items[${i}][quantity]`" x-model="row.quantity" class="{{ $cell }}"></td>
                            <td class="px-3 py-2 align-top"><input type="number" step="0.01" min="0" :name="`items[${i}][unit_price]`" x-model="row.unit_price" class="{{ $cell }}"></td>
                            <td class="px-3 py-2 text-right font-semibold text-on-surface align-top pt-4" x-text="money(lineTotal(row))"></td>
                            <td class="px-3 py-2 text-right align-top">
                                <button type="button" @click="removeItem(i)" title="Remove" class="p-1 rounded text-on-surface-variant hover:text-error"><span class="material-symbols-outlined text-[18px]">close</span></button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
        <button type="button" @click="addItem()" class="mt-4 flex items-center gap-2 px-4 py-2 text-sm font-semibold text-primary border border-dashed border-outline-variant rounded-lg hover:bg-surface-container-low transition-colors">
            <span class="material-symbols-outlined text-[20px]">add</span> Add line
        </button>
        @error('items')<p class="text-xs text-error mt-1">{{ $message }}</p>@enderror
        @error('items.*.product_variant_id')<p class="text-xs text-error mt-1">{{ $message }}</p>@enderror

        <div class="flex justify-end pt-4">
            <div class="w-full max-w-sm space-y-2 text-sm">
                <div class="flex justify-between items-center text-on-surface-variant"><span>Subtotal</span><span class="font-semibold text-on-surface" x-text="money(subtotal())"></span></div>
                <div class="flex justify-between items-center gap-3">
                    <span class="text-on-surface-variant">Discount</span>
                    <input type="number" step="0.01" min="0" name="discount_total" x-model="discount" class="w-32 {{ $cell }} text-right">
                </div>
                <div class="flex justify-between items-center gap-3">
                    <span class="text-on-surface-variant">Tax</span>
                    <input type="number" step="0.01" min="0" name="tax_total" x-model="tax" class="w-32 {{ $cell }} text-right">
                </div>
                <div class="flex justify-between items-center font-bold text-on-surface text-base pt-2 border-t border-outline-variant/60"><span>Grand total</span><span x-text="money(grand())"></span></div>
            </div>
        </div>
    </x-settings.section>

    <x-settings.section title="Notes">
        <textarea name="notes" rows="3" maxlength="5000" class="{{ $cell }} resize-y" placeholder="Terms, delivery, or anything the customer should see (optional)">{{ old('notes', $quotation->notes) }}</textarea>
    </x-settings.section>
</div>

@push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('quotationItems', (state, variants) => ({
                variants: (variants || []).map(v => ({ id: String(v.id), label: v.label, retail: v.retail, wholesale: v.wholesale })),
                cur: state.currency || 'Rs',
                tier: state.tier || 'retail',
                items: (state.items || []).map(r => ({
                    product_variant_id: r.product_variant_id ? String(r.product_variant_id) : '',
                    quantity: r.quantity ?? '1',
                    unit_price: r.unit_price ?? '',
                    description: r.description ?? '',
                })),
                discount: state.discount ?? '',
                tax: state.tax ?? '',
                init() { if (!this.items.length) this.addItem(); },
                addItem() { this.items.push({ product_variant_id: '', quantity: '1', unit_price: '', description: '' }); },
                removeItem(i) { this.items.splice(i, 1); if (!this.items.length) this.addItem(); },
                tierPrice(v) { return this.tier === 'wholesale' ? v.wholesale : v.retail; },
                onVariantChange(row) {
                    const v = this.variants.find(x => x.id === String(row.product_variant_id));
                    if (v && (row.unit_price === '' || row.unit_price == null)) row.unit_price = this.tierPrice(v);
                },
                repriceEmpty() {
                    this.items.forEach(row => {
                        const v = this.variants.find(x => x.id === String(row.product_variant_id));
                        if (v && (row.unit_price === '' || row.unit_price == null)) row.unit_price = this.tierPrice(v);
                    });
                },
                lineTotal(row) { return (parseFloat(row.quantity) || 0) * (parseFloat(row.unit_price) || 0); },
                subtotal() { return this.items.reduce((s, r) => s + this.lineTotal(r), 0); },
                grand() { return this.subtotal() - (parseFloat(this.discount) || 0) + (parseFloat(this.tax) || 0); },
                money(n) { return this.cur + ' ' + (Number(n) || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
            }));
        });
    </script>
@endpush
