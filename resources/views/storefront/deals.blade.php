@extends('layouts.storefront')

@section('title', 'Deals & Bundles — ' . config('app.name'))
@section('meta_description', 'Live deals and bundles at ' . config('app.name') . ' — save on air coolers, geysers, kitchen appliances, solar and more.')

@section('content')
    {{-- Header band --}}
    <div class="relative overflow-hidden bg-inverse-surface text-inverse-on-surface">
        <div class="pointer-events-none absolute -top-24 -right-16 w-80 h-80 rounded-full bg-primary-container/20 blur-3xl" aria-hidden="true"></div>
        <div class="app-container relative py-12 md:py-14 text-center">
            <p class="inline-flex items-center gap-2 border border-primary-container/50 text-primary-container rounded-full px-4 py-1.5 font-bold uppercase tracking-widest text-label-sm mb-4">
                <span class="material-symbols-outlined text-[16px]">sell</span> Deals &amp; Bundles
            </p>
            <h1 class="text-3xl md:text-headline-lg font-bold">Save more with our deals<span class="text-primary-container">.</span></h1>
            <p class="text-inverse-on-surface/80 mt-3 max-w-2xl mx-auto">Curated bundles across appliances, kitchen and solar — grab them while they last.</p>
        </div>
    </div>

    <div class="bg-background py-12">
        <div class="app-container">
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

            @if ($deals->isEmpty())
                <div class="bg-white rounded-lg border border-outline-variant p-16 text-center">
                    <span class="material-symbols-outlined text-gray-300" style="font-size:64px;">sell</span>
                    <p class="mt-4 text-xl font-light text-on-surface-variant">No active deals right now.</p>
                    <a href="{{ route('shop') }}" class="inline-block mt-6 bg-primary-container text-on-primary-container px-8 py-3 font-bold rounded hover:brightness-95 transition-all">Browse the shop</a>
                </div>
            @else
                {{-- Two cards per row --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    @foreach ($deals as $deal)
                        <a href="{{ $deal['url'] }}"
                            class="bg-white rounded-xl border border-outline-variant overflow-hidden flex group hover:shadow-md transition-shadow">
                            <div class="w-40 shrink-0 bg-surface-container-low flex items-center justify-center p-4">
                                <img src="{{ $deal['image'] }}" alt="{{ $deal['name'] }}" loading="lazy"
                                    class="w-full h-32 object-contain group-hover:scale-105 transition-transform">
                            </div>
                            <div class="flex-1 min-w-0 p-5 flex flex-col">
                                <p class="text-label-sm font-bold uppercase text-secondary">Deal · {{ $deal['items_count'] }} {{ \Illuminate\Support\Str::plural('item', $deal['items_count']) }}</p>
                                <h3 class="font-bold text-headline-md leading-tight text-on-surface line-clamp-2 mt-0.5">{{ $deal['name'] }}</h3>
                                @if ($deal['description'])
                                    <p class="text-label-sm text-on-surface-variant line-clamp-2 mt-1">{{ $deal['description'] }}</p>
                                @endif
                                <div class="mt-auto pt-3 flex items-end justify-between gap-2">
                                    <div>
                                        <span class="text-2xl font-black text-error leading-none">Rs {{ number_format($deal['total']) }}</span>
                                        @if ($deal['discount_amount'] > 0)
                                            <span class="text-label-sm text-on-surface-variant line-through ml-1">Rs {{ number_format($deal['subtotal']) }}</span>
                                        @endif
                                        @if ($deal['discount_label'])
                                            <p class="text-label-sm font-bold text-secondary">{{ $deal['discount_label'] }}</p>
                                        @endif
                                    </div>
                                    <span class="w-9 h-9 rounded-full bg-primary-container flex items-center justify-center shrink-0 group-hover:translate-x-1 transition-transform">
                                        <span class="material-symbols-outlined text-[20px] text-on-surface">arrow_forward</span>
                                    </span>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <x-storefront.brand-strip />

    <x-storefront.product-columns />
@endsection
