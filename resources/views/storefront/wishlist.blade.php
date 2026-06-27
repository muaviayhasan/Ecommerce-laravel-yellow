@extends('layouts.storefront')

@section('title', 'Wishlist — ' . config('app.name'))

@section('content')
    <div class="bg-background py-8">
        <div class="app-container">
            <nav class="flex items-center gap-2 text-label-sm text-on-surface-variant mb-8" aria-label="Breadcrumb">
                <a href="{{ route('home') }}" class="hover:text-primary transition-colors">Home</a>
                <span class="material-symbols-outlined text-[14px]">chevron_right</span>
                <span class="text-on-surface">Wishlist</span>
            </nav>

            <div class="flex flex-wrap items-center justify-between gap-4 mb-8">
                <h1 class="text-3xl sm:text-4xl font-light">My Wishlist</h1>
                @if ($products->isNotEmpty())
                    <form method="POST" action="{{ route('wishlist.clear') }}">@csrf @method('DELETE')
                        <button type="submit" class="text-on-surface-variant font-bold hover:text-error transition-colors text-label-sm">Clear wishlist</button>
                    </form>
                @endif
            </div>

            @if (session('status'))
                <div class="mb-6 p-4 rounded-lg bg-secondary-container/40 text-on-surface flex items-center gap-2">
                    <span class="material-symbols-outlined text-secondary">check_circle</span> {{ session('status') }}
                </div>
            @endif

            @if ($products->isEmpty())
                <div class="bg-white rounded-lg border border-outline-variant p-16 text-center">
                    <span class="material-symbols-outlined text-gray-300" style="font-size:72px;">favorite</span>
                    <p class="mt-4 text-xl font-light text-on-surface-variant">Your wishlist is empty.</p>
                    <a href="{{ route('shop') }}" class="inline-block mt-6 bg-primary-container text-on-primary-container px-8 py-3 font-bold rounded hover:brightness-95 transition-all">Browse products</a>
                </div>
            @else
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                    @foreach ($products as $product)
                        <div class="bg-white border border-outline-variant rounded-lg overflow-hidden flex flex-col group">
                            <div class="relative">
                                <a href="{{ $product['url'] }}" class="block aspect-square bg-white p-4">
                                    <img src="{{ $product['image'] }}" alt="{{ $product['name'] }}" class="w-full h-full object-contain group-hover:scale-105 transition-transform duration-500">
                                </a>
                                <form method="POST" action="{{ route('wishlist.remove', $product['slug']) }}" class="absolute top-2 right-2">
                                    @csrf @method('DELETE')
                                    <button type="submit" aria-label="Remove" class="w-8 h-8 grid place-items-center rounded-full bg-white shadow border border-outline-variant text-on-surface-variant hover:text-error"><span class="material-symbols-outlined text-[18px]">close</span></button>
                                </form>
                            </div>
                            <div class="p-4 flex flex-col flex-1">
                                @if ($product['category'])<span class="text-label-sm text-on-surface-variant mb-1">{{ $product['category'] }}</span>@endif
                                <a href="{{ $product['url'] }}" class="text-product-title font-medium hover:text-primary transition-colors line-clamp-2 mb-2">{{ $product['name'] }}</a>
                                <div class="mt-auto flex items-center justify-between gap-2 pt-2">
                                    <span class="font-bold {{ $product['compare'] ? 'text-error' : '' }}">Rs {{ number_format($product['price']) }}</span>
                                    @if ($product['variant_id'])
                                        <form method="POST" action="{{ route('cart.add') }}">
                                            @csrf
                                            <input type="hidden" name="variant_id" value="{{ $product['variant_id'] }}">
                                            <button type="submit" class="bg-primary-container text-on-primary-container px-3 py-2 rounded text-label-sm font-bold hover:brightness-95 transition-all flex items-center gap-1"><span class="material-symbols-outlined text-[16px]">shopping_cart</span> Add</button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
@endsection
