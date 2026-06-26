@php
    $cell = 'w-full bg-surface-container-low border border-outline-variant rounded-lg px-3 py-2 text-sm text-on-surface placeholder:text-outline focus:ring-1 focus:ring-primary focus:border-primary outline-none';
    $bState = [
        'items' => array_values(old('items', $initialItems)),
        'output' => (string) old('output_quantity', $bom->output_quantity ?? '1'),
        'labor' => (string) old('labor_cost', $bom->labor_cost ?? '0'),
        'overhead' => (string) old('overhead_cost', $bom->overhead_cost ?? '0'),
        'currency' => setting('general', 'currency_symbol', 'Rs'),
    ];
@endphp

<div class="space-y-6">
    <x-settings.section title="Finished product">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-5">
            <div class="space-y-1.5">
                <label class="block text-sm font-medium text-on-surface-variant">Product <span class="text-error">*</span></label>
                <select name="product_id" class="{{ $cell }} cursor-pointer">
                    <option value="">Choose product…</option>
                    @foreach ($productOptions as $id => $name)
                        <option value="{{ $id }}" @selected((string) old('product_id', $bom->product_id) === (string) $id)>{{ $name }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-outline">The product this recipe assembles (it becomes manufacturable).</p>
                @error('product_id')<p class="text-xs text-error">{{ $message }}</p>@enderror
            </div>
            <div class="space-y-1.5">
                <label class="block text-sm font-medium text-on-surface-variant">BOM name <span class="text-outline font-normal">(optional)</span></label>
                <input type="text" name="name" value="{{ old('name', $bom->name) }}" maxlength="255" placeholder="e.g. Standard build" class="{{ $cell }}">
            </div>
            <div class="md:col-span-2">
                <x-settings.field group="bom" name="is_active" :meta="['input' => 'toggle', 'label' => 'Active', 'help' => 'Inactive BOMs cannot start new production.']" :value="(bool) old('is_active', $bom->is_active ?? true)" />
            </div>
        </div>
    </x-settings.section>

    <x-settings.section title="Recipe & cost">
        <div x-data="bomItems(@js($bState), @js($variantOptions))" class="space-y-5">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-x-6 gap-y-5">
                <div class="space-y-1.5">
                    <label class="block text-sm font-medium text-on-surface-variant">Output quantity <span class="text-error">*</span></label>
                    <input type="number" step="0.001" min="0.001" name="output_quantity" x-model="output" class="{{ $cell }}">
                    <p class="text-xs text-outline">Finished units produced per run.</p>
                    @error('output_quantity')<p class="text-xs text-error">{{ $message }}</p>@enderror
                </div>
                <div class="space-y-1.5">
                    <label class="block text-sm font-medium text-on-surface-variant">Labor cost</label>
                    <input type="number" step="0.01" min="0" name="labor_cost" x-model="labor" class="{{ $cell }}">
                </div>
                <div class="space-y-1.5">
                    <label class="block text-sm font-medium text-on-surface-variant">Overhead cost</label>
                    <input type="number" step="0.01" min="0" name="overhead_cost" x-model="overhead" class="{{ $cell }}">
                </div>
            </div>

            <div class="overflow-x-auto border border-outline-variant/60 rounded-lg">
                <table class="w-full text-left text-sm">
                    <thead class="text-[10px] font-bold text-outline uppercase tracking-wider border-b border-outline-variant/60 bg-surface-container-low/40">
                        <tr>
                            <th class="px-3 py-2">Component</th>
                            <th class="px-3 py-2 w-28">Quantity</th>
                            <th class="px-3 py-2 w-24">Waste %</th>
                            <th class="px-3 py-2 w-32 text-right">Line cost</th>
                            <th class="px-3 py-2 w-10"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/40">
                        <template x-for="(row, i) in items" :key="i">
                            <tr>
                                <td class="px-3 py-2">
                                    <select :name="`items[${i}][component_variant_id]`" x-model="row.component_variant_id" data-no-select2 class="{{ $cell }} cursor-pointer">
                                        <option value="">Select component…</option>
                                        <template x-for="v in variants" :key="v.id"><option :value="v.id" x-text="v.label"></option></template>
                                    </select>
                                </td>
                                <td class="px-3 py-2"><input type="number" step="0.001" min="0" :name="`items[${i}][quantity]`" x-model="row.quantity" class="{{ $cell }}"></td>
                                <td class="px-3 py-2"><input type="number" step="0.01" min="0" max="100" :name="`items[${i}][waste_percent]`" x-model="row.waste_percent" class="{{ $cell }}"></td>
                                <td class="px-3 py-2 text-right font-semibold text-on-surface" x-text="money(lineCost(row))"></td>
                                <td class="px-3 py-2 text-right"><button type="button" @click="removeItem(i)" class="p-1 rounded text-on-surface-variant hover:text-error"><span class="material-symbols-outlined text-[18px]">close</span></button></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
            <button type="button" @click="addItem()" class="flex items-center gap-2 px-4 py-2 text-sm font-semibold text-primary border border-dashed border-outline-variant rounded-lg hover:bg-surface-container-low transition-colors">
                <span class="material-symbols-outlined text-[20px]">add</span> Add component
            </button>
            @error('items')<p class="text-xs text-error">{{ $message }}</p>@enderror
            @error('items.*.component_variant_id')<p class="text-xs text-error">{{ $message }}</p>@enderror

            <div class="flex justify-end pt-2">
                <div class="w-full max-w-sm space-y-2 text-sm">
                    <div class="flex justify-between text-on-surface-variant"><span>Components</span><span class="font-semibold text-on-surface" x-text="money(componentTotal())"></span></div>
                    <div class="flex justify-between text-on-surface-variant"><span>Labor + overhead</span><span x-text="money((parseFloat(labor)||0) + (parseFloat(overhead)||0))"></span></div>
                    <div class="flex justify-between font-bold text-on-surface text-base pt-2 border-t border-outline-variant/60"><span>Cost / finished unit</span><span x-text="money(unitCost())"></span></div>
                </div>
            </div>
        </div>
    </x-settings.section>
</div>

@push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('bomItems', (state, variants) => ({
                variants: (variants || []).map(v => ({ id: String(v.id), label: v.label, cost: v.cost })),
                cur: state.currency || 'Rs',
                items: (state.items || []).map(r => ({
                    component_variant_id: r.component_variant_id ? String(r.component_variant_id) : '',
                    quantity: r.quantity ?? '1',
                    waste_percent: r.waste_percent ?? '0',
                })),
                output: state.output ?? '1',
                labor: state.labor ?? '0',
                overhead: state.overhead ?? '0',
                init() { if (!this.items.length) this.addItem(); },
                addItem() { this.items.push({ component_variant_id: '', quantity: '1', waste_percent: '0' }); },
                removeItem(i) { this.items.splice(i, 1); if (!this.items.length) this.addItem(); },
                varCost(row) { const v = this.variants.find(x => x.id === String(row.component_variant_id)); return v ? (parseFloat(v.cost) || 0) : 0; },
                lineCost(row) { return this.varCost(row) * (parseFloat(row.quantity) || 0) * (1 + (parseFloat(row.waste_percent) || 0) / 100); },
                componentTotal() { return this.items.reduce((s, r) => s + this.lineCost(r), 0); },
                unitCost() { const out = Math.max(parseFloat(this.output) || 1, 0.001); return (this.componentTotal() + (parseFloat(this.labor) || 0) + (parseFloat(this.overhead) || 0)) / out; },
                money(n) { return this.cur + ' ' + (Number(n) || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
            }));
        });
    </script>
@endpush
