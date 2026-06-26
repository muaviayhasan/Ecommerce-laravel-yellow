@extends('layouts.admin')

@section('title', $purchase->purchase_number)

@php
    $statusTone = [
        'draft' => 'bg-surface-container-high text-on-surface-variant',
        'received' => 'bg-secondary-container text-on-secondary-container',
        'cancelled' => 'bg-error-container text-on-error-container',
    ];
    $payable = (float) $purchase->grand_total - (float) $purchase->paid_total;
@endphp

@section('content')
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-label-sm mb-1">
                <a href="{{ route('admin.purchases.index') }}" class="text-primary font-semibold hover:underline">Purchases</a>
                <span class="material-symbols-outlined text-outline text-[16px]">chevron_right</span>
                <span class="text-on-surface-variant font-semibold">{{ $purchase->purchase_number }}</span>
            </div>
            <div class="flex items-center gap-3 flex-wrap">
                <h2 class="text-2xl font-bold text-on-surface">{{ $purchase->purchase_number }}</h2>
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold capitalize {{ $statusTone[$purchase->status] ?? '' }}">{{ $purchase->status }}</span>
            </div>
            <p class="text-sm text-on-surface-variant mt-1">{{ $purchase->supplier?->name ?? '—' }} · {{ format_date($purchase->purchase_date) }}@if ($purchase->reference) · Ref {{ $purchase->reference }}@endif</p>
        </div>

        <div class="flex items-center gap-2 shrink-0">
            @if ($purchase->status === 'draft')
                @can('purchases.edit')
                    <a href="{{ route('admin.purchases.edit', $purchase) }}" class="px-4 py-2.5 border border-outline text-on-surface font-semibold text-sm rounded-lg hover:bg-surface-container transition-colors flex items-center gap-2">
                        <span class="material-symbols-outlined text-[20px]">edit</span> Edit
                    </a>
                @endcan
                @can('purchases.receive')
                    <form method="POST" action="{{ route('admin.purchases.receive', $purchase) }}" onsubmit="return confirm('Receive this purchase? Stock and the ledger will be updated.');">
                        @csrf
                        <button type="submit" class="px-4 py-2.5 bg-primary text-on-primary font-semibold text-sm rounded-lg hover:brightness-110 active:scale-95 transition-all flex items-center gap-2">
                            <span class="material-symbols-outlined text-[20px]">inventory_2</span> Receive
                        </button>
                    </form>
                @endcan
                @can('purchases.delete')
                    <form method="POST" action="{{ route('admin.purchases.destroy', $purchase) }}" onsubmit="return confirm('Delete this draft purchase?');">
                        @csrf @method('DELETE')
                        <button type="submit" title="Delete" class="p-2.5 rounded-lg text-on-surface-variant hover:bg-error-container hover:text-error transition-colors"><span class="material-symbols-outlined text-[20px]">delete</span></button>
                    </form>
                @endcan
            @elseif ($purchase->status === 'received')
                @can('purchases.receive')
                    <form method="POST" action="{{ route('admin.purchases.cancel', $purchase) }}" onsubmit="return confirm('Cancel this received purchase? Stock and ledger entries will be reversed.');">
                        @csrf
                        <button type="submit" class="px-4 py-2.5 border border-error text-error font-semibold text-sm rounded-lg hover:bg-error-container transition-colors flex items-center gap-2">
                            <span class="material-symbols-outlined text-[20px]">undo</span> Cancel purchase
                        </button>
                    </form>
                @endcan
            @endif
        </div>
    </div>

    <div class="grid grid-cols-12 gap-6 items-start">
        <div class="col-span-12 lg:col-span-8 space-y-6">
            <x-admin.panel class="!p-0 overflow-hidden">
                <div class="px-6 py-4 border-b border-outline-variant/60 flex items-center justify-between">
                    <h3 class="text-lg font-bold text-on-surface">Items</h3>
                    <span class="text-xs text-outline">{{ $purchase->items->count() }} line(s)</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="text-[10px] font-bold text-outline uppercase tracking-widest border-b border-outline-variant/60">
                            <tr>
                                <th class="px-6 py-3">Product</th>
                                <th class="px-6 py-3 text-right">Quantity</th>
                                <th class="px-6 py-3 text-right">Unit cost</th>
                                <th class="px-6 py-3 text-right">Line total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/40">
                            @foreach ($purchase->items as $item)
                                <tr>
                                    <td class="px-6 py-3">
                                        <p class="font-medium text-on-surface">{{ $item->variant?->product?->name ?? '—' }}</p>
                                        <p class="text-[11px] text-outline font-mono">{{ $item->variant?->sku }}</p>
                                    </td>
                                    <td class="px-6 py-3 text-right text-on-surface-variant">{{ rtrim(rtrim(number_format((float) $item->quantity, 3), '0'), '.') }}</td>
                                    <td class="px-6 py-3 text-right text-on-surface-variant">{{ format_money($item->unit_cost) }}</td>
                                    <td class="px-6 py-3 text-right font-semibold text-on-surface">{{ format_money($item->line_total) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="px-6 py-5 border-t border-outline-variant/60 bg-surface-container-low/40">
                    <div class="ml-auto max-w-xs space-y-2 text-sm">
                        <div class="flex justify-between text-on-surface-variant"><span>Subtotal</span><span>{{ format_money($purchase->subtotal) }}</span></div>
                        <div class="flex justify-between text-on-surface-variant"><span>Tax</span><span>{{ format_money($purchase->tax_total) }}</span></div>
                        <div class="flex justify-between font-bold text-on-surface text-base pt-2 border-t border-outline-variant/60"><span>Grand total</span><span>{{ format_money($purchase->grand_total) }}</span></div>
                        <div class="flex justify-between text-on-surface-variant"><span>Paid</span><span>{{ format_money($purchase->paid_total) }}</span></div>
                        <div class="flex justify-between"><span class="text-on-surface-variant">Payable</span><span class="font-semibold {{ $payable > 0 ? 'text-error' : 'text-secondary' }}">{{ format_money($payable) }}</span></div>
                    </div>
                </div>
            </x-admin.panel>

            @if ($purchase->notes)
                <x-admin.panel title="Notes">
                    <p class="text-sm text-on-surface-variant whitespace-pre-line">{{ $purchase->notes }}</p>
                </x-admin.panel>
            @endif
        </div>

        <div class="col-span-12 lg:col-span-4 space-y-6">
            <x-admin.panel title="Summary">
                <dl class="space-y-2.5 text-sm">
                    <div class="flex justify-between gap-4"><dt class="text-on-surface-variant">Supplier</dt><dd class="text-on-surface font-medium text-right">{{ $purchase->supplier?->name ?? '—' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-on-surface-variant">Date</dt><dd class="text-on-surface font-medium text-right">{{ format_date($purchase->purchase_date) }}</dd></div>
                    @if ($purchase->reference)<div class="flex justify-between gap-4"><dt class="text-on-surface-variant">Reference</dt><dd class="text-on-surface font-medium text-right">{{ $purchase->reference }}</dd></div>@endif
                    <div class="flex justify-between gap-4"><dt class="text-on-surface-variant">Created by</dt><dd class="text-on-surface font-medium text-right">{{ $purchase->author?->name ?? '—' }}</dd></div>
                </dl>
            </x-admin.panel>

            <x-admin.panel title="What happens on receive">
                <ul class="space-y-2 text-sm text-on-surface-variant">
                    <li class="flex items-start gap-2"><span class="material-symbols-outlined text-secondary text-[18px]">add_box</span>Each line is added to stock (a <span class="font-medium">stock movement</span>).</li>
                    <li class="flex items-start gap-2"><span class="material-symbols-outlined text-secondary text-[18px]">calculate</span>Variant <span class="font-medium">moving-average cost</span> is re-blended.</li>
                    <li class="flex items-start gap-2"><span class="material-symbols-outlined text-secondary text-[18px]">account_balance</span>Inventory / cash / payable post to the <span class="font-medium">ledger</span>.</li>
                </ul>
            </x-admin.panel>
        </div>
    </div>
@endsection
