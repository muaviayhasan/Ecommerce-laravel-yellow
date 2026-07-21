@extends('layouts.admin')

@section('title', 'Vendor sale')

@section('content')
    <div x-data="vendorSale({
            searchUrl: @js(route('admin.vendor-sales.search')),
            currency: @js($currency),
            taxEnabled: @js($taxEnabled),
            taxRate: @js($taxRate),
            customers: @js($customers),
            defaultCustomerId: @js($defaultCustomerId),
            allowNegative: @js($allowNegative),
        })">

        <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
            <div>
                <h2 class="text-2xl font-bold text-on-surface">Vendor sale</h2>
                <p class="text-sm text-on-surface-variant mt-1">Wholesale pricing · sell on credit · balance posts to receivables.</p>
            </div>
        </div>

        @if (session('last_order_id'))
            <div class="mb-6 flex flex-wrap items-center justify-between gap-3 p-4 rounded-xl bg-secondary-container text-on-secondary-container">
                <span class="flex items-center gap-2 font-semibold"><span class="material-symbols-outlined">check_circle</span>{{ session('status') }}</span>
                <div class="flex items-center gap-2">
                    <a href="{{ route('admin.orders.show', session('last_order_id')) }}" class="flex items-center gap-2 px-4 py-2 bg-on-secondary-container/10 rounded-lg text-sm font-semibold hover:bg-on-secondary-container/20">
                        <span class="material-symbols-outlined text-[18px]">receipt_long</span> View order
                    </a>
                    <a href="{{ route('admin.orders.print', session('last_order_id')) }}" target="_blank" class="flex items-center gap-2 px-4 py-2 bg-on-secondary-container text-secondary-container rounded-lg text-sm font-semibold hover:brightness-110">
                        <span class="material-symbols-outlined text-[18px]">print</span> Print
                    </a>
                </div>
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

                        <div x-show="open" x-cloak @scroll.passive="onScroll($event)" class="absolute left-4 right-4 z-20 mt-1 bg-surface-container-lowest dark:bg-surface-container border border-outline-variant rounded-xl shadow-2xl max-h-80 overflow-y-auto">
                            <template x-for="p in results" :key="p.id">
                                <button type="button" @click="add(p)" class="w-full flex items-center justify-between gap-3 px-4 py-2.5 text-left hover:bg-surface-container-high transition-colors border-b border-outline-variant/40 last:border-0">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <template x-if="p.image"><img :src="p.image" alt="" loading="lazy" class="w-9 h-9 rounded-md object-cover bg-surface-container-high shrink-0"></template>
                                        <template x-if="!p.image"><div class="w-9 h-9 rounded-md bg-surface-container-high grid place-items-center shrink-0"><span class="material-symbols-outlined text-[18px] text-outline">image</span></div></template>
                                        <div class="min-w-0">
                                            <p class="text-sm font-medium text-on-surface truncate" x-text="p.name"></p>
                                            <p class="text-[11px] text-outline font-mono" x-text="p.sku"></p>
                                        </div>
                                    </div>
                                    <div class="text-right shrink-0">
                                        <p class="text-sm font-semibold text-on-surface" x-text="money(p.price)"></p>
                                        <p class="text-[11px]" :class="(!p.tracked || p.stock > 0) ? 'text-secondary' : 'text-error'" x-text="!p.tracked ? 'Available (dropship)' : (p.stock > 0 ? (num(p.stock) + ' in stock') : 'Out of stock')"></p>
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
                        <h3 class="text-lg font-bold text-on-surface">Cart <span class="text-xs font-normal text-outline">· wholesale</span></h3>
                        <div class="flex items-center gap-3">
                            <span class="text-xs text-outline" x-text="count() + ' item(s)'"></span>
                            <button type="button" x-show="cart.length" @click="clearCart()" class="text-xs font-semibold text-error hover:opacity-70 transition-opacity flex items-center gap-1"><span class="material-symbols-outlined text-[16px]">delete_sweep</span> Clear</button>
                        </div>
                    </div>
                    <div x-show="stockNote" x-cloak class="px-6 py-2 bg-error-container/40 text-error text-xs font-medium border-b border-outline-variant/60 flex items-center gap-2">
                        <span class="material-symbols-outlined text-[16px]">inventory_2</span><span x-text="stockNote"></span>
                    </div>
                    <template x-if="!cart.length">
                        <div class="px-6 py-16 text-center text-on-surface-variant">
                            <span class="material-symbols-outlined text-outline" style="font-size:48px;">inventory_2</span>
                            <p class="mt-3 text-sm">Search and add products to build the order.</p>
                        </div>
                    </template>
                    <div class="divide-y divide-outline-variant/40">
                        <template x-for="(l, i) in cart" :key="l.id">
                            <div class="flex flex-wrap sm:flex-nowrap items-center gap-x-3 gap-y-2 px-4 sm:px-6 py-3 hover:bg-surface-container-low/40 transition-colors">
                                <div class="flex-1 min-w-0 basis-full sm:basis-auto">
                                    <p class="font-medium text-on-surface truncate" x-text="l.name"></p>
                                    <p class="text-[11px] text-outline" x-text="l.sku + ' · ' + money(l.price) + ' each'"></p>
                                </div>
                                <div class="shrink-0">
                                    <div class="flex items-center gap-1.5">
                                        <button type="button" @click="dec(l)" class="w-8 h-8 grid place-items-center rounded-full bg-primary text-on-primary hover:brightness-110 active:scale-95 transition"><span class="material-symbols-outlined text-[18px]">remove</span></button>
                                        <input type="number" min="1" step="1" :max="maxQty(l)" x-model.number="l.qty" @input="clampQty(l)" class="w-12 text-center bg-surface-container-low border border-outline-variant rounded-lg py-1.5 text-sm text-on-surface outline-none focus:ring-1 focus:ring-primary">
                                        <button type="button" @click="inc(l)" :disabled="atMax(l)" :class="atMax(l) ? 'opacity-40 cursor-not-allowed' : 'hover:brightness-110 active:scale-95'" class="w-8 h-8 grid place-items-center rounded-full bg-primary text-on-primary transition"><span class="material-symbols-outlined text-[18px]">add</span></button>
                                    </div>
                                    <p x-show="atMax(l)" x-cloak class="text-[10px] text-error text-center mt-0.5" x-text="'Max ' + num(l.stock)"></p>
                                </div>
                                <div class="text-right shrink-0 ml-auto sm:ml-5">
                                    <p class="w-24 font-semibold text-on-surface" x-text="money(lineTotal(l))"></p>
                                    <p x-show="l.qty > 1" x-cloak class="text-[10px] text-outline" x-text="l.qty + ' × ' + money(l.price)"></p>
                                </div>
                                <button type="button" @click="remove(i)" title="Remove" class="shrink-0 w-7 h-7 ml-3 mr-1 grid place-items-center rounded-lg bg-error-container text-error hover:bg-error hover:text-on-error transition-colors"><span class="material-symbols-outlined text-[16px] leading-none">delete</span></button>
                            </div>
                        </template>
                    </div>
                </x-admin.panel>
            </div>

            {{-- Checkout --}}
            <div class="col-span-12 lg:col-span-5 lg:sticky lg:top-6 lg:self-start">
                <form method="POST" action="{{ route('admin.vendor-sales.store') }}" @submit="if (!cart.length || !customerId) $event.preventDefault()">
                    @csrf
                    <input type="hidden" name="payment_method" :value="paymentMethod">
                    <input type="hidden" name="paid" :value="paid || 0">
                    <input type="hidden" name="discount_type" :value="discountType">
                    <input type="hidden" name="discount_value" :value="discountValue || 0">
                    <input type="hidden" name="shipping_method" :value="deliveryMethod">
                    <input type="hidden" name="courier" :value="courier">
                    <input type="hidden" name="tracking_number" :value="tracking">
                    <input type="hidden" name="shipping_total" :value="shippingCharge || 0">
                    <template x-for="(l, i) in cart" :key="l.id">
                        <span>
                            <input type="hidden" :name="`items[${i}][variant_id]`" :value="l.id">
                            <input type="hidden" :name="`items[${i}][quantity]`" :value="l.qty">
                        </span>
                    </template>

                    <x-admin.panel title="Checkout">
                        <div class="space-y-1.5 mb-5">
                            <label class="block text-sm font-medium text-on-surface-variant">Customer <span class="text-error">*</span></label>
                            <select name="customer_id" data-no-select2
                                x-init="window.$($el).select2({ width: '100%', dropdownParent: window.$(document.body) }); window.$($el).on('change', () => customerId = $el.value);"
                                class="w-full bg-surface-container-low border border-outline-variant rounded-lg px-3 py-2 text-sm text-on-surface focus:ring-1 focus:ring-primary outline-none cursor-pointer">
                                <option value="">Choose customer…</option>
                                @foreach ($customers as $c)
                                    <option value="{{ $c['id'] }}" @selected((string) $defaultCustomerId === (string) $c['id'])>{{ $c['name'] }}@if ($c['wholesale']) · wholesale @endif</option>
                                @endforeach
                            </select>
                            @error('customer_id')<p class="text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        {{-- Walk-in extras: optional name + contact, printed on the bill. --}}
                        <div x-show="customerId === '' || customerId === @js((string) ($defaultCustomerId ?? ''))"
                            class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-5">
                            <div class="space-y-1.5">
                                <label class="block text-sm font-medium text-on-surface-variant">Customer name <span class="text-outline font-normal">(optional)</span></label>
                                <input type="text" name="walk_in_name" value="{{ old('walk_in_name') }}" maxlength="100" placeholder="e.g. Ahmed"
                                    class="w-full bg-surface-container-low border border-outline-variant rounded-lg px-3 py-2 text-sm text-on-surface focus:ring-1 focus:ring-primary outline-none">
                                @error('walk_in_name')<p class="text-xs text-error">{{ $message }}</p>@enderror
                            </div>
                            <div class="space-y-1.5">
                                <label class="block text-sm font-medium text-on-surface-variant">Contact no <span class="text-outline font-normal">(optional)</span></label>
                                <x-storefront.phone-input name="walk_in_phone" error="walk_in_phone" :value="old('walk_in_phone')" />
                            </div>
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
                            <div class="flex justify-between" x-show="(parseFloat(shippingCharge) || 0) > 0" x-cloak><dt class="text-on-surface-variant">Delivery</dt><dd class="text-on-surface" x-text="money(parseFloat(shippingCharge) || 0)"></dd></div>
                            <div class="flex justify-between font-bold text-on-surface text-lg pt-2 border-t border-outline-variant/60"><dt>Total</dt><dd x-text="money(grand())"></dd></div>
                        </dl>

                        {{-- Delivery (optional) --}}
                        <div class="mt-5 border-t border-outline-variant/60 pt-4">
                            <button type="button" @click="deliveryOpen = !deliveryOpen" class="flex items-center justify-between w-full text-sm font-semibold text-on-surface-variant">
                                <span class="flex items-center gap-2"><span class="material-symbols-outlined text-[18px]">local_shipping</span> Delivery <span class="text-outline font-normal">(optional)</span></span>
                                <span class="material-symbols-outlined text-[18px]" x-text="deliveryOpen ? 'expand_less' : 'expand_more'"></span>
                            </button>
                            <div x-show="deliveryOpen" x-cloak class="mt-3 space-y-3">
                                <select x-model="deliveryMethod" data-no-select2 class="w-full bg-surface-container-low border border-outline-variant rounded-lg px-3 py-2 text-sm text-on-surface outline-none focus:ring-1 focus:ring-primary cursor-pointer">
                                    <option value="pickup">Store pickup (collected)</option>
                                    <option value="own_rider">Own rider</option>
                                    <option value="courier">Third-party courier</option>
                                    <option value="other">Other person</option>
                                </select>
                                <template x-if="deliveryMethod !== 'pickup'">
                                    <div class="space-y-3">
                                        <input type="text" x-model="courier" maxlength="255" placeholder="Handled by (rider / courier / person)" class="w-full bg-surface-container-low border border-outline-variant rounded-lg px-3 py-2 text-sm text-on-surface outline-none focus:ring-1 focus:ring-primary">
                                        <input type="text" x-model="tracking" maxlength="255" placeholder="Contact phone or tracking #" class="w-full bg-surface-container-low border border-outline-variant rounded-lg px-3 py-2 text-sm text-on-surface outline-none focus:ring-1 focus:ring-primary">
                                    </div>
                                </template>
                                <input type="number" step="any" min="0" x-model="shippingCharge" placeholder="Delivery charge (added to total)" class="w-full bg-surface-container-low border border-outline-variant rounded-lg px-3 py-2 text-sm text-on-surface outline-none focus:ring-1 focus:ring-primary">
                            </div>
                        </div>

                        <div class="mt-5">
                            <label class="block text-sm font-medium text-on-surface-variant mb-2">Payment method</label>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                                @foreach (['cash' => 'payments', 'card' => 'credit_card', 'bank' => 'account_balance', 'credit' => 'schedule'] as $m => $icon)
                                    <button type="button" @click="paymentMethod = '{{ $m }}'"
                                        :class="paymentMethod === '{{ $m }}' ? 'border-primary bg-primary/10 text-primary' : 'border-outline-variant text-on-surface-variant hover:border-primary/50'"
                                        class="flex flex-col items-center gap-1 py-2.5 rounded-lg border text-[11px] font-semibold capitalize transition-colors">
                                        <span class="material-symbols-outlined text-[20px]">{{ $icon }}</span> {{ $m }}
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        <div class="mt-4 space-y-1.5">
                            <div class="flex items-center justify-between">
                                <label class="block text-sm font-medium text-on-surface-variant">Amount paid now</label>
                                <button type="button" @click="paid = grand().toFixed(2)" class="text-xs font-semibold text-primary hover:underline">Pay full</button>
                            </div>
                            <input type="number" step="any" min="0" x-model="paid" placeholder="0.00" class="w-full bg-surface-container-low border border-outline-variant rounded-lg px-3 py-2 text-sm text-on-surface outline-none focus:ring-1 focus:ring-primary">
                            <p class="text-sm flex justify-between pt-1">
                                <span class="text-on-surface-variant">Balance (on account)</span>
                                <span class="font-bold" :class="balance() > 0 ? 'text-error' : 'text-secondary'" x-text="money(balance())"></span>
                            </p>
                        </div>

                        <button type="submit" :disabled="!cart.length || !customerId" :class="(cart.length && customerId) ? '' : 'opacity-50 cursor-not-allowed'"
                            class="w-full mt-6 bg-primary text-on-primary py-3 rounded-lg font-bold text-sm flex items-center justify-center gap-2 hover:brightness-110 active:scale-95 transition-all">
                            <span class="material-symbols-outlined">sell</span> Record sale · <span x-text="money(grand())"></span>
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
            Alpine.data('vendorSale', (cfg) => ({
                cur: cfg.currency || 'Rs',
                taxEnabled: !!cfg.taxEnabled,
                taxRate: Number(cfg.taxRate) || 0,
                customers: (cfg.customers || []).map(c => ({ id: String(c.id), name: c.name, wholesale: !!c.wholesale })),
                searchUrl: cfg.searchUrl,
                allowNegative: !!cfg.allowNegative,
                query: '', results: [],
                open: false, offset: 0, limit: 15, hasMore: true, loading: false,
                cart: [],
                stockNote: '', _noteT: null,
                customerId: cfg.defaultCustomerId ? String(cfg.defaultCustomerId) : '',
                paymentMethod: 'cash', paid: '',
                discountType: 'fixed', discountValue: '',
                deliveryOpen: false, deliveryMethod: 'pickup', courier: '', tracking: '', shippingCharge: '',
                init() { this.$nextTick(() => this.openList()); },
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
                // Available cap for a line/product: on-hand stock for tracked items
                // (unless the store allows negative stock), otherwise unlimited.
                capFor(item) { return (item.tracked && !this.allowNegative) ? Number(item.stock) || 0 : Infinity; },
                maxQty(l) { const c = this.capFor(l); return c === Infinity ? undefined : c; },
                atMax(l) { return this.capFor(l) !== Infinity && (Number(l.qty) || 0) >= this.capFor(l); },
                noteStock(name, cap) {
                    this.stockNote = cap <= 0 ? `${name} is out of stock.` : `Only ${this.num(cap)} of ${name} in stock.`;
                    clearTimeout(this._noteT);
                    this._noteT = setTimeout(() => { this.stockNote = ''; }, 3500);
                },
                clampQty(l) {
                    let v = Math.floor(Number(l.qty) || 0);
                    if (v < 1) v = 1;
                    const cap = this.capFor(l);
                    if (v > cap) { v = cap; this.noteStock(l.name, cap); }
                    l.qty = v;
                },
                add(p) {
                    const cap = this.capFor(p);
                    const line = this.cart.find(l => l.id === p.id);
                    const current = line ? (Number(line.qty) || 0) : 0;
                    if (current + 1 > cap) { this.noteStock(p.name, cap); if (line) line.qty = cap; return; }
                    if (line) { line.qty = current + 1; }
                    else { this.cart.push({ id: p.id, name: p.name, sku: p.sku, price: p.price, stock: p.stock, tracked: p.tracked, qty: 1 }); }
                },
                inc(l) {
                    if (this.atMax(l)) { this.noteStock(l.name, this.capFor(l)); return; }
                    l.qty = (Number(l.qty) || 0) + 1;
                },
                dec(l) { l.qty = Math.max(1, (Number(l.qty) || 0) - 1); },
                remove(i) { this.cart.splice(i, 1); },
                clearCart() { this.cart = []; this.stockNote = ''; },
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
                grand() { return this.subtotal() - this.discountAmt() + this.tax() + (parseFloat(this.shippingCharge) || 0); },
                balance() { return Math.max(0, this.grand() - (parseFloat(this.paid) || 0)); },
                count() { return this.cart.reduce((s, l) => s + (Number(l.qty) || 0), 0); },
                money(n) { return this.cur + ' ' + (Number(n) || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
                num(n) { return (Number(n) || 0).toLocaleString(undefined, { maximumFractionDigits: 3 }); },
            }));
        });
    </script>
@endpush
