@php
    use App\Models\Product;

    $variant = $product->defaultVariant;
    $selectedImages = old('images', $product->exists ? $product->media->pluck('id')->all() : []);

    $inputClass = 'w-full bg-surface-container-low border border-outline-variant rounded-lg px-3 py-2 text-sm text-on-surface focus:ring-1 focus:ring-primary focus:border-primary outline-none transition-all';

    $detailFields = [
        'name' => ['input' => 'text', 'label' => 'Product name', 'max' => 255],
        'sku' => ['input' => 'text', 'label' => 'SKU', 'max' => 255, 'help' => 'Leave blank to auto-generate.'],
        'slug' => ['input' => 'text', 'label' => 'Slug', 'max' => 255, 'help' => 'Leave blank to auto-generate from the name.'],
        'category_id' => ['input' => 'select', 'label' => 'Category', 'select2' => true, 'options' => ['' => '— Choose category —'] + $categoryOptions],
        'brand_id' => ['input' => 'select', 'label' => 'Brand', 'select2' => true, 'options' => ['' => '— None —'] + $brandOptions],
        'unit_id' => ['input' => 'select', 'label' => 'Unit', 'select2' => true, 'options' => ['' => '— None —'] + $unitOptions, 'help' => 'Unit of measure (pcs, kg, ltr, …).'],
        'type' => ['input' => 'select', 'label' => 'Type', 'select2' => true, 'options' => [
            Product::TYPE_TRADING => 'Trading (bought & sold)',
            Product::TYPE_MANUFACTURED => 'Manufactured',
            Product::TYPE_RAW => 'Raw material',
            Product::TYPE_SERVICE => 'Service',
        ]],
        'short_description' => ['input' => 'text', 'label' => 'Short description', 'max' => 500, 'help' => 'One-line summary shown on cards.'],
        'description' => ['input' => 'textarea', 'label' => 'Description', 'rows' => 6, 'max' => 20000],
    ];

    $priceFields = [
        'retail_price' => ['label' => 'Retail price', 'required' => true, 'help' => 'Selling price (web + POS).'],
        'compare_at_price' => ['label' => 'Compare-at price', 'help' => 'Strikethrough “was” price → flags an item “on sale”.'],
        'cost' => ['label' => 'Unit cost', 'help' => 'For margin reporting.'],
        'wholesale_price' => ['label' => 'Wholesale price', 'help' => 'Vendor-channel price.'],
        'stock_quantity' => ['label' => 'Stock quantity'],
        'low_stock_threshold' => ['label' => 'Low-stock alert at'],
    ];

    $placementFields = [
        'is_active' => ['label' => 'Active', 'help' => 'Inactive products are hidden everywhere.'],
        'is_web_listed' => ['label' => 'List on storefront', 'help' => 'Allow this product to appear on the website.'],
        'published' => ['label' => 'Published', 'help' => 'Off = saved as a draft.'],
        'is_featured' => ['label' => 'Featured', 'help' => 'Show in the home-page Featured section.'],
        'is_trending' => ['label' => 'Trending', 'help' => 'Show in the home-page Trending section.'],
        'is_bestseller' => ['label' => 'Bestseller', 'help' => 'Show in the home-page Bestsellers section.'],
    ];

    $numCell = 'w-24 bg-surface-container-low border border-outline-variant rounded-md px-2 py-1.5 text-sm text-on-surface outline-none focus:ring-1 focus:ring-primary';

    // Builder state for Alpine — prefer old() input so a failed variable submit keeps edits.
    $jsState = [
        'mode' => old('variant_mode', $variantState['mode']),
        'options' => old('variants') !== null ? [['attributeId' => '', 'valueIds' => []]] : $variantState['options'],
        'variants' => old('variants') !== null ? array_values(old('variants')) : $variantState['variants'],
        'defaultIndex' => (int) old('variant_default', $variantState['defaultIndex']),
        // Dropshipping: false = product sourced per-order from a supplier (no stock held).
        'trackStock' => (bool) old('is_stock_tracked', $product->exists ? $product->is_stock_tracked : true),
    ];

    // Compact field class (the global rule makes it white in admin light mode).
    $cell = 'w-full bg-surface-container-low border border-outline-variant rounded-lg px-3 py-2 text-sm text-on-surface placeholder:text-outline focus:ring-1 focus:ring-primary outline-none';

    // Flatten the stored grouped specifications back into editable [group, label, value] rows.
    $flatSpecs = [];
    foreach ((array) ($product->specifications ?? []) as $group => $rows) {
        foreach ((array) $rows as $label => $value) {
            $flatSpecs[] = [
                'group' => is_string($group) ? $group : '',
                'label' => is_string($label) ? $label : '',
                'value' => is_array($value) ? implode(', ', $value) : (string) $value,
            ];
        }
    }
    $specsState = [
        'highlights' => array_values((array) old('highlights', $product->highlights ?? [])),
        'specs' => array_values(old('specs', $flatSpecs)),
    ];

    // Markup config for the "suggest price from cost" helper (Pricing settings tab).
    $pricingService = app(\App\Services\PricingService::class);
    $pricing = [
        'markup' => $pricingService->markupPercent(),
        'wholesaleDiscount' => $pricingService->wholesaleDiscountPercent(),
    ];
@endphp

<div class="grid grid-cols-12 gap-6 items-start">
    {{-- Left: details + pricing --}}
    <div class="col-span-12 lg:col-span-8 space-y-6">
        <x-settings.section title="Product details">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-5">
                @foreach ($detailFields as $name => $meta)
                    <div @class(['md:col-span-2' => in_array($meta['input'], ['textarea'], true) || $name === 'name'])>
                        <x-settings.field group="product" :name="$name" :meta="$meta" :value="data_get($product, $name)" />
                    </div>
                @endforeach
            </div>
        </x-settings.section>

        <x-settings.section title="Pricing & variants">
            <div x-data="productVariants(@js($jsState), @js($variationAttributes), @js($mediaItems), @js($pricing))" class="space-y-5">
                <input type="hidden" name="variant_mode" :value="mode">

                {{-- Mode switch --}}
                <div class="inline-flex gap-1 p-1 bg-surface-container-low rounded-lg">
                    <button type="button" @click="mode = 'simple'"
                        :class="mode === 'simple' ? 'bg-primary text-on-primary shadow-sm' : 'text-on-surface-variant hover:text-on-surface'"
                        class="px-4 py-1.5 rounded-md text-sm font-semibold transition-colors">Single product</button>
                    <button type="button" @click="mode = 'variable'"
                        :class="mode === 'variable' ? 'bg-primary text-on-primary shadow-sm' : 'text-on-surface-variant hover:text-on-surface'"
                        class="px-4 py-1.5 rounded-md text-sm font-semibold transition-colors">Has variants</button>
                </div>

                {{-- Stock tracking — turn OFF for dropshipping (sourced per order from a supplier). --}}
                <div class="rounded-lg border border-outline-variant bg-surface-container-low/40 p-3.5">
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="checkbox" name="is_stock_tracked" value="1" x-model="trackStock"
                            class="mt-0.5 w-4 h-4 rounded border-outline-variant accent-primary shrink-0">
                        <span>
                            <span class="block text-sm font-semibold text-on-surface">Track stock in inventory</span>
                            <span class="block text-xs text-on-surface-variant mt-0.5">Keeps an on-hand count that goes down on each sale and up on purchases.</span>
                        </span>
                    </label>
                    <div x-show="!trackStock" x-cloak class="mt-2 flex items-start gap-2 text-xs text-on-surface-variant bg-tertiary-container/30 rounded-md px-3 py-2">
                        <span class="material-symbols-outlined text-[16px] text-tertiary shrink-0">local_shipping</span>
                        <span><strong>Dropshipping mode.</strong> No stock is held or counted, it's never in your stock value, and it always stays available — you source it from the supplier when an order comes in.</span>
                    </div>
                </div>

                {{-- SIMPLE: single default variant --}}
                <div x-show="mode === 'simple'" class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-5">
                    @foreach ($priceFields as $key => $cfg)
                        <div class="space-y-1.5" @if (in_array($key, ['stock_quantity', 'low_stock_threshold'], true)) x-show="trackStock" x-cloak @endif>
                            <label class="block text-sm font-medium text-on-surface-variant">
                                {{ $cfg['label'] }}@if (! empty($cfg['required'])) <span class="text-error">*</span>@endif
                            </label>
                            <input type="number" step="any" min="0" name="variant[{{ $key }}]"
                                value="{{ old('variant.' . $key, $variant?->{$key}) }}" class="{{ $inputClass }}">
                            @if (! empty($cfg['help']))<p class="text-xs text-outline">{{ $cfg['help'] }}</p>@endif
                            @error('variant.' . $key)<p class="text-xs text-error flex items-center gap-1"><span class="material-symbols-outlined text-[16px]">error</span>{{ $message }}</p>@enderror
                        </div>
                    @endforeach
                    <div class="space-y-1.5 md:col-span-2">
                        <label class="block text-sm font-medium text-on-surface-variant">Barcode</label>
                        <input type="text" name="variant[barcode]" maxlength="100" value="{{ old('variant.barcode', $variant?->barcode) }}" class="{{ $inputClass }}">
                    </div>
                    <div class="md:col-span-2">
                        <button type="button" @click="suggestSimple()" class="text-xs font-semibold text-primary hover:underline flex items-center gap-1">
                            <span class="material-symbols-outlined text-[16px]">auto_fix_high</span> Suggest retail &amp; wholesale from cost (<span x-text="markup"></span>% markup)
                        </button>
                    </div>
                </div>

                {{-- VARIABLE: attribute matrix --}}
                <div x-show="mode === 'variable'" x-cloak class="space-y-5">
                    <div class="space-y-3">
                        <p class="text-sm font-semibold text-on-surface">Variation options</p>
                        <template x-for="(opt, oi) in options" :key="oi">
                            <div class="p-3 rounded-lg border border-outline-variant bg-surface-container-low/40 space-y-3">
                                <div class="flex items-center gap-2">
                                    <select x-model="opt.attributeId" data-no-select2
                                        class="flex-1 bg-surface-container-lowest dark:bg-surface-container-high border border-outline-variant rounded-lg py-2 px-3 text-sm text-on-surface outline-none focus:ring-1 focus:ring-primary cursor-pointer">
                                        <option value="">Choose attribute…</option>
                                        <template x-for="a in allAttributes" :key="a.id">
                                            <option :value="a.id" x-text="a.name" :disabled="usedAttributeIds(oi).includes(String(a.id))"></option>
                                        </template>
                                    </select>
                                    <button type="button" @click="removeOption(oi)" title="Remove option"
                                        class="p-2 rounded-lg text-on-surface-variant hover:bg-error-container/50 hover:text-error transition-colors">
                                        <span class="material-symbols-outlined text-[20px]">close</span>
                                    </button>
                                </div>
                                <div x-show="opt.attributeId" class="space-y-0.5">
                                    <template x-for="val in availableValues(opt.attributeId)" :key="val.id">
                                        <label class="flex items-center gap-3 px-2.5 py-1.5 rounded-lg hover:bg-surface-container-low cursor-pointer">
                                            <input type="checkbox" :checked="opt.valueIds.includes(Number(val.id))" @change="toggleValue(opt, val.id)" class="accent-primary w-4 h-4 shrink-0">
                                            <span x-show="val.color" :style="`background:${val.color}`" class="w-3.5 h-3.5 rounded-full border border-black/10 shrink-0"></span>
                                            <span x-text="val.label" class="flex-1 text-sm text-on-surface"></span>
                                            <div x-show="opt.valueIds.includes(Number(val.id))" @click.stop class="flex items-center gap-1.5 text-xs text-on-surface-variant">
                                                <span>price&nbsp;+</span>
                                                <input type="number" step="any" x-model="adjustments[val.id]" placeholder="0"
                                                    class="w-20 bg-surface-container-lowest dark:bg-surface-container-high border border-outline-variant rounded-md px-2 py-1 text-xs text-on-surface outline-none focus:ring-1 focus:ring-primary">
                                            </div>
                                        </label>
                                    </template>
                                </div>
                            </div>
                        </template>
                        <button type="button" @click="addOption()" x-show="options.length < allAttributes.length"
                            class="flex items-center gap-2 px-4 py-2 text-sm font-semibold text-primary border border-dashed border-outline-variant rounded-lg hover:bg-surface-container-low transition-colors">
                            <span class="material-symbols-outlined text-[20px]">add</span> Add option (Size, Colour…)
                        </button>
                    </div>

                    <div class="flex flex-wrap items-end gap-3">
                        <div class="space-y-1">
                            <label class="block text-xs font-medium text-on-surface-variant">Base price</label>
                            <input type="number" step="any" min="0" x-model="basePrice" placeholder="0.00" class="w-32 {{ $numCell }}">
                        </div>
                        <button type="button" @click="generate()"
                            class="px-5 py-2.5 bg-primary text-on-primary font-semibold text-sm rounded-lg hover:brightness-110 active:scale-95 transition-all flex items-center gap-2">
                            <span class="material-symbols-outlined text-[20px]">auto_awesome</span> Generate variants
                        </button>
                        <button type="button" x-show="variants.length" @click="applyPrices()"
                            class="px-4 py-2.5 border border-outline text-on-surface font-semibold text-sm rounded-lg hover:bg-surface-container transition-colors flex items-center gap-2">
                            <span class="material-symbols-outlined text-[18px]">calculate</span> Recalculate prices
                        </button>
                        <button type="button" x-show="variants.length" @click="suggestVariants()" :title="`Set each variant's price to cost + ${markup}% markup`"
                            class="px-4 py-2.5 border border-outline text-on-surface font-semibold text-sm rounded-lg hover:bg-surface-container transition-colors flex items-center gap-2">
                            <span class="material-symbols-outlined text-[18px]">auto_fix_high</span> Suggest from cost
                        </button>
                    </div>
                    <p class="text-xs text-on-surface-variant -mt-2">Each variant's price = <span class="font-medium">base price + its options' adjustments</span>. Every price stays editable in the table below.</p>

                    <div x-show="variants.length" x-cloak class="overflow-x-auto border border-outline-variant/60 rounded-lg">
                        <table class="w-full text-left text-sm">
                            <thead class="text-[10px] font-bold text-outline uppercase tracking-wider border-b border-outline-variant/60 bg-surface-container-low/40">
                                <tr>
                                    <th class="px-3 py-2">Variant</th>
                                    <th class="px-3 py-2 text-center">Img</th>
                                    <th class="px-3 py-2">SKU</th>
                                    <th class="px-3 py-2">Price <span class="text-error">*</span></th>
                                    <th class="px-3 py-2">Compare</th>
                                    <th class="px-3 py-2">Cost</th>
                                    <th class="px-3 py-2" x-show="trackStock" x-cloak>Stock</th>
                                    <th class="px-3 py-2 text-center">Default</th>
                                    <th class="px-3 py-2 text-center">Active</th>
                                    <th class="px-3 py-2"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-outline-variant/40">
                                <template x-for="(v, idx) in variants" :key="idx">
                                    <tr>
                                        <td class="px-3 py-2 font-semibold text-on-surface whitespace-nowrap">
                                            <span x-text="comboLabel(v.value_ids)"></span>
                                            <template x-for="vid in v.value_ids" :key="vid"><input type="hidden" :name="`variants[${idx}][value_ids][]`" :value="vid"></template>
                                            <input type="hidden" :name="`variants[${idx}][id]`" :value="v.id || ''">
                                            <input type="hidden" :name="`variants[${idx}][is_active]`" :value="v.is_active ? 1 : 0">
                                        </td>
                                        <td class="px-3 py-2 text-center">
                                            <button type="button" @click="openImagePicker(idx)" title="Variant image"
                                                class="w-9 h-9 rounded-lg border border-outline-variant overflow-hidden grid place-items-center bg-surface-container-low hover:border-primary transition-colors mx-auto">
                                                <template x-if="v.image_media_id && mediaUrl(v.image_media_id)"><img :src="mediaUrl(v.image_media_id)" alt="" class="w-full h-full object-cover"></template>
                                                <template x-if="!(v.image_media_id && mediaUrl(v.image_media_id))"><span class="material-symbols-outlined text-[16px] text-outline">add_photo_alternate</span></template>
                                            </button>
                                            <input type="hidden" :name="`variants[${idx}][image_media_id]`" :value="v.image_media_id || ''">
                                        </td>
                                        <td class="px-3 py-2"><input type="text" :name="`variants[${idx}][sku]`" x-model="v.sku" placeholder="auto" class="w-28 {{ $numCell }}"></td>
                                        <td class="px-3 py-2"><input type="number" step="any" min="0" :name="`variants[${idx}][retail_price]`" x-model="v.retail_price" class="{{ $numCell }}"></td>
                                        <td class="px-3 py-2"><input type="number" step="any" min="0" :name="`variants[${idx}][compare_at_price]`" x-model="v.compare_at_price" class="{{ $numCell }}"></td>
                                        <td class="px-3 py-2"><input type="number" step="any" min="0" :name="`variants[${idx}][cost]`" x-model="v.cost" class="{{ $numCell }}"></td>
                                        <td class="px-3 py-2" x-show="trackStock" x-cloak><input type="number" step="any" min="0" :name="`variants[${idx}][stock_quantity]`" x-model="v.stock_quantity" class="w-20 {{ $numCell }}"></td>
                                        <td class="px-3 py-2 text-center"><input type="radio" name="variant_default" :value="idx" x-model.number="defaultIndex" class="accent-primary w-4 h-4 cursor-pointer"></td>
                                        <td class="px-3 py-2 text-center">
                                            <button type="button" @click="v.is_active = !v.is_active" :class="v.is_active ? 'text-secondary' : 'text-outline'">
                                                <span class="material-symbols-outlined" style="font-variation-settings:'FILL' 1;" x-text="v.is_active ? 'toggle_on' : 'toggle_off'"></span>
                                            </button>
                                        </td>
                                        <td class="px-3 py-2 text-right"><button type="button" @click="removeVariant(idx)" title="Remove" class="p-1 rounded text-on-surface-variant hover:text-error"><span class="material-symbols-outlined text-[18px]">delete</span></button></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                    <p x-show="!variants.length" class="text-sm text-on-surface-variant">Pick attributes &amp; values above, then <span class="font-semibold">Generate variants</span>.</p>
                    @error('variants')<p class="text-xs text-error">{{ $message }}</p>@enderror
                    @error('variants.*.value_ids')<p class="text-xs text-error">{{ $message }}</p>@enderror
                    @error('variants.*.retail_price')<p class="text-xs text-error">{{ $message }}</p>@enderror

                    {{-- Per-variant image picker (optional; powers variant switching on the product page) --}}
                    <div x-show="pickerOpen" x-cloak @keydown.escape.window="pickerOpen = false" class="fixed inset-0 z-50 overflow-y-auto">
                        <div class="fixed inset-0 bg-black/50"></div>
                        <div class="relative min-h-full flex items-start justify-center p-4 sm:p-6" @click.self="pickerOpen = false">
                            <div class="w-full max-w-3xl my-4 sm:my-8 bg-surface-container-lowest dark:bg-surface-container border border-outline-variant rounded-xl shadow-2xl">
                                <div class="flex items-center justify-between p-5 border-b border-outline-variant/60">
                                    <h3 class="text-lg font-bold text-on-surface">Variant image</h3>
                                    <button type="button" @click="pickerOpen = false" class="cursor-pointer p-1 -mr-1 text-on-surface-variant hover:text-primary"><span class="material-symbols-outlined">close</span></button>
                                </div>
                                <template x-if="!mediaItems.length">
                                    <div class="p-12 text-center text-sm text-on-surface-variant">No media yet. Upload images in the <a href="{{ route('admin.gallery.index') }}" class="text-primary font-semibold hover:underline">Gallery</a> first.</div>
                                </template>
                                <div x-show="mediaItems.length" class="p-5 grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-4 max-h-[60vh] overflow-y-auto">
                                    <template x-for="m in mediaItems" :key="m.id">
                                        <button type="button" @click="chooseImage(m.id)"
                                            :class="(pickingIndex !== null && variants[pickingIndex] && String(variants[pickingIndex].image_media_id) === String(m.id)) ? 'ring-2 ring-primary border-primary' : 'border-outline-variant/50 hover:border-primary/50'"
                                            class="cursor-pointer rounded-xl border overflow-hidden">
                                            <div class="aspect-square bg-surface-container-low overflow-hidden"><img :src="m.url" :alt="m.title" loading="lazy" class="w-full h-full object-cover"></div>
                                        </button>
                                    </template>
                                </div>
                                <div class="flex justify-between gap-3 p-5 border-t border-outline-variant/60">
                                    <button type="button" @click="chooseImage('')" class="cursor-pointer px-4 py-2.5 text-sm font-semibold text-on-surface-variant hover:text-error">Use none</button>
                                    <button type="button" @click="pickerOpen = false" class="cursor-pointer px-5 py-2.5 bg-primary text-on-primary font-semibold text-sm rounded-lg hover:brightness-110">Done</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </x-settings.section>

        <x-settings.section title="Specifications & details">
            <div x-data="productSpecs(@js($specsState))" class="space-y-6">
                {{-- Key features (hero bullet list) --}}
                <div>
                    <label class="block text-sm font-medium text-on-surface-variant mb-1">Key features</label>
                    <p class="text-xs text-outline mb-2">Bullet highlights shown on the product page hero.</p>
                    <div class="space-y-2">
                        <template x-for="(h, i) in highlights" :key="i">
                            <div class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-outline text-[18px] shrink-0">check_circle</span>
                                <input type="text" :name="`highlights[${i}]`" x-model="highlights[i]" maxlength="255" placeholder="e.g. 17.3” Full-HD 144 Hz display" class="{{ $cell }}">
                                <button type="button" @click="removeHighlight(i)" title="Remove" class="p-2 rounded-lg text-on-surface-variant hover:text-error shrink-0"><span class="material-symbols-outlined text-[20px]">close</span></button>
                            </div>
                        </template>
                    </div>
                    <button type="button" @click="addHighlight()" class="mt-2 flex items-center gap-2 text-sm font-semibold text-primary hover:underline"><span class="material-symbols-outlined text-[20px]">add</span> Add feature</button>
                </div>

                {{-- Specifications (grouped key/value → Specification tab) --}}
                <div>
                    <label class="block text-sm font-medium text-on-surface-variant mb-1">Specifications</label>
                    <p class="text-xs text-outline mb-2">Grouped technical details. Rows sharing a group name appear under the same heading.</p>
                    <div class="space-y-2">
                        <template x-for="(s, i) in specs" :key="i">
                            <div class="flex items-center gap-2">
                                <input type="text" :name="`specs[${i}][group]`" x-model="s.group" maxlength="100" placeholder="Group" class="!w-36 {{ $cell }}">
                                <input type="text" :name="`specs[${i}][label]`" x-model="s.label" maxlength="150" placeholder="Label" class="{{ $cell }}">
                                <input type="text" :name="`specs[${i}][value]`" x-model="s.value" maxlength="1000" placeholder="Value" class="{{ $cell }}">
                                <button type="button" @click="removeSpec(i)" title="Remove" class="p-2 rounded-lg text-on-surface-variant hover:text-error shrink-0"><span class="material-symbols-outlined text-[20px]">close</span></button>
                            </div>
                        </template>
                    </div>
                    <button type="button" @click="addSpec()" class="mt-2 flex items-center gap-2 text-sm font-semibold text-primary hover:underline"><span class="material-symbols-outlined text-[20px]">add</span> Add specification</button>
                </div>

                {{-- Warranty / video / returns --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-5 pt-1">
                    <x-settings.field group="product" name="warranty" :meta="['input' => 'text', 'label' => 'Warranty', 'max' => 255, 'help' => 'e.g. 1 Year Manufacturer Warranty.']" :value="data_get($product, 'warranty')" />
                    <x-settings.field group="product" name="video_url" :meta="['input' => 'text', 'label' => 'Video URL', 'max' => 255, 'help' => 'YouTube / Vimeo link.']" :value="data_get($product, 'video_url')" />
                    <div class="md:col-span-2">
                        <x-settings.field group="product" name="return_policy" :meta="['input' => 'textarea', 'label' => 'Return policy', 'rows' => 3, 'max' => 5000]" :value="data_get($product, 'return_policy')" />
                    </div>
                </div>
            </div>
        </x-settings.section>

        <x-settings.section title="SEO">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-5">
                <div class="md:col-span-2">
                    <x-settings.field group="product" name="meta_title" :meta="['input' => 'text', 'label' => 'Meta title', 'max' => 255]" :value="data_get($product, 'meta_title')" />
                </div>
                <div class="md:col-span-2">
                    <x-settings.field group="product" name="meta_description" :meta="['input' => 'textarea', 'label' => 'Meta description', 'rows' => 2, 'max' => 255]" :value="data_get($product, 'meta_description')" />
                </div>
                <x-settings.field group="product" name="meta_keywords" :meta="['input' => 'text', 'label' => 'Meta keywords', 'max' => 255, 'help' => 'Comma-separated.']" :value="data_get($product, 'meta_keywords')" />
                <div class="flex items-end">
                    <x-settings.field group="product" name="no_index" :meta="['input' => 'toggle', 'label' => 'Hide from search engines', 'help' => 'Adds noindex.']" :value="(bool) $product->no_index" />
                </div>
            </div>
        </x-settings.section>
    </div>

    {{-- Right: images + placement --}}
    <div class="col-span-12 lg:col-span-4 space-y-6">
        <x-settings.section title="Images">
            <x-admin.image-picker name="images" :selected="$selectedImages" :media="$mediaItems" />
            @error('images')<p class="mt-2 text-xs text-error">{{ $message }}</p>@enderror
            @error('images.*')<p class="mt-2 text-xs text-error">{{ $message }}</p>@enderror
        </x-settings.section>

        <x-settings.section title="Storefront placement">
            <div class="space-y-4">
                @foreach ($placementFields as $name => $meta)
                    @php $val = $name === 'published' ? (bool) $product->published_at : (bool) data_get($product, $name); @endphp
                    <x-settings.field group="product" :name="$name" :meta="['input' => 'toggle'] + $meta" :value="$val" />
                @endforeach
            </div>
        </x-settings.section>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('productVariants', (state, attributes, media, pricing) => ({
                mode: state.mode || 'simple',
                trackStock: state.trackStock === undefined ? true : !!state.trackStock,
                markup: Number((pricing && pricing.markup) || 0),
                wholesaleDiscount: Number((pricing && pricing.wholesaleDiscount) || 0),
                allAttributes: (attributes || []).map(a => ({ id: a.id, name: a.name, values: a.values || [] })),
                mediaItems: (media || []).map(m => ({ id: m.id, url: m.url, title: m.title })),
                pickerOpen: false,
                pickingIndex: null,
                options: ((state.options && state.options.length) ? state.options : [{ attributeId: '', valueIds: [] }])
                    .map(o => ({ attributeId: o.attributeId ? String(o.attributeId) : '', valueIds: (o.valueIds || []).map(Number) })),
                variants: (state.variants || []).map(v => ({
                    id: v.id || null,
                    value_ids: (v.value_ids || []).map(Number),
                    sku: v.sku || '',
                    retail_price: v.retail_price ?? '',
                    compare_at_price: v.compare_at_price ?? '',
                    cost: v.cost ?? '',
                    stock_quantity: v.stock_quantity ?? '',
                    low_stock_threshold: v.low_stock_threshold ?? '',
                    image_media_id: v.image_media_id ? String(v.image_media_id) : '',
                    is_active: v.is_active === undefined ? true : (v.is_active === true || v.is_active === 1 || v.is_active === '1'),
                })),
                defaultIndex: Number(state.defaultIndex || 0),
                basePrice: '',
                // Per attribute-value price adjustment (ephemeral helper, keyed by value id).
                adjustments: (() => { const m = {}; (attributes || []).forEach(a => (a.values || []).forEach(v => { m[v.id] = ''; })); return m; })(),

                init() {
                    // Rebuild the option pickers from variants (e.g. after a failed submit re-populates from old()).
                    if (this.variants.length && !this.options.some(o => o.attributeId)) {
                        const byAttr = {};
                        for (const v of this.variants) {
                            for (const vid of v.value_ids) {
                                const a = this.attrOfValue(vid);
                                if (!a) continue;
                                (byAttr[a.id] = byAttr[a.id] || new Set()).add(Number(vid));
                            }
                        }
                        const opts = Object.entries(byAttr).map(([id, set]) => ({ attributeId: String(id), valueIds: [...set] }));
                        if (opts.length) this.options = opts;
                    }
                },

                attrById(id) { return this.allAttributes.find(a => String(a.id) === String(id)); },
                attrOfValue(vid) { return this.allAttributes.find(a => a.values.some(x => Number(x.id) === Number(vid))); },
                availableValues(id) { const a = this.attrById(id); return a ? a.values : []; },
                valueLabel(vid) {
                    for (const a of this.allAttributes) { const v = a.values.find(x => Number(x.id) === Number(vid)); if (v) return v.label; }
                    return vid;
                },
                comboLabel(ids) { return (ids || []).map(id => this.valueLabel(id)).join(' / '); },
                usedAttributeIds(except) { return this.options.filter((o, i) => i !== except).map(o => String(o.attributeId)).filter(Boolean); },
                addOption() { if (this.options.length < this.allAttributes.length) this.options.push({ attributeId: '', valueIds: [] }); },
                removeOption(i) { this.options.splice(i, 1); if (!this.options.length) this.options.push({ attributeId: '', valueIds: [] }); },
                toggleValue(opt, vid) { vid = Number(vid); const i = opt.valueIds.indexOf(vid); if (i > -1) opt.valueIds.splice(i, 1); else opt.valueIds.push(vid); },

                valueAdj(id) { const n = parseFloat(this.adjustments[id]); return isNaN(n) ? 0 : n; },
                computedPrice(ids) { return (parseFloat(this.basePrice) || 0) + (ids || []).reduce((s, id) => s + this.valueAdj(id), 0); },
                prefillPrice(ids) {
                    const touched = this.basePrice !== '' || (ids || []).some(id => this.adjustments[id] !== '' && this.adjustments[id] != null);
                    return touched ? String(this.computedPrice(ids)) : '';
                },
                applyPrices() { this.variants.forEach(v => { v.retail_price = String(this.computedPrice(v.value_ids)); }); },

                // Pricing suggestions (§8) — retail = cost × (1 + markup%), wholesale = retail − discount%.
                suggestRetail(cost) { return Math.round((parseFloat(cost) || 0) * (1 + this.markup / 100) * 100) / 100; },
                suggestWholesale(retail) { return Math.round((parseFloat(retail) || 0) * (1 - this.wholesaleDiscount / 100) * 100) / 100; },
                suggestSimple() {
                    const root = this.$root;
                    const cost = parseFloat(root.querySelector('[name="variant[cost]"]')?.value) || 0;
                    if (!cost) return;
                    const retail = this.suggestRetail(cost);
                    const r = root.querySelector('[name="variant[retail_price]"]');
                    const w = root.querySelector('[name="variant[wholesale_price]"]');
                    if (r) r.value = retail.toFixed(2);
                    if (w) w.value = this.suggestWholesale(retail).toFixed(2);
                },
                suggestVariants() { this.variants.forEach(v => { const c = parseFloat(v.cost) || 0; if (c) v.retail_price = String(this.suggestRetail(c)); }); },

                generate() {
                    const dims = this.options.filter(o => o.attributeId && o.valueIds.length).map(o => o.valueIds.slice());
                    if (!dims.length) { this.variants = []; return; }
                    let combos = [[]];
                    for (const dim of dims) {
                        const next = [];
                        for (const c of combos) for (const v of dim) next.push([...c, v]);
                        combos = next;
                    }
                    const keyOf = ids => ids.slice().map(Number).sort((a, b) => a - b).join('-');
                    const existing = {};
                    this.variants.forEach(v => { existing[keyOf(v.value_ids)] = v; });
                    this.variants = combos.map(ids => {
                        const prev = existing[keyOf(ids)];
                        return prev
                            ? { ...prev, value_ids: ids }
                            : { id: null, value_ids: ids, sku: '', retail_price: this.prefillPrice(ids), compare_at_price: '', cost: '', stock_quantity: '', low_stock_threshold: '', image_media_id: '', is_active: true };
                    });
                    if (this.defaultIndex >= this.variants.length) this.defaultIndex = 0;
                },
                removeVariant(i) {
                    this.variants.splice(i, 1);
                    if (this.defaultIndex >= this.variants.length) this.defaultIndex = Math.max(0, this.variants.length - 1);
                },

                mediaUrl(id) { const m = this.mediaItems.find(x => String(x.id) === String(id)); return m ? m.url : ''; },
                openImagePicker(i) { this.pickingIndex = i; this.pickerOpen = true; },
                chooseImage(id) {
                    if (this.pickingIndex !== null && this.variants[this.pickingIndex]) {
                        this.variants[this.pickingIndex].image_media_id = id ? String(id) : '';
                    }
                    this.pickerOpen = false;
                },
            }));

            Alpine.data('productSpecs', (state) => ({
                highlights: (state.highlights || []).map(h => String(h)),
                specs: (state.specs || []).map(s => ({ group: s.group || '', label: s.label || '', value: s.value || '' })),
                addHighlight() { this.highlights.push(''); },
                removeHighlight(i) { this.highlights.splice(i, 1); },
                addSpec() { this.specs.push({ group: '', label: '', value: '' }); },
                removeSpec(i) { this.specs.splice(i, 1); },
            }));
        });
    </script>
@endpush
