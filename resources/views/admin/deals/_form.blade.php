@php
    $cell = 'w-full bg-surface-container-low border border-outline-variant rounded-lg px-3.5 py-2.5 text-sm text-on-surface placeholder:text-outline focus:ring-1 focus:ring-primary focus:border-primary outline-none';
    $label = 'block text-sm font-medium text-on-surface-variant mb-1.5';

    // Builder state — prefer old() so a failed submit keeps the built list.
    $stateItems = old('items') !== null
        ? collect(old('items'))->map(fn ($r) => [
            'variant_id' => (int) ($r['variant_id'] ?? 0),
            'name' => $r['name'] ?? 'Item',
            'sku' => $r['sku'] ?? '',
            'price' => (float) ($r['price'] ?? 0),
            'image' => $r['image'] ?? null,
            'quantity' => (float) ($r['quantity'] ?? 1),
        ])->values()->all()
        : $itemsState;
@endphp

<div class="grid grid-cols-12 gap-6 items-start"
    x-data="dealForm({
        items: @js($stateItems),
        discountType: @js(old('discount_type', $deal->discount_type ?? 'fixed')),
        discountValue: @js((float) old('discount_value', $deal->discount_value ?? 0)),
        searchUrl: @js(route('admin.deals.search-variants')),
    })">

    {{-- Left: details + items --}}
    <div class="col-span-12 lg:col-span-8 space-y-6">
        <x-settings.section title="Deal details">
            <div class="space-y-5">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label for="deal-name" class="{{ $label }}">Name <span class="text-error">*</span></label>
                        <input id="deal-name" name="name" type="text" maxlength="255" value="{{ old('name', $deal->name) }}" class="{{ $cell }}" placeholder="e.g. Winter Warm-Up Bundle">
                        @error('name')<p class="text-xs text-error mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="deal-slug" class="{{ $label }}">Slug</label>
                        <input id="deal-slug" name="slug" type="text" maxlength="255" value="{{ old('slug', $deal->slug) }}" class="{{ $cell }}" placeholder="auto from name">
                        @error('slug')<p class="text-xs text-error mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div>
                    <label for="deal-description" class="{{ $label }}">Description</label>
                    <textarea id="deal-description" name="description" rows="2" maxlength="2000" class="{{ $cell }} resize-y" placeholder="Short pitch shown wherever the deal is promoted.">{{ old('description', $deal->description) }}</textarea>
                    @error('description')<p class="text-xs text-error mt-1">{{ $message }}</p>@enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label for="deal-starts" class="{{ $label }}">Starts</label>
                        <input id="deal-starts" name="starts_at" type="datetime-local" value="{{ old('starts_at', $deal->starts_at?->format('Y-m-d\TH:i')) }}" class="{{ $cell }}">
                        <p class="text-[11px] text-outline mt-1">Blank = live as soon as it's active.</p>
                        @error('starts_at')<p class="text-xs text-error mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="deal-ends" class="{{ $label }}">Ends</label>
                        <input id="deal-ends" name="ends_at" type="datetime-local" value="{{ old('ends_at', $deal->ends_at?->format('Y-m-d\TH:i')) }}" class="{{ $cell }}">
                        <p class="text-[11px] text-outline mt-1">Blank = never expires.</p>
                        @error('ends_at')<p class="text-xs text-error mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>
        </x-settings.section>

        <x-settings.section title="Deal items">
            {{-- Variant search (drops up when there's no room below, e.g. near the sticky bar) --}}
            <div class="relative" x-ref="searchWrap" @click.outside="open = false">
                <div class="relative">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline">search</span>
                    <input type="text" x-model="query" @focus="openList()" @input.debounce.250ms="search()" @keydown.escape="open = false"
                        placeholder="Search products by name, SKU or barcode…" maxlength="255"
                        class="w-full bg-surface-container-low border border-outline-variant rounded-lg pl-11 pr-3 py-2.5 text-sm text-on-surface placeholder:text-outline focus:ring-1 focus:ring-primary focus:border-primary outline-none">
                </div>
                <div x-show="open" x-cloak :class="up ? 'bottom-full mb-1' : 'mt-1'" @scroll.passive="onScroll($event)"
                    class="absolute left-0 right-0 z-20 bg-surface-container-lowest dark:bg-surface-container border border-outline-variant rounded-xl shadow-2xl max-h-72 overflow-y-auto">
                    <template x-for="p in results" :key="p.id">
                        <button type="button" @click="add(p)"
                            class="w-full flex items-center justify-between gap-3 px-4 py-2.5 text-left hover:bg-surface-container-high transition-colors border-b border-outline-variant/40 last:border-0">
                            <div class="flex items-center gap-3 min-w-0">
                                <template x-if="p.image"><img :src="p.image" alt="" loading="lazy" class="w-9 h-9 rounded-md object-cover bg-surface-container-high shrink-0"></template>
                                <template x-if="!p.image"><div class="w-9 h-9 rounded-md bg-surface-container-high grid place-items-center shrink-0"><span class="material-symbols-outlined text-[18px] text-outline">image</span></div></template>
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-on-surface truncate" x-text="p.name"></p>
                                    <p class="text-[11px] text-outline font-mono" x-text="p.sku"></p>
                                </div>
                            </div>
                            <span class="text-sm font-semibold text-on-surface shrink-0" x-text="money(p.price)"></span>
                        </button>
                    </template>
                    <p x-show="loading || loadingMore" class="px-4 py-3 text-sm text-on-surface-variant flex items-center gap-2"><span class="material-symbols-outlined text-[18px] animate-spin">progress_activity</span> Loading…</p>
                    <p x-show="!loading && !results.length" class="px-4 py-3 text-sm text-on-surface-variant" x-text="query ? 'No matches.' : 'Type to search the catalogue.'"></p>
                </div>
            </div>

            {{-- Picked items --}}
            <div class="mt-5">
                <template x-if="!items.length">
                    <div class="px-6 py-10 text-center text-on-surface-variant border-2 border-dashed border-outline-variant rounded-xl">
                        <span class="material-symbols-outlined text-outline" style="font-size:40px;">playlist_add</span>
                        <p class="mt-2 text-sm">Search above and add the products this deal covers.</p>
                    </div>
                </template>
                <div class="divide-y divide-outline-variant/40 border border-outline-variant/60 rounded-xl overflow-hidden" x-show="items.length" x-cloak>
                    <template x-for="(it, i) in items" :key="it.variant_id">
                        <div class="flex flex-wrap sm:flex-nowrap items-center gap-x-3 gap-y-2 px-4 py-3 bg-surface-container-lowest dark:bg-surface-container">
                            <template x-if="it.image"><img :src="it.image" alt="" class="w-10 h-10 rounded-md object-cover bg-surface-container-high shrink-0"></template>
                            <template x-if="!it.image"><div class="w-10 h-10 rounded-md bg-surface-container-high grid place-items-center shrink-0"><span class="material-symbols-outlined text-[18px] text-outline">image</span></div></template>
                            <div class="flex-1 min-w-0 basis-full sm:basis-auto">
                                <p class="font-medium text-on-surface truncate" x-text="it.name"></p>
                                <p class="text-[11px] text-outline" x-text="it.sku + ' · retail ' + money(it.price)"></p>
                            </div>
                            <label class="flex items-center gap-1.5 text-xs text-on-surface-variant shrink-0">
                                Qty
                                <input type="number" step="0.001" min="0.001" x-model.number="it.quantity"
                                    class="w-20 bg-surface-container-low border border-outline-variant rounded-md px-2 py-1.5 text-sm text-on-surface outline-none focus:ring-1 focus:ring-primary">
                            </label>
                            <span class="text-sm font-semibold text-on-surface shrink-0 w-24 text-right" x-text="money(it.price * it.quantity)"></span>
                            <button type="button" @click="remove(i)" title="Remove"
                                class="p-1.5 rounded-lg text-on-surface-variant hover:text-error transition-colors shrink-0">
                                <span class="material-symbols-outlined text-[18px]">delete</span>
                            </button>

                            {{-- Submitted state --}}
                            <span class="hidden">
                                <input type="hidden" :name="`items[${i}][variant_id]`" :value="it.variant_id">
                                <input type="hidden" :name="`items[${i}][quantity]`" :value="it.quantity">
                                <input type="hidden" :name="`items[${i}][name]`" :value="it.name">
                                <input type="hidden" :name="`items[${i}][sku]`" :value="it.sku">
                                <input type="hidden" :name="`items[${i}][price]`" :value="it.price">
                                <input type="hidden" :name="`items[${i}][image]`" :value="it.image || ''">
                            </span>
                        </div>
                    </template>
                </div>
                @error('items')<p class="text-xs text-error mt-2">{{ $message }}</p>@enderror
                @error('items.*.variant_id')<p class="text-xs text-error mt-2">{{ $message }}</p>@enderror
                @error('items.*.quantity')<p class="text-xs text-error mt-2">{{ $message }}</p>@enderror

                {{-- Totals: items subtotal − deal discount = deal total --}}
                <dl class="mt-5 space-y-2 text-sm border-t border-outline-variant/60 pt-4" x-show="items.length" x-cloak>
                    <div class="flex justify-between">
                        <dt class="text-on-surface-variant">Subtotal</dt>
                        <dd class="text-on-surface" x-text="'Rs ' + money(retailTotal())"></dd>
                    </div>
                    <div class="flex justify-between items-center gap-2">
                        <dt class="text-on-surface-variant">Discount</dt>
                        <dd class="flex items-center gap-2">
                            <div class="inline-flex p-0.5 bg-surface-container rounded-md text-xs font-bold">
                                <button type="button" @click="discountType = 'fixed'"
                                    :class="discountType === 'fixed' ? 'bg-primary text-on-primary shadow-sm' : 'text-on-surface-variant'"
                                    class="px-2 py-0.5 rounded">Rs</button>
                                <button type="button" @click="discountType = 'percent'"
                                    :class="discountType === 'percent' ? 'bg-primary text-on-primary shadow-sm' : 'text-on-surface-variant'"
                                    class="px-2 py-0.5 rounded">%</button>
                            </div>
                            <input type="number" step="0.01" min="0" x-model.number="discountValue" placeholder="0"
                                class="w-24 bg-surface-container-low border border-outline-variant rounded-md px-2 py-1.5 text-sm text-on-surface text-right outline-none focus:ring-1 focus:ring-primary">
                        </dd>
                    </div>
                    <div class="flex justify-between border-t border-outline-variant/60 pt-3">
                        <dt class="font-bold text-on-surface text-base">Total</dt>
                        <dd class="font-bold text-on-surface text-base" x-text="'Rs ' + money(total())"></dd>
                    </div>
                </dl>
                <input type="hidden" name="discount_type" :value="discountType">
                <input type="hidden" name="discount_value" :value="discountValue || 0">
                @error('discount_type')<p class="text-xs text-error mt-2">{{ $message }}</p>@enderror
                @error('discount_value')<p class="text-xs text-error mt-2">{{ $message }}</p>@enderror
            </div>
        </x-settings.section>
    </div>

    {{-- Right: image + placement --}}
    <div class="col-span-12 lg:col-span-4 space-y-6">
        <x-settings.section title="Deal image">
            <x-settings.media-picker id="image_media_id" name="image_media_id" :selected="old('image_media_id', $deal->image_media_id)" :media="$mediaItems" placeholder="Choose a banner image" />
            @error('image_media_id')<p class="text-xs text-error mt-2">{{ $message }}</p>@enderror
        </x-settings.section>

        <x-settings.section title="Placement">
            <x-settings.field group="deal" name="is_active" :meta="['input' => 'toggle', 'label' => 'Active', 'help' => 'Inactive deals are hidden everywhere.']" :value="(bool) old('is_active', $deal->is_active)" />
        </x-settings.section>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('dealForm', (cfg) => ({
                items: cfg.items || [],
                discountType: cfg.discountType || 'fixed',
                discountValue: Number(cfg.discountValue) || 0,
                query: '',
                results: [],
                open: false,
                up: false,
                loading: false,
                money(n) { return Number(n || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
                retailTotal() { return this.items.reduce((s, it) => s + (Number(it.price) || 0) * (Number(it.quantity) || 0), 0); },
                discountAmount() {
                    const sub = this.retailTotal();
                    const v = Number(this.discountValue) || 0;
                    return this.discountType === 'percent' ? sub * Math.min(v, 100) / 100 : Math.min(v, sub);
                },
                total() { return Math.max(0, this.retailTotal() - this.discountAmount()); },
                // Open upward when the space under the search box can't fit the
                // list (max-h-72 ≈ 288px) and there's more room above.
                flip() {
                    const el = this.$refs.searchWrap;
                    if (! el) return;
                    const r = el.getBoundingClientRect();
                    const below = window.innerHeight - r.bottom;
                    this.up = below < 320 && r.top > below;
                },
                hasMore: false,
                loadingMore: false,
                openList() { this.flip(); this.open = true; if (! this.results.length) this.search(); },
                async search() {
                    this.flip();
                    this.loading = true; this.open = true;
                    try {
                        const r = await fetch(cfg.searchUrl + '?q=' + encodeURIComponent(this.query), { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
                        this.results = r.ok ? await r.json() : [];
                    } catch (e) { this.results = []; }
                    this.hasMore = this.results.length >= 15;
                    this.loading = false;
                },
                // Near the list's bottom → pull the next page (offset-based, 15 per page).
                onScroll(e) {
                    const el = e.target;
                    if (el.scrollTop + el.clientHeight >= el.scrollHeight - 80) this.loadMore();
                },
                async loadMore() {
                    if (this.loadingMore || ! this.hasMore) return;
                    this.loadingMore = true;
                    try {
                        const r = await fetch(cfg.searchUrl + '?q=' + encodeURIComponent(this.query) + '&offset=' + this.results.length, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
                        const more = r.ok ? await r.json() : [];
                        const seen = new Set(this.results.map(x => x.id));
                        more.forEach(m => { if (! seen.has(m.id)) this.results.push(m); });
                        this.hasMore = more.length >= 15;
                    } catch (e) { this.hasMore = false; }
                    this.loadingMore = false;
                },
                add(p) {
                    if (this.items.some(it => it.variant_id === p.id)) { this.open = false; return; }
                    this.items.push({ variant_id: p.id, name: p.name, sku: p.sku, price: p.price, image: p.image, quantity: 1 });
                    this.open = false; this.query = '';
                },
                remove(i) { this.items.splice(i, 1); },
            }));
        });
    </script>
@endpush
