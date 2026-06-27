@php
    $cell = 'w-full bg-surface-container-low border border-outline-variant rounded-lg px-3 py-2 text-sm text-on-surface placeholder:text-outline focus:ring-1 focus:ring-primary focus:border-primary outline-none';
    $dateVal = old('purchase_date', $purchase->purchase_date?->format('Y-m-d'));
    $discRaw = old('discount_value', $purchase->discount_value ?? null);
    $discVal = $discRaw !== null && (float) $discRaw > 0
        ? rtrim(rtrim(number_format((float) $discRaw, 2, '.', ''), '0'), '.')
        : '';
    $pState = [
        'items' => array_values(old('items', $initialItems)),
        'discountType' => old('discount_type', $purchase->discount_type ?? 'fixed'),
        'discountValue' => (string) $discVal,
        'tax' => (string) old('tax_total', $purchase->tax_total ?? ''),
        'paid' => (string) old('paid_total', $purchase->paid_total ?? ''),
        'currency' => setting('general', 'currency_symbol', 'Rs'),
    ];
@endphp

<div class="space-y-6">
    {{-- Validation summary (AJAX submit keeps the form state; errors render here without a reload) --}}
    <div x-show="hasErrors()" x-cloak class="rounded-lg border border-error/40 bg-error-container/30 p-4">
        <p class="text-sm font-semibold text-error flex items-center gap-2">
            <span class="material-symbols-outlined text-[18px]">error</span> Please fix the following:
        </p>
        <ul class="mt-2 list-disc list-inside space-y-0.5 text-sm text-error">
            <template x-for="(msg, idx) in errorMessages" :key="idx"><li x-text="msg"></li></template>
        </ul>
    </div>

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
                <template x-if="errors.supplier_id"><p class="text-xs text-error" x-text="errors.supplier_id[0]"></p></template>
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
            <div class="border border-outline-variant/60 rounded-lg overflow-x-auto">
                <table class="w-full text-left text-sm min-w-[680px]">
                    <thead class="text-[10px] font-bold text-outline uppercase tracking-wider border-b border-outline-variant/60 bg-surface-container-low/40">
                        <tr>
                            <th class="px-3 py-2">Product</th>
                            <th class="px-3 py-2 w-40">Quantity</th>
                            <th class="px-3 py-2 w-32">Unit cost</th>
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
                                </td>
                                <td class="px-3 py-2">
                                    <div class="flex items-center gap-1.5">
                                        <input type="number" step="any" min="0" :name="`items[${i}][quantity]`" x-model="row.quantity" class="{{ $cell }}">
                                        <span class="text-xs text-on-surface-variant whitespace-nowrap" x-show="variantUnit(row.product_variant_id)" x-text="variantUnit(row.product_variant_id)"></span>
                                    </div>
                                </td>
                                <td class="px-3 py-2"><input type="number" step="any" min="0" :name="`items[${i}][unit_cost]`" x-model="row.unit_cost" class="{{ $cell }}"></td>
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
                        <span class="text-on-surface-variant">Discount</span>
                        <div class="flex items-center gap-2">
                            <div class="inline-flex p-0.5 bg-surface-container rounded-md text-xs font-bold">
                                <button type="button" @click="setDiscountType('fixed')" :class="discountType === 'fixed' ? 'bg-primary text-on-primary shadow-sm' : 'text-on-surface-variant'" class="px-2.5 py-1 rounded">{{ $pState['currency'] }}</button>
                                <button type="button" @click="setDiscountType('percent')" :class="discountType === 'percent' ? 'bg-primary text-on-primary shadow-sm' : 'text-on-surface-variant'" class="px-2.5 py-1 rounded">%</button>
                            </div>
                            <input type="number" step="any" min="0" :max="discountType === 'percent' ? 100 : subtotal()"
                                   name="discount_value" x-model="discountValue" @input="clampDiscount()"
                                   placeholder="0" class="w-24 {{ $cell }} text-right">
                        </div>
                        <input type="hidden" name="discount_type" :value="discountType">
                    </div>
                    <div x-show="discount() > 0" x-cloak class="flex justify-between items-center text-on-surface-variant text-xs">
                        <span x-text="discountType === 'percent' ? `Discount (${discountValue || 0}%)` : 'Discount'"></span>
                        <span class="text-error font-medium" x-text="'- ' + money(discount())"></span>
                    </div>
                    <div class="flex justify-between items-center gap-3">
                        <span class="text-on-surface-variant">Tax</span>
                        <input type="number" step="any" min="0" name="tax_total" x-model="tax" class="w-32 {{ $cell }} text-right">
                    </div>
                    <div class="flex justify-between items-center font-bold text-on-surface text-base pt-2 border-t border-outline-variant/60"><span>Grand total</span><span x-text="money(grand())"></span></div>
                    <div class="flex justify-between items-center gap-3">
                        <span class="text-on-surface-variant">Paid now</span>
                        <input type="number" step="any" min="0" name="paid_total" x-model="paid" class="w-32 {{ $cell }} text-right">
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
            // Form-level component: AJAX submit so a validation error never reloads the
            // page or loses entered lines; errors render inline instead.
            Alpine.data('purchaseForm', () => ({
                errors: {},
                submitting: false,
                async submit(form) {
                    if (this.submitting) return;
                    this.submitting = true;
                    this.errors = {};
                    try {
                        const res = await fetch(form.action, {
                            method: 'POST',
                            body: new FormData(form),
                            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                            credentials: 'same-origin',
                        });
                        if (res.status === 422) {
                            const data = await res.json();
                            this.errors = data.errors || {};
                            window.scrollTo({ top: 0, behavior: 'smooth' });
                        } else if (res.ok) {
                            const data = await res.json().catch(() => ({}));
                            window.location.href = data.redirect || window.location.href;
                            return;
                        } else {
                            this.errors = { _form: ['Something went wrong (' + res.status + '). Please try again.'] };
                            window.scrollTo({ top: 0, behavior: 'smooth' });
                        }
                    } catch (e) {
                        this.errors = { _form: ['Network error — please check your connection and try again.'] };
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                    } finally {
                        this.submitting = false;
                    }
                },
                hasErrors() { return Object.keys(this.errors).length > 0; },
                get errorMessages() { return Object.values(this.errors).flat(); },
            }));

            Alpine.data('purchaseItems', (state, variants) => ({
                variants: (variants || []).map(v => ({ id: String(v.id), label: v.label, cost: v.cost, unit: v.unit || '' })),
                cur: state.currency || 'Rs',
                _seq: (state.items || []).length,
                items: (state.items || []).map((r, idx) => ({
                    _uid: idx + 1,
                    _open: false,
                    _q: '',
                    _x: 0, _y: 0, _w: 0, _maxh: 288,
                    product_variant_id: r.product_variant_id ? String(r.product_variant_id) : '',
                    quantity: r.quantity ?? '1',
                    unit_cost: r.unit_cost ?? '',
                })),
                tax: state.tax ?? '',
                paid: state.paid ?? '',
                discountType: state.discountType || 'fixed',
                discountValue: state.discountValue ?? '',
                init() {
                    if (!this.items.length) this.addItem();
                    // The picker dropdown is position:fixed (so no table overflow can clip it);
                    // keep it glued to its trigger while the page or table scrolls/resizes.
                    const reposition = () => {
                        const open = this.items.find(r => r._open);
                        if (!open) return;
                        const btn = this.$root.querySelector(`[data-uid="${open._uid}"]`);
                        if (btn) this.positionPicker(open, btn); else open._open = false;
                    };
                    window.addEventListener('scroll', reposition, true);
                    window.addEventListener('resize', reposition);
                },
                addItem() { this.items.push({ _uid: ++this._seq, _open: false, _q: '', _x: 0, _y: 0, _w: 0, _maxh: 288, product_variant_id: '', quantity: '1', unit_cost: '' }); },
                removeItem(i) { this.items.splice(i, 1); if (!this.items.length) this.addItem(); },
                variantLabel(id) { const v = this.variants.find(x => x.id === String(id)); return v ? v.label : ''; },
                variantUnit(id) { const v = this.variants.find(x => x.id === String(id)); return v && v.unit ? v.unit : ''; },
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
                    // Cap the dropdown to the space left below the trigger (min 160px) so it
                    // never runs off the bottom of the screen; the list scrolls internally.
                    row._maxh = Math.min(288, Math.max(160, Math.round(window.innerHeight - r.bottom - 12)));
                },
                pick(row, v) {
                    row._open = false;
                    // If another line already holds this product, merge into it (add this line's
                    // quantity, default 1) and drop this line — no duplicate rows.
                    const existing = this.items.find(r => r._uid !== row._uid && r.product_variant_id === String(v.id));
                    if (existing) {
                        const add = parseFloat(row.quantity) || 1;
                        existing.quantity = String((parseFloat(existing.quantity) || 0) + add);
                        this.removeItem(this.items.indexOf(row));
                        return;
                    }
                    row.product_variant_id = v.id;
                    this.onVariantChange(row);
                },
                onVariantChange(row) {
                    const v = this.variants.find(x => x.id === String(row.product_variant_id));
                    if (v && (row.unit_cost === '' || row.unit_cost == null)) row.unit_cost = v.cost;
                },
                lineTotal(row) { return (parseFloat(row.quantity) || 0) * (parseFloat(row.unit_cost) || 0); },
                subtotal() { return this.items.reduce((s, r) => s + this.lineTotal(r), 0); },
                setDiscountType(type) { this.discountType = type; this.clampDiscount(); },
                clampDiscount() {
                    let v = parseFloat(this.discountValue);
                    if (isNaN(v) || v < 0) { this.discountValue = v < 0 ? '0' : this.discountValue; return; }
                    const cap = this.discountType === 'percent' ? 100 : this.subtotal();
                    if (v > cap) this.discountValue = String(Math.round(cap * 100) / 100);
                },
                discount() {
                    const v = parseFloat(this.discountValue) || 0;
                    if (this.discountType === 'percent') return this.subtotal() * Math.min(v, 100) / 100;
                    return Math.min(v, this.subtotal());
                },
                grand() { return this.subtotal() - this.discount() + (parseFloat(this.tax) || 0); },
                balance() { return this.grand() - (parseFloat(this.paid) || 0); },
                money(n) { return this.cur + ' ' + (Number(n) || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
            }));
        });
    </script>
@endpush
