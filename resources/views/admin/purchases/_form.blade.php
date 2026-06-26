@php
    $cell = 'w-full bg-surface-container-low border border-outline-variant rounded-lg px-3 py-2 text-sm text-on-surface placeholder:text-outline focus:ring-1 focus:ring-primary focus:border-primary outline-none';
    $dateVal = old('purchase_date', $purchase->purchase_date?->format('Y-m-d'));
    $pState = [
        'items' => array_values(old('items', $initialItems)),
        'tax' => (string) old('tax_total', $purchase->tax_total ?? ''),
        'paid' => (string) old('paid_total', $purchase->paid_total ?? ''),
        'currency' => setting('general', 'currency_symbol', 'Rs'),
    ];
@endphp

<div class="space-y-6">
    <x-settings.section title="Purchase details">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-x-6 gap-y-5">
            <div class="space-y-1.5">
                <label class="block text-sm font-medium text-on-surface-variant">Supplier <span class="text-error">*</span></label>
                <select name="supplier_id" class="{{ $cell }} cursor-pointer">
                    <option value="">Choose supplier…</option>
                    @foreach ($suppliers as $id => $name)
                        <option value="{{ $id }}" @selected((string) old('supplier_id', $purchase->supplier_id) === (string) $id)>{{ $name }}</option>
                    @endforeach
                </select>
                @error('supplier_id')<p class="text-xs text-error">{{ $message }}</p>@enderror
            </div>
            <div class="space-y-1.5">
                <label class="block text-sm font-medium text-on-surface-variant">Purchase date <span class="text-error">*</span></label>
                <input type="date" name="purchase_date" value="{{ $dateVal }}" class="{{ $cell }}">
                @error('purchase_date')<p class="text-xs text-error">{{ $message }}</p>@enderror
            </div>
            <div class="space-y-1.5">
                <label class="block text-sm font-medium text-on-surface-variant">Reference <span class="text-outline font-normal">(supplier invoice #)</span></label>
                <input type="text" name="reference" value="{{ old('reference', $purchase->reference) }}" maxlength="100" class="{{ $cell }}">
                @error('reference')<p class="text-xs text-error">{{ $message }}</p>@enderror
            </div>
        </div>
    </x-settings.section>

    <x-settings.section title="Items">
        <div x-data="purchaseItems(@js($pState), @js($variantOptions))" class="space-y-4">
            <div class="overflow-x-auto border border-outline-variant/60 rounded-lg">
                <table class="w-full text-left text-sm">
                    <thead class="text-[10px] font-bold text-outline uppercase tracking-wider border-b border-outline-variant/60 bg-surface-container-low/40">
                        <tr>
                            <th class="px-3 py-2">Product</th>
                            <th class="px-3 py-2 w-28">Quantity</th>
                            <th class="px-3 py-2 w-32">Unit cost</th>
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
                                </td>
                                <td class="px-3 py-2"><input type="number" step="0.001" min="0" :name="`items[${i}][quantity]`" x-model="row.quantity" class="{{ $cell }}"></td>
                                <td class="px-3 py-2"><input type="number" step="0.01" min="0" :name="`items[${i}][unit_cost]`" x-model="row.unit_cost" class="{{ $cell }}"></td>
                                <td class="px-3 py-2 text-right font-semibold text-on-surface" x-text="money(lineTotal(row))"></td>
                                <td class="px-3 py-2 text-right">
                                    <button type="button" @click="removeItem(i)" title="Remove" class="p-1 rounded text-on-surface-variant hover:text-error"><span class="material-symbols-outlined text-[18px]">close</span></button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
            <button type="button" @click="addItem()" class="flex items-center gap-2 px-4 py-2 text-sm font-semibold text-primary border border-dashed border-outline-variant rounded-lg hover:bg-surface-container-low transition-colors">
                <span class="material-symbols-outlined text-[20px]">add</span> Add line
            </button>
            @error('items')<p class="text-xs text-error">{{ $message }}</p>@enderror
            @error('items.*.product_variant_id')<p class="text-xs text-error">{{ $message }}</p>@enderror
            @error('items.*.quantity')<p class="text-xs text-error">{{ $message }}</p>@enderror

            {{-- Totals --}}
            <div class="flex justify-end pt-2">
                <div class="w-full max-w-sm space-y-2 text-sm">
                    <div class="flex justify-between items-center text-on-surface-variant"><span>Subtotal</span><span class="font-semibold text-on-surface" x-text="money(subtotal())"></span></div>
                    <div class="flex justify-between items-center gap-3">
                        <span class="text-on-surface-variant">Tax</span>
                        <input type="number" step="0.01" min="0" name="tax_total" x-model="tax" class="w-32 {{ $cell }} text-right">
                    </div>
                    <div class="flex justify-between items-center font-bold text-on-surface text-base pt-2 border-t border-outline-variant/60"><span>Grand total</span><span x-text="money(grand())"></span></div>
                    <div class="flex justify-between items-center gap-3">
                        <span class="text-on-surface-variant">Paid now</span>
                        <input type="number" step="0.01" min="0" name="paid_total" x-model="paid" class="w-32 {{ $cell }} text-right">
                    </div>
                    <div class="flex justify-between items-center text-on-surface-variant"><span>Balance (payable)</span><span class="font-semibold" :class="balance() > 0 ? 'text-error' : 'text-secondary'" x-text="money(balance())"></span></div>
                </div>
            </div>
        </div>
    </x-settings.section>

    <x-settings.section title="Notes">
        <textarea name="notes" rows="3" maxlength="2000" class="{{ $cell }} resize-y" placeholder="Internal notes (optional)">{{ old('notes', $purchase->notes) }}</textarea>
    </x-settings.section>
</div>

@push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('purchaseItems', (state, variants) => ({
                variants: (variants || []).map(v => ({ id: String(v.id), label: v.label, cost: v.cost })),
                cur: state.currency || 'Rs',
                items: (state.items || []).map(r => ({
                    product_variant_id: r.product_variant_id ? String(r.product_variant_id) : '',
                    quantity: r.quantity ?? '1',
                    unit_cost: r.unit_cost ?? '',
                })),
                tax: state.tax ?? '',
                paid: state.paid ?? '',
                init() { if (!this.items.length) this.addItem(); },
                addItem() { this.items.push({ product_variant_id: '', quantity: '1', unit_cost: '' }); },
                removeItem(i) { this.items.splice(i, 1); if (!this.items.length) this.addItem(); },
                onVariantChange(row) {
                    const v = this.variants.find(x => x.id === String(row.product_variant_id));
                    if (v && (row.unit_cost === '' || row.unit_cost == null)) row.unit_cost = v.cost;
                },
                lineTotal(row) { return (parseFloat(row.quantity) || 0) * (parseFloat(row.unit_cost) || 0); },
                subtotal() { return this.items.reduce((s, r) => s + this.lineTotal(r), 0); },
                grand() { return this.subtotal() + (parseFloat(this.tax) || 0); },
                balance() { return this.grand() - (parseFloat(this.paid) || 0); },
                money(n) { return this.cur + ' ' + (Number(n) || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
            }));
        });
    </script>
@endpush
