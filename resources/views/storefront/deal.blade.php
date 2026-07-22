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
                <a href="{{ route('shop') }}" class="hover:text-primary transition-colors">Deals</a>
                <span class="material-symbols-outlined text-[14px]">chevron_right</span>
                <span class="text-on-surface line-clamp-1">{{ $deal->name }}</span>
            </nav>

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

                    <a href="{{ route('shop') }}" class="inline-flex items-center justify-center gap-2 self-start bg-primary-container text-on-primary-container px-8 h-12 font-bold rounded hover:brightness-95 transition-all">
                        <span class="material-symbols-outlined">storefront</span> Continue shopping
                    </a>
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
