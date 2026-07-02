@extends('layouts.admin')

@section('title', 'Point of Sale')

@section('content')
    <div x-data="pos({
            searchUrl: @js(route('admin.pos.search')),
            currency: @js($currency),
            taxEnabled: @js($taxEnabled),
            taxRate: @js($taxRate),
            customers: @js($customers),
            defaultCustomerId: @js($defaultCustomerId),
        })">

        <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
            <div>
                <h2 class="text-2xl font-bold text-on-surface">Point of Sale</h2>
                <p class="text-sm text-on-surface-variant mt-1">Search items, build the cart, take payment.</p>
            </div>
        </div>

        @if (session('last_order_id'))
            <div class="mb-6 flex flex-wrap items-center justify-between gap-3 p-4 rounded-xl bg-secondary-container text-on-secondary-container">
                <span class="flex items-center gap-2 font-semibold"><span class="material-symbols-outlined">check_circle</span>{{ session('status') }}</span>
                <a href="{{ route('admin.orders.print', session('last_order_id')) }}" target="_blank" class="flex items-center gap-2 px-4 py-2 bg-on-secondary-container text-secondary-container rounded-lg text-sm font-semibold hover:brightness-110">
                    <span class="material-symbols-outlined text-[18px]">print</span> Print receipt
                </a>
            </div>
        @endif

        <div class="grid grid-cols-12 gap-6 items-start">
            {{-- Search + cart --}}
            <div class="col-span-12 lg:col-span-7 space-y-6">
                <x-admin.panel class="!p-0 overflow-visible">
                    <div class="p-4 relative" @click.outside="open = false">
                        <div class="relative">
                            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline">search</span>
                            <input type="text" x-model="query" @focus="openList()" @input.debounce.250ms="search()" @keydown.escape="open = false" autofocus
                                placeholder="Search products by name, SKU or barcode…" maxlength="255"
                                class="w-full bg-surface-container-low border border-outline-variant rounded-lg pl-11 pr-3 py-2.5 text-sm text-on-surface placeholder:text-outline focus:ring-1 focus:ring-primary focus:border-primary outline-none">
                        </div>

                        {{-- Results --}}
                        <div x-show="open" x-cloak @scroll.passive="onScroll($event)" class="absolute left-4 right-4 z-20 mt-1 bg-surface-container-lowest dark:bg-surface-container border border-outline-variant rounded-xl shadow-2xl max-h-80 overflow-y-auto">
                            <template x-for="p in results" :key="p.id">
                                <button type="button" @click="add(p)" class="w-full flex items-center justify-between gap-3 px-4 py-2.5 text-left hover:bg-surface-container-high transition-colors border-b border-outline-variant/40 last:border-0">
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-on-surface truncate" x-text="p.name"></p>
                                        <p class="text-[11px] text-outline font-mono" x-text="p.sku"></p>
                                    </div>
                                    <div class="text-right shrink-0">
                                        <p class="text-sm font-semibold text-on-surface" x-text="money(p.price)"></p>
                                        <p class="text-[11px]" :class="p.stock > 0 ? 'text-secondary' : 'text-error'" x-text="p.stock > 0 ? (num(p.stock) + ' in stock') : 'Out of stock'"></p>
                                    </div>
                                </button>
                            </template>
                            <p x-show="loading" class="px-4 py-3 text-sm text-on-surface-variant flex items-center gap-2"><span class="material-symbols-outlined text-[18px] animate-spin">progress_activity</span> Loading…</p>
                            <p x-show="!loading && !results.length" class="px-4 py-3 text-sm text-on-surface-variant" x-text="query ? 'No matches.' : 'No products found.'"></p>
                        </div>
                    </div>
                </x-admin.panel>

                <x-admin.panel class="!p-0 overflow-hidden">
                    <div class="px-6 py-4 border-b border-outline-variant/60 flex items-center justify-between">
                        <h3 class="text-lg font-bold text-on-surface">Cart</h3>
                        <span class="text-xs text-outline" x-text="count() + ' item(s)'"></span>
                    </div>
                    <template x-if="!cart.length">
                        <div class="px-6 py-16 text-center text-on-surface-variant">
                            <span class="material-symbols-outlined text-outline" style="font-size:48px;">shopping_cart</span>
                            <p class="mt-3 text-sm">Search and add products to start a sale.</p>
                        </div>
                    </template>
                    <div class="divide-y divide-outline-variant/40">
                        <template x-for="(l, i) in cart" :key="l.id">
                            <div class="flex flex-wrap sm:flex-nowrap items-center gap-x-3 gap-y-2 px-4 sm:px-6 py-3">
                                <div class="flex-1 min-w-0 basis-full sm:basis-auto">
                                    <p class="font-medium text-on-surface truncate" x-text="l.name"></p>
                                    <p class="text-[11px] text-outline" x-text="l.sku + ' · ' + money(l.price)"></p>
                                </div>
                                <div class="flex items-center gap-1 shrink-0">
                                    <button type="button" @click="dec(l)" class="w-8 h-8 grid place-items-center rounded-lg border border-outline-variant text-on-surface-variant hover:bg-surface-container-high"><span class="material-symbols-outlined text-[18px]">remove</span></button>
                                    <input type="number" min="1" step="1" x-model.number="l.qty" class="w-12 text-center bg-surface-container-low border border-outline-variant rounded-lg py-1.5 text-sm text-on-surface outline-none focus:ring-1 focus:ring-primary">
                                    <button type="button" @click="inc(l)" class="w-8 h-8 grid place-items-center rounded-lg border border-outline-variant text-on-surface-variant hover:bg-surface-container-high"><span class="material-symbols-outlined text-[18px]">add</span></button>
                                </div>
                                <div class="w-24 text-right font-semibold text-on-surface shrink-0 ml-auto sm:ml-0" x-text="money(lineTotal(l))"></div>
                                <button type="button" @click="remove(i)" class="p-1 rounded text-on-surface-variant hover:text-error shrink-0"><span class="material-symbols-outlined text-[20px]">close</span></button>
                            </div>
                        </template>
                    </div>
                </x-admin.panel>
            </div>

            {{-- Checkout --}}
            <div class="col-span-12 lg:col-span-5">
                <form method="POST" action="{{ route('admin.pos.store') }}" @submit="if (!cart.length) $event.preventDefault()">
                    @csrf
                    <input type="hidden" name="payment_method" :value="paymentMethod">
                    <input type="hidden" name="discount_type" :value="discountType">
                    <input type="hidden" name="discount_value" :value="discountValue || 0">
                    <template x-for="(l, i) in cart" :key="l.id">
                        <span>
                            <input type="hidden" :name="`items[${i}][variant_id]`" :value="l.id">
                            <input type="hidden" :name="`items[${i}][quantity]`" :value="l.qty">
                        </span>
                    </template>

                    <x-admin.panel title="Checkout">
                        <div class="space-y-1.5 mb-5">
                            <label class="block text-sm font-medium text-on-surface-variant">Customer</label>
                            <select name="customer_id" data-no-select2
                                x-init="window.$($el).select2({ width: '100%', dropdownParent: window.$(document.body) }); window.$($el).on('change', () => customerId = $el.value);"
                                class="w-full bg-surface-container-low border border-outline-variant rounded-lg px-3 py-2 text-sm text-on-surface focus:ring-1 focus:ring-primary outline-none cursor-pointer">
                                <option value="">Walk-in customer</option>
                                @foreach ($customers as $c)
                                    <option value="{{ $c['id'] }}" @selected((string) $defaultCustomerId === (string) $c['id'])>{{ $c['name'] }}@if ($c['wholesale']) · wholesale @endif</option>
                                @endforeach
                            </select>
                        </div>

                        <dl class="space-y-2 text-sm border-t border-outline-variant/60 pt-4">
                            <div class="flex justify-between"><dt class="text-on-surface-variant">Subtotal</dt><dd class="text-on-surface" x-text="money(subtotal())"></dd></div>
                            <div class="flex justify-between items-center gap-2">
                                <dt class="text-on-surface-variant">Discount</dt>
                                <dd class="flex items-center gap-2">
                                    <div class="inline-flex p-0.5 bg-surface-container rounded-md text-xs font-bold">
                                        <button type="button" @click="setDiscountType('fixed')" :class="discountType === 'fixed' ? 'bg-primary text-on-primary shadow-sm' : 'text-on-surface-variant'" class="px-2 py-0.5 rounded">{{ $currency }}</button>
                                        <button type="button" @click="setDiscountType('percent')" :class="discountType === 'percent' ? 'bg-primary text-on-primary shadow-sm' : 'text-on-surface-variant'" class="px-2 py-0.5 rounded">%</button>
                                    </div>
                                    <input type="number" step="any" min="0" :max="discountType === 'percent' ? 100 : subtotal()" x-model="discountValue" @input="clampDiscount()" placeholder="0"
                                           class="w-20 bg-surface-container-low border border-outline-variant rounded-lg px-2 py-1 text-sm text-right text-on-surface outline-none focus:ring-1 focus:ring-primary">
                                </dd>
                            </div>
                            <div class="flex justify-between" x-show="discountAmt() > 0" x-cloak><dt class="text-on-surface-variant" x-text="discountType === 'percent' ? `Discount (${discountValue || 0}%)` : 'Discount'"></dt><dd class="text-error" x-text="'- ' + money(discountAmt())"></dd></div>
                            <div class="flex justify-between" x-show="taxEnabled"><dt class="text-on-surface-variant">Tax (<span x-text="taxRate"></span>%)</dt><dd class="text-on-surface" x-text="money(tax())"></dd></div>
                            <div class="flex justify-between font-bold text-on-surface text-lg pt-2 border-t border-outline-variant/60"><dt>Total</dt><dd x-text="money(grand())"></dd></div>
                        </dl>

                        <div class="mt-5">
                            <label class="block text-sm font-medium text-on-surface-variant mb-2">Payment</label>
                            <div class="grid grid-cols-3 gap-2">
                                @foreach (['cash' => 'payments', 'card' => 'credit_card', 'qr' => 'qr_code_2'] as $m => $icon)
                                    <button type="button" @click="paymentMethod = '{{ $m }}'"
                                        :class="paymentMethod === '{{ $m }}' ? 'border-primary bg-primary/10 text-primary' : 'border-outline-variant text-on-surface-variant hover:border-primary/50'"
                                        class="flex flex-col items-center gap-1 py-3 rounded-lg border text-xs font-semibold capitalize transition-colors">
                                        <span class="material-symbols-outlined text-[22px]">{{ $icon }}</span> {{ $m }}
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        <div x-show="paymentMethod === 'cash'" x-cloak class="mt-4 space-y-1.5">
                            <label class="block text-sm font-medium text-on-surface-variant">Cash tendered</label>
                            <input type="number" step="any" min="0" x-model="tendered" placeholder="0.00" class="w-full bg-surface-container-low border border-outline-variant rounded-lg px-3 py-2 text-sm text-on-surface outline-none focus:ring-1 focus:ring-primary">
                            <p class="text-sm text-on-surface-variant">Change: <span class="font-semibold text-on-surface" x-text="money(change())"></span></p>
                        </div>

                        <button type="submit" :disabled="!cart.length" :class="cart.length ? '' : 'opacity-50 cursor-not-allowed'"
                            class="w-full mt-6 bg-primary text-on-primary py-3 rounded-lg font-bold text-sm flex items-center justify-center gap-2 hover:brightness-110 active:scale-95 transition-all">
                            <span class="material-symbols-outlined">point_of_sale</span> Complete sale · <span x-text="money(grand())"></span>
                        </button>
                    </x-admin.panel>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('pos', (cfg) => ({
                cur: cfg.currency || 'Rs',
                taxEnabled: !!cfg.taxEnabled,
                taxRate: Number(cfg.taxRate) || 0,
                customers: (cfg.customers || []).map(c => ({ id: String(c.id), name: c.name, wholesale: !!c.wholesale })),
                searchUrl: cfg.searchUrl,
                query: '', results: [],
                open: false, offset: 0, limit: 15, hasMore: true, loading: false,
                cart: [],
                customerId: cfg.defaultCustomerId ? String(cfg.defaultCustomerId) : '',
                paymentMethod: 'cash', tendered: '',
                discountType: 'fixed', discountValue: '',
                openList() { this.open = true; if (!this.results.length && !this.loading) this.reload(); },
                search() { this.reload(); },
                async reload() {
                    this.offset = 0; this.results = []; this.hasMore = true; this.open = true;
                    await this.loadMore();
                },
                async loadMore() {
                    if (this.loading || !this.hasMore) return;
                    this.loading = true;
                    const url = this.searchUrl + '?q=' + encodeURIComponent(this.query.trim()) + '&offset=' + this.offset;
                    try {
                        const r = await fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
                        const rows = r.ok ? await r.json() : [];
                        this.results.push(...rows);
                        this.offset += rows.length;
                        this.hasMore = rows.length === this.limit;
                    } catch (e) { this.hasMore = false; }
                    this.loading = false;
                },
                onScroll(e) {
                    const el = e.target;
                    if (el.scrollTop + el.clientHeight >= el.scrollHeight - 48) this.loadMore();
                },
                add(p) {
                    const line = this.cart.find(l => l.id === p.id);
                    if (line) { line.qty++; } else { this.cart.push({ id: p.id, name: p.name, sku: p.sku, price: p.price, stock: p.stock, qty: 1 }); }
                },
                inc(l) { l.qty++; },
                dec(l) { l.qty = Math.max(1, l.qty - 1); },
                remove(i) { this.cart.splice(i, 1); },
                lineTotal(l) { return l.price * (Number(l.qty) || 0); },
                subtotal() { return this.cart.reduce((s, l) => s + this.lineTotal(l), 0); },
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
                tax() { return this.taxEnabled ? (this.subtotal() - this.discountAmt()) * this.taxRate / 100 : 0; },
                grand() { return this.subtotal() - this.discountAmt() + this.tax(); },
                change() { return Math.max(0, (parseFloat(this.tendered) || 0) - this.grand()); },
                count() { return this.cart.reduce((s, l) => s + (Number(l.qty) || 0), 0); },
                money(n) { return this.cur + ' ' + (Number(n) || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
                num(n) { return (Number(n) || 0).toLocaleString(undefined, { maximumFractionDigits: 3 }); },
            }));
        });
    </script>
@endpush
