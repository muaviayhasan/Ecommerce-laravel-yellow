@extends('layouts.storefront')

@section('title', 'Shopping Cart — ' . config('app.name'))

@section('content')
    <div class="bg-background py-8">
        <div class="app-container">
            <nav class="flex items-center gap-2 text-label-sm text-on-surface-variant mb-8" aria-label="Breadcrumb">
                <a href="{{ route('home') }}" class="hover:text-primary transition-colors">Home</a>
                <span class="material-symbols-outlined text-[14px]">chevron_right</span>
                <span class="text-on-surface">Shopping Cart</span>
            </nav>

            <h1 class="text-3xl sm:text-4xl font-light mb-8">Your Cart</h1>

            @if (session('status'))
                <div class="mb-6 p-4 rounded-lg bg-secondary-container/40 text-on-surface flex items-center gap-2">
                    <span class="material-symbols-outlined text-secondary">check_circle</span> {{ session('status') }}
                </div>
            @endif
            @if (session('error'))
                <div class="mb-6 p-4 rounded-lg bg-error-container/40 text-on-surface flex items-center gap-2">
                    <span class="material-symbols-outlined text-error">error</span> {{ session('error') }}
                </div>
            @endif

            @if ($items->isEmpty())
                <div class="bg-white rounded-lg border border-outline-variant p-16 text-center">
                    <span class="material-symbols-outlined text-gray-300" style="font-size:72px;">shopping_cart</span>
                    <p class="mt-4 text-xl font-light text-on-surface-variant">Your cart is empty.</p>
                    <a href="{{ route('shop') }}" class="inline-block mt-6 bg-primary-container text-on-primary-container px-8 py-3 font-bold rounded hover:brightness-95 transition-all">Start shopping</a>
                </div>
            @else
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
                    {{-- Items --}}
                    <div class="lg:col-span-2 bg-white rounded-lg border border-outline-variant overflow-hidden">
                        <table class="w-full text-left">
                            <thead class="border-b border-outline-variant text-label-sm uppercase tracking-wide text-on-surface-variant">
                                <tr>
                                    <th class="p-4">Product</th>
                                    <th class="p-4 text-center hidden sm:table-cell">Price</th>
                                    <th class="p-4 text-center">Qty</th>
                                    <th class="p-4 text-right">Total</th>
                                    <th class="p-4"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-outline-variant">
                                @foreach ($items as $item)
                                    <tr>
                                        <td class="p-4">
                                            <div class="flex items-center gap-4">
                                                <a href="{{ $item->url }}" class="w-16 h-16 shrink-0 border border-outline-variant rounded-lg overflow-hidden bg-white p-1">
                                                    <img src="{{ $item->image }}" alt="{{ $item->name }}" class="w-full h-full object-contain">
                                                </a>
                                                <div class="min-w-0">
                                                    <a href="{{ $item->url }}" class="font-bold hover:text-primary transition-colors line-clamp-2">{{ $item->name }}</a>
                                                    <p class="text-label-sm text-on-surface-variant">{{ $item->sku }}</p>
                                                    @if ($item->qty > $item->stock)
                                                        <p class="text-label-sm text-error">Only {{ rtrim(rtrim(number_format($item->stock, 3), '0'), '.') }} in stock</p>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td class="p-4 text-center hidden sm:table-cell whitespace-nowrap">Rs {{ number_format($item->price) }}</td>
                                        <td class="p-4">
                                            <form method="POST" action="{{ route('cart.update', $item->variant_id) }}" class="flex justify-center">
                                                @csrf @method('PATCH')
                                                <input type="number" name="quantity" value="{{ $item->qty }}" min="1" max="999" onchange="this.form.submit()"
                                                    class="w-16 text-center border border-outline rounded-lg py-1.5 outline-none focus:ring-1 focus:ring-primary">
                                            </form>
                                        </td>
                                        <td class="p-4 text-right font-bold whitespace-nowrap">Rs {{ number_format($item->line_total) }}</td>
                                        <td class="p-4 text-right">
                                            <form method="POST" action="{{ route('cart.remove', $item->variant_id) }}">
                                                @csrf @method('DELETE')
                                                <button type="submit" aria-label="Remove" class="p-2 rounded text-on-surface-variant hover:text-error transition-colors"><span class="material-symbols-outlined">close</span></button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <div class="p-4 flex justify-between items-center border-t border-outline-variant">
                            <a href="{{ route('shop') }}" class="text-primary font-bold hover:underline flex items-center gap-1"><span class="material-symbols-outlined text-[18px]">arrow_back</span> Continue shopping</a>
                            <form method="POST" action="{{ route('cart.clear') }}">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-on-surface-variant font-bold hover:text-error transition-colors text-label-sm">Clear cart</button>
                            </form>
                        </div>
                    </div>

                    {{-- Summary --}}
                    <div class="bg-white rounded-lg border border-outline-variant p-6">
                        <h2 class="text-xl font-bold mb-6">Cart Totals</h2>
                        <div class="space-y-3 text-body-base border-b border-outline-variant pb-4 mb-4">
                            <div class="flex justify-between"><span class="text-on-surface-variant">Subtotal</span><span class="font-bold">Rs {{ number_format($subtotal) }}</span></div>
                            <div class="flex justify-between"><span class="text-on-surface-variant">Shipping</span><span class="text-on-surface-variant">Calculated at checkout</span></div>
                        </div>
                        <div class="flex justify-between text-lg font-black mb-6"><span>Total</span><span>Rs {{ number_format($subtotal) }}</span></div>
                        <a href="{{ route('checkout') }}" class="w-full bg-primary-container text-on-primary-container py-3 rounded font-bold flex items-center justify-center gap-2 hover:brightness-95 transition-all">
                            Proceed to Checkout <span class="material-symbols-outlined">arrow_forward</span>
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
