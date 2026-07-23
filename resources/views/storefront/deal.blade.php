@extends('layouts.storefront')

@section('title', $deal->name . ' — ' . config('app.name'))
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags($deal->description ?: ('Deal: ' . $deal->name . ' at ' . config('app.name'))), 155))
@section('og_type', 'product')
@if ($card['image'])@section('og_image', $card['image'])@endif

@section('content')
    <div class="bg-background py-8">
        <div class="app-container">
            {{-- Breadcrumbs --}}
            <nav class="flex flex-wrap items-center gap-2 text-label-sm text-on-surface-variant mb-8" aria-label="Breadcrumb">
                <a href="{{ route('home') }}" class="hover:text-primary transition-colors">Home</a>
                <span class="material-symbols-outlined text-[14px]">chevron_right</span>
                <a href="{{ route('deals') }}" class="hover:text-primary transition-colors">Deals</a>
                <span class="material-symbols-outlined text-[14px]">chevron_right</span>
                <span class="text-on-surface line-clamp-1">{{ $deal->name }}</span>
            </nav>

            {{-- Flash — confirms the deal was added, or surfaces an error/timeout. --}}
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

            {{-- Deal hero --}}
            <section class="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-12 mb-12 bg-white p-6 lg:p-8 rounded-lg shadow-sm border border-outline-variant">
                <div class="aspect-square bg-white rounded-lg overflow-hidden flex items-center justify-center p-6">
                    <img src="{{ $card['image'] }}" alt="{{ $deal->name }}" class="w-full h-full object-contain">
                </div>

                <div class="flex flex-col justify-center">
                    <span class="inline-flex items-center gap-1.5 self-start bg-primary-container text-on-primary-container px-3 py-1 rounded-full text-label-sm font-bold uppercase tracking-wide mb-3">
                        <span class="material-symbols-outlined text-[16px]">sell</span> Deal
                    </span>
                    <h1 class="text-headline-lg font-bold mb-3">{{ $deal->name }}</h1>
                    @if ($deal->description)
                        <p class="text-on-surface-variant text-body-base leading-relaxed mb-6">{{ $deal->description }}</p>
                    @endif

                    <div class="flex items-end gap-3 mb-2">
                        <span class="text-[40px] leading-none font-black text-error">Rs {{ number_format($card['total']) }}</span>
                        @if ($card['discount_amount'] > 0)
                            <span class="text-xl text-on-surface-variant line-through pb-1">Rs {{ number_format($card['subtotal']) }}</span>
                        @endif
                    </div>
                    @if ($card['discount_label'])
                        <p class="text-secondary font-bold mb-6">{{ $card['discount_label'] }} on {{ $card['items_count'] }} {{ \Illuminate\Support\Str::plural('item', $card['items_count']) }}</p>
                    @endif

                    @if ($deal->ends_at)
                        <p class="text-label-sm text-on-surface-variant flex items-center gap-1.5 mb-6">
                            <span class="material-symbols-outlined text-[18px]">schedule</span>
                            Offer ends {{ format_date($deal->ends_at) }}
                        </p>
                    @endif

                    @php $inCart = app(\App\Services\CartService::class)->hasDeal($deal->id); @endphp
                    <div class="flex flex-wrap items-center gap-3">
                        @if ($inCart)
                            <span class="inline-flex items-center gap-2 bg-secondary-container text-on-secondary-container px-6 h-12 font-bold rounded">
                                <span class="material-symbols-outlined">check_circle</span> In your cart
                            </span>
                            <a href="{{ route('cart') }}" class="inline-flex items-center gap-2 text-primary font-bold hover:underline">
                                View cart <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
                            </a>
                        @else
                            {{-- Adds all the deal's items as one linked, auto-discounted group. --}}
                            <form method="POST" action="{{ route('cart.add-deal', $deal) }}">
                                @csrf
                                <button type="submit" class="inline-flex items-center justify-center gap-2 bg-primary-container text-on-primary-container px-8 h-12 font-bold rounded hover:brightness-95 active:scale-95 transition-all">
                                    <span class="material-symbols-outlined">add_shopping_cart</span> Add deal to cart
                                </button>
                            </form>
                        @endif
                        <a href="{{ route('shop') }}" class="inline-flex items-center gap-2 text-on-surface-variant font-bold hover:text-primary transition-colors">
                            Continue shopping
                        </a>
                    </div>
                </div>
            </section>

            {{-- What's in this deal --}}
            <section class="mb-12">
                <h2 class="text-headline-md mb-8 pb-3 border-b-2 border-primary-container w-max">What's in this deal</h2>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-6">
                    @foreach ($items as $item)
                        <x-storefront.product-card-grid :product="$item" />
                    @endforeach
                </div>
            </section>
        </div>
    </div>

    <x-storefront.brand-strip />

    <x-storefront.product-columns />

    <x-storefront.share-fab :url="route('deal.show', $deal->slug)" :title="$deal->name" />
@endsection
