@php
    $cell = 'w-full bg-surface-container-low border border-outline-variant rounded-lg px-3 py-2 text-sm text-on-surface placeholder:text-outline focus:ring-1 focus:ring-primary focus:border-primary outline-none';
    $discRaw = old('discount_value', $quotation->discount_value ?? null);
    $discVal = $discRaw !== null && (float) $discRaw > 0
        ? rtrim(rtrim(number_format((float) $discRaw, 2, '.', ''), '0'), '.')
        : '';
    $qState = [
        'items' => array_values(old('items', $initialItems)),
        'discountType' => old('discount_type', $quotation->discount_type ?? 'fixed'),
        'discountValue' => (string) $discVal,
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
                    <template x-for="(row, i) in items" :key="row._uid">
                        <tr>
                            <td class="px-3 py-2">
                                <div @click.outside="row._open = false">
                                    <input type="hidden" :name="`items[${i}][product_variant_id]`" :value="row.product_variant_id">
                                    <button type="button" :data-uid="row._uid" @click="togglePicker(row, $el)"
                                            class="{{ $cell }} cursor-pointer text-left flex items-center justify-between gap-2 overflow-hidden">
                                        <span class="truncate" :class="row.product_variant_id ? '' : 'text-outline'" x-text="variantLabel(row.product_variant_id) || 'Select product…'"></span>
                                        <span class="material-symbols-outlined text-[18px] text-outline shrink-0">expand_more</span>
                                    </button>
                                    <div x-show="row._open" x-cloak
                                         :style="`top:${row._y}px; left:${row._x}px; width:${row._w}px; max-height:${row._maxh}px`"
                                         class="fixed z-50 mt-1 bg-surface-container-lowest dark:bg-surface-container border border-outline-variant rounded-lg shadow-2xl flex flex-col overflow-hidden">
                                        <div class="p-2 border-b border-outline-variant/60">
                                            <input type="text" x-model="row._q" @keydown.escape.stop="row._open = false"
                                                   placeholder="Search product or SKU…" class="{{ $cell }} !py-1.5">
                                        </div>
                                        <ul class="overflow-y-auto">
                                            <template x-for="v in filterVariants(row._q)" :key="v.id">
                                                <li>
                                                    <button type="button" @click="pick(row, v)" class="w-full text-left px-3 py-2 text-sm text-on-surface hover:bg-surface-container-low" x-text="v.label"></button>
                                                </li>
                                            </template>
                                            <li x-show="!filterVariants(row._q).length" class="px-3 py-2 text-sm text-on-surface-variant">No matches.</li>
                                        </ul>
                                    </div>
                                </div>
                                <input type="text" :name="`items[${i}][description]`" x-model="row.description" maxlength="500" placeholder="Optional note for this line" class="{{ $cell }} mt-1.5 !py-1 text-xs">
                            </td>
                            <td class="px-3 py-2 align-top"><input type="number" step="any" min="0" :name="`items[${i}][quantity]`" x-model="row.quantity" class="{{ $cell }}"></td>
                            <td class="px-3 py-2 align-top"><input type="number" step="any" min="0" :name="`items[${i}][unit_price]`" x-model="row.unit_price" class="{{ $cell }}"></td>
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
                    <div class="flex items-center gap-2">
                        <div class="inline-flex p-0.5 bg-surface-container rounded-md text-xs font-bold">
                            <button type="button" @click="setDiscountType('fixed')" :class="discountType === 'fixed' ? 'bg-primary text-on-primary shadow-sm' : 'text-on-surface-variant'" class="px-2.5 py-1 rounded">{{ $qState['currency'] }}</button>
                            <button type="button" @click="setDiscountType('percent')" :class="discountType === 'percent' ? 'bg-primary text-on-primary shadow-sm' : 'text-on-surface-variant'" class="px-2.5 py-1 rounded">%</button>
                        </div>
                        <input type="number" step="any" min="0" :max="discountType === 'percent' ? 100 : subtotal()"
                               name="discount_value" x-model="discountValue" @input="clampDiscount()" placeholder="0" class="w-24 {{ $cell }} text-right">
                    </div>
                    <input type="hidden" name="discount_type" :value="discountType">
                </div>
                <div x-show="discountAmt() > 0" x-cloak class="flex justify-between items-center text-on-surface-variant text-xs">
                    <span x-text="discountType === 'percent' ? `Discount (${discountValue || 0}%)` : 'Discount'"></span>
                    <span class="text-error font-medium" x-text="'- ' + money(discountAmt())"></span>
                </div>
                <div class="flex justify-between items-center gap-3">
                    <span class="text-on-surface-variant">Tax</span>
                    <input type="number" step="any" min="0" name="tax_total" x-model="tax" class="w-32 {{ $cell }} text-right">
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
                _seq: (state.items || []).length,
                items: (state.items || []).map((r, idx) => ({
                    _uid: idx + 1,
                    _open: false,
                    _q: '',
                    _x: 0, _y: 0, _w: 0, _maxh: 288,
                    product_variant_id: r.product_variant_id ? String(r.product_variant_id) : '',
                    quantity: r.quantity ?? '1',
                    unit_price: r.unit_price ?? '',
                    description: r.description ?? '',
                })),
                discountType: state.discountType || 'fixed',
                discountValue: state.discountValue ?? '',
                tax: state.tax ?? '',
                init() {
                    if (!this.items.length) this.addItem();
                    // The picker dropdown is position:fixed so the table's overflow can't clip it;
                    // keep it glued to its trigger while the page/table scrolls or resizes.
                    const reposition = () => {
                        const open = this.items.find(r => r._open);
                        if (!open) return;
                        const btn = this.$root.querySelector(`[data-uid="${open._uid}"]`);
                        if (btn) this.positionPicker(open, btn); else open._open = false;
                    };
                    window.addEventListener('scroll', reposition, true);
                    window.addEventListener('resize', reposition);
                },
                addItem() { this.items.push({ _uid: ++this._seq, _open: false, _q: '', _x: 0, _y: 0, _w: 0, _maxh: 288, product_variant_id: '', quantity: '1', unit_price: '', description: '' }); },
                removeItem(i) { this.items.splice(i, 1); if (!this.items.length) this.addItem(); },
                variantLabel(id) { const v = this.variants.find(x => x.id === String(id)); return v ? v.label : ''; },
                filterVariants(q) {
                    q = (q || '').toLowerCase().trim();
                    if (!q) return this.variants;
                    return this.variants.filter(v => v.label.toLowerCase().includes(q));
                },
                togglePicker(row, btn) {
                    const opening = !row._open;
                    this.items.forEach(r => { r._open = false; });
                    if (opening) {
                        this.positionPicker(row, btn);
                        row._q = '';
                        row._open = true;
                        this.$nextTick(() => btn?.parentElement?.querySelector('input[type=text]')?.focus());
                    }
                },
                positionPicker(row, btn) {
                    const r = btn.getBoundingClientRect();
                    row._x = Math.round(r.left);
                    row._y = Math.round(r.bottom);
                    row._w = Math.round(r.width);
                    row._maxh = Math.min(288, Math.max(160, Math.round(window.innerHeight - r.bottom - 12)));
                },
                pick(row, v) { row.product_variant_id = v.id; row._open = false; this.onVariantChange(row); },
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
                setDiscountType(type) { this.discountType = type; this.clampDiscount(); },
                clampDiscount() {
                    let v = parseFloat(this.discountValue);
                    if (isNaN(v) || v < 0) { if (v < 0) this.discountValue = '0'; return; }
                    const cap = this.discountType === 'percent' ? 100 : this.subtotal();
                    if (v > cap) this.discountValue = String(Math.round(cap * 100) / 100);
                },
                discountAmt() {
                    const v = parseFloat(this.discountValue) || 0;
                    if (this.discountType === 'percent') return this.subtotal() * Math.min(v, 100) / 100;
                    return Math.min(v, this.subtotal());
                },
                grand() { return this.subtotal() - this.discountAmt() + (parseFloat(this.tax) || 0); },
                money(n) { return this.cur + ' ' + (Number(n) || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
            }));
        });
    </script>
@endpush
