@extends('layouts.admin')

@section('title', ($variant->product?->name ?? 'Variant') . ' · stock')

@php
    $onHand = (float) $variant->stock_quantity;
    $threshold = (float) $variant->low_stock_threshold;
    $num = fn ($q) => rtrim(rtrim(number_format((float) $q, 3), '0'), '.');
    $typeLabels = [
        'purchase_in' => 'Purchase in', 'sale_out' => 'Sale', 'production_consume' => 'Production use',
        'production_output' => 'Production output', 'adjustment' => 'Adjustment', 'return_in' => 'Return', 'transfer' => 'Transfer',
    ];
@endphp

@section('content')
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-label-sm mb-1">
                <a href="{{ route('admin.inventory.index') }}" class="text-primary font-semibold hover:underline">Inventory</a>
                <span class="material-symbols-outlined text-outline text-[16px]">chevron_right</span>
                <span class="text-on-surface-variant font-semibold line-clamp-1">{{ $variant->product?->name }}</span>
            </div>
            <div class="flex items-center gap-3 flex-wrap">
                <h2 class="text-2xl font-bold text-on-surface">{{ $variant->product?->name ?? 'Variant' }}</h2>
                @if ($onHand <= 0)
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-error-container text-on-error-container">Out</span>
                @elseif ($onHand <= $threshold)
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-tertiary-fixed text-tertiary">Low</span>
                @else
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-secondary-container text-on-secondary-container">In stock</span>
                @endif
            </div>
            <p class="text-sm text-on-surface-variant mt-1 font-mono">{{ $variant->sku }}
                @if ($variant->attributeValues->isNotEmpty())· {{ $variant->attributeValues->map(fn ($a) => $a->label ?: $a->value)->implode(' / ') }}@endif
            </p>
        </div>
        @if ($variant->product)
            <a href="{{ route('admin.products.show', $variant->product) }}" class="px-4 py-2.5 border border-outline text-on-surface font-semibold text-sm rounded-lg hover:bg-surface-container transition-colors flex items-center gap-2 shrink-0">
                <span class="material-symbols-outlined text-[20px]">open_in_new</span> Product
            </a>
        @endif
    </div>

    <div class="grid grid-cols-12 gap-6 items-start">
        {{-- Movement history --}}
        <div class="col-span-12 lg:col-span-8">
            <x-admin.panel class="!p-0 overflow-hidden">
                <div class="px-6 py-4 border-b border-outline-variant/60">
                    <h3 class="text-lg font-bold text-on-surface">Movement history</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="text-[10px] font-bold text-outline uppercase tracking-widest border-b border-outline-variant/60">
                            <tr>
                                <th class="px-6 py-3">Type</th>
                                <th class="px-6 py-3 text-right">Change</th>
                                <th class="px-6 py-3 text-right">Balance</th>
                                <th class="px-6 py-3">Reason</th>
                                <th class="px-6 py-3">When</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/40">
                            @forelse ($movements as $m)
                                @php $qty = (float) $m->quantity; @endphp
                                <tr class="hover:bg-surface-container-high/50 transition-colors">
                                    <td class="px-6 py-3 text-on-surface font-medium">{{ $typeLabels[$m->type] ?? ucfirst(str_replace('_', ' ', $m->type)) }}</td>
                                    <td class="px-6 py-3 text-right font-semibold {{ $qty < 0 ? 'text-error' : 'text-secondary' }}">{{ $qty > 0 ? '+' : '' }}{{ $num($qty) }}</td>
                                    <td class="px-6 py-3 text-right text-on-surface-variant">{{ $num($m->balance_after) }}</td>
                                    <td class="px-6 py-3 text-on-surface-variant">{{ $m->reason ?: '—' }}</td>
                                    <td class="px-6 py-3 text-on-surface-variant">
                                        {{ format_date($m->created_at) }}
                                        @if ($m->author)<span class="text-[11px] text-outline block">{{ $m->author->name }}</span>@endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-6 py-12 text-center text-on-surface-variant">No movements yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($movements->hasPages())
                    <div class="px-6 py-4 border-t border-outline-variant/60"><x-admin.pagination :paginator="$movements" /></div>
                @endif
            </x-admin.panel>
        </div>

        {{-- Summary + adjust --}}
        <div class="col-span-12 lg:col-span-4 space-y-6">
            <x-admin.panel title="Stock">
                <dl class="space-y-2.5 text-sm">
                    <div class="flex justify-between"><dt class="text-on-surface-variant">On hand</dt><dd class="font-bold text-on-surface">{{ $num($onHand) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-on-surface-variant">Reserved</dt><dd class="text-on-surface">{{ $num($variant->reserved_quantity) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-on-surface-variant">Available</dt><dd class="text-on-surface">{{ $num($variant->availableQuantity()) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-on-surface-variant">Low-stock at</dt><dd class="text-on-surface">{{ $num($threshold) }}</dd></div>
                    <div class="flex justify-between pt-2 border-t border-outline-variant/60"><dt class="text-on-surface-variant">Unit cost</dt><dd class="text-on-surface">{{ format_money($variant->cost) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-on-surface-variant">Stock value</dt><dd class="font-semibold text-on-surface">{{ format_money($onHand * (float) $variant->cost) }}</dd></div>
                </dl>
            </x-admin.panel>

            @can('stock.adjust')
                <x-admin.panel title="Adjust stock">
                    <form method="POST" action="{{ route('admin.inventory.adjust', $variant) }}"
                        x-data="{ mode: 'set', quantity: @js($onHand), current: @js($onHand), resulting() { const q = Number(this.quantity) || 0; return this.mode === 'set' ? q : this.current + q; } }"
                        class="space-y-4">
                        @csrf
                        <div class="inline-flex gap-1 p-1 bg-surface-container-low rounded-lg">
                            <button type="button" @click="mode = 'set'; quantity = current" :class="mode === 'set' ? 'bg-primary text-on-primary' : 'text-on-surface-variant'" class="px-3 py-1.5 rounded-md text-sm font-semibold transition-colors">Set to</button>
                            <button type="button" @click="mode = 'add'; quantity = ''" :class="mode === 'add' ? 'bg-primary text-on-primary' : 'text-on-surface-variant'" class="px-3 py-1.5 rounded-md text-sm font-semibold transition-colors">Add / remove</button>
                        </div>
                        <input type="hidden" name="mode" :value="mode">
                        <div class="space-y-1.5">
                            <label class="block text-sm font-medium text-on-surface-variant" x-text="mode === 'set' ? 'New on-hand count' : 'Change (use a minus to remove)'"></label>
                            <input type="number" step="any" name="quantity" x-model="quantity" class="w-full bg-surface-container-low border border-outline-variant rounded-lg px-3 py-2 text-sm text-on-surface focus:ring-1 focus:ring-primary outline-none">
                        </div>
                        <div class="space-y-1.5">
                            <label class="block text-sm font-medium text-on-surface-variant">Reason <span class="text-error">*</span></label>
                            <input type="text" name="reason" maxlength="255" required placeholder="e.g. Damaged, count correction" class="w-full bg-surface-container-low border border-outline-variant rounded-lg px-3 py-2 text-sm text-on-surface focus:ring-1 focus:ring-primary outline-none">
                        </div>
                        <p class="text-xs text-on-surface-variant">New on-hand will be <span class="font-bold text-on-surface" x-text="resulting()"></span>.</p>
                        <button type="submit" class="w-full bg-primary text-on-primary py-2.5 rounded-lg font-semibold text-sm hover:brightness-110 active:scale-95 transition-all flex items-center justify-center gap-2">
                            <span class="material-symbols-outlined text-[20px]">tune</span> Save adjustment
                        </button>
                    </form>
                </x-admin.panel>
            @endcan
        </div>
    </div>
@endsection
