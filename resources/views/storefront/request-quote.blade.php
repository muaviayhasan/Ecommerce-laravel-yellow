@extends('layouts.storefront')

@section('title', 'Request a quote — ' . config('app.name'))
@section('meta_description', 'Tell us what you need and we’ll prepare a custom quotation for you.')

@section('content')
    <div class="bg-surface-container-low py-16 px-4 min-h-[60vh]">
        <div class="max-w-2xl mx-auto">
            <div class="text-center mb-8">
                <h1 class="text-headline-md font-bold mb-2">Request a quote</h1>
                <p class="text-body-base text-on-surface-variant">Add the products you’re interested in and/or describe your requirement — our team will get back to you with pricing.</p>
            </div>

            @if (session('quote_status'))
                <div class="mb-6 flex items-start gap-2 bg-secondary-container text-on-secondary-container px-4 py-3 rounded-lg text-label-sm">
                    <span class="material-symbols-outlined text-[18px] shrink-0">check_circle</span>
                    <span>{{ session('quote_status') }}</span>
                </div>
            @endif

            <form method="POST" action="{{ route('quote.store') }}" x-data="quoteForm()"
                class="bg-surface-container-lowest p-6 lg:p-8 rounded-lg shadow-[0_1px_3px_rgba(0,0,0,0.06)] border border-outline-variant/40 space-y-5">
                @csrf

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between">
                            <label for="name" class="block text-product-title font-semibold text-on-surface-variant">Name <span class="text-error">*</span></label>
                            <span class="text-[11px] text-outline"><span x-text="nameLen">0</span>/150</span>
                        </div>
                        <input id="name" name="name" type="text" required maxlength="150" value="{{ old('name', auth()->user()->name ?? '') }}"
                            @input="nameLen = $event.target.value.length"
                            class="w-full h-12 px-4 rounded border bg-surface focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary transition-all text-body-base @error('name') border-error @else border-outline-variant @enderror">
                        @error('name')<p class="text-error text-label-sm">{{ $message }}</p>@enderror
                    </div>
                    <div class="space-y-1.5">
                        <label for="email" class="block text-product-title font-semibold text-on-surface-variant">Email <span class="text-error">*</span></label>
                        <input id="email" name="email" type="email" required maxlength="255" value="{{ old('email', auth()->user()->email ?? '') }}"
                            class="w-full h-12 px-4 rounded border bg-surface focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary transition-all text-body-base @error('email') border-error @else border-outline-variant @enderror">
                        @error('email')<p class="text-error text-label-sm">{{ $message }}</p>@enderror
                    </div>
                    <div class="space-y-1.5">
                        <label class="block text-product-title font-semibold text-on-surface-variant">Phone</label>
                        <div class="flex h-12 rounded border overflow-hidden bg-surface focus-within:ring-1 focus-within:ring-primary focus-within:border-primary transition-all @error('phone') border-error @else border-outline-variant @enderror">
                            <span class="grid place-items-center px-3 bg-surface-container-low text-on-surface-variant font-semibold border-r border-outline-variant select-none">03</span>
                            <input type="tel" inputmode="numeric" x-model="phoneRest" @input="phoneRest = fmtPhone(phoneRest)" maxlength="10"
                                placeholder="00-0000000" autocomplete="tel-national" aria-label="Phone number"
                                class="flex-1 min-w-0 px-4 outline-none bg-transparent text-body-base">
                        </div>
                        <input type="hidden" name="phone" :value="phoneFull">
                        @error('phone')<p class="text-error text-label-sm">{{ $message }}</p>@enderror
                    </div>
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between">
                            <label for="company" class="block text-product-title font-semibold text-on-surface-variant">Company</label>
                            <span class="text-[11px] text-outline"><span x-text="companyLen">0</span>/150</span>
                        </div>
                        <input id="company" name="company" type="text" maxlength="150" value="{{ old('company') }}"
                            @input="companyLen = $event.target.value.length"
                            class="w-full h-12 px-4 rounded border bg-surface focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary transition-all text-body-base border-outline-variant">
                    </div>
                </div>

                {{-- Products the customer wants quoted --}}
                <div class="space-y-2">
                    <label class="block text-product-title font-semibold text-on-surface-variant">Products</label>
                    <div class="relative" @click.away="open = false">
                        <div class="flex h-12 rounded border border-outline-variant bg-surface focus-within:ring-1 focus-within:ring-primary focus-within:border-primary transition-all overflow-hidden">
                            <span class="grid place-items-center pl-3 text-outline"><span class="material-symbols-outlined text-[20px]">search</span></span>
                            <input type="text" x-model="search" @input.debounce.300ms="searchProducts()" @focus="if (results.length) open = true"
                                placeholder="Search products by name or SKU…"
                                class="flex-1 min-w-0 px-3 outline-none bg-transparent text-body-base">
                            <span x-show="loading" class="grid place-items-center pr-3 text-outline"><span class="material-symbols-outlined text-[20px] animate-spin">progress_activity</span></span>
                        </div>
                        {{-- Results dropdown --}}
                        <div x-show="open && results.length" x-cloak
                            class="absolute z-20 left-0 right-0 mt-1 bg-surface-container-lowest border border-outline-variant rounded-lg shadow-lg max-h-64 overflow-y-auto">
                            <template x-for="r in results" :key="r.id">
                                <button type="button" @click="addItem(r)"
                                    class="w-full text-left px-4 py-2.5 hover:bg-surface-container-low flex items-center justify-between gap-3 border-b border-outline-variant/40 last:border-0">
                                    <span class="min-w-0">
                                        <span class="block font-medium truncate" x-text="r.name"></span>
                                        <span class="block text-label-sm text-on-surface-variant" x-text="r.sku"></span>
                                    </span>
                                    <span class="material-symbols-outlined text-[20px] text-primary shrink-0">add_circle</span>
                                </button>
                            </template>
                        </div>
                    </div>

                    {{-- Selected items --}}
                    <div x-show="items.length" x-cloak class="space-y-2 pt-1">
                        <template x-for="(it, i) in items" :key="it.id">
                            <div class="flex items-center gap-3 bg-surface-container-low rounded-lg border border-outline-variant/40 px-3 py-2">
                                <div class="min-w-0 flex-1">
                                    <div class="font-medium truncate" x-text="it.name"></div>
                                    <div class="text-label-sm text-on-surface-variant" x-text="it.sku"></div>
                                </div>
                                <div class="flex items-center gap-1 shrink-0">
                                    <span class="text-label-sm text-on-surface-variant mr-1">Qty</span>
                                    <input type="number" min="1" max="9999999" x-model.number="it.qty"
                                        :name="`items[${i}][quantity]`"
                                        class="w-20 h-9 px-2 rounded border border-outline-variant bg-surface text-center text-body-base focus:outline-none focus:ring-1 focus:ring-primary">
                                    <input type="hidden" :name="`items[${i}][product_variant_id]`" :value="it.id">
                                    <button type="button" @click="removeItem(i)" aria-label="Remove"
                                        class="w-9 h-9 grid place-items-center rounded text-on-surface-variant hover:text-error hover:bg-surface-container transition-colors">
                                        <span class="material-symbols-outlined text-[20px]">delete</span>
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                    <p class="text-label-sm text-outline" x-show="!items.length">Search above to add the items you’d like priced. This is optional — you can also just describe what you need below.</p>
                </div>

                <div class="space-y-1.5">
                    <div class="flex items-center justify-between">
                        <label for="message" class="block text-product-title font-semibold text-on-surface-variant">Anything else?</label>
                        <span class="text-[11px] text-outline"><span x-text="messageLen">0</span>/2000</span>
                    </div>
                    <textarea id="message" name="message" rows="4" maxlength="2000"
                        @input="messageLen = $event.target.value.length"
                        placeholder="Quantities, delivery location, a custom requirement, or anything not in the catalogue."
                        class="w-full px-4 py-3 rounded border bg-surface focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary transition-all text-body-base @error('message') border-error @else border-outline-variant @enderror">{{ old('message', $product ? "I'd like a quote for: {$product}" : '') }}</textarea>
                    @error('message')<p class="text-error text-label-sm">{{ $message }}</p>@enderror
                </div>

                <button type="submit"
                    class="w-full h-12 bg-primary-container text-on-surface font-bold rounded shadow-sm hover:bg-primary-fixed-dim active:scale-[0.98] transition-all flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined text-[20px]">request_quote</span> Send request
                </button>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            function quoteForm() {
                return {
                    phoneRest: '',
                    nameLen: {{ mb_strlen((string) old('name', auth()->user()->name ?? '')) }},
                    companyLen: {{ mb_strlen((string) old('company', '')) }},
                    messageLen: {{ mb_strlen((string) old('message', $product ? "I'd like a quote for: {$product}" : '')) }},
                    // Item picker
                    search: '',
                    results: [],
                    open: false,
                    loading: false,
                    items: @js($oldItems ?? []),
                    init() {
                        @if (old('phone')) this.seedPhone(@js(old('phone'))); @endif
                    },
                    async searchProducts() {
                        const q = this.search.trim();
                        if (!q) { this.results = []; this.open = false; return; }
                        this.loading = true;
                        try {
                            const res = await fetch(`{{ route('quote.search') }}?q=${encodeURIComponent(q)}`, { headers: { 'Accept': 'application/json' } });
                            this.results = res.ok ? await res.json() : [];
                            this.open = true;
                        } catch (e) { this.results = []; }
                        this.loading = false;
                    },
                    addItem(p) {
                        if (!this.items.some((i) => i.id === p.id)) {
                            this.items.push({ id: p.id, name: p.name, sku: p.sku, qty: 1 });
                        }
                        this.search = ''; this.results = []; this.open = false;
                    },
                    removeItem(i) { this.items.splice(i, 1); },
                    // Same 03-prefix phone handling as the checkout page.
                    fmtPhone(v) { const d = (v || '').replace(/\D/g, '').slice(0, 9); return d.length > 2 ? d.slice(0, 2) + '-' + d.slice(2) : d; },
                    fullPhone(rest) { const d = (rest || '').replace(/\D/g, ''); return d ? (d.length > 2 ? '03' + d.slice(0, 2) + '-' + d.slice(2) : '03' + d) : ''; },
                    seedPhone(full) { const d = (full || '').replace(/\D/g, ''); const r = d.startsWith('03') ? d.slice(2) : d; this.phoneRest = this.fmtPhone(r); },
                    get phoneFull() { return this.fullPhone(this.phoneRest); },
                };
            }
        </script>
    @endpush
@endsection
