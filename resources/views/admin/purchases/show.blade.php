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
                        @if ((float) $purchase->discount_total > 0)
                            <div class="flex justify-between text-on-surface-variant">
                                <span>Discount{{ $purchase->discount_type === 'percent' ? ' (' . rtrim(rtrim(number_format((float) $purchase->discount_value, 2), '0'), '.') . '%)' : '' }}</span>
                                <span class="text-error">- {{ format_money($purchase->discount_total) }}</span>
                            </div>
                        @endif
                        <div class="flex justify-between text-on-surface-variant"><span>Tax</span><span>{{ format_money($purchase->tax_total) }}</span></div>
                        <div class="flex justify-between font-bold text-on-surface text-base pt-2 border-t border-outline-variant/60"><span>Grand total</span><span>{{ format_money($purchase->grand_total) }}</span></div>
                        <div class="flex justify-between text-on-surface-variant"><span>Paid</span><span>{{ format_money($purchase->paid_total) }}</span></div>
                        <div class="flex justify-between"><span class="text-on-surface-variant">Payable</span><span class="font-semibold {{ $payable > 0 ? 'text-error' : 'text-secondary' }}">{{ format_money($payable) }}</span></div>
                    </div>
                </div>
            </x-admin.panel>

            @if ($purchase->status === 'received')
                @php $cell = 'w-full bg-surface-container-low border border-outline-variant rounded-lg px-3 py-2 text-sm text-on-surface focus:ring-1 focus:ring-primary focus:border-primary outline-none'; @endphp
                <div x-data="{ open: {{ $errors->any() ? 'true' : 'false' }} }">
                    <x-admin.panel class="!p-0 overflow-hidden">
                        <div class="px-6 py-4 border-b border-outline-variant/60 flex items-center justify-between gap-3">
                            <div>
                                <h3 class="text-lg font-bold text-on-surface">Payments</h3>
                                <p class="text-xs text-outline mt-0.5">
                                    Paid {{ format_money($purchase->paid_total) }} of {{ format_money($purchase->grand_total) }} ·
                                    <span class="{{ $payable > 0 ? 'text-error font-semibold' : 'text-secondary font-semibold' }}">{{ $payable > 0 ? format_money($payable) . ' due' : 'Fully paid' }}</span>
                                </p>
                            </div>
                            @can('purchases.pay')
                                @if ($payable > 0)
                                    <button type="button" @click="open = !open" class="px-4 py-2 bg-primary text-on-primary font-semibold text-sm rounded-lg hover:brightness-110 active:scale-95 transition-all flex items-center gap-2 shrink-0">
                                        <span class="material-symbols-outlined text-[18px]" x-text="open ? 'close' : 'payments'">payments</span>
                                        <span x-text="open ? 'Cancel' : 'Record payment'">Record payment</span>
                                    </button>
                                @endif
                            @endcan
                        </div>

                        @can('purchases.pay')
                            @if ($payable > 0)
                                <form method="POST" action="{{ route('admin.purchases.payment', $purchase) }}" x-show="open" x-cloak class="px-6 py-5 border-b border-outline-variant/60 bg-surface-container-low/40 space-y-4">
                                    @csrf
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div class="space-y-1.5">
                                            <label class="block text-sm font-medium text-on-surface-variant">Amount <span class="text-error">*</span></label>
                                            <input type="number" step="0.01" min="0.01" max="{{ $payable }}" name="amount" value="{{ old('amount', $payable) }}" class="{{ $cell }}">
                                            <p class="text-xs text-outline">Outstanding: {{ format_money($payable) }}</p>
                                            @error('amount')<p class="text-xs text-error">{{ $message }}</p>@enderror
                                        </div>
                                        <div class="space-y-1.5">
                                            <label class="block text-sm font-medium text-on-surface-variant">Date <span class="text-error">*</span></label>
                                            <input type="date" name="paid_on" value="{{ old('paid_on', now()->format('Y-m-d')) }}" class="{{ $cell }}">
                                            @error('paid_on')<p class="text-xs text-error">{{ $message }}</p>@enderror
                                        </div>
                                        <div class="space-y-1.5">
                                            <label class="block text-sm font-medium text-on-surface-variant">Method <span class="text-error">*</span></label>
                                            <select name="method" class="{{ $cell }} cursor-pointer">
                                                <option value="cash" @selected(old('method') === 'cash')>Cash</option>
                                                <option value="bank" @selected(old('method') === 'bank')>Bank</option>
                                            </select>
                                            @error('method')<p class="text-xs text-error">{{ $message }}</p>@enderror
                                        </div>
                                        <div class="space-y-1.5">
                                            <label class="block text-sm font-medium text-on-surface-variant">Reference <span class="text-outline font-normal">(cheque/txn #)</span></label>
                                            <input type="text" name="reference" value="{{ old('reference') }}" maxlength="100" class="{{ $cell }}">
                                            @error('reference')<p class="text-xs text-error">{{ $message }}</p>@enderror
                                        </div>
                                    </div>
                                    <div class="space-y-1.5">
                                        <label class="block text-sm font-medium text-on-surface-variant">Note</label>
                                        <input type="text" name="note" value="{{ old('note') }}" maxlength="500" class="{{ $cell }}">
                                        @error('note')<p class="text-xs text-error">{{ $message }}</p>@enderror
                                    </div>
                                    <div class="flex justify-end">
                                        <button type="submit" class="px-5 py-2.5 bg-primary text-on-primary font-semibold text-sm rounded-lg hover:brightness-110 active:scale-95 transition-all flex items-center gap-2">
                                            <span class="material-symbols-outlined text-[18px]">check</span> Save payment
                                        </button>
                                    </div>
                                </form>
                            @endif
                        @endcan

                        @if ($purchase->payments->isNotEmpty())
                            <table class="w-full text-left text-sm">
                                <thead class="text-[10px] font-bold text-outline uppercase tracking-widest border-b border-outline-variant/60">
                                    <tr>
                                        <th class="px-6 py-3">Date</th>
                                        <th class="px-6 py-3">Method</th>
                                        <th class="px-6 py-3 text-right">Amount</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-outline-variant/40">
                                    @foreach ($purchase->payments as $payment)
                                        <tr>
                                            <td class="px-6 py-3">
                                                <p class="text-on-surface">{{ format_date($payment->paid_on) }}</p>
                                                @if ($payment->reference || $payment->note)
                                                    <p class="text-[11px] text-outline">{{ collect([$payment->reference, $payment->note])->filter()->implode(' · ') }}</p>
                                                @endif
                                            </td>
                                            <td class="px-6 py-3 capitalize text-on-surface-variant">{{ $payment->method }}</td>
                                            <td class="px-6 py-3 text-right font-semibold text-on-surface">{{ format_money($payment->amount) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <p class="px-6 py-5 text-sm text-on-surface-variant">No payments recorded yet.</p>
                        @endif
                    </x-admin.panel>
                </div>
            @endif

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
