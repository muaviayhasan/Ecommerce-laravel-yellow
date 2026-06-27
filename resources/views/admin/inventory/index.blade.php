@extends('layouts.admin')

@section('title', 'Inventory')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-label-sm mb-1">
                <a href="{{ route('admin.dashboard') }}" class="text-primary font-semibold hover:underline">Dashboard</a>
                <span class="material-symbols-outlined text-outline text-[16px]">chevron_right</span>
                <span class="text-on-surface-variant font-semibold">Inventory</span>
            </div>
            <h2 class="text-2xl font-bold text-on-surface">Inventory</h2>
            <p class="text-sm text-on-surface-variant mt-1">On-hand stock per variant. Adjustments post a movement and a ledger entry.</p>
        </div>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-6">
        <x-admin.stat-card title="Tracked variants" tone="primary" icon="inventory_2" :value="number_format($stats['variants'])" />
        <x-admin.stat-card title="Low stock" tone="tertiary" icon="warning" :value="number_format($stats['low'])" />
        <x-admin.stat-card title="Out of stock" tone="primary" icon="error" :value="number_format($stats['out'])" />
        <x-admin.stat-card title="Stock value" tone="secondary" icon="savings" :value="format_money($stats['value'])" />
    </div>

    <div x-data="stockAdjust(@js(route('admin.inventory.adjust', '__ID__')), @js(setting('general', 'currency_symbol', 'Rs')))">
        <x-admin.panel class="!p-0 overflow-hidden">
            <form method="GET" class="js-filters p-4 border-b border-outline-variant/60 flex flex-wrap items-center gap-3">
                <div class="relative flex-1 min-w-48">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-[20px]">search</span>
                    <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search product or SKU…" maxlength="255"
                        class="w-full bg-surface-container-low border border-outline-variant/40 rounded-lg pl-10 pr-3 py-2 text-sm text-on-surface placeholder:text-outline focus:ring-1 focus:ring-primary focus:border-primary outline-none">
                </div>
                <select name="category" class="bg-surface-container-low border border-outline-variant/40 rounded-lg px-3 py-2 text-sm text-on-surface focus:ring-1 focus:ring-primary outline-none cursor-pointer">
                    <option value="">All categories</option>
                    @foreach ($categories as $id => $name)
                        <option value="{{ $id }}" @selected((string) ($filters['category'] ?? '') === (string) $id)>{{ $name }}</option>
                    @endforeach
                </select>
                <select name="status" class="bg-surface-container-low border border-outline-variant/40 rounded-lg px-3 py-2 text-sm text-on-surface focus:ring-1 focus:ring-primary outline-none cursor-pointer">
                    <option value="">Any level</option>
                    <option value="in" @selected(($filters['status'] ?? '') === 'in')>In stock</option>
                    <option value="low" @selected(($filters['status'] ?? '') === 'low')>Low stock</option>
                    <option value="out" @selected(($filters['status'] ?? '') === 'out')>Out of stock</option>
                </select>
                <button type="submit" class="px-4 py-2 bg-primary-container text-white text-sm font-semibold rounded-lg hover:brightness-110 transition-all">Filter</button>
                @if (array_filter($filters))
                    <a href="{{ route('admin.inventory.index') }}" class="px-3 py-2 text-sm font-semibold text-on-surface-variant hover:text-primary">Reset</a>
                @endif
            </form>

            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="text-[10px] font-bold text-outline uppercase tracking-widest border-b border-outline-variant/60">
                        <tr>
                            <th class="px-6 py-3">Variant</th>
                            <th class="px-6 py-3 text-right">On hand</th>
                            <th class="px-6 py-3 text-right">Reserved</th>
                            <th class="px-6 py-3 text-right">Available</th>
                            <th class="px-6 py-3 text-right">Cost</th>
                            <th class="px-6 py-3 text-right">Value</th>
                            <th class="px-6 py-3">Level</th>
                            <th class="px-6 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/40 text-sm">
                        @forelse ($variants as $v)
                            @php
                                $onHand = (float) $v->stock_quantity;
                                $threshold = (float) $v->low_stock_threshold;
                                $num = fn ($q) => rtrim(rtrim(number_format((float) $q, 3), '0'), '.');
                            @endphp
                            <tr class="hover:bg-surface-container-high/60 transition-colors">
                                <td class="px-6 py-3">
                                    <a href="{{ route('admin.inventory.show', $v) }}" class="font-semibold text-on-surface hover:text-primary transition-colors line-clamp-1">{{ $v->product?->name ?? '—' }}</a>
                                    <div class="text-[11px] text-outline font-mono">{{ $v->sku }}{{ $v->product?->category ? ' · ' . $v->product->category->name : '' }}</div>
                                </td>
                                <td class="px-6 py-3 text-right font-semibold text-on-surface">{{ $num($onHand) }}</td>
                                <td class="px-6 py-3 text-right text-on-surface-variant">{{ $num($v->reserved_quantity) }}</td>
                                <td class="px-6 py-3 text-right text-on-surface-variant">{{ $num($v->availableQuantity()) }}</td>
                                <td class="px-6 py-3 text-right text-on-surface-variant">{{ format_money($v->cost) }}</td>
                                <td class="px-6 py-3 text-right text-on-surface-variant">{{ format_money($onHand * (float) $v->cost) }}</td>
                                <td class="px-6 py-3">
                                    @if ($onHand <= 0)
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-error-container text-on-error-container">Out</span>
                                    @elseif ($onHand <= $threshold)
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-tertiary-fixed text-tertiary">Low</span>
                                    @else
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-secondary-container text-on-secondary-container">In stock</span>
                                    @endif
                                </td>
                                <td class="px-6 py-3">
                                    <div class="flex items-center justify-end gap-1">
                                        @can('stock.adjust')
                                            <button type="button" title="Adjust stock"
                                                @click="open({ id: {{ $v->id }}, name: @js($v->product?->name . ' · ' . $v->sku), current: {{ $onHand }} })"
                                                class="p-2 rounded-lg text-on-surface-variant hover:bg-surface-container-high hover:text-primary transition-colors">
                                                <span class="material-symbols-outlined text-[20px]">tune</span>
                                            </button>
                                        @endcan
                                        <a href="{{ route('admin.inventory.show', $v) }}" title="Movement history" class="p-2 rounded-lg text-on-surface-variant hover:bg-surface-container-high hover:text-primary transition-colors">
                                            <span class="material-symbols-outlined text-[20px]">history</span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-16 text-center">
                                    <span class="material-symbols-outlined text-outline" style="font-size:48px;">inventory_2</span>
                                    <p class="mt-3 font-semibold text-on-surface">No stock-tracked variants</p>
                                    <p class="text-sm text-on-surface-variant mt-1">@if (array_filter($filters)) Try clearing the filters. @else Add products with stock tracking to see them here. @endif</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($variants->hasPages())
                <div class="px-6 py-4 border-t border-outline-variant/60">
                    <x-admin.pagination :paginator="$variants" />
                </div>
            @endif
        </x-admin.panel>

        {{-- Adjust modal --}}
        <div x-show="show" x-cloak @keydown.escape.window="show = false" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="fixed inset-0 bg-black/50"></div>
            <div class="relative min-h-full flex items-start justify-center p-4 sm:p-6" @click.self="show = false">
                <form :action="action()" method="POST" class="w-full max-w-md my-8 bg-surface-container-lowest dark:bg-surface-container border border-outline-variant rounded-xl shadow-2xl">
                    @csrf
                    <div class="flex items-center justify-between p-5 border-b border-outline-variant/60">
                        <h3 class="text-lg font-bold text-on-surface">Adjust stock</h3>
                        <button type="button" @click="show = false" class="cursor-pointer p-1 -mr-1 text-on-surface-variant hover:text-primary"><span class="material-symbols-outlined">close</span></button>
                    </div>
                    <div class="p-5 space-y-4">
                        <p class="text-sm text-on-surface-variant" x-text="name"></p>
                        <p class="text-sm text-on-surface-variant">Current on-hand: <span class="font-semibold text-on-surface" x-text="current"></span></p>

                        <div class="inline-flex gap-1 p-1 bg-surface-container-low rounded-lg">
                            <button type="button" @click="mode = 'set'; quantity = String(current)" :class="mode === 'set' ? 'bg-primary text-on-primary' : 'text-on-surface-variant'" class="px-3 py-1.5 rounded-md text-sm font-semibold transition-colors">Set to</button>
                            <button type="button" @click="mode = 'add'; quantity = ''" :class="mode === 'add' ? 'bg-primary text-on-primary' : 'text-on-surface-variant'" class="px-3 py-1.5 rounded-md text-sm font-semibold transition-colors">Add / remove</button>
                        </div>
                        <input type="hidden" name="mode" :value="mode">

                        <div class="space-y-1.5">
                            <label class="block text-sm font-medium text-on-surface-variant" x-text="mode === 'set' ? 'New on-hand count' : 'Change (use a minus to remove)'"></label>
                            <input type="number" step="any" name="quantity" x-model="quantity" placeholder="0"
                                class="w-full bg-surface-container-low border border-outline-variant rounded-lg px-3 py-2 text-sm text-on-surface focus:ring-1 focus:ring-primary outline-none">
                        </div>
                        <div class="space-y-1.5">
                            <label class="block text-sm font-medium text-on-surface-variant">Reason <span class="text-error">*</span></label>
                            <input type="text" name="reason" x-model="reason" maxlength="255" placeholder="e.g. Damaged, stock count correction" required
                                class="w-full bg-surface-container-low border border-outline-variant rounded-lg px-3 py-2 text-sm text-on-surface focus:ring-1 focus:ring-primary outline-none">
                        </div>
                        <p class="text-xs text-on-surface-variant">New on-hand will be
                            <span class="font-bold text-on-surface" x-text="resulting()"></span>.</p>
                    </div>
                    <div class="flex justify-end gap-3 p-5 border-t border-outline-variant/60">
                        <button type="button" @click="show = false" class="px-4 py-2.5 text-sm font-semibold text-on-surface-variant hover:text-on-surface">Cancel</button>
                        <button type="submit" class="px-5 py-2.5 bg-primary text-on-primary font-semibold text-sm rounded-lg hover:brightness-110">Save adjustment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('stockAdjust', (actionTpl, currency) => ({
                show: false,
                id: null, name: '', current: 0,
                mode: 'set', quantity: '', reason: '',
                cur: currency || 'Rs',
                action() { return actionTpl.replace('__ID__', this.id); },
                open(v) { this.id = v.id; this.name = v.name; this.current = v.current; this.mode = 'set'; this.quantity = String(v.current); this.reason = ''; this.show = true; },
                resulting() {
                    const q = Number(this.quantity) || 0;
                    return this.mode === 'set' ? q : this.current + q;
                },
            }));
        });
    </script>
@endpush
