@php
    $initialRows = array_values(old('values', $attribute->values->map(fn ($v) => [
        'id' => $v->id,
        'label' => $v->label,
        'value' => $v->value,
        'color_hex' => $v->color_hex ?: '#2563eb',
        'sort_order' => $v->sort_order,
    ])->all()));

    $currentType = old('type', $attribute->type ?? 'select');
@endphp

<div x-data="attributeForm(@js($initialRows), @js($currentType))" class="space-y-6">
    {{-- Attribute --}}
    <x-settings.section title="Attribute">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-5">
            <x-settings.field group="attribute" name="name"
                :meta="['input' => 'text', 'label' => 'Name', 'max' => 255]" :value="data_get($attribute, 'name')" />

            <x-settings.field group="attribute" name="code"
                :meta="['input' => 'text', 'label' => 'Code', 'max' => 255, 'help' => 'Leave blank to auto-generate from the name.']"
                :value="data_get($attribute, 'code')" />

            {{-- Type — bound to Alpine so the swatch type reveals colour inputs below. --}}
            <div class="space-y-1.5">
                <label for="attribute_type" class="block text-sm font-medium text-on-surface-variant">Display type</label>
                <select id="attribute_type" name="type" x-model="type" data-no-select2
                    class="w-full bg-surface-container-low border border-outline-variant rounded-lg py-2.5 px-4 text-sm text-on-surface focus:ring-2 focus:ring-primary focus:border-primary outline-none transition cursor-pointer">
                    @foreach ($types as $value => $label)
                        <option value="{{ $value }}" @selected($currentType === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('type')<p class="text-xs text-error mt-1">{{ $message }}</p>@enderror
            </div>

            <x-settings.field group="attribute" name="sort_order"
                :meta="['input' => 'number', 'label' => 'Sort order', 'help' => 'Lower numbers appear first.']"
                :value="data_get($attribute, 'sort_order')" />

            <div class="md:col-span-2">
                <x-settings.field group="attribute" name="is_variation"
                    :meta="['input' => 'toggle', 'label' => 'Used to create product variants', 'help' => 'On for Size/Colour-style attributes that generate variants; off for informational ones.']"
                    :value="data_get($attribute, 'is_variation')" />
            </div>
        </div>
    </x-settings.section>

    {{-- Values --}}
    <x-settings.section title="Values" description="Options shoppers can choose (e.g. Red, Blue). The list order is saved as the sort order.">
        <div class="space-y-3">
            <template x-for="(row, i) in rows" :key="row._k">
                <div class="flex flex-wrap md:flex-nowrap items-center gap-3 p-3 rounded-lg border border-outline-variant bg-surface-container-low/40">
                    <input type="hidden" :name="`values[${i}][id]`" :value="row.id">
                    <input type="hidden" :name="`values[${i}][sort_order]`" :value="i">

                    <div class="flex-1 min-w-40">
                        <input type="text" :name="`values[${i}][label]`" x-model="row.label" maxlength="255" placeholder="Label — e.g. Red"
                            class="w-full bg-surface-container-lowest dark:bg-surface-container-high border border-outline-variant rounded-lg py-2 px-3 text-sm text-on-surface placeholder:text-outline focus:ring-2 focus:ring-primary outline-none">
                    </div>
                    <div class="flex-1 min-w-40">
                        <input type="text" :name="`values[${i}][value]`" x-model="row.value" maxlength="255" placeholder="Code — auto from label"
                            class="w-full bg-surface-container-lowest dark:bg-surface-container-high border border-outline-variant rounded-lg py-2 px-3 text-sm text-on-surface placeholder:text-outline focus:ring-2 focus:ring-primary outline-none">
                    </div>
                    <div x-show="type === 'swatch'" x-cloak class="shrink-0">
                        <input type="color" :name="`values[${i}][color_hex]`" x-model="row.color_hex" title="Swatch colour"
                            class="w-10 h-9 rounded-lg border border-outline-variant bg-transparent cursor-pointer p-0.5">
                    </div>
                    <button type="button" @click="remove(i)" title="Remove value"
                        class="shrink-0 p-2 rounded-lg text-on-surface-variant hover:bg-error-container/50 hover:text-error transition-colors">
                        <span class="material-symbols-outlined text-[20px]">close</span>
                    </button>
                </div>
            </template>

            <button type="button" @click="add()"
                class="flex items-center justify-center gap-2 w-full px-4 py-2.5 text-sm font-semibold text-primary border border-dashed border-outline-variant rounded-lg hover:bg-surface-container-low transition-colors">
                <span class="material-symbols-outlined text-[20px]">add</span> Add value
            </button>
        </div>
    </x-settings.section>
</div>

@push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('attributeForm', (initialRows, initialType) => ({
                type: initialType || 'select',
                rows: (initialRows || []).map((r, i) => ({
                    id: r.id ?? '',
                    label: r.label ?? '',
                    value: r.value ?? '',
                    color_hex: r.color_hex || '#2563eb',
                    _k: i,
                })),
                _next: (initialRows || []).length,
                init() {
                    if (this.rows.length === 0) this.add();
                },
                add() {
                    this.rows.push({ id: '', label: '', value: '', color_hex: '#2563eb', _k: this._next++ });
                },
                remove(i) {
                    this.rows.splice(i, 1);
                    if (this.rows.length === 0) this.add();
                },
            }));
        });
    </script>
@endpush
