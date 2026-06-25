@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
    {{-- Analytics cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <x-admin.stat-card title="Total Sales" tone="primary" icon="shopping_basket"
            :value="number_format($stats['sales']['value'])" :trend="$stats['sales']['trend']" />

        <x-admin.stat-card title="Total Income" tone="tertiary" icon="payments"
            :value="format_money($stats['income']['value'])" :trend="$stats['income']['trend']" />

        <x-admin.stat-card title="Orders Paid" tone="neutral" icon="description"
            :value="number_format($stats['paid_orders']['value'])" :trend="$stats['paid_orders']['trend']" />

        <x-admin.stat-card title="Customers" tone="secondary" icon="group"
            :value="number_format($stats['customers']['value'])" :trend="$stats['customers']['trend']" />
    </div>

    {{-- Main grid --}}
    <div class="grid grid-cols-12 gap-6">
        {{-- Recent Order bar chart --}}
        <x-admin.panel class="col-span-12 lg:col-span-8" title="Recent Orders">
            <x-slot:actions>
                <span class="text-xs font-medium text-on-surface-variant">Last 12 months</span>
            </x-slot:actions>

            <div class="h-72 w-full flex items-end gap-1.5 px-1 relative">
                {{-- gridlines --}}
                <div class="absolute inset-0 flex flex-col justify-between py-2 pointer-events-none">
                    @for ($i = 0; $i < 5; $i++)
                        <div class="w-full h-px bg-surface-container-low"></div>
                    @endfor
                </div>

                @foreach ($orderChart['data'] as $bar)
                    @php $h = max(4, round(($bar['count'] / $orderChart['max']) * 100)); @endphp
                    <div class="flex-1 h-full flex items-end relative group">
                        <div class="w-full rounded-t bg-surface-container-high hover:bg-primary-container transition-all"
                            style="height: {{ $h }}%">
                        </div>
                        <span class="absolute -top-1 left-1/2 -translate-x-1/2 -translate-y-full opacity-0 group-hover:opacity-100
                                     text-[10px] font-bold text-on-surface bg-surface-container-high px-1.5 py-0.5 rounded transition-opacity">
                            {{ $bar['count'] }}
                        </span>
                    </div>
                @endforeach
            </div>
            <div class="flex justify-between mt-4 px-1 text-[10px] text-outline font-bold uppercase tracking-wider">
                @foreach ($orderChart['data'] as $bar)
                    <span class="flex-1 text-center">{{ $bar['label'] }}</span>
                @endforeach
            </div>
        </x-admin.panel>

        {{-- Top products --}}
        <x-admin.panel class="col-span-12 lg:col-span-4" title="Top Products"
            :view-all="\Illuminate\Support\Facades\Route::has('admin.products.index') ? route('admin.products.index') : '#'">
            <div class="space-y-5">
                @forelse ($topProducts as $product)
                    <div class="flex items-center gap-4 group cursor-pointer hover:bg-surface-container-low p-2 rounded-lg -mx-2 transition-colors">
                        <div class="w-12 h-12 bg-surface-container-high rounded-lg p-1.5 border border-outline-variant/30 shrink-0">
                            @if ($product->image)
                                <img src="{{ $product->image }}" alt="{{ $product->name }}" class="w-full h-full object-contain">
                            @else
                                <span class="material-symbols-outlined text-outline w-full h-full grid place-items-center">inventory_2</span>
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="text-sm font-bold text-on-surface truncate">{{ $product->name }}</div>
                            <div class="text-[10px] text-on-surface-variant font-medium">{{ number_format($product->units) }} sold</div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-on-surface-variant py-8 text-center">No sales recorded yet.</p>
                @endforelse
            </div>
        </x-admin.panel>

        {{-- Top customers --}}
        <x-admin.panel class="col-span-12 lg:col-span-6" title="Top Customers"
            :view-all="\Illuminate\Support\Facades\Route::has('admin.customers.index') ? route('admin.customers.index') : '#'">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="text-[10px] font-bold text-outline uppercase tracking-widest border-b border-outline-variant/40">
                        <tr>
                            <th class="pb-4 px-2">Customer</th>
                            <th class="pb-4 px-2">Type</th>
                            <th class="pb-4 px-2 text-right">Total Spent</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/30 text-sm">
                        @forelse ($topCustomers as $customer)
                            <tr class="hover:bg-surface-container-low transition-colors group">
                                <td class="py-4 px-2">
                                    <div class="flex items-center gap-3">
                                        <span class="w-9 h-9 rounded-full bg-primary-container text-white grid place-items-center font-bold text-xs shrink-0">
                                            {{ strtoupper(substr($customer->name, 0, 1)) }}
                                        </span>
                                        <div>
                                            <div class="font-bold text-on-surface group-hover:text-primary transition-colors">{{ $customer->name }}</div>
                                            <div class="text-[10px] text-on-surface-variant">{{ $customer->orders_count }} orders</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-4 px-2 text-on-surface-variant font-medium capitalize">{{ $customer->type }}</td>
                                <td class="py-4 px-2 text-right font-bold text-on-surface">{{ format_money($customer->total_spent ?? 0) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="py-8 text-center text-on-surface-variant">No customers yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-admin.panel>

        {{-- Product overview --}}
        <x-admin.panel class="col-span-12 lg:col-span-6" title="Product Overview"
            :view-all="\Illuminate\Support\Facades\Route::has('admin.products.index') ? route('admin.products.index') : '#'">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="text-[10px] font-bold text-outline uppercase tracking-widest border-b border-outline-variant/40">
                        <tr>
                            <th class="pb-4 px-2">Name</th>
                            <th class="pb-4 px-2">SKU</th>
                            <th class="pb-4 px-2">Price</th>
                            <th class="pb-4 px-2">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/30 text-sm">
                        @forelse ($recentProducts as $product)
                            @php $variant = $product->defaultVariant; @endphp
                            <tr>
                                <td class="py-4 px-2">
                                    <span class="font-bold text-on-surface">{{ $product->name }}</span>
                                </td>
                                <td class="py-4 px-2 text-on-surface-variant font-medium">{{ $product->sku ?? '—' }}</td>
                                <td class="py-4 px-2 font-bold text-on-surface">
                                    {{ $variant ? format_money($variant->retail_price) : '—' }}
                                </td>
                                <td class="py-4 px-2">
                                    @if ($variant && $variant->isOnSale())
                                        <span class="px-2 py-0.5 bg-primary-container text-white text-[10px] font-bold rounded">On sale</span>
                                    @elseif ($product->is_active)
                                        <span class="px-2 py-0.5 bg-secondary-container text-on-secondary-container text-[10px] font-bold rounded">Active</span>
                                    @else
                                        <span class="text-[10px] text-outline font-medium">Draft</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="py-8 text-center text-on-surface-variant">No products yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-admin.panel>

        {{-- Earnings --}}
        <x-admin.panel class="col-span-12 lg:col-span-8">
            <div class="flex items-start justify-between mb-8 gap-4">
                <div>
                    <h3 class="text-lg font-bold text-on-surface">Earnings</h3>
                    <div class="flex flex-wrap gap-x-6 gap-y-2 mt-3">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 bg-primary rounded-full"></span>
                            <span class="text-xs font-semibold text-on-surface">Revenue: {{ format_money($earnings['revenue_total']) }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 bg-surface-dim dark:bg-on-surface-variant rounded-full"></span>
                            <span class="text-xs font-semibold text-on-surface">Profit: {{ format_money($earnings['profit_total']) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="h-64 flex items-end gap-4 md:gap-6 px-2 border-b border-outline-variant/30">
                @foreach ($earnings['data'] as $col)
                    @php
                        $revH = max(3, round(($col['revenue'] / $earnings['max']) * 100));
                        $profH = max(2, round((max($col['profit'], 0) / $earnings['max']) * 100));
                    @endphp
                    <div class="flex-1 flex flex-col items-center justify-end gap-1 h-full group">
                        <div class="w-full flex items-end justify-center gap-1 h-full">
                            <div class="w-3 bg-primary rounded-t-sm group-hover:opacity-80 transition-all" style="height: {{ $revH }}%"
                                title="Revenue: {{ format_money($col['revenue']) }}"></div>
                            <div class="w-3 bg-surface-dim dark:bg-on-surface-variant rounded-t-sm group-hover:opacity-80 transition-all" style="height: {{ $profH }}%"
                                title="Profit: {{ format_money($col['profit']) }}"></div>
                        </div>
                        <span class="text-[10px] font-bold text-outline mt-2">{{ $col['label'] }}</span>
                    </div>
                @endforeach
            </div>
        </x-admin.panel>

        {{-- Recent reviews --}}
        <x-admin.panel class="col-span-12 lg:col-span-4" title="New Reviews"
            :view-all="\Illuminate\Support\Facades\Route::has('admin.reviews.index') ? route('admin.reviews.index') : '#'">
            <div class="space-y-7">
                @forelse ($reviews as $review)
                    <div class="space-y-3">
                        <div class="flex items-center gap-3">
                            @if ($review->user?->avatar)
                                <img src="{{ $review->user->avatar }}" alt="{{ $review->user->name }}"
                                    class="w-10 h-10 rounded-full object-cover border border-outline-variant">
                            @else
                                <span class="w-10 h-10 rounded-full bg-secondary-container text-on-secondary-container grid place-items-center font-bold">
                                    {{ strtoupper(substr($review->user?->name ?? 'A', 0, 1)) }}
                                </span>
                            @endif
                            <div>
                                <div class="font-bold text-sm text-on-surface">{{ $review->user?->name ?? 'Anonymous' }}</div>
                                <div class="flex text-tertiary-fixed-dim">
                                    @for ($i = 1; $i <= 5; $i++)
                                        <span class="material-symbols-outlined text-xs"
                                            style="font-variation-settings: 'FILL' {{ $i <= $review->rating ? 1 : 0 }};">star</span>
                                    @endfor
                                </div>
                            </div>
                        </div>
                        @if ($review->body)
                            <p class="text-xs text-on-surface-variant leading-relaxed italic px-1">"{{ \Illuminate\Support\Str::limit($review->body, 120) }}"</p>
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-on-surface-variant py-8 text-center">No reviews yet.</p>
                @endforelse
            </div>
        </x-admin.panel>
    </div>
@endsection
