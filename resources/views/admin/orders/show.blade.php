@extends('layouts.admin')

@section('title', 'Order #' . $order->order_number)

@section('content')
    {{-- Header --}}
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-label-sm mb-1">
                <a href="{{ route('admin.orders.index') }}" class="text-primary font-semibold hover:underline">Orders</a>
                <span class="material-symbols-outlined text-outline text-[16px]">chevron_right</span>
                <span class="text-on-surface-variant font-semibold">#{{ $order->order_number }}</span>
            </div>
            <div class="flex items-center gap-3 flex-wrap">
                <h2 class="text-2xl font-bold text-on-surface">Order #{{ $order->order_number }}</h2>
                <x-admin.order-badge :status="$order->status" />
                <x-admin.order-badge :status="$order->payment_status" type="payment" />
            </div>
            <p class="text-sm text-on-surface-variant mt-1">
                Placed {{ format_datetime($order->placed_at ?? $order->created_at) }} · {{ ucfirst($order->channel) }} channel
            </p>
        </div>

        <div class="shrink-0">
            <x-admin.print-menu :base="route('admin.orders.print', $order)" label="Print bill" />
        </div>
    </div>

    <div class="grid grid-cols-12 gap-6 items-start">
        {{-- Left: items + totals --}}
        <div class="col-span-12 lg:col-span-8 space-y-6">
            <x-admin.panel class="!p-0 overflow-hidden">
                <div class="px-6 py-4 border-b border-outline-variant/60 flex items-center justify-between">
                    <h3 class="text-lg font-bold text-on-surface">Items</h3>
                    <span class="text-xs text-outline">{{ $order->items->count() }} item(s)</span>
                </div>
                <div class="divide-y divide-outline-variant/40">
                    @foreach ($order->items as $item)
                        @php $img = $item->variant?->image?->url ?? $item->variant?->product?->media?->first()?->url; @endphp
                        <div class="px-6 py-4 flex items-center gap-4">
                            <div class="w-14 h-14 rounded-lg bg-surface-container-low border border-outline-variant/40 overflow-hidden grid place-items-center shrink-0">
                                @if ($img)
                                    <img src="{{ $img }}" alt="{{ $item->name_snapshot }}" class="w-full h-full object-cover">
                                @else
                                    <span class="material-symbols-outlined text-outline">inventory_2</span>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-semibold text-on-surface truncate">{{ $item->name_snapshot }}</p>
                                <p class="text-[11px] text-outline">
                                    {{ $item->sku_snapshot ?: '—' }}
                                    @if (! empty($item->attributes_snapshot))
                                        · {{ collect($item->attributes_snapshot)->implode(', ') }}
                                    @endif
                                </p>
                            </div>
                            <div class="text-sm text-on-surface-variant text-right shrink-0">
                                {{ rtrim(rtrim(number_format((float) $item->quantity, 3), '0'), '.') }} × {{ format_money($item->unit_price) }}
                            </div>
                            <div class="w-28 text-right font-bold text-on-surface shrink-0">{{ format_money($item->line_total) }}</div>
                        </div>
                    @endforeach
                </div>

                {{-- Totals --}}
                <div class="px-6 py-5 border-t border-outline-variant/60 bg-surface-container-low/40">
                    <div class="ml-auto max-w-xs space-y-2 text-sm">
                        <div class="flex justify-between text-on-surface-variant"><span>Subtotal</span><span>{{ format_money($order->subtotal) }}</span></div>
                        @if ((float) $order->discount_total > 0)
                            <div class="flex justify-between text-on-surface-variant"><span>Discount{{ $order->discount_type === 'percent' ? ' (' . rtrim(rtrim(number_format((float) $order->discount_value, 2), '0'), '.') . '%)' : '' }}</span><span>− {{ format_money($order->discount_total) }}</span></div>
                        @endif
                        <div class="flex justify-between text-on-surface-variant"><span>Tax</span><span>{{ format_money($order->tax_total) }}</span></div>
                        <div class="flex justify-between text-on-surface-variant"><span>Shipping</span><span>{{ format_money($order->shipping_total) }}</span></div>
                        <div class="flex justify-between font-bold text-on-surface text-base pt-2 border-t border-outline-variant/60">
                            <span>Total</span><span>{{ format_money($order->grand_total) }}</span>
                        </div>
                        <div class="flex justify-between text-on-surface-variant"><span>Paid</span><span>{{ format_money($order->paid_total) }}</span></div>
                    </div>
                </div>
            </x-admin.panel>

            {{-- Timeline --}}
            @if ($order->statusHistory->isNotEmpty())
                <x-admin.panel title="Order timeline">
                    <ol class="relative border-l border-outline-variant ml-2 space-y-5">
                        @foreach ($order->statusHistory as $event)
                            <li class="ml-5">
                                <span class="absolute -left-1.5 w-3 h-3 rounded-full bg-primary border-2 border-surface-container-lowest dark:border-surface-container"></span>
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="text-sm font-semibold text-on-surface capitalize">{{ str_replace('_', ' ', $event->to_status) }}</span>
                                    @if ($event->from_status)
                                        <span class="text-[11px] text-outline">from {{ str_replace('_', ' ', $event->from_status) }}</span>
                                    @endif
                                </div>
                                @if ($event->note)<p class="text-xs text-on-surface-variant mt-0.5">{{ $event->note }}</p>@endif
                                <p class="text-[11px] text-outline mt-0.5">
                                    {{ format_datetime($event->created_at) }}{{ $event->author ? ' · ' . $event->author->name : '' }}
                                </p>
                            </li>
                        @endforeach
                    </ol>
                </x-admin.panel>
            @endif
        </div>

        {{-- Right: status, customer, addresses, payment --}}
        <div class="col-span-12 lg:col-span-4 space-y-6">
            @can('orders.edit')
                <x-admin.panel title="Update status">
                    <form method="POST" action="{{ route('admin.orders.status', $order) }}" class="space-y-3">
                        @csrf
                        @method('PATCH')
                        <div>
                            <label class="block text-xs font-medium text-on-surface-variant mb-1">Status</label>
                            <select name="status" data-no-select2
                                class="w-full bg-surface-container-low border border-outline-variant rounded-lg px-3 py-2 text-sm text-on-surface focus:ring-1 focus:ring-primary outline-none cursor-pointer">
                                @foreach ($statuses as $status)
                                    <option value="{{ $status }}" @selected($order->status === $status)>{{ ucfirst($status) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-on-surface-variant mb-1">Courier</label>
                                <input type="text" name="courier" value="{{ $order->courier }}" maxlength="100"
                                    class="w-full bg-surface-container-low border border-outline-variant rounded-lg px-3 py-2 text-sm text-on-surface focus:ring-1 focus:ring-primary outline-none">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-on-surface-variant mb-1">Tracking #</label>
                                <input type="text" name="tracking_number" value="{{ $order->tracking_number }}" maxlength="100"
                                    class="w-full bg-surface-container-low border border-outline-variant rounded-lg px-3 py-2 text-sm text-on-surface focus:ring-1 focus:ring-primary outline-none">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-on-surface-variant mb-1">Note <span class="text-outline font-normal">(optional)</span></label>
                            <textarea name="note" rows="2" maxlength="500"
                                class="w-full bg-surface-container-low border border-outline-variant rounded-lg px-3 py-2 text-sm text-on-surface focus:ring-1 focus:ring-primary outline-none resize-y"></textarea>
                        </div>
                        <button type="submit"
                            class="w-full bg-primary text-on-primary py-2.5 rounded-lg font-semibold text-sm flex items-center justify-center gap-2 hover:brightness-110 active:scale-95 transition-all">
                            <span class="material-symbols-outlined text-[20px]">save</span> Update order
                        </button>
                    </form>
                </x-admin.panel>
            @endcan

            <x-admin.panel title="Customer">
                <div class="flex items-center gap-3">
                    <span class="w-10 h-10 rounded-full bg-primary-container text-white grid place-items-center font-bold shrink-0">
                        {{ strtoupper(substr($order->customer?->name ?? 'G', 0, 1)) }}
                    </span>
                    <div class="min-w-0">
                        <p class="font-semibold text-on-surface truncate">{{ $order->customer?->name ?? 'Guest' }}</p>
                        @if ($order->customer)
                            <a href="{{ route('admin.customers.edit', $order->customer) }}" class="text-xs text-primary font-semibold hover:underline">View customer</a>
                        @endif
                    </div>
                </div>
                @if ($order->customer?->email || $order->customer?->phone)
                    <div class="mt-3 space-y-1 text-sm text-on-surface-variant">
                        @if ($order->customer?->email)<p class="flex items-center gap-2"><span class="material-symbols-outlined text-[18px] text-outline">mail</span>{{ $order->customer->email }}</p>@endif
                        @if ($order->customer?->phone)<p class="flex items-center gap-2"><span class="material-symbols-outlined text-[18px] text-outline">call</span>{{ $order->customer->phone }}</p>@endif
                    </div>
                @endif
            </x-admin.panel>

            @foreach (['shipping' => 'Shipping address', 'billing' => 'Billing address'] as $type => $label)
                @php $addr = $order->addresses->firstWhere('type', $type); @endphp
                @if ($addr)
                    <x-admin.panel :title="$label">
                        <div class="text-sm text-on-surface-variant space-y-0.5">
                            <p class="font-semibold text-on-surface">{{ $addr->name }}</p>
                            @if ($addr->company)<p>{{ $addr->company }}</p>@endif
                            <p>{{ $addr->line1 }}</p>
                            @if ($addr->line2)<p>{{ $addr->line2 }}</p>@endif
                            <p>{{ collect([$addr->city, $addr->state, $addr->zip])->filter()->implode(', ') }}</p>
                            <p>{{ $addr->country }}</p>
                            @if ($addr->phone)<p class="pt-1">{{ $addr->phone }}</p>@endif
                        </div>
                    </x-admin.panel>
                @endif
            @endforeach

            @php
                $outstanding = round((float) $order->grand_total - (float) $order->paid_total, 2);
                $cellP = 'w-full bg-surface-container-low border border-outline-variant rounded-lg px-3 py-2 text-sm text-on-surface outline-none focus:ring-1 focus:ring-primary';
            @endphp
            <div x-data="{ pay: false }">
                <x-admin.panel title="Payment">
                    @can('orders.edit')
                        @if ($outstanding > 0)
                            <x-slot:actions>
                                <button type="button" @click="pay = !pay" class="text-xs font-semibold text-primary" x-text="pay ? 'Cancel' : 'Record payment'">Record payment</button>
                            </x-slot:actions>
                        @endif
                    @endcan

                    <div x-show="!pay">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-on-surface-variant capitalize">{{ str_replace('_', ' ', $order->payment_method) }}</span>
                            <x-admin.order-badge :status="$order->payment_status" type="payment" />
                        </div>
                        <div class="mt-3 flex items-center justify-between text-sm">
                            <span class="text-on-surface-variant">Paid</span>
                            <span class="font-semibold text-on-surface">{{ format_money($order->paid_total) }} / {{ format_money($order->grand_total) }}</span>
                        </div>
                        @if ($outstanding > 0)
                            <div class="mt-2 flex items-center justify-between text-sm">
                                <span class="text-on-surface-variant">Outstanding</span>
                                <span class="font-semibold text-error">{{ format_money($outstanding) }}</span>
                            </div>
                        @endif
                        @if ($order->payments->isNotEmpty())
                            <div class="mt-3 pt-3 border-t border-outline-variant/60 space-y-2">
                                @foreach ($order->payments as $payment)
                                    <div class="flex items-center justify-between text-xs">
                                        <span class="text-on-surface-variant capitalize">{{ str_replace('_', ' ', $payment->gateway) }} · {{ $payment->status }}{{ $payment->receiver ? ' · ' . $payment->receiver->name : '' }}</span>
                                        <span class="font-semibold text-on-surface">{{ format_money($payment->amount) }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    @can('orders.edit')
                        @if ($outstanding > 0)
                            <form x-show="pay" x-cloak method="POST" action="{{ route('admin.orders.payment', $order) }}" class="space-y-3">
                                @csrf @method('PATCH')
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-medium text-on-surface-variant">Amount received</label>
                                    <input type="number" name="amount" step="0.01" min="0.01" max="{{ $outstanding }}" value="{{ $outstanding }}" required class="{{ $cellP }}">
                                    <p class="text-xs text-on-surface-variant">Outstanding: {{ format_money($outstanding) }}</p>
                                </div>
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-medium text-on-surface-variant">Method</label>
                                    <select name="method" data-no-select2 class="{{ $cellP }} cursor-pointer">
                                        <option value="cash">Cash</option>
                                        <option value="bank">Bank transfer</option>
                                    </select>
                                </div>
                                <input type="text" name="reference" maxlength="255" placeholder="Reference / txn # (optional)" class="{{ $cellP }}">
                                <button type="submit" class="w-full px-4 py-2 bg-primary text-on-primary font-semibold text-sm rounded-lg hover:brightness-110 active:scale-95 transition-all">Record payment</button>
                            </form>
                        @endif
                    @endcan
                </x-admin.panel>
            </div>

            {{-- Delivery --}}
            @php
                $deliveryLabel = delivery_method_label($order->shipping_method);
                $cellD = 'w-full bg-surface-container-low border border-outline-variant rounded-lg px-3 py-2 text-sm text-on-surface outline-none focus:ring-1 focus:ring-primary';
            @endphp
            <div x-data="{ edit: false }">
                <x-admin.panel title="Delivery">
                    @can('orders.edit')
                        <x-slot:actions>
                            <button type="button" @click="edit = !edit" class="text-xs font-semibold text-primary" x-text="edit ? 'Cancel' : 'Edit'">Edit</button>
                        </x-slot:actions>
                    @endcan

                    <div x-show="!edit">
                        @if ($deliveryLabel || $order->courier || $order->tracking_number || (float) $order->shipping_total > 0)
                            <dl class="space-y-2.5 text-sm">
                                @if ($deliveryLabel)<div class="flex justify-between gap-4"><dt class="text-on-surface-variant">Method</dt><dd class="text-on-surface font-medium text-right">{{ $deliveryLabel }}</dd></div>@endif
                                @if ($order->courier)<div class="flex justify-between gap-4"><dt class="text-on-surface-variant">Handled by</dt><dd class="text-on-surface font-medium text-right">{{ $order->courier }}</dd></div>@endif
                                @if ($order->tracking_number)<div class="flex justify-between gap-4"><dt class="text-on-surface-variant">Contact / tracking</dt><dd class="text-on-surface font-medium text-right">{{ $order->tracking_number }}</dd></div>@endif
                                @if ((float) $order->shipping_total > 0)<div class="flex justify-between gap-4"><dt class="text-on-surface-variant">Delivery charge</dt><dd class="text-on-surface font-medium text-right">{{ format_money($order->shipping_total) }}</dd></div>@endif
                            </dl>
                        @else
                            <p class="text-sm text-on-surface-variant">No delivery recorded.</p>
                        @endif
                    </div>

                    @can('orders.edit')
                        <form x-show="edit" x-cloak method="POST" action="{{ route('admin.orders.delivery', $order) }}" class="space-y-3">
                            @csrf @method('PATCH')
                            <div class="space-y-1.5">
                                <label class="block text-xs font-medium text-on-surface-variant">Method</label>
                                <select name="shipping_method" data-no-select2 class="{{ $cellD }} cursor-pointer">
                                    <option value="pickup" @selected($order->shipping_method === 'pickup' || ! $order->shipping_method)>Store pickup (collected)</option>
                                    <option value="own_rider" @selected($order->shipping_method === 'own_rider')>Own rider</option>
                                    <option value="courier" @selected($order->shipping_method === 'courier')>Third-party courier</option>
                                    <option value="other" @selected($order->shipping_method === 'other')>Other person</option>
                                </select>
                            </div>
                            <input type="text" name="courier" value="{{ $order->courier }}" maxlength="255" placeholder="Handled by (rider / courier / person)" class="{{ $cellD }}">
                            <input type="text" name="tracking_number" value="{{ $order->tracking_number }}" maxlength="255" placeholder="Contact phone or tracking #" class="{{ $cellD }}">
                            <button type="submit" class="w-full px-4 py-2 bg-primary text-on-primary font-semibold text-sm rounded-lg hover:brightness-110 active:scale-95 transition-all">Save delivery</button>
                        </form>
                    @endcan
                </x-admin.panel>
            </div>
        </div>
    </div>
@endsection
