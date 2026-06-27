@php
    $cell = 'w-full bg-surface-container-low border border-outline-variant rounded-lg px-3 py-2 text-sm text-on-surface placeholder:text-outline focus:ring-1 focus:ring-primary focus:border-primary outline-none';
    $pState = [
        'bomId' => (string) old('bom_id', $preselect ?: ($order->bom_id ?? '')),
        'quantity' => (string) old('quantity', $order->quantity ?? '1'),
        'currency' => setting('general', 'currency_symbol', 'Rs'),
    ];
@endphp

<div x-data="productionForm(@js($pState), @js($bomData))" class="grid grid-cols-12 gap-6 items-start">
    <div class="col-span-12 lg:col-span-7 space-y-6">
        <x-settings.section title="Run details">
            <div class="space-y-5">
                <div class="space-y-1.5">
                    <label class="block text-sm font-medium text-on-surface-variant">BOM <span class="text-error">*</span></label>
                    <select name="bom_id" x-model="bomId" data-no-select2 class="{{ $cell }} cursor-pointer">
                        <option value="">Choose a BOM…</option>
                        <template x-for="b in boms" :key="b.id"><option :value="b.id" x-text="b.label"></option></template>
                    </select>
                    @error('bom_id')<p class="text-xs text-error">{{ $message }}</p>@enderror
                </div>
                <div class="space-y-1.5 max-w-xs">
                    <label class="block text-sm font-medium text-on-surface-variant">Quantity to produce <span class="text-error">*</span></label>
                    <input type="number" step="any" min="0.001" name="quantity" x-model="quantity" class="{{ $cell }}">
                    @error('quantity')<p class="text-xs text-error">{{ $message }}</p>@enderror
                </div>
                <div class="space-y-1.5">
                    <label class="block text-sm font-medium text-on-surface-variant">Notes</label>
                    <textarea name="notes" rows="2" maxlength="2000" class="{{ $cell }} resize-y" placeholder="Optional">{{ old('notes', $order->notes) }}</textarea>
                </div>
            </div>
        </x-settings.section>

        <template x-if="selected()">
            <x-settings.section title="Components required">
                <div x-show="anyShort()" x-cloak class="mb-4 flex items-start gap-2 p-3 rounded-lg bg-error-container/40 text-on-error-container text-sm">
                    <span class="material-symbols-outlined text-[18px]">warning</span>
                    <span>Some components are short — completing this run will be rejected unless stock is topped up (or negative stock is allowed).</span>
                </div>
                <div class="overflow-x-auto border border-outline-variant/60 rounded-lg">
                    <table class="w-full text-left text-sm">
                        <thead class="text-[10px] font-bold text-outline uppercase tracking-wider border-b border-outline-variant/60 bg-surface-container-low/40">
                            <tr><th class="px-3 py-2">Component</th><th class="px-3 py-2 text-right">Needed</th><th class="px-3 py-2 text-right">In stock</th></tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/40">
                            <template x-for="(c, i) in needs()" :key="i">
                                <tr>
                                    <td class="px-3 py-2"><span class="font-medium text-on-surface" x-text="c.label"></span> <span class="text-[11px] text-outline font-mono" x-text="c.sku"></span></td>
                                    <td class="px-3 py-2 text-right font-semibold" :class="c.short ? 'text-error' : 'text-on-surface'" x-text="num(c.need)"></td>
                                    <td class="px-3 py-2 text-right text-on-surface-variant" x-text="num(c.stock)"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </x-settings.section>
        </template>
    </div>

    <div class="col-span-12 lg:col-span-5">
        <x-settings.section title="Estimate">
            <template x-if="selected()">
                <dl class="space-y-2.5 text-sm">
                    <div class="flex justify-between gap-4"><dt class="text-on-surface-variant">Finished product</dt><dd class="text-on-surface font-medium text-right" x-text="selected().productName"></dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-on-surface-variant">Cost / unit</dt><dd class="text-on-surface font-medium text-right" x-text="money(selected().unitCost)"></dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-on-surface-variant">Quantity</dt><dd class="text-on-surface font-medium text-right" x-text="num(parseFloat(quantity) || 0)"></dd></div>
                    <div class="flex justify-between gap-4 pt-2 border-t border-outline-variant/60"><dt class="text-on-surface-variant">Estimated total</dt><dd class="text-on-surface font-bold text-right" x-text="money(estCost())"></dd></div>
                </dl>
            </template>
            <template x-if="!selected()">
                <p class="text-sm text-on-surface-variant">Choose a BOM to see the cost estimate and component requirements.</p>
            </template>
        </x-settings.section>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('productionForm', (state, bomData) => ({
                boms: (bomData || []).map(b => ({ ...b, id: String(b.id) })),
                cur: state.currency || 'Rs',
                bomId: state.bomId ? String(state.bomId) : '',
                quantity: state.quantity ?? '1',
                selected() { return this.boms.find(b => b.id === String(this.bomId)) || null; },
                scale() { const b = this.selected(); if (!b) return 0; return (parseFloat(this.quantity) || 0) / Math.max(b.output, 0.001); },
                needs() {
                    const b = this.selected();
                    if (!b) return [];
                    const s = this.scale();
                    return b.components.map(c => ({ ...c, need: c.perRun * s, short: c.perRun * s > c.stock }));
                },
                estCost() { const b = this.selected(); return b ? b.unitCost * (parseFloat(this.quantity) || 0) : 0; },
                anyShort() { return this.needs().some(c => c.short); },
                money(n) { return this.cur + ' ' + (Number(n) || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
                num(n) { return (Number(n) || 0).toLocaleString(undefined, { maximumFractionDigits: 3 }); },
            }));
        });
    </script>
@endpush
